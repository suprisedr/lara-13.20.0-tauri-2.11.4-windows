<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiologicalAssetEvent extends Model
{
    public const TYPE_ACQUISITION        = 'acquisition';
    public const TYPE_FAIR_VALUE         = 'fair_value_adjustment';
    public const TYPE_HARVEST            = 'harvest';
    public const TYPE_NATURAL_INCREASE   = 'natural_increase';
    public const TYPE_MORTALITY          = 'mortality';
    public const TYPE_DISPOSAL           = 'disposal';

    public const STATUS_PENDING = 'pending';
    public const STATUS_POSTED  = 'posted';
    public const STATUS_FAILED  = 'failed';

    protected $fillable = [
        'biological_asset_id',
        'event_type',
        'event_date',
        'amount',
        'quantity_change',
        'description',
        'journal_status',
        'transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'event_date'      => 'date',
            'amount'          => 'decimal:2',
            'quantity_change'  => 'decimal:2',
        ];
    }

    public function biologicalAsset(): BelongsTo
    {
        return $this->belongsTo(BiologicalAsset::class);
    }

    public static function labels(): array
    {
        return [
            self::TYPE_ACQUISITION      => 'Acquisition',
            self::TYPE_FAIR_VALUE       => 'Fair Value Adjustment',
            self::TYPE_HARVEST          => 'Harvest',
            self::TYPE_NATURAL_INCREASE => 'Natural Increase',
            self::TYPE_MORTALITY        => 'Mortality / Loss',
            self::TYPE_DISPOSAL         => 'Disposal / Sale',
        ];
    }

    public static function colors(): array
    {
        return [
            self::TYPE_ACQUISITION      => ['bg' => '#dbeafe', 'color' => '#1e40af'],
            self::TYPE_FAIR_VALUE       => ['bg' => '#ede9fe', 'color' => '#5b21b6'],
            self::TYPE_HARVEST          => ['bg' => '#dcfce7', 'color' => '#166534'],
            self::TYPE_NATURAL_INCREASE => ['bg' => '#d1fae5', 'color' => '#065f46'],
            self::TYPE_MORTALITY        => ['bg' => '#fee2e2', 'color' => '#991b1b'],
            self::TYPE_DISPOSAL         => ['bg' => '#fef3c7', 'color' => '#92400e'],
        ];
    }
}
