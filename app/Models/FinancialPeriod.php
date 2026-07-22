<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A company financial period (financial year). Opening balances are recorded
 * per period; the opening balance of a new period defaults to the closing
 * balance carried forward from the prior period (editable thereafter).
 */
class FinancialPeriod extends Model
{
    protected $fillable = [
        'company_id',
        'label',
        'start_date',
        'end_date',
        'is_closed',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date'   => 'date',
            'is_closed'  => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function openingBalances(): HasMany
    {
        return $this->hasMany(AccountOpeningBalance::class);
    }

    /** True when the given Y-m-d date falls within this period (inclusive). */
    public function containsDate(string $date): bool
    {
        return $this->start_date->toDateString() <= $date
            && $this->end_date->toDateString() >= $date;
    }
}
