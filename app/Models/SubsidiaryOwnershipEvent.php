<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A change in the parent's ownership interest in a subsidiary, accounted for
 * under IFRS 10 / IFRS 3:
 *  - acquisition: control obtained (incl. step acquisition with remeasurement).
 *  - increase / decrease: change while control is retained — an equity
 *    transaction with owners (no gain/loss; difference taken to equity).
 *  - disposal: control lost — derecognition with gain/loss to profit or loss.
 */
class SubsidiaryOwnershipEvent extends Model
{
    protected $fillable = [
        'parent_company_id',
        'subsidiary_company_id',
        'event_date',
        'type',
        'ownership_before',
        'ownership_after',
        'consideration',
        'equity_at_event',
        'fair_value_previously_held',
        'carrying_previously_held',
        'fair_value_retained',
        'goodwill_derecognised',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'ownership_before' => 'decimal:2',
            'ownership_after' => 'decimal:2',
            'consideration' => 'decimal:2',
            'equity_at_event' => 'decimal:2',
            'fair_value_previously_held' => 'decimal:2',
            'carrying_previously_held' => 'decimal:2',
            'fair_value_retained' => 'decimal:2',
            'goodwill_derecognised' => 'decimal:2',
        ];
    }

    public const TYPES = [
        'acquisition' => 'Acquisition — control obtained',
        'increase'    => 'Increase — additional interest (control retained)',
        'decrease'    => 'Decrease — partial disposal (control retained)',
        'disposal'    => 'Disposal — control lost',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'parent_company_id');
    }

    public function subsidiary(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'subsidiary_company_id');
    }

    public function beforeFraction(): float
    {
        return ((float) $this->ownership_before) / 100;
    }

    public function afterFraction(): float
    {
        return ((float) $this->ownership_after) / 100;
    }

    /**
     * Goodwill recognised at the date control was obtained (IFRS 3, partial-
     * goodwill method). Frozen thereafter; not affected by later changes.
     */
    public function goodwillOnAcquisition(): float
    {
        if ($this->type !== 'acquisition') {
            return 0.0;
        }

        $consideration = (float) $this->consideration + (float) ($this->fair_value_previously_held ?? 0);

        return $consideration - $this->afterFraction() * (float) ($this->equity_at_event ?? 0);
    }

    /**
     * Gain (or loss) on remeasuring a previously held interest to fair value
     * when control is obtained in stages (IFRS 3.42). Recognised in profit or
     * loss in the period control is obtained.
     */
    public function remeasurementGain(): float
    {
        if ($this->type !== 'acquisition' || $this->fair_value_previously_held === null) {
            return 0.0;
        }

        return (float) $this->fair_value_previously_held - (float) ($this->carrying_previously_held ?? 0);
    }

    /**
     * Adjustment to equity attributable to owners of the parent for an equity
     * transaction (increase/decrease with control retained, IFRS 10.B96).
     * Positive = credit to equity; negative = debit (premium paid to NCI).
     */
    public function equityAdjustment(): float
    {
        $equity = (float) ($this->equity_at_event ?? 0);
        $parentDelta = $this->afterFraction() - $this->beforeFraction(); // +ve when parent buys from NCI

        if ($this->type === 'increase') {
            // NCI derecognised (their proportionate net assets) less cash paid.
            return ($parentDelta * $equity) - (float) $this->consideration;
        }

        if ($this->type === 'decrease') {
            // Cash received less NCI recognised (parent's interest falls).
            return (float) $this->consideration - (abs($parentDelta) * $equity);
        }

        return 0.0;
    }

    /**
     * Gain (or loss) on loss of control (IFRS 10.B98):
     *   proceeds + fair value of retained interest
     *   − parent's share of net assets derecognised − goodwill derecognised.
     */
    public function disposalGain(): float
    {
        if ($this->type !== 'disposal') {
            return 0.0;
        }

        $proceeds = (float) $this->consideration + (float) ($this->fair_value_retained ?? 0);
        $parentShareNetAssets = $this->beforeFraction() * (float) ($this->equity_at_event ?? 0);

        return $proceeds - $parentShareNetAssets - (float) ($this->goodwill_derecognised ?? 0);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
