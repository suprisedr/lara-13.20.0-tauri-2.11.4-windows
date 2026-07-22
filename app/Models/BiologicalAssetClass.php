<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BiologicalAssetClass extends Model
{
    public const CATEGORY_CONSUMABLE = 'consumable';
    public const CATEGORY_BEARER    = 'bearer';

    public const CATEGORIES = [
        self::CATEGORY_CONSUMABLE => 'Consumable (IAS 41.44)',
        self::CATEGORY_BEARER    => 'Bearer (IAS 41.44)',
    ];

    protected $fillable = [
        'company_id',
        'name',
        'category',
        'sort_order',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function biologicalAssets(): HasMany
    {
        return $this->hasMany(BiologicalAsset::class);
    }
}
