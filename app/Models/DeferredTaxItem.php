<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\BroadcastsChanges;

class DeferredTaxItem extends Model
{
    use BroadcastsChanges;

    public const SOURCE_PPE              = 'ppe';
    public const SOURCE_INTANGIBLE       = 'intangible';
    public const SOURCE_LEASE            = 'lease';
    public const SOURCE_PROVISION        = 'provision';
    public const SOURCE_REVENUE_CONTRACT = 'revenue_contract';
    public const SOURCE_INVENTORY        = 'inventory';
    public const SOURCE_OTHER            = 'other';

    public const STATUS_ACTIVE   = 'active';
    public const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'company_id',
        'name',
        'source_type',
        'source_id',
        'tax_base',
        'carrying_amount',
        'temporary_difference',
        'deferred_tax_asset',
        'deferred_tax_liability',
        'tax_rate',
        'is_taxable',
        'measurement_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'tax_base'              => 'decimal:2',
            'carrying_amount'       => 'decimal:2',
            'temporary_difference'  => 'decimal:2',
            'deferred_tax_asset'    => 'decimal:2',
            'deferred_tax_liability'=> 'decimal:2',
            'tax_rate'              => 'decimal:2',
            'is_taxable'            => 'boolean',
            'measurement_date'      => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(DeferredTaxEvent::class);
    }

    /**
     * IAS 12 — Temporary difference = carrying amount - tax base.
     */
    public function calculateTemporaryDifference(): float
    {
        return (float) $this->carrying_amount - (float) $this->tax_base;
    }

    /**
     * IAS 12 — Deferred tax = |temporary difference| * tax rate / 100.
     */
    public function calculateDeferredTax(): float
    {
        return abs($this->calculateTemporaryDifference()) * (float) $this->tax_rate / 100;
    }

    /**
     * A deferred tax asset arises when carrying amount < tax base (deductible temporary difference).
     */
    public function isAsset(): bool
    {
        return $this->calculateTemporaryDifference() < 0;
    }

    /**
     * A deferred tax liability arises when carrying amount > tax base (taxable temporary difference).
     */
    public function isLiability(): bool
    {
        return $this->calculateTemporaryDifference() > 0;
    }

    /**
     * Net deferred tax position: positive = net liability, negative = net asset.
     */
    public function netPosition(): float
    {
        return (float) $this->deferred_tax_liability - (float) $this->deferred_tax_asset;
    }

    // ─── Scopes ──────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
