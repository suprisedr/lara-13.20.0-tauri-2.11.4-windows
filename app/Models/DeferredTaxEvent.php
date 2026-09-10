<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeferredTaxEvent extends Model
{
    public const TYPE_INITIAL_RECOGNITION = 'initial_recognition';
    public const TYPE_REMEASUREMENT       = 'remeasurement';
    public const TYPE_RATE_CHANGE         = 'rate_change';
    public const TYPE_REVERSAL            = 'reversal';

    public const STATUS_PENDING = 'pending';
    public const STATUS_POSTED  = 'posted';
    public const STATUS_FAILED  = 'failed';

    protected $fillable = [
        'deferred_tax_item_id',
        'event_type',
        'event_date',
        'amount',
        'previous_balance',
        'new_balance',
        'description',
        'journal_status',
        'transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'event_date'       => 'date',
            'amount'           => 'decimal:2',
            'previous_balance' => 'decimal:2',
            'new_balance'      => 'decimal:2',
        ];
    }

    public function deferredTaxItem(): BelongsTo
    {
        return $this->belongsTo(DeferredTaxItem::class);
    }

    public static function labels(): array
    {
        return [
            self::TYPE_INITIAL_RECOGNITION => 'Initial Recognition',
            self::TYPE_REMEASUREMENT       => 'Remeasurement',
            self::TYPE_RATE_CHANGE         => 'Rate Change',
            self::TYPE_REVERSAL            => 'Reversal',
        ];
    }

    public static function colors(): array
    {
        return [
            self::TYPE_INITIAL_RECOGNITION => ['bg' => '#ede9fe', 'color' => '#5b21b6'],
            self::TYPE_REMEASUREMENT       => ['bg' => '#dbeafe', 'color' => '#1e40af'],
            self::TYPE_RATE_CHANGE         => ['bg' => '#fef3c7', 'color' => '#92400e'],
            self::TYPE_REVERSAL            => ['bg' => '#dcfce7', 'color' => '#166534'],
        ];
    }
}
