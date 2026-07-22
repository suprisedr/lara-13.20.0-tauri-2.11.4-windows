<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountVector extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'account_vectors';

    protected $fillable = [
        'mysql_account_id',
        'mysql_company_id',
        'account_code',
        'account_name',
        'account_type',
        'category',
        'embedding',
    ];

    public static function formatVector(array $embedding): string
    {
        return '['.implode(',', array_map(fn ($v) => (float) $v, $embedding)).']';
    }
}
