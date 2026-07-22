<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionVector extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'transaction_vectors';

    protected $fillable = [
        'mysql_transaction_id',
        'amount',
        'currency',
        'counterparty',
        'reference',
        'tx_date',
        'embedding',
    ];

    protected function casts(): array
    {
        return [
            'tx_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * Format a float array into the textual `[0.1,0.2,...]` representation
     * pgvector accepts for INSERT / UPDATE / comparison.
     */
    public static function formatVector(array $embedding): string
    {
        return '['.implode(',', array_map(fn ($v) => (float) $v, $embedding)).']';
    }
}
