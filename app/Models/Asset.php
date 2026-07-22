<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asset extends Model
{
    public const STATUS_ACTIVE        = 'active';
    public const STATUS_DISPOSED      = 'disposed';
    public const STATUS_HELD_FOR_SALE = 'held_for_sale';

    protected $fillable = [
        'company_id',
        'ppe_class_id',
        'name',
        'asset_tag',
        'location',
        'acquisition_date',
        'depreciation_start_date',
        'cost',
        'residual_value',
        'useful_life_years',
        'sars_wear_tear_years',
        'depreciation_method',
        'disposal_date',
        'disposal_proceeds',
        'notes',
        'status',
        'posted_at',
        'posting_transaction_id',
        'disposal_transaction_id',
        'last_depreciation_posted_on',
        'is_embedded',
        'embedded_at',
        'accumulated_impairment',
        'revaluation_surplus',
        'impairment_surplus_consumed',
    ];

    protected function casts(): array
    {
        return [
            'acquisition_date'  => 'date',
            'depreciation_start_date' => 'date',
            'cost'              => 'decimal:2',
            'residual_value'    => 'decimal:2',
            'useful_life_years'      => 'decimal:2',
            'sars_wear_tear_years'   => 'decimal:2',
            'disposal_date'     => 'date',
            'disposal_proceeds' => 'decimal:2',
            'posted_at' => 'datetime',
            'last_depreciation_posted_on' => 'date',
            'is_embedded'            => 'boolean',
            'embedded_at'            => 'datetime',
            'accumulated_impairment' => 'decimal:2',
            'revaluation_surplus'    => 'decimal:2',
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

    public function postingTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'posting_transaction_id');
    }

    public function disposalTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'disposal_transaction_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function ppeClass(): BelongsTo
    {
        return $this->belongsTo(PpeClass::class);
    }

    /** Selectable depreciation methods (key => label). */
    public const METHODS = [
        'straight_line'    => 'Straight-line',
        'reducing_balance' => 'Reducing balance',
    ];

    public function isDisposed(): bool
    {
        return $this->disposal_date !== null;
    }

    /**
     * The depreciation method used for calculations. Falls back to the linked PPE
     * class, then straight-line. Tolerates legacy free-text values by keyword.
     */
    public function effectiveDepreciationMethod(): string
    {
        $raw = strtolower((string) ($this->depreciation_method ?: $this->ppeClass?->depreciation_method));

        if (str_contains($raw, 'reduc') || str_contains($raw, 'diminish') || str_contains($raw, 'declin')) {
            return 'reducing_balance';
        }

        return 'straight_line';
    }

    /** Useful life in years, falling back to the linked PPE class's useful life. */
    public function effectiveUsefulLifeYears(): ?float
    {
        $life = $this->useful_life_years !== null
            ? (float) $this->useful_life_years
            : (float) ($this->ppeClass?->useful_life_years ?? 0);

        return $life > 0 ? $life : null;
    }

    public function depreciationStartDate(): Carbon
    {
        return $this->depreciation_start_date ?? $this->acquisition_date;
    }

    /** Accumulated depreciation as at the given date, per the asset's method. */
    public function accumulatedDepreciation(string $asOfDate): float
    {
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

        $startDate = $this->depreciationStartDate();

        if ($asOf->lessThan($startDate)) {
            return 0.0;
        }

        $monthsInUse = $startDate->diffInMonths($asOf);

        if ($this->effectiveDepreciationMethod() === 'reducing_balance') {
            // Diminishing-value: a constant rate applied to the carrying amount.
            // Derive the annual rate from cost, residual and life so the asset
            // reaches its residual value at the end of its useful life; fall back
            // to double-declining (2/life) when there is no residual value.
            $annualRate = ($residual > 0 && $cost > 0)
                ? 1 - pow($residual / $cost, 1 / $life)
                : min(1.0, 2 / $life);

            $monthlyFactor = pow(1 - $annualRate, 1 / 12);
            $nbv = $cost * pow($monthlyFactor, $monthsInUse);
            $accDep = $cost - $nbv;

            // Never depreciate below the residual value.
            return round(min($accDep, $depreciable), 2);
        }

        // Straight-line.
        $totalMonths = $life * 12;
        $accDep = $depreciable * min($monthsInUse, $totalMonths) / $totalMonths;

        return round(min($accDep, $depreciable), 2);
    }

    /** Net book value (carrying amount) as at the given date, net of depreciation and impairment. */
    public function netBookValue(string $asOfDate): float
    {
        $nbv = (float) $this->cost - $this->accumulatedDepreciation($asOfDate) - (float) ($this->accumulated_impairment ?? 0);
        return round(max($nbv, 0), 2);
    }

    /** SARS wear & tear useful life; null means no tax depreciation difference. */
    public function effectiveSarsWearTearYears(): ?float
    {
        $life = $this->sars_wear_tear_years !== null ? (float) $this->sars_wear_tear_years : null;
        return ($life !== null && $life > 0) ? $life : null;
    }

    /** Tax base (SARS carrying amount) at the given date using straight-line wear & tear. */
    public function taxBase(string $asOfDate): ?float
    {
        $sarsLife = $this->effectiveSarsWearTearYears();
        if ($sarsLife === null) {
            return null;
        }

        $cost = (float) $this->cost;
        $asOf = Carbon::parse($asOfDate);

        $startDate = $this->depreciationStartDate();

        if ($asOf->lessThan($startDate)) {
            return $cost;
        }

        $monthsInUse = $startDate->diffInMonths($asOf);
        $totalMonths = $sarsLife * 12;
        $taxDep = $cost * min($monthsInUse, $totalMonths) / $totalMonths;

        return round(max($cost - $taxDep, 0), 2);
    }

    /** Monthly SARS wear & tear straight-line amount. */
    public function monthlySarsDepreciation(): float
    {
        $sarsLife = $this->effectiveSarsWearTearYears();
        if ($sarsLife === null || $sarsLife <= 0) {
            return 0.0;
        }
        return round((float) $this->cost / ($sarsLife * 12), 2);
    }

    protected static function booted(): void
    {
        static::saved(function (Asset $asset) {
            $asset->forceFill(['is_embedded' => false])->saveQuietly();
            app(\App\Services\RoadRunnerEmbeddingDispatcher::class)->embedAsset($asset->id);
        });

        static::deleting(function (Asset $asset) {
            try {
                \Illuminate\Support\Facades\DB::connection('pgsql')
                    ->table('asset_vectors')
                    ->where('mysql_asset_id', $asset->id)
                    ->delete();
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning(
                    'Failed to purge asset_vectors row for asset '.$asset->id.': '.$e->getMessage()
                );
            }
        });
    }

    public function scopeNotEmbedded($query)
    {
        return $query->where('is_embedded', false);
    }

    public function toEmbeddableText(): string
    {
        $cost = number_format((float) $this->cost, 2, '.', '');
        $name = $this->name ?? '-';
        $class = $this->ppeClass?->name ?? '-';
        $policy = $this->ppeClass?->accounting_policy ?? 'cost';
        $method = $this->depreciation_method ?? '-';
        $date = optional($this->acquisition_date)->format('Y-m-d') ?? '-';
        $life = $this->useful_life_years !== null ? (float) $this->useful_life_years : null;
        $tag = $this->asset_tag ?? '-';
        $location = $this->location ?? '-';

        return "type:asset name:{$name} class:{$class} policy:{$policy} cost:{$cost} currency:ZAR "
            . "method:{$method} life:{$life} date:{$date} tag:{$tag} location:{$location}";
    }

}
