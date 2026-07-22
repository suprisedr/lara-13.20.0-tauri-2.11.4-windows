<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The opening balance of a single chart-of-accounts account for a single
 * financial period. `amount` is the signed balance in the account's normal
 * direction (positive = normal debit balance for assets/expenses; positive =
 * normal credit balance for liabilities/equity/income).
 */
class AccountOpeningBalance extends Model
{
    protected $fillable = [
        'financial_period_id',
        'chart_of_account_id',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(FinancialPeriod::class, 'financial_period_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }
}
