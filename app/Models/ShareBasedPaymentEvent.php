<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShareBasedPaymentEvent extends Model
{
    public const TYPE_GRANT         = 'grant';
    public const TYPE_VESTING_EXPENSE = 'vesting_expense';
    public const TYPE_FORFEITURE    = 'forfeiture';
    public const TYPE_EXERCISE      = 'exercise';
    public const TYPE_MODIFICATION  = 'modification';
    public const TYPE_CANCELLATION  = 'cancellation';

    public const STATUS_PENDING = 'pending';
    public const STATUS_POSTED  = 'posted';
    public const STATUS_FAILED  = 'failed';

    protected $fillable = [
        'share_based_payment_arrangement_id',
        'event_type',
        'event_date',
        'amount',
        'instruments_affected',
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

    public function arrangement(): BelongsTo
    {
        return $this->belongsTo(ShareBasedPaymentArrangement::class, 'share_based_payment_arrangement_id');
    }

    public static function labels(): array
    {
        return [
            self::TYPE_GRANT          => 'Grant',
            self::TYPE_VESTING_EXPENSE => 'Vesting Expense',
            self::TYPE_FORFEITURE     => 'Forfeiture',
            self::TYPE_EXERCISE       => 'Exercise',
            self::TYPE_MODIFICATION   => 'Modification',
            self::TYPE_CANCELLATION   => 'Cancellation',
        ];
    }

    public static function colors(): array
    {
        return [
            self::TYPE_GRANT          => ['bg' => '#dbeafe', 'color' => '#1e40af'],
            self::TYPE_VESTING_EXPENSE => ['bg' => '#ede9fe', 'color' => '#5b21b6'],
            self::TYPE_FORFEITURE     => ['bg' => '#fee2e2', 'color' => '#991b1b'],
            self::TYPE_EXERCISE       => ['bg' => '#dcfce7', 'color' => '#166534'],
            self::TYPE_MODIFICATION   => ['bg' => '#fef3c7', 'color' => '#92400e'],
            self::TYPE_CANCELLATION   => ['bg' => '#f3f4f6', 'color' => '#374151'],
        ];
    }
}
