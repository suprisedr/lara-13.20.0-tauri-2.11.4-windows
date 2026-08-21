<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceObligation extends Model
{
    public const METHOD_POINT_IN_TIME = 'point_in_time';
    public const METHOD_OVER_TIME     = 'over_time';

    public const OVER_TIME_OUTPUT       = 'output';
    public const OVER_TIME_INPUT        = 'input';
    public const OVER_TIME_COST_TO_COST = 'cost_to_cost';
    public const OVER_TIME_UNITS        = 'units_delivered';
    public const OVER_TIME_TIME_ELAPSED = 'time_elapsed';

    public const STATUS_UNSATISFIED        = 'unsatisfied';
    public const STATUS_PARTIALLY_SATISFIED = 'partially_satisfied';
    public const STATUS_SATISFIED           = 'satisfied';

    protected $fillable = [
        'revenue_contract_id',
        'name',
        'description',
        'standalone_selling_price',
        'allocated_transaction_price',
        'recognition_method',
        'over_time_method',
        'total_expected_cost',
        'costs_incurred_to_date',
        'percentage_complete',
        'revenue_recognised',
        'status',
        'satisfaction_date',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'standalone_selling_price'   => 'decimal:2',
            'allocated_transaction_price' => 'decimal:2',
            'total_expected_cost'        => 'decimal:2',
            'costs_incurred_to_date'     => 'decimal:2',
            'percentage_complete'        => 'decimal:4',
            'revenue_recognised'         => 'decimal:2',
            'satisfaction_date'          => 'date',
        ];
    }

    public function revenueContract(): BelongsTo
    {
        return $this->belongsTo(RevenueContract::class);
    }

    public function calculatePercentageComplete(): float
    {
        if ($this->recognition_method !== self::METHOD_OVER_TIME) {
            return $this->status === self::STATUS_SATISFIED ? 100.0 : 0.0;
        }

        if ($this->over_time_method === self::OVER_TIME_COST_TO_COST) {
            $total = (float) $this->total_expected_cost;
            if ($total <= 0) return 0.0;
            return min(((float) $this->costs_incurred_to_date / $total) * 100, 100);
        }

        return (float) $this->percentage_complete;
    }

    public function recognisableRevenue(): float
    {
        $pct       = $this->calculatePercentageComplete();
        $allocated = (float) $this->allocated_transaction_price;
        $target    = $allocated * ($pct / 100);

        return max($target - (float) $this->revenue_recognised, 0);
    }

    public function isSatisfied(): bool
    {
        return $this->status === self::STATUS_SATISFIED;
    }

    public function remainingRevenue(): float
    {
        return max((float) $this->allocated_transaction_price - (float) $this->revenue_recognised, 0);
    }
}
