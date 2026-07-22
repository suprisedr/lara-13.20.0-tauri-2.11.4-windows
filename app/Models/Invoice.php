<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'customer_id',
        'invoice_number',
        'customer_name',
        'customer_email',
        'customer_address',
        'invoice_date',
        'due_date',
        'status',
        'notes',
        'posting_transaction_id',
        'payment_transaction_id',
        'is_embedded',
        'embedded_at',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'is_embedded' => 'boolean',
            'embedded_at' => 'datetime',
        ];
    }

    public function scopeNotEmbedded($query)
    {
        return $query->where('is_embedded', false);
    }

    public function embeddableAmount(): float
    {
        return round($this->subtotal() + $this->taxTotal(), 2);
    }

    public function embeddableCurrency(): string
    {
        return $this->company?->currency ?? 'ZAR';
    }

    public function toEmbeddableText(): string
    {
        $amount = number_format($this->embeddableAmount(), 2, '.', '');
        $currency = $this->embeddableCurrency();
        $customer = $this->customer_name ?? '-';
        $number = $this->invoice_number ?? '-';
        $date = optional($this->invoice_date)->format('Y-m-d') ?? '-';
        $notes = $this->notes ?? '-';
        $status = $this->status ?? '-';

        return "type:invoice amount:{$amount} currency:{$currency} customer:{$customer} ref:{$number} date:{$date} status:{$status} desc:{$notes}";
    }

    protected static function booted(): void
    {
        static::created(function (Invoice $invoice): void {
            if (! config('services.gemini.api_key')) {
                return;
            }
            \Illuminate\Support\Facades\Cache::put(
                \App\Jobs\EmbedPendingInvoicesJob::LAST_SEEN_KEY,
                now()->timestamp,
                now()->addMinutes(30),
            );
            \App\Jobs\EmbedPendingInvoicesJob::dispatch()
                ->delay(now()->addSeconds(\App\Jobs\EmbedPendingInvoicesJob::QUIET_WINDOW_SECONDS));
        });

        static::deleted(function (Invoice $invoice): void {
            try {
                \Illuminate\Support\Facades\DB::connection('pgsql')
                    ->table('invoice_vectors')
                    ->where('mysql_invoice_id', $invoice->id)
                    ->delete();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning(
                    'Failed to purge invoice_vectors row for invoice '.$invoice->id.': '.$e->getMessage()
                );
            }
        });
    }

    public function company(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function postingTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'posting_transaction_id');
    }

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'payment_transaction_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function amountPaid(): float
    {
        return (float) $this->payments->sum('amount');
    }

    public function balanceDue(): float
    {
        return round($this->total() - $this->amountPaid(), 2);
    }

    public function isOverdue(): bool
    {
        return $this->status === InvoiceStatus::Overdue->value;
    }

    public function subtotal(): float
    {
        return (float) $this->items->sum(fn(InvoiceItem $item) => $item->quantity * $item->unit_price);
    }

    public function taxTotal(): float
    {
        return (float) $this->items->sum(function (InvoiceItem $item) {
            if ($item->tax_rate === null) {
                return 0;
            }

            return $item->quantity * $item->unit_price * ($item->tax_rate / 100);
        });
    }

    public function total(): float
    {
        return $this->subtotal() + $this->taxTotal();
    }
}
