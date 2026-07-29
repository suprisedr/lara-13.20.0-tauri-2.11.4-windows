<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\BroadcastsChanges;

/**
 * An intangible asset (e.g. software, patents, trademarks, goodwill) carried at
 * cost less accumulated amortisation, per IAS 38 / IFRS for SMEs §18.
 */
class IntangibleAsset extends Model
{
    use BroadcastsChanges;
    public const STATUS_ACTIVE   = 'active';
    public const STATUS_DISPOSED = 'disposed';

    protected $fillable = [
        'company_id',
        'intangible_class_id',
        'name',
        'reference',
        'category',
        'acquisition_date',
        'cost',
        'residual_value',
        'accumulated_impairment',
        'revaluation_surplus',
        'useful_life_years',
        'useful_life_indefinite',
        'amortisation_method',
        'disposal_date',
        'disposal_proceeds',
        'notes',
        'status',
        'posted_at',
        'posting_transaction_id',
        'disposal_transaction_id',
        'last_amortisation_posted_on',
        'is_embedded',
        'embedded_at',
    ];

    protected function casts(): array
    {
        return [
            'acquisition_date'            => 'date',
            'cost'                        => 'decimal:2',
            'residual_value'              => 'decimal:2',
            'accumulated_impairment'      => 'decimal:2',
            'revaluation_surplus'         => 'decimal:2',
            'useful_life_years'           => 'decimal:2',
            'useful_life_indefinite'      => 'boolean',
            'disposal_date'               => 'date',
            'disposal_proceeds'           => 'decimal:2',
            'posted_at'                   => 'datetime',
            'last_amortisation_posted_on' => 'date',
            'is_embedded'                 => 'boolean',
            'embedded_at'                 => 'datetime',
        ];
    }

    public const METHODS = [
        'straight_line'    => 'Straight-line',
        'reducing_balance' => 'Reducing balance',
    ];

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

    public function intangibleClass(): BelongsTo
    {
        return $this->belongsTo(IntangibleClass::class);
    }

    public function postingTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'posting_transaction_id');
    }

    public function disposalTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'disposal_transaction_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(IntangibleAssetEvent::class);
    }

    public function isDisposed(): bool
    {
        return $this->disposal_date !== null;
    }

    public function isIndefiniteLife(): bool
    {
        return (bool) $this->useful_life_indefinite
            || (bool) ($this->intangibleClass?->indefinite_life);
    }

    public function effectiveAmortisationMethod(): string
    {
        $raw = strtolower((string) ($this->amortisation_method ?: $this->intangibleClass?->amortisation_method));

        if (str_contains($raw, 'reduc') || str_contains($raw, 'diminish') || str_contains($raw, 'declin')) {
            return 'reducing_balance';
        }

        return 'straight_line';
    }

    public function effectiveUsefulLifeYears(): ?float
    {
        if ($this->isIndefiniteLife()) {
            return null;
        }

        $life = $this->useful_life_years !== null
            ? (float) $this->useful_life_years
            : (float) ($this->intangibleClass?->useful_life_years ?? 0);

        return $life > 0 ? $life : null;
    }

    /**
     * Accumulated amortisation as at the given date. Indefinite-life intangibles
     * (IAS 38.107) are NOT amortised — they are only impairment-tested annually.
     */
    public function accumulatedAmortisation(string $asOfDate): float
    {
        if ($this->isIndefiniteLife()) {
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

        if ($this->effectiveAmortisationMethod() === 'reducing_balance') {
            $annualRate = ($residual > 0 && $cost > 0)
                ? 1 - pow($residual / $cost, 1 / $life)
                : min(1.0, 2 / $life);

            $monthlyFactor = pow(1 - $annualRate, 1 / 12);
            $nbv           = $cost * pow($monthlyFactor, $monthsInUse);

            return round(min($cost - $nbv, $depreciable), 2);
        }

        $totalMonths = $life * 12;
        $accAmort    = $depreciable * min($monthsInUse, $totalMonths) / $totalMonths;

        return round(min($accAmort, $depreciable), 2);
    }

    /** Net book value (carrying amount) net of amortisation and impairment. */
    public function netBookValue(string $asOfDate): float
    {
        $nbv = (float) $this->cost
            - $this->accumulatedAmortisation($asOfDate)
            - (float) ($this->accumulated_impairment ?? 0);

        return round(max($nbv, 0), 2);
    }

    /** Goodwill is special: revaluation prohibited, impairment reversal prohibited (IAS 36.124). */
    public function isGoodwill(): bool
    {
        $class = strtolower((string) ($this->intangibleClass?->name ?? $this->category ?? ''));
        return str_contains($class, 'goodwill');
    }
}
