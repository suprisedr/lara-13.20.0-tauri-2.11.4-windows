<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceVector extends Model
{
    protected $connection = 'pgsql';

    protected $table = 'invoice_vectors';

    protected $fillable = [
        'mysql_invoice_id',
        'amount',
        'currency',
        'customer_name',
        'invoice_number',
        'invoice_date',
        'embedding',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }
}
