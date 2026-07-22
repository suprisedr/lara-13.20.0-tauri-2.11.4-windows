<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupElimination extends Model
{
    protected $fillable = [
        'company_id',
        'elimination_date',
        'type',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'elimination_date' => 'date',
        ];
    }

    /** Human-readable labels for the standard IFRS 10 / IFRS 12 elimination types. */
    public const TYPES = [
        'intercompany_balance'  => 'Intragroup balance (receivable/payable, loan)',
        'intercompany_trading'  => 'Intragroup trading (sales/purchases)',
        'unrealised_profit'     => 'Unrealised profit in inventory/PPE',
        'intragroup_dividend'   => 'Intragroup dividend',
        'other'                 => 'Other consolidation adjustment',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(GroupEliminationLine::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
