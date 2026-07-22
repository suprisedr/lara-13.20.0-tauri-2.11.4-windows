<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditNote extends Model
{
    protected $fillable = [
        'company_id',
        'invoice_id',
        'customer_id',
        'credit_note_number',
        'customer_name',
        'customer_email',
        'customer_address',
        'credit_note_date',
        'status',
        'reason',
        'notes',
        'posting_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'credit_note_date' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CreditNoteItem::class);
    }

    public function postingTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'posting_transaction_id');
    }

    public function subtotal(): float
    {
        return (float) $this->items->sum(fn($item) => $item->quantity * $item->unit_price);
    }

    public function taxTotal(): float
    {
        return (float) $this->items->sum(function ($item) {
            if ($item->tax_rate === null) return 0;
            return $item->quantity * $item->unit_price * ($item->tax_rate / 100);
        });
    }

    public function total(): float
    {
        return $this->subtotal() + $this->taxTotal();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft'   => 'Draft',
            'issued'  => 'Issued',
            'applied' => 'Applied',
            'voided'  => 'Voided',
            default   => ucfirst($this->status),
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'draft'   => '#6b7280',
            'issued'  => '#d97706',
            'applied' => '#16a34a',
            'voided'  => '#dc2626',
            default   => '#000',
        };
    }
}
