<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovernmentGrantEvent extends Model
{
    public const TYPE_RECOGNITION  = 'recognition';
    public const TYPE_RECEIPT      = 'receipt';
    public const TYPE_AMORTISATION = 'amortisation';
    public const TYPE_REFUND       = 'refund';

    public const STATUS_PENDING = 'pending';
    public const STATUS_POSTED  = 'posted';
    public const STATUS_FAILED  = 'failed';

    protected $fillable = [
        'government_grant_id',
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

    public function governmentGrant(): BelongsTo
    {
        return $this->belongsTo(GovernmentGrant::class);
    }

    public static function labels(): array
    {
        return [
            self::TYPE_RECOGNITION  => 'Grant Recognised',
            self::TYPE_RECEIPT      => 'Cash Received',
            self::TYPE_AMORTISATION => 'Amortised to Income',
            self::TYPE_REFUND       => 'Grant Refunded',
        ];
    }

    public static function colors(): array
    {
        return [
            self::TYPE_RECOGNITION  => ['bg' => '#ede9fe', 'color' => '#5b21b6'],
            self::TYPE_RECEIPT      => ['bg' => '#dbeafe', 'color' => '#1e40af'],
            self::TYPE_AMORTISATION => ['bg' => '#dcfce7', 'color' => '#166534'],
            self::TYPE_REFUND       => ['bg' => '#fee2e2', 'color' => '#991b1b'],
        ];
    }
}
