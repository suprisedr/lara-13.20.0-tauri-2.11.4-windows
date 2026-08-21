<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\BroadcastsChanges;

class RelatedPartyTransaction extends Model
{
    use BroadcastsChanges;

    public const TRANSACTION_TYPES = [
        'sale'            => 'Sale of Goods/Services',
        'purchase'        => 'Purchase of Goods/Services',
        'loan'            => 'Loan',
        'management_fee'  => 'Management Fee',
        'dividend'        => 'Dividend',
        'guarantee'       => 'Guarantee',
        'compensation'    => 'Key Management Compensation',
        'lease'           => 'Lease',
        'other'           => 'Other',
    ];

    protected $fillable = [
        'company_id',
        'related_party_id',
        'transaction_id',
        'transaction_date',
        'transaction_type',
        'amount',
        'description',
        'outstanding_balance',
        'terms_and_conditions',
        'is_arm_length',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date'    => 'date',
            'amount'              => 'decimal:2',
            'outstanding_balance' => 'decimal:2',
            'is_arm_length'       => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function relatedParty(): BelongsTo
    {
        return $this->belongsTo(RelatedParty::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
