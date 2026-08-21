<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\BroadcastsChanges;

class ShareBasedPaymentArrangement extends Model
{
    use BroadcastsChanges;

    public const TYPE_EQUITY_SETTLED = 'equity_settled';
    public const TYPE_CASH_SETTLED   = 'cash_settled';
    public const TYPE_CHOICE         = 'choice';

    public const STATUS_ACTIVE    = 'active';
    public const STATUS_VESTED    = 'vested';
    public const STATUS_EXPIRED   = 'expired';
    public const STATUS_FORFEITED = 'forfeited';

    protected $fillable = [
        'company_id',
        'name',
        'arrangement_type',
        'grant_date',
        'vesting_start_date',
        'vesting_end_date',
        'number_of_instruments',
        'exercise_price',
        'fair_value_at_grant',
        'total_expense',
        'vesting_conditions',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'grant_date'          => 'date',
            'vesting_start_date'  => 'date',
            'vesting_end_date'    => 'date',
            'exercise_price'      => 'decimal:2',
            'fair_value_at_grant' => 'decimal:2',
            'total_expense'       => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ShareBasedPaymentEvent::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Total vesting period in months (from vesting_start_date to vesting_end_date).
     */
    public function vestingPeriodMonths(): ?int
    {
        if (!$this->vesting_start_date || !$this->vesting_end_date) {
            return null;
        }

        return (int) $this->vesting_start_date->diffInMonths($this->vesting_end_date);
    }

    /**
     * Number of months elapsed since vesting started, capped at the vesting period.
     */
    public function monthsElapsed(): int
    {
        if (!$this->vesting_start_date) {
            return 0;
        }

        $end = $this->vesting_end_date ?? now();
        $elapsed = (int) $this->vesting_start_date->diffInMonths(min(now(), $end));

        $total = $this->vestingPeriodMonths();
        if ($total !== null) {
            return min($elapsed, $total);
        }

        return $elapsed;
    }

    /**
     * Vesting percentage (0–100) based on elapsed months vs total vesting period.
     */
    public function vestingPercentage(): float
    {
        $total = $this->vestingPeriodMonths();
        if (!$total || $total <= 0) {
            return $this->status === self::STATUS_VESTED ? 100.0 : 0.0;
        }

        return min(round($this->monthsElapsed() / $total * 100, 2), 100);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
