<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VatRegistration extends Model
{
    protected $fillable = [
        'company_id',
        'vat_number',
        'registered_at',
        'deregistered_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'registered_at'   => 'date',
            'deregistered_at' => 'date',
            'is_active'       => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
