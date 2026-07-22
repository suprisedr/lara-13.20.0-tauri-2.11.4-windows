<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EclRateSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'current_rate',
        'days_31_60_rate',
        'days_61_90_rate',
        'days_91_plus_rate',
    ];

    protected function casts(): array
    {
        return [
            'current_rate' => 'decimal:4',
            'days_31_60_rate' => 'decimal:4',
            'days_61_90_rate' => 'decimal:4',
            'days_91_plus_rate' => 'decimal:4',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Default IFRS 9 simplified-approach provision matrix rates, used when a
     * company has not configured its own loss rates yet.
     */
    public static function defaults(): array
    {
        return [
            'current_rate' => 0.0100,
            'days_31_60_rate' => 0.0500,
            'days_61_90_rate' => 0.2500,
            'days_91_plus_rate' => 0.5000,
        ];
    }

    public static function forCompany(Company $company): self
    {
        return static::firstOrCreate(
            ['company_id' => $company->id],
            static::defaults(),
        );
    }
}
