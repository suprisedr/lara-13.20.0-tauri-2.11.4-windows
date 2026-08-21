<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\BroadcastsChanges;

class Provision extends Model
{
    use BroadcastsChanges;

    public const TYPE_PROVISION           = 'provision';
    public const TYPE_CONTINGENT_LIABILITY = 'contingent_liability';
    public const TYPE_CONTINGENT_ASSET     = 'contingent_asset';

    public const STATUS_ACTIVE   = 'active';
    public const STATUS_SETTLED  = 'settled';
    public const STATUS_REVERSED = 'reversed';
    public const STATUS_LAPSED   = 'lapsed';

    public const PROB_PROBABLE = 'probable';
    public const PROB_POSSIBLE = 'possible';
    public const PROB_REMOTE   = 'remote';

    protected $fillable = [
        'company_id',
        'provision_class_id',
        'name',
        'provision_type',
        'status',
        'recognition_date',
        'expected_settlement_date',
        'initial_estimate',
        'current_estimate',
        'discount_rate',
        'present_value',
        'probability',
        'settlement_date',
        'settlement_amount',
        'notes',
        'posted_at',
        'posting_transaction_id',
        'last_unwinding_posted_on',
    ];

    protected function casts(): array
    {
        return [
            'recognition_date'        => 'date',
            'expected_settlement_date' => 'date',
            'initial_estimate'         => 'decimal:2',
            'current_estimate'         => 'decimal:2',
            'discount_rate'            => 'decimal:4',
            'present_value'            => 'decimal:2',
            'settlement_date'          => 'date',
            'settlement_amount'        => 'decimal:2',
            'posted_at'                => 'datetime',
            'last_unwinding_posted_on' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function provisionClass(): BelongsTo
    {
        return $this->belongsTo(ProvisionClass::class);
    }

    public function postingTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'posting_transaction_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ProvisionEvent::class);
    }

    /**
     * Carrying amount — the current estimate (or present value if discounted).
     */
    public function carryingAmount(): float
    {
        if ($this->present_value !== null) {
            return (float) $this->present_value;
        }

        return (float) $this->current_estimate;
    }

    /**
     * IAS 37.14 — a provision is recognised only when:
     * (a) there is a present obligation,
     * (b) an outflow is probable, and
     * (c) a reliable estimate can be made.
     */
    public function isRecognisable(): bool
    {
        return $this->provision_type === self::TYPE_PROVISION
            && $this->probability === self::PROB_PROBABLE;
    }

    // ─── Scopes ──────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeProvisions($query)
    {
        return $query->where('provision_type', self::TYPE_PROVISION);
    }

    public function scopeContingentLiabilities($query)
    {
        return $query->where('provision_type', self::TYPE_CONTINGENT_LIABILITY);
    }

    public function scopeContingentAssets($query)
    {
        return $query->where('provision_type', self::TYPE_CONTINGENT_ASSET);
    }
}
