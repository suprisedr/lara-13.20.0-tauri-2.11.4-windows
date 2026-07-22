<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IntangibleClass extends Model
{
    public const POLICY_COST        = 'cost';
    public const POLICY_REVALUATION = 'revaluation';

    public const POLICIES = [
        self::POLICY_COST        => 'Cost Model (IAS 38.74)',
        self::POLICY_REVALUATION => 'Revaluation Model (IAS 38.75 — active market required)',
    ];

    protected $fillable = [
        'company_id',
        'name',
        'useful_life_years',
        'amortisation_method',
        'accounting_policy',
        'indefinite_life',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'useful_life_years' => 'decimal:2',
            'indefinite_life'   => 'boolean',
            'sort_order'        => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function intangibleAssets(): HasMany
    {
        return $this->hasMany(IntangibleAsset::class);
    }
}
