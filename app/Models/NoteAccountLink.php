<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Links a chart-of-accounts account to a financial-statement note so the note
 * can display a figures table. `sign` (+1 / -1) controls whether the account's
 * amount is added to or subtracted from the note total.
 */
class NoteAccountLink extends Model
{
    protected $fillable = [
        'financial_statement_note_id',
        'chart_of_account_id',
        'sign',
        'balance_point',
        'label',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sign'       => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function note(): BelongsTo
    {
        return $this->belongsTo(FinancialStatementNote::class, 'financial_statement_note_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'chart_of_account_id');
    }
}
