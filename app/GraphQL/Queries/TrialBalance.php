<?php

namespace App\GraphQL\Queries;

use App\Models\Company;
use Carbon\Carbon;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class TrialBalance
{
    /**
     * @param  array{company_id: string, start_date: string, end_date: string}  $args
     * @return Collection<int, \App\Models\ChartOfAccount>
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Collection
    {
        /** @var Company $company */
        $company = Company::findOrFail($args['company_id']);

        if ($company->user_id !== $context->user()->id) {
            abort(403, 'You do not own this company.');
        }

        $startDate = $args['start_date'];
        $endDate   = $args['end_date'];

        $debitSub = DB::table('journal_lines')
            ->join('transactions', 'transactions.id', '=', 'journal_lines.transaction_id')
            ->whereColumn('journal_lines.chart_of_account_id', 'chart_of_accounts.id')
            ->where('journal_lines.type', 'debit')
            ->whereIn('transactions.status', ['posted', 'reversed'])
            ->whereDate('transactions.transaction_date', '>=', $startDate)
            ->whereDate('transactions.transaction_date', '<=', $endDate)
            ->selectRaw('COALESCE(SUM(journal_lines.amount), 0)');

        $creditSub = DB::table('journal_lines')
            ->join('transactions', 'transactions.id', '=', 'journal_lines.transaction_id')
            ->whereColumn('journal_lines.chart_of_account_id', 'chart_of_accounts.id')
            ->where('journal_lines.type', 'credit')
            ->whereIn('transactions.status', ['posted', 'reversed'])
            ->whereDate('transactions.transaction_date', '>=', $startDate)
            ->whereDate('transactions.transaction_date', '<=', $endDate)
            ->selectRaw('COALESCE(SUM(journal_lines.amount), 0)');

        $accounts = $company->chartOfAccounts()
            ->select('chart_of_accounts.*')
            ->selectSub($debitSub, 'posted_debits')
            ->selectSub($creditSub, 'posted_credits')
            ->orderBy('account_code')
            ->get();

        // Apply period-aware opening balances
        $ctx = $company->openingContext($startDate);
        if ($ctx['amounts'] !== null) {
            $dayBefore = Carbon::parse($startDate)->subDay()->format('Y-m-d');

            $pre = DB::table('journal_lines')
                ->join('transactions', 'transactions.id', '=', 'journal_lines.transaction_id')
                ->whereIn('journal_lines.chart_of_account_id', $accounts->pluck('id'))
                ->whereIn('transactions.status', ['posted', 'reversed'])
                ->whereDate('transactions.transaction_date', '>=', $ctx['start'])
                ->whereDate('transactions.transaction_date', '<=', $dayBefore)
                ->selectRaw('journal_lines.chart_of_account_id,
                    COALESCE(SUM(CASE WHEN journal_lines.type = ? THEN journal_lines.amount ELSE 0 END), 0) AS total_debits,
                    COALESCE(SUM(CASE WHEN journal_lines.type = ? THEN journal_lines.amount ELSE 0 END), 0) AS total_credits',
                    ['debit', 'credit'])
                ->groupBy('journal_lines.chart_of_account_id')
                ->get()
                ->keyBy('chart_of_account_id');

            foreach ($accounts as $account) {
                $row = $pre->get($account->id);
                $d = $row ? (float) $row->total_debits : 0.0;
                $c = $row ? (float) $row->total_credits : 0.0;
                $signedPre = in_array($account->account_type, ['assets', 'expenses'], true)
                    ? ($d - $c)
                    : ($c - $d);
                $account->opening_balance = (float) ($ctx['amounts'][$account->id] ?? 0) + $signedPre;
            }
        }

        return $accounts;
    }
}
