<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && ($this->current_period_end === null || $this->current_period_end->isFuture());
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function isOnGracePeriod(): bool
    {
        return $this->isCancelled() && $this->current_period_end?->isFuture();
    }

    public function daysRemaining(): ?int
    {
        if (! $this->current_period_end) {
            return null;
        }

        return max(0, (int) now()->diffInDays($this->current_period_end, false));
    }
}
