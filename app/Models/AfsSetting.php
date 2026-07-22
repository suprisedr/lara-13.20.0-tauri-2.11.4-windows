<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AfsSetting extends Model
{
    protected $fillable = [
        'company_id',
        'country_of_incorporation',
        'nature_of_business',
        'directors',
        'registered_office',
        'business_address',
        'postal_address',
        'practitioner_name',
        'practitioner_qualification',
        'practitioner_membership',
        'practitioner_contact',
        'compilation_directors',
        'approval_date',
        'level_of_assurance',
        'oci_account_ids',
    ];

    protected function casts(): array
    {
        return [
            'directors'       => 'array',
            'approval_date'   => 'date',
            'oci_account_ids' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
