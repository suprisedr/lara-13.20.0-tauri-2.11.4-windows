<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvestmentPropertyClass extends Model
{
    const MODEL_COST       = 'cost';
    const MODEL_FAIR_VALUE = 'fair_value';

    const MODELS = [
        self::MODEL_COST       => 'Cost Model (IAS 40.56)',
        self::MODEL_FAIR_VALUE => 'Fair Value Model (IAS 40.33)',
    ];

    protected $fillable = [
        'company_id',
        'name',
        'useful_life_years',
        'depreciation_method',
        'measurement_model',
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

    public function investmentProperties(): HasMany
    {
        return $this->hasMany(InvestmentProperty::class);
    }
}
