<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupEliminationLine extends Model
{
    protected $fillable = [
        'group_elimination_id',
        'bucket',
        'label',
        'debit',
        'credit',
    ];

    protected function casts(): array
    {
        return [
            'debit'  => 'decimal:2',
            'credit' => 'decimal:2',
        ];
    }

    /**
     * Consolidated-statement buckets an elimination line can target, grouped by
     * the statement they belong to. The boolean marks whether the bucket is
     * debit-normal (assets/expenses) — used to convert a Dr/Cr line into the
     * signed effect on that consolidated total.
     */
    public const BUCKETS = [
        // Statement of financial position
        'non_current_assets'      => ['label' => 'Non-current assets', 'debit_normal' => true,  'statement' => 'sfp'],
        'current_assets'          => ['label' => 'Current assets',     'debit_normal' => true,  'statement' => 'sfp'],
        'goodwill'                => ['label' => 'Goodwill',           'debit_normal' => true,  'statement' => 'sfp'],
        'non_current_liabilities' => ['label' => 'Non-current liabilities', 'debit_normal' => false, 'statement' => 'sfp'],
        'current_liabilities'     => ['label' => 'Current liabilities', 'debit_normal' => false, 'statement' => 'sfp'],
        'equity'                  => ['label' => 'Equity / reserves',  'debit_normal' => false, 'statement' => 'sfp'],
        // Statement of profit or loss
        'revenue'                 => ['label' => 'Revenue',            'debit_normal' => false, 'statement' => 'pl'],
        'other_income'            => ['label' => 'Other income',       'debit_normal' => false, 'statement' => 'pl'],
        'cost_of_sales'           => ['label' => 'Cost of sales',      'debit_normal' => true,  'statement' => 'pl'],
        'operating_expenses'      => ['label' => 'Operating expenses', 'debit_normal' => true,  'statement' => 'pl'],
    ];

    public function elimination(): BelongsTo
    {
        return $this->belongsTo(GroupElimination::class, 'group_elimination_id');
    }

    /**
     * Signed effect of this line on its consolidated bucket total.
     * Debit-normal buckets increase with debits; credit-normal with credits.
     */
    public function signedEffect(): float
    {
        $debitNormal = self::BUCKETS[$this->bucket]['debit_normal'] ?? true;

        return $debitNormal
            ? (float) $this->debit - (float) $this->credit
            : (float) $this->credit - (float) $this->debit;
    }

    public function getBucketLabelAttribute(): string
    {
        return self::BUCKETS[$this->bucket]['label'] ?? $this->bucket;
    }
}
