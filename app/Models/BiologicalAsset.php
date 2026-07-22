<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BiologicalAsset extends Model
{
    public const STATUS_ACTIVE   = 'active';
    public const STATUS_DISPOSED = 'disposed';

    protected $fillable = [
        'company_id',
        'biological_asset_class_id',
        'name',
        'reference',
        'location',
        'acquisition_date',
        'quantity',
        'unit',
        'cost',
        'fair_value',
        'fair_value_date',
        'fair_value_gain_loss',
        'accumulated_impairment',
        'status',
        'disposal_date',
        'disposal_proceeds',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'acquisition_date'      => 'date',
            'cost'                  => 'decimal:2',
            'quantity'              => 'decimal:2',
            'fair_value'            => 'decimal:2',
            'fair_value_date'       => 'date',
            'fair_value_gain_loss'  => 'decimal:2',
            'accumulated_impairment' => 'decimal:2',
            'disposal_date'         => 'date',
            'disposal_proceeds'     => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function biologicalAssetClass(): BelongsTo
    {
        return $this->belongsTo(BiologicalAssetClass::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(BiologicalAssetEvent::class);
    }

    public function carryingAmount(): float
    {
        if ($this->fair_value !== null) {
            return max((float) $this->fair_value - (float) $this->accumulated_impairment, 0);
        }

        return max((float) $this->cost - (float) $this->accumulated_impairment, 0);
    }

    public function isDisposed(): bool
    {
        return $this->status === self::STATUS_DISPOSED;
    }
}
