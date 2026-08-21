<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProvisionEvent extends Model
{
    public const TYPE_RECOGNITION    = 'recognition';
    public const TYPE_REMEASUREMENT  = 'remeasurement';
    public const TYPE_UNWINDING      = 'unwinding';
    public const TYPE_UTILISATION    = 'utilisation';
    public const TYPE_REVERSAL       = 'reversal';

    public const STATUS_PENDING = 'pending';
    public const STATUS_POSTED  = 'posted';
    public const STATUS_FAILED  = 'failed';

    protected $fillable = [
        'provision_id',
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

    public function provision(): BelongsTo
    {
        return $this->belongsTo(Provision::class);
    }

    public static function labels(): array
    {
        return [
            self::TYPE_RECOGNITION   => 'Recognition',
            self::TYPE_REMEASUREMENT => 'Remeasurement',
            self::TYPE_UNWINDING     => 'Unwinding of Discount',
            self::TYPE_UTILISATION   => 'Utilisation',
            self::TYPE_REVERSAL      => 'Reversal',
        ];
    }

    public static function colors(): array
    {
        return [
            self::TYPE_RECOGNITION   => ['bg' => '#ede9fe', 'color' => '#5b21b6'],
            self::TYPE_REMEASUREMENT => ['bg' => '#dbeafe', 'color' => '#1e40af'],
            self::TYPE_UNWINDING     => ['bg' => '#fef3c7', 'color' => '#92400e'],
            self::TYPE_UTILISATION   => ['bg' => '#fee2e2', 'color' => '#991b1b'],
            self::TYPE_REVERSAL      => ['bg' => '#dcfce7', 'color' => '#166534'],
        ];
    }
}
