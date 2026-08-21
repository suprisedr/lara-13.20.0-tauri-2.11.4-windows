<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\Concerns\BroadcastsChanges;

class BorrowingCostCapitalisation extends Model
{
    use BroadcastsChanges;

    public const STATUS_ACTIVE    = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'company_id',
        'qualifying_asset_id',
        'qualifying_asset_type',
        'borrowing_source',
        'capitalisation_start_date',
        'capitalisation_end_date',
        'status',
        'total_capitalised',
        'borrowing_rate',
        'weighted_average_rate',
        'notes',
        'last_capitalisation_posted_on',
    ];

    protected function casts(): array
    {
        return [
            'capitalisation_start_date'     => 'date',
            'capitalisation_end_date'       => 'date',
            'total_capitalised'             => 'decimal:2',
            'borrowing_rate'                => 'decimal:4',
            'weighted_average_rate'         => 'decimal:4',
            'last_capitalisation_posted_on' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function qualifyingAsset(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'qualifying_asset_type', 'qualifying_asset_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(BorrowingCostEvent::class);
    }

    public function monthlyCapitalisableAmount(): float
    {
        $rate = (float) ($this->weighted_average_rate ?? $this->borrowing_rate);
        $monthlyRate = $rate / 100 / 12;

        return round((float) $this->total_capitalised * $monthlyRate, 2);
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}
