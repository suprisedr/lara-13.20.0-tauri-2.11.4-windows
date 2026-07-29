<?php

namespace App\Models;

use App\Enums\InventoryType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;
use App\Models\Concerns\BroadcastsChanges;

class InventoryItem extends Model
{
    use HasFactory, Searchable;
    use BroadcastsChanges;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'sku',
        'unit_price',
        'purchase_cost',
        'freight_in',
        'import_duties',
        'handling_costs',
        'trade_discount',
        'nrv_per_unit',
        'accumulated_write_down',
        'tax_rate',
        'is_active',
        'is_service',
        'inventory_type',
        'quantity_on_hand',
        'quantity_reserved',
        'initial_quantity',
        'inventory_account_id',
        'cogs_account_id',
        'write_down_account_id',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'purchase_cost' => 'decimal:2',
            'freight_in' => 'decimal:2',
            'import_duties' => 'decimal:2',
            'handling_costs' => 'decimal:2',
            'trade_discount' => 'decimal:2',
            'nrv_per_unit' => 'decimal:2',
            'accumulated_write_down' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'is_active' => 'boolean',
            'is_service' => 'boolean',
            'inventory_type' => InventoryType::class,
            'quantity_on_hand' => 'decimal:4',
            'quantity_reserved' => 'decimal:4',
            'initial_quantity' => 'decimal:4',
        ];
    }

    /**
     * IAS 2 landed cost: purchase_cost + freight_in + import_duties + handling_costs − trade_discount.
     */
    public function landedCost(): string
    {
        return number_format(
            (float) $this->purchase_cost
                + (float) $this->freight_in
                + (float) $this->import_duties
                + (float) $this->handling_costs
                - (float) $this->trade_discount,
            2,
            '.',
            ''
        );
    }

    /**
     * Units available for sale: quantity_on_hand minus quantity_reserved.
     */
    public function quantityAvailable(): string
    {
        return number_format(
            (float) $this->quantity_on_hand - (float) $this->quantity_reserved,
            4,
            '.',
            ''
        );
    }

    /**
     * Stock value: quantity_on_hand multiplied by the unit cost (landed cost if available, otherwise unit price).
     */
    public function stockValue(): float
    {
        $cost = (float) $this->purchase_cost ?: (float) $this->unit_price;

        return (float) $this->quantity_on_hand * $cost;
    }

    /**
     * IAS 2.9 carrying amount: lower of cost and net realisable value, applied at item level.
     */
    public function carryingAmount(): float
    {
        return $this->stockValue() - (float) $this->accumulated_write_down;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function inventoryAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'inventory_account_id');
    }

    public function cogsAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'cogs_account_id');
    }

    public function writeDownAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'write_down_account_id');
    }

    public function toSearchableArray(): array
    {
        return [
            'id'               => $this->id,
            'company_id'       => $this->company_id,
            'name'             => $this->name,
            'description'      => $this->description,
            'sku'              => $this->sku,
            'unit_price'       => (float) $this->unit_price,
            'tax_rate'         => $this->tax_rate !== null ? (float) $this->tax_rate : null,
            'is_service'       => $this->is_service,
            'is_active'        => $this->is_active,
            'quantity_on_hand' => (float) $this->quantity_on_hand,
        ];
    }
}
