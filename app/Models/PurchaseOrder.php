<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\BroadcastsChanges;

class PurchaseOrder extends Model
{
    use BroadcastsChanges;
    protected $fillable = [
        'company_id',
        'supplier_id',
        'supplier_email_id',
        'po_number',
        'order_date',
        'expected_delivery_date',
        'currency',
        'subtotal',
        'tax_total',
        'total',
        'status',
        'notes',
        'ai_raw_extraction',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_delivery_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'status' => PurchaseOrderStatus::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function supplierEmail(): BelongsTo
    {
        return $this->belongsTo(SupplierEmail::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}
