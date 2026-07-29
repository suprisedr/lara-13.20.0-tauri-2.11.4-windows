<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use App\Models\Concerns\BroadcastsChanges;

class Customer extends Model
{
    use HasFactory, Searchable;
    use BroadcastsChanges;

    protected $fillable = [
        'company_id',
        'name',
        'contact_name',
        'email',
        'phone',
        'address',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function company(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function invoices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function quotations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function journalLines(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function ageAnalyses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CustomerAgeAnalysis::class);
    }

    public function toSearchableArray(): array
    {
        return [
            'id'           => $this->id,
            'company_id'   => $this->company_id,
            'name'         => $this->name,
            'contact_name' => $this->contact_name,
            'email'        => $this->email,
            'phone'        => $this->phone,
            'address'      => $this->address,
        ];
    }
}
