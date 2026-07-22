<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaseEvent extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_POSTED  = 'posted';
    public const STATUS_FAILED  = 'failed';

    public const TYPES = [
        'commencement'   => 'Commencement',
        'payment'        => 'Lease Payment',
        'modification'   => 'Lease Modification',
        'impairment'     => 'Impairment',
        'reverse_impairment' => 'Reversal of Impairment',
        'reassessment'   => 'Reassessment',
        'termination'    => 'Early Termination',
        'rou_depreciation' => 'ROU Depreciation',
    ];

    protected $fillable = [
        'lease_id',
        'event_date',
        'type',
        'amount',
        'description',
        'details',
        'journal_status',
        'transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'amount' => 'decimal:2',
            'details' => 'array',
        ];
    }

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
