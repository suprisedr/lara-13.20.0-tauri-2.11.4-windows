<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PpeClass extends Model
{
    const POLICY_COST        = 'cost';
    const POLICY_REVALUATION = 'revaluation';

    const POLICIES = [
        self::POLICY_COST        => 'Cost Model (IAS 16.30)',
        self::POLICY_REVALUATION => 'Revaluation Model (IAS 16.31)',
    ];

    protected $fillable = [
        'company_id',
        'name',
        'useful_life_years',
        'depreciation_method',
        'accounting_policy',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'useful_life_years' => 'decimal:2',
            'sort_order'        => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function accountLinks(): HasMany
    {
        return $this->hasMany(PpeClassAccountLink::class);
    }

    /** @return HasMany<PpeClassAccountLink> */
    public function costLinks(): HasMany
    {
        return $this->accountLinks()->where('role', PpeClassAccountLink::ROLE_COST);
    }

    /** @return HasMany<PpeClassAccountLink> */
    public function accumulatedDepreciationLinks(): HasMany
    {
        return $this->accountLinks()->where('role', PpeClassAccountLink::ROLE_ACCUMULATED_DEPRECIATION);
    }

    /** @return HasMany<PpeClassAccountLink> */
    public function depreciationExpenseLinks(): HasMany
    {
        return $this->accountLinks()->where('role', PpeClassAccountLink::ROLE_DEPRECIATION_EXPENSE);
    }
}
