<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetVector extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'asset_vectors';

    protected $fillable = [
        'mysql_asset_id',
        'cost',
        'currency',
        'asset_name',
        'asset_class',
        'accounting_policy',
        'acquisition_date',
        'embedding',
    ];

    protected function casts(): array
    {
        return [
            'cost'             => 'decimal:2',
            'acquisition_date' => 'date',
        ];
    }

    public static function formatVector(array $embedding): string
    {
        return '['.implode(',', array_map(fn ($v) => (float) $v, $embedding)).']';
    }
}
