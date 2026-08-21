<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\BroadcastsChanges;

class GovernmentGrant extends Model
{
    use BroadcastsChanges;

    public const TYPE_INCOME = 'income';
    public const TYPE_ASSET  = 'asset';

    public const STATUS_ACTIVE   = 'active';
    public const STATUS_FULFILLED = 'fulfilled';
    public const STATUS_REFUNDED = 'refunded';

    public const METHOD_SYSTEMATIC = 'systematic';
    public const METHOD_IMMEDIATE  = 'immediate';

    protected $fillable = [
        'company_id',
        'name',
        'grant_type',
        'grant_reference',
        'granting_authority',
        'grant_date',
        'total_amount',
        'recognised_amount',
        'deferred_amount',
        'related_asset_type',
        'related_asset_id',
        'recognition_method',
        'conditions_text',
        'status',
        'fulfilment_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'grant_date'        => 'date',
            'fulfilment_date'   => 'date',
            'total_amount'      => 'decimal:2',
            'recognised_amount' => 'decimal:2',
            'deferred_amount'   => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(GovernmentGrantEvent::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function remainingDeferred(): float
    {
        return max((float) $this->deferred_amount, 0);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
