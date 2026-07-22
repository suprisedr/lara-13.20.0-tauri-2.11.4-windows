<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quotation extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'customer_id',
        'quotation_number',
        'customer_name',
        'customer_email',
        'customer_address',
        'quotation_date',
        'expiry_date',
        'status',
        'notes',
        'converted_invoice_id',
    ];

    protected function casts(): array
    {
        return [
            'quotation_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function convertedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'converted_invoice_id');
    }

    public function subtotal(): float
    {
        return (float) $this->items->sum(fn (QuotationItem $item) => $item->quantity * $item->unit_price);
    }

    public function taxTotal(): float
    {
        return (float) $this->items->sum(function (QuotationItem $item) {
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

    public function isExpired(): bool
    {
        return $this->expiry_date !== null
            && $this->expiry_date->isPast()
            && in_array($this->status, ['draft', 'sent'], true);
    }
}
