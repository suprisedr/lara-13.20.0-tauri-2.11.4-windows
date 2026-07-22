<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_id',
        'inventory_item_id',
        'description',
        'quantity',
        'unit_price',
        'tax_rate',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
        ];
    }

    public function quotation(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function inventoryItem(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function lineTotal(): float
    {
        $base = (float) $this->quantity * (float) $this->unit_price;
        $tax = $this->tax_rate !== null ? $base * ((float) $this->tax_rate / 100) : 0;

        return $base + $tax;
    }
}
