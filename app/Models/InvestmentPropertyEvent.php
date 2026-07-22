<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestmentPropertyEvent extends Model
{
    protected $fillable = [
        'investment_property_id',
        'event_type',
        'event_date',
        'amount',
        'description',
        'journal_status',
        'transaction_id',
    ];

    protected $casts = [
        'event_date' => 'date',
        'amount'     => 'decimal:2',
    ];

    public const TYPE_ACQUISITION          = 'acquisition';
    public const TYPE_CAPITALISATION       = 'capitalisation';
    public const TYPE_FAIR_VALUE_ADJUSTMENT = 'fair_value_adjustment';
    public const TYPE_IMPAIRMENT           = 'impairment';
    public const TYPE_IMPAIRMENT_REVERSAL  = 'impairment_reversal';
    public const TYPE_DISPOSAL             = 'disposal';

    public const STATUS_PENDING = 'pending';
    public const STATUS_POSTED  = 'posted';
    public const STATUS_FAILED  = 'failed';

    public static function labels(): array
    {
        return [
            self::TYPE_ACQUISITION           => 'Acquisition',
            self::TYPE_CAPITALISATION        => 'Subsequent Cost',
            self::TYPE_FAIR_VALUE_ADJUSTMENT  => 'Fair Value Adjustment',
            self::TYPE_IMPAIRMENT            => 'Impairment',
            self::TYPE_IMPAIRMENT_REVERSAL   => 'Impairment Reversal',
            self::TYPE_DISPOSAL              => 'Disposal',
        ];
    }

    public static function colors(): array
    {
        return [
            self::TYPE_ACQUISITION           => ['bg' => '#dbeafe', 'color' => '#1e40af'],
            self::TYPE_CAPITALISATION        => ['bg' => '#e0f2fe', 'color' => '#0369a1'],
            self::TYPE_FAIR_VALUE_ADJUSTMENT => ['bg' => '#f5f3ff', 'color' => '#7c3aed'],
            self::TYPE_IMPAIRMENT            => ['bg' => '#fef3c7', 'color' => '#92400e'],
            self::TYPE_IMPAIRMENT_REVERSAL   => ['bg' => '#d1fae5', 'color' => '#065f46'],
            self::TYPE_DISPOSAL              => ['bg' => '#fee2e2', 'color' => '#b91c1c'],
        ];
    }

    public function investmentProperty(): BelongsTo
    {
        return $this->belongsTo(InvestmentProperty::class);
    }
}
