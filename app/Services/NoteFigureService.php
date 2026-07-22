<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\FinancialStatementNote;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Builds the figures table for a financial-statement note from its linked
 * chart-of-accounts accounts. Each linked account contributes its amount for the
 * period (current and prior year), added or subtracted per the link's sign.
 *
 * Income/expense accounts contribute their movement for the period; balance-sheet
 * accounts (assets/liabilities/equity) contribute their balance as at the period
 * end (period-aware, matching the statements).
 */
class NoteFigureService
{
    /**
     * @return array{
     *   has: bool,
     *   rows: array<int, array{label: string, current: float, prior: float}>,
     *   total_current: float,
     *   total_prior: float,
     * }
     */
    public function figuresFor(Company $company, FinancialStatementNote $note, string $startDate, string $endDate): array
    {
        $links = $note->accountLinks()->with('account')->get();

        $empty = ['has' => false, 'rows' => [], 'total_current' => 0.0, 'total_prior' => 0.0];
        if ($links->isEmpty()) {
            return $empty;
        }

        $priorStart = Carbon::parse($startDate)->subYear()->format('Y-m-d');
        $priorEnd   = Carbon::parse($endDate)->subYear()->format('Y-m-d');

        $rows = [];
        $totalCurrent = 0.0;
        $totalPrior = 0.0;

        foreach ($links as $link) {
            $account = $link->account;
            if (! $account || $account->company_id !== $company->id) {
                continue;
            }

            $balancePoint = $link->balance_point ?? 'closing';

            // For opening-balance rows, shift the effective end date back to
            // the day before the period starts so we read the opening stock/balance.
            $effectiveEnd      = $balancePoint === 'opening'
                ? Carbon::parse($startDate)->subDay()->format('Y-m-d')
                : $endDate;
            $effectivePriorEnd = $balancePoint === 'opening'
                ? Carbon::parse($priorStart)->subDay()->format('Y-m-d')
                : $priorEnd;

            $sign = (int) $link->sign < 0 ? -1 : 1;
            $current = $sign * $this->accountAmount($company, $account, $startDate, $effectiveEnd);
            $prior   = $sign * $this->accountAmount($company, $account, $priorStart, $effectivePriorEnd);

            $rows[] = [
                'label'   => $link->label ?: $account->account_name,
                'current' => round($current, 2),
                'prior'   => round($prior, 2),
            ];

            $totalCurrent += $current;
            $totalPrior += $prior;
        }

        return [
            'has'           => $rows !== [],
            'rows'          => $rows,
            'total_current' => round($totalCurrent, 2),
            'total_prior'   => round($totalPrior, 2),
        ];
    }

    /**
     * The account's natural (positive-normal) amount for a period: a movement for
     * income/expense accounts, or an as-at-end balance for balance-sheet accounts.
     */
    private function accountAmount(Company $company, ChartOfAccount $account, string $startDate, string $endDate): float
    {
        if (in_array($account->account_type, ['income', 'expenses'], true)) {
            [$debits, $credits] = $this->movementTotals($company, $account->id, $startDate, $endDate);

            return $account->account_type === 'income'
                ? $credits - $debits
                : $debits - $credits;
        }

        // Balance-sheet account: opening balance (period-aware) + movements to the
        // period end.
        $ctx = $company->openingContext($endDate);
        $opening = $ctx['amounts'] !== null
            ? (float) ($ctx['amounts'][$account->id] ?? 0)
            : (float) $account->opening_balance;

        [$debits, $credits] = $ctx['start']
            ? $this->movementTotals($company, $account->id, $ctx['start'], $endDate)
            : $this->movementTotals($company, $account->id, null, $endDate);

        return in_array($account->account_type, ['assets'], true)
            ? $opening + ($debits - $credits)
            : $opening + ($credits - $debits);
    }

    /**
     * @return array{0: float, 1: float} [debits, credits]
     */
    private function movementTotals(Company $company, int $accountId, ?string $startDate, string $endDate): array
    {
        $query = DB::table('journal_lines')
            ->join('transactions', 'journal_lines.transaction_id', '=', 'transactions.id')
            ->where('journal_lines.chart_of_account_id', $accountId)
            ->where('transactions.company_id', $company->id)
            ->whereIn('transactions.status', ['posted', 'reversed'])
            ->where('transactions.transaction_date', '<=', $endDate);

        if ($startDate !== null) {
            $query->where('transactions.transaction_date', '>=', $startDate);
        }

        $row = $query->selectRaw(
            "SUM(CASE WHEN journal_lines.type = 'debit'  THEN journal_lines.amount ELSE 0 END) as total_debits,"
            . " SUM(CASE WHEN journal_lines.type = 'credit' THEN journal_lines.amount ELSE 0 END) as total_credits"
        )->first();

        return [(float) ($row->total_debits ?? 0), (float) ($row->total_credits ?? 0)];
    }
}
