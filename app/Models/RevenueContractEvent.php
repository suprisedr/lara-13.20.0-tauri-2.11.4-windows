<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevenueContractEvent extends Model
{
    public const TYPE_INCEPTION                   = 'inception';
    public const TYPE_REVENUE_RECOGNITION         = 'revenue_recognition';
    public const TYPE_VARIABLE_CONSIDERATION      = 'variable_consideration_update';
    public const TYPE_MODIFICATION                = 'modification';
    public const TYPE_COST_INCURRED               = 'cost_incurred';
    public const TYPE_COMPLETION                  = 'completion';

    public const STATUS_PENDING = 'pending';
    public const STATUS_POSTED  = 'posted';
    public const STATUS_FAILED  = 'failed';

    protected $fillable = [
        'revenue_contract_id',
        'performance_obligation_id',
        'event_type',
        'event_date',
        'amount',
        'description',
        'journal_status',
        'transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'amount'     => 'decimal:2',
        ];
    }

    public function revenueContract(): BelongsTo
    {
        return $this->belongsTo(RevenueContract::class);
    }

    public function performanceObligation(): BelongsTo
    {
        return $this->belongsTo(PerformanceObligation::class);
    }

    public static function labels(): array
    {
        return [
            self::TYPE_INCEPTION              => 'Contract Inception',
            self::TYPE_REVENUE_RECOGNITION    => 'Revenue Recognition',
            self::TYPE_VARIABLE_CONSIDERATION => 'Variable Consideration Update',
            self::TYPE_MODIFICATION           => 'Contract Modification',
            self::TYPE_COST_INCURRED          => 'Cost Incurred',
            self::TYPE_COMPLETION             => 'Contract Completion',
        ];
    }

    public static function colors(): array
    {
        return [
            self::TYPE_INCEPTION              => ['bg' => '#dbeafe', 'color' => '#1e40af'],
            self::TYPE_REVENUE_RECOGNITION    => ['bg' => '#dcfce7', 'color' => '#166534'],
            self::TYPE_VARIABLE_CONSIDERATION => ['bg' => '#fef3c7', 'color' => '#92400e'],
            self::TYPE_MODIFICATION           => ['bg' => '#ede9fe', 'color' => '#5b21b6'],
            self::TYPE_COST_INCURRED          => ['bg' => '#f3e8ff', 'color' => '#7e22ce'],
            self::TYPE_COMPLETION             => ['bg' => '#d1fae5', 'color' => '#065f46'],
        ];
    }
}
