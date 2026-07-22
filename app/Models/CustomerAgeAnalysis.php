<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAgeAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'customer_id',
        'as_of_date',
        'current_amount',
        'days_31_60',
        'days_61_90',
        'days_91_plus',
        'total_outstanding',
        'ecl_rate_current',
        'ecl_rate_31_60',
        'ecl_rate_61_90',
        'ecl_rate_91_plus',
        'ecl_current',
        'ecl_31_60',
        'ecl_61_90',
        'ecl_91_plus',
        'total_ecl',
    ];

    protected function casts(): array
    {
        return [
            'as_of_date' => 'date',
            'current_amount' => 'decimal:2',
            'days_31_60' => 'decimal:2',
            'days_61_90' => 'decimal:2',
            'days_91_plus' => 'decimal:2',
            'total_outstanding' => 'decimal:2',
            'ecl_rate_current' => 'decimal:4',
            'ecl_rate_31_60' => 'decimal:4',
            'ecl_rate_61_90' => 'decimal:4',
            'ecl_rate_91_plus' => 'decimal:4',
            'ecl_current' => 'decimal:2',
            'ecl_31_60' => 'decimal:2',
            'ecl_61_90' => 'decimal:2',
            'ecl_91_plus' => 'decimal:2',
            'total_ecl' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
