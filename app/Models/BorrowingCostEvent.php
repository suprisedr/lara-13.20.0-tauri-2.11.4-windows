<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BorrowingCostEvent extends Model
{
    public const TYPE_CAPITALISATION = 'capitalisation';
    public const TYPE_SUSPENSION     = 'suspension';
    public const TYPE_COMPLETION     = 'completion';

    public const STATUS_PENDING = 'pending';
    public const STATUS_POSTED  = 'posted';
    public const STATUS_FAILED  = 'failed';

    protected $fillable = [
        'borrowing_cost_capitalisation_id',
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

    public function borrowingCostCapitalisation(): BelongsTo
    {
        return $this->belongsTo(BorrowingCostCapitalisation::class);
    }

    public static function labels(): array
    {
        return [
            self::TYPE_CAPITALISATION => 'Capitalisation',
            self::TYPE_SUSPENSION     => 'Suspension',
            self::TYPE_COMPLETION     => 'Completion',
        ];
    }

    public static function colors(): array
    {
        return [
            self::TYPE_CAPITALISATION => ['bg' => '#ede9fe', 'color' => '#5b21b6'],
            self::TYPE_SUSPENSION     => ['bg' => '#fef3c7', 'color' => '#92400e'],
            self::TYPE_COMPLETION     => ['bg' => '#dbeafe', 'color' => '#1e40af'],
        ];
    }
}
