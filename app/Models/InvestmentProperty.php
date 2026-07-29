<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BroadcastsChanges;

class InvestmentProperty extends Model
{
    use BroadcastsChanges;
    public const STATUS_ACTIVE   = 'active';
    public const STATUS_DISPOSED = 'disposed';

    public const METHODS = [
        'straight_line'    => 'Straight-line',
        'reducing_balance' => 'Reducing balance',
    ];

    protected $fillable = [
        'company_id',
        'investment_property_class_id',
        'name',
        'property_reference',
        'location',
        'acquisition_date',
        'cost',
        'residual_value',
        'useful_life_years',
        'depreciation_method',
        'fair_value',
        'fair_value_date',
        'disposal_date',
        'disposal_proceeds',
        'notes',
        'status',
        'posted_at',
        'posting_transaction_id',
        'disposal_transaction_id',
        'last_depreciation_posted_on',
        'accumulated_impairment',
        'fair_value_gain_loss',
    ];

    protected function casts(): array
    {
        return [
            'acquisition_date'            => 'date',
            'cost'                        => 'decimal:2',
            'residual_value'              => 'decimal:2',
            'useful_life_years'           => 'decimal:2',
            'fair_value'                  => 'decimal:2',
            'fair_value_date'             => 'date',
            'disposal_date'               => 'date',
            'disposal_proceeds'           => 'decimal:2',
            'posted_at'                   => 'datetime',
            'last_depreciation_posted_on' => 'date',
            'accumulated_impairment'      => 'decimal:2',
            'fair_value_gain_loss'        => 'decimal:2',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeDisposed($query)
    {
        return $query->where('status', self::STATUS_DISPOSED);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function investmentPropertyClass(): BelongsTo
    {
        return $this->belongsTo(InvestmentPropertyClass::class);
    }

    public function postingTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'posting_transaction_id');
    }

    public function disposalTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'disposal_transaction_id');
    }

    public function isDisposed(): bool
    {
        return $this->disposal_date !== null;
    }

    public function measurementModel(): string
    {
        return $this->investmentPropertyClass?->measurement_model ?? InvestmentPropertyClass::MODEL_COST;
    }

    public function isFairValueModel(): bool
    {
        return $this->measurementModel() === InvestmentPropertyClass::MODEL_FAIR_VALUE;
    }

    public function effectiveDepreciationMethod(): string
    {
        $raw = strtolower((string) ($this->depreciation_method ?: $this->investmentPropertyClass?->depreciation_method));

        if (str_contains($raw, 'reduc') || str_contains($raw, 'diminish') || str_contains($raw, 'declin')) {
            return 'reducing_balance';
        }

        return 'straight_line';
    }

    public function effectiveUsefulLifeYears(): ?float
    {
        $life = $this->useful_life_years !== null
            ? (float) $this->useful_life_years
            : (float) ($this->investmentPropertyClass?->useful_life_years ?? 0);

        return $life > 0 ? $life : null;
    }

    /**
     * Accumulated depreciation as at the given date.
     * Fair value model properties are NOT depreciated (IAS 40.56).
     */
    public function accumulatedDepreciation(string $asOfDate): float
    {
        if ($this->isFairValueModel()) {
            return 0.0;
        }

        $cost        = (float) $this->cost;
        $residual    = (float) $this->residual_value;
        $depreciable = $cost - $residual;

        if ($depreciable <= 0) {
            return 0.0;
        }

        $life = $this->effectiveUsefulLifeYears();

        if ($life === null) {
            return 0.0;
        }

        $asOf = Carbon::parse($asOfDate);

        if ($this->isDisposed() && $this->disposal_date->lessThan($asOf)) {
            $asOf = $this->disposal_date;
        }

        if ($asOf->lessThan($this->acquisition_date)) {
            return 0.0;
        }

        $monthsInUse = $this->acquisition_date->diffInMonths($asOf);

        if ($this->effectiveDepreciationMethod() === 'reducing_balance') {
            $annualRate = ($residual > 0 && $cost > 0)
                ? 1 - pow($residual / $cost, 1 / $life)
                : min(1.0, 2 / $life);

            $monthlyFactor = pow(1 - $annualRate, 1 / 12);
            $nbv = $cost * pow($monthlyFactor, $monthsInUse);
            $accDep = $cost - $nbv;

            return round(min($accDep, $depreciable), 2);
        }

        $totalMonths = $life * 12;
        $accDep = $depreciable * min($monthsInUse, $totalMonths) / $totalMonths;

        return round(min($accDep, $depreciable), 2);
    }

    /**
     * Carrying amount as at the given date.
     * Fair value model: fair_value (or cost if not yet remeasured).
     * Cost model: cost − accumulated depreciation − accumulated impairment.
     */
    public function carryingAmount(string $asOfDate): float
    {
        if ($this->isFairValueModel()) {
            return (float) ($this->fair_value ?? $this->cost);
        }

        $ca = (float) $this->cost
            - $this->accumulatedDepreciation($asOfDate)
            - (float) ($this->accumulated_impairment ?? 0);

        return round(max($ca, 0), 2);
    }
}
