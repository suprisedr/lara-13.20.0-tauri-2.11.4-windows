<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'interval_months' => 'integer',
            'monthly_equivalent' => 'integer',
            'discount_percent' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function formattedPrice(): string
    {
        return 'R ' . number_format($this->price / 100, 2);
    }

    public function formattedMonthlyEquivalent(): string
    {
        return 'R ' . number_format($this->monthly_equivalent / 100, 2);
    }
}
