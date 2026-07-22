<?php

namespace App\Services;

use App\Ai\Agents\EclPostingAgent;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;

class EclPostingService
{
    public function __construct(
        private readonly TransactionService $transactions,
        private readonly AccountVectorSearchService $accountSearch,
    ) {}

    public function postProvision(Company $company, User $user, string $asOfDate): void
    {
        $analyses          = (new AgeAnalysisService)->generate($company, $asOfDate);
        $requiredAllowance = round($analyses->sum('total_ecl'), 2);
        $currentBalance    = $this->getAllowanceBalance($company, $asOfDate);
        $movement          = round($requiredAllowance - $currentBalance, 2);

        if (abs($movement) < 0.01) {
            return;
        }

        $accounts = $this->accountSearch->searchMultiple($company->id, [
            'expected credit loss ECL provision expense',
            'allowance for credit losses contra receivable',
            'bad debt expense credit loss',
            'accounts receivable debtors asset',
        ], 10);

        $list = $accounts
            ->map(fn (ChartOfAccount $a) => "- id={$a->id}, code={$a->account_code}, name=\"{$a->account_name}\", type={$a->account_type}".($a->is_contra ? ' (contra)' : ''))
            ->implode("\n");

        $direction = $movement > 0 ? 'increase' : 'decrease';

        $prompt = <<<TEXT
        Posting type: ecl_provision_{$direction}

        Company: {$company->registered_name}
        As at: {$asOfDate}
        Gross trade receivables: {$analyses->sum('total_outstanding')}
        Required ECL allowance: {$requiredAllowance}
        Current GL allowance balance: {$currentBalance}
        Movement ({$direction}): {$movement}

        Chart of accounts:
        {$list}

        Pick the IFRS 9 accounts for posting this ECL provision {$direction}.
        TEXT;

        $response = (new EclPostingAgent)->prompt($prompt)->toArray();

        $allowanceId = $this->resolveAllowanceAccount($company, $response['allowance_account_id'] ?? null);
        $expenseId   = $this->resolveExpenseAccount($company, $response['ecl_expense_account_id'] ?? null);

        if ($movement > 0) {
            $lines = [
                ['chart_of_account_id' => $expenseId,   'type' => 'debit',  'amount' => $movement,       'description' => 'IFRS 9 ECL provision increase'],
                ['chart_of_account_id' => $allowanceId, 'type' => 'credit', 'amount' => $movement,       'description' => 'Allowance for credit losses — increase'],
            ];
        } else {
            $lines = [
                ['chart_of_account_id' => $allowanceId, 'type' => 'debit',  'amount' => abs($movement), 'description' => 'Allowance for credit losses — decrease'],
                ['chart_of_account_id' => $expenseId,   'type' => 'credit', 'amount' => abs($movement), 'description' => 'IFRS 9 ECL provision decrease (reversal)'],
            ];
        }

        $this->transactions->record($company, $user, [
            'transaction_date' => $asOfDate,
            'description'      => 'IFRS 9 ECL provision — ' . Carbon::parse($asOfDate)->format('d M Y'),
            'reference'        => 'ECL-' . str_replace('-', '', $asOfDate),
            'status'           => 'draft',
            'source_document'  => 'ecl_provision:' . $asOfDate,
            'notes'            => "IFRS 9 simplified approach. Required allowance: R " . number_format($requiredAllowance, 2) . ". Movement: R " . number_format($movement, 2) . ". AI reasoning: " . ($response['reasoning'] ?? 'n/a'),
            'lines'            => $lines,
        ]);
    }

    private function resolveAllowanceAccount(Company $company, ?int $aiPick): int
    {
        if ($aiPick) {
            $exists = $company->chartOfAccounts()->where('id', $aiPick)->exists();
            if ($exists) return $aiPick;
        }

        $existing = $company->chartOfAccounts()
            ->where('is_contra', true)
            ->where(fn ($q) =>
                $q->where('account_name', 'like', '%allowance%')
                  ->orWhere('account_name', 'like', '%provision%doubtful%')
                  ->orWhere('account_name', 'like', '%credit loss%')
            )
            ->first();

        if ($existing) return $existing->id;

        return ChartOfAccount::firstOrCreate(
            ['company_id' => $company->id, 'account_code' => '1002099'],
            [
                'account_name'       => 'Allowance for Credit Losses',
                'account_type'       => 'assets',
                'category'           => 'Current Assets',
                'cash_flow_category' => 'operating',
                'is_contra'          => true,
                'is_active'          => true,
            ]
        )->id;
    }

    private function resolveExpenseAccount(Company $company, ?int $aiPick): int
    {
        if ($aiPick) {
            $exists = $company->chartOfAccounts()->where('id', $aiPick)->exists();
            if ($exists) return $aiPick;
        }

        $existing = $company->chartOfAccounts()
            ->where('account_type', 'expenses')
            ->where(fn ($q) =>
                $q->where('account_name', 'like', '%bad debt%')
                  ->orWhere('account_name', 'like', '%credit loss%')
                  ->orWhere('account_name', 'like', '%ECL%')
                  ->orWhere('account_name', 'like', '%impairment%receivable%')
            )
            ->first();

        if ($existing) return $existing->id;

        return ChartOfAccount::firstOrCreate(
            ['company_id' => $company->id, 'account_code' => '6009050'],
            [
                'account_name'       => 'Expected Credit Losses',
                'account_type'       => 'expenses',
                'category'           => 'Operating Expenses',
                'cash_flow_category' => 'operating',
                'is_contra'          => false,
                'is_active'          => true,
            ]
        )->id;
    }

    private function getAllowanceBalance(Company $company, string $asOfDate): float
    {
        $account = $company->chartOfAccounts()
            ->where('is_contra', true)
            ->where(fn ($q) =>
                $q->where('account_name', 'like', '%allowance%')
                  ->orWhere('account_name', 'like', '%provision%doubtful%')
                  ->orWhere('account_name', 'like', '%credit loss%')
            )
            ->first();

        if (!$account) return 0.0;

        $credits = (float) $account->journalLines()
            ->whereHas('transaction', fn ($q) => $q->where('transaction_date', '<=', $asOfDate))
            ->where('type', 'credit')
            ->sum('amount');

        $debits = (float) $account->journalLines()
            ->whereHas('transaction', fn ($q) => $q->where('transaction_date', '<=', $asOfDate))
            ->where('type', 'debit')
            ->sum('amount');

        return round($credits - $debits, 2);
    }
}
