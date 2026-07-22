<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetHeldForSale extends Model
{
    protected $table = 'assets_held_for_sale';

    public const STATUS_HELD_FOR_SALE = 'held_for_sale';
    public const STATUS_SOLD          = 'sold';
    public const STATUS_REVERSED      = 'reversed';

    protected $fillable = [
        'company_id',
        'asset_id',
        'reclassification_date',
        'carrying_amount_at_reclassification',
        'fair_value_less_costs_to_sell',
        'impairment_on_reclassification',
        'expected_sale_date',
        'buyer_details',
        'notes',
        'status',
        'disposal_date',
        'disposal_proceeds',
        'reclassification_transaction_id',
        'disposal_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'reclassification_date'                => 'date',
            'carrying_amount_at_reclassification'  => 'decimal:2',
            'fair_value_less_costs_to_sell'        => 'decimal:2',
            'impairment_on_reclassification'       => 'decimal:2',
            'expected_sale_date'                   => 'date',
            'disposal_date'                        => 'date',
            'disposal_proceeds'                    => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function reclassificationTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'reclassification_transaction_id');
    }

    public function disposalTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'disposal_transaction_id');
    }

    public function carryingAmount(): float
    {
        $fvlcts = $this->fair_value_less_costs_to_sell;
        $ca     = (float) $this->carrying_amount_at_reclassification - (float) $this->impairment_on_reclassification;

        if ($fvlcts !== null) {
            return min($ca, (float) $fvlcts);
        }

        return round(max($ca, 0), 2);
    }

    public function isSold(): bool
    {
        return $this->status === self::STATUS_SOLD;
    }

    public function isReversed(): bool
    {
        return $this->status === self::STATUS_REVERSED;
    }
}
