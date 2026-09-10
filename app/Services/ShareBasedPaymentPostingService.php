<?php

namespace App\Services;

use App\Ai\Agents\ShareBasedPaymentPostingAgent;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\ShareBasedPaymentArrangement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ShareBasedPaymentPostingService
{
    private const EXPENSE_CODE        = '6015000';
    private const EQUITY_RESERVE_CODE = '3005000';
    private const LIABILITY_CODE      = '2009000';

    private array $accountCache = [];

    public function __construct(
        private readonly TransactionService $transactions,
        private readonly AccountVectorSearchService $accountSearch,
        private readonly AgentHistoryService $agentHistory = new AgentHistoryService,
    ) {}

    // ─── Vesting Expense (IFRS 2.7–2.8) ─────────────────────────────────

    public function postVestingExpenseWithAi(ShareBasedPaymentArrangement $arrangement, User $user, float $amount, string $date): ShareBasedPaymentArrangement
    {
        $company  = $arrangement->company;
        $response = $this->prompt($arrangement, $this->companyAccounts($company), 'vesting_expense', [
            'expense_amount' => $amount,
        ]);

        $expenseId = $this->resolveAccount($company, $response['expense_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::EXPENSE_CODE, '6015000', '6015099', 'Share-Based Payment Expense', 'expenses', 'Operating Expenses'));

        if ($arrangement->arrangement_type === ShareBasedPaymentArrangement::TYPE_CASH_SETTLED) {
            $creditId = $this->resolveAccount($company, $response['liability_account_id'] ?? null,
                fn () => $this->ensureAccount($company, self::LIABILITY_CODE, '2009000', '2009099', 'Share-Based Payment Liability', 'liabilities', 'Current Liabilities'));
            $creditDesc = 'SBP liability';
        } else {
            $creditId = $this->resolveAccount($company, $response['equity_reserve_account_id'] ?? null,
                fn () => $this->ensureAccount($company, self::EQUITY_RESERVE_CODE, '3005000', '3005099', 'Share-Based Payment Reserve', 'equity', 'Equity'));
            $creditDesc = 'SBP equity reserve';
        }

        $lines = [
            ['chart_of_account_id' => $expenseId, 'type' => 'debit',  'amount' => $amount, 'description' => 'SBP expense: '.$arrangement->name],
            ['chart_of_account_id' => $creditId,  'type' => 'credit', 'amount' => $amount, 'description' => $creditDesc.': '.$arrangement->name],
        ];

        return DB::transaction(function () use ($arrangement, $user, $company, $lines, $response, $date) {
            $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description'      => 'IFRS 2 vesting expense — '.$arrangement->name,
                'reference'        => 'SBP-VEST-'.str_replace('-', '', $date).'-'.$arrangement->id,
                'status'           => 'draft',
                'source_document'  => 'sbp:'.$arrangement->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);
            return $arrangement;
        });
    }

    // ─── Exercise (IFRS 2.23) ────────────────────────────────────────────

    public function postExerciseWithAi(ShareBasedPaymentArrangement $arrangement, User $user, float $amount, int $instruments, string $date): ShareBasedPaymentArrangement
    {
        $company  = $arrangement->company;
        $response = $this->prompt($arrangement, $this->companyAccounts($company), 'exercise', [
            'exercise_amount' => $amount,
            'instruments'     => $instruments,
        ]);

        $exerciseProceeds = $instruments * (float) ($arrangement->exercise_price ?? 0);

        if ($arrangement->arrangement_type === ShareBasedPaymentArrangement::TYPE_CASH_SETTLED) {
            $liabilityId = $this->resolveAccount($company, $response['liability_account_id'] ?? null,
                fn () => $this->ensureAccount($company, self::LIABILITY_CODE, '2009000', '2009099', 'Share-Based Payment Liability', 'liabilities', 'Current Liabilities'));
            $bankId = $response['bank_account_id'] ?? null;
            if (!$bankId) throw new RuntimeException('AI could not determine a bank account for exercise settlement.');

            $lines = [
                ['chart_of_account_id' => $liabilityId, 'type' => 'debit',  'amount' => $amount, 'description' => 'SBP liability settled: '.$arrangement->name],
                ['chart_of_account_id' => $bankId,      'type' => 'credit', 'amount' => $amount, 'description' => 'Cash paid on SBP exercise: '.$arrangement->name],
            ];
        } else {
            $reserveId = $this->resolveAccount($company, $response['equity_reserve_account_id'] ?? null,
                fn () => $this->ensureAccount($company, self::EQUITY_RESERVE_CODE, '3005000', '3005099', 'Share-Based Payment Reserve', 'equity', 'Equity'));
            $shareCapId = $response['share_capital_account_id'] ?? null;
            if (!$shareCapId) throw new RuntimeException('AI could not determine a share capital account for exercise.');

            $lines = [
                ['chart_of_account_id' => $reserveId,   'type' => 'debit',  'amount' => $amount, 'description' => 'Transfer from SBP reserve: '.$arrangement->name],
                ['chart_of_account_id' => $shareCapId,  'type' => 'credit', 'amount' => $amount, 'description' => 'Share capital issued: '.$arrangement->name],
            ];

            if ($exerciseProceeds > 0) {
                $bankId = $response['bank_account_id'] ?? null;
                if ($bankId) {
                    $lines[] = ['chart_of_account_id' => $bankId,      'type' => 'debit',  'amount' => $exerciseProceeds, 'description' => 'Exercise proceeds received: '.$arrangement->name];
                    $lines[] = ['chart_of_account_id' => $shareCapId,  'type' => 'credit', 'amount' => $exerciseProceeds, 'description' => 'Share capital — exercise proceeds: '.$arrangement->name];
                }
            }
        }

        return DB::transaction(function () use ($arrangement, $user, $company, $lines, $response, $date) {
            $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description'      => 'IFRS 2 exercise — '.$arrangement->name,
                'reference'        => 'SBP-EXER-'.str_replace('-', '', $date).'-'.$arrangement->id,
                'status'           => 'draft',
                'source_document'  => 'sbp:'.$arrangement->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);
            return $arrangement;
        });
    }

    // ─── Forfeiture (IFRS 2.19–2.21) ────────────────────────────────────

    public function postForfeitureWithAi(ShareBasedPaymentArrangement $arrangement, User $user, float $amount, int $instruments, string $date): ShareBasedPaymentArrangement
    {
        $company  = $arrangement->company;
        $response = $this->prompt($arrangement, $this->companyAccounts($company), 'forfeiture', [
            'forfeiture_amount' => $amount,
            'instruments'       => $instruments,
        ]);

        $expenseId = $this->resolveAccount($company, $response['expense_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::EXPENSE_CODE, '6015000', '6015099', 'Share-Based Payment Expense', 'expenses', 'Operating Expenses'));

        if ($arrangement->arrangement_type === ShareBasedPaymentArrangement::TYPE_CASH_SETTLED) {
            $creditId = $this->resolveAccount($company, $response['liability_account_id'] ?? null,
                fn () => $this->ensureAccount($company, self::LIABILITY_CODE, '2009000', '2009099', 'Share-Based Payment Liability', 'liabilities', 'Current Liabilities'));
        } else {
            $creditId = $this->resolveAccount($company, $response['equity_reserve_account_id'] ?? null,
                fn () => $this->ensureAccount($company, self::EQUITY_RESERVE_CODE, '3005000', '3005099', 'Share-Based Payment Reserve', 'equity', 'Equity'));
        }

        // Forfeiture reverses previously recognised expense
        $lines = [
            ['chart_of_account_id' => $creditId,  'type' => 'debit',  'amount' => $amount, 'description' => 'SBP forfeiture reversal: '.$arrangement->name],
            ['chart_of_account_id' => $expenseId, 'type' => 'credit', 'amount' => $amount, 'description' => 'SBP expense reversed on forfeiture: '.$arrangement->name],
        ];

        return DB::transaction(function () use ($arrangement, $user, $company, $lines, $response, $date) {
            $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description'      => 'IFRS 2 forfeiture — '.$arrangement->name,
                'reference'        => 'SBP-FORF-'.str_replace('-', '', $date).'-'.$arrangement->id,
                'status'           => 'draft',
                'source_document'  => 'sbp:'.$arrangement->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);
            return $arrangement;
        });
    }

    // ─── Account helpers ─────────────────────────────────────────────────

    private function resolveAccount(Company $company, ?int $aiPick, \Closure $createMissing): int
    {
        if ($aiPick && ChartOfAccount::where('company_id', $company->id)->where('id', $aiPick)->exists()) {
            return $aiPick;
        }
        return $createMissing()->id;
    }

    private function ensureAccount(Company $company, string $fallbackCode, string $rangeStart, string $rangeEnd, string $name, string $type, string $category): ChartOfAccount
    {
        $existing = ChartOfAccount::where('company_id', $company->id)
            ->where('account_name', 'like', '%'.$name.'%')
            ->first();
        if ($existing) return $existing;

        $code = $this->nextCode($company, $rangeStart, $rangeEnd) ?? $fallbackCode;

        $account = ChartOfAccount::create([
            'company_id'         => $company->id,
            'account_code'       => $code,
            'account_name'       => $name,
            'account_type'       => $type,
            'category'           => $category,
            'cash_flow_category' => 'operating',
            'is_contra'          => false,
        ]);
        return $account;
    }

    private function nextCode(Company $company, string $start, string $end): ?string
    {
        $used = ChartOfAccount::where('company_id', $company->id)
            ->whereBetween('account_code', [$start, $end])
            ->pluck('account_code')
            ->map(fn ($c) => (int) $c)
            ->toArray();

        for ($i = (int) $start; $i <= (int) $end; $i++) {
            if (!in_array($i, $used, true)) return (string) $i;
        }
        return null;
    }

    private function companyAccounts(Company $company): string
    {
        if (isset($this->accountCache[$company->id])) {
            return $this->accountCache[$company->id];
        }

        return $this->accountCache[$company->id] = $this->accountSearch->searchMultiple($company->id, [
            'share-based payment expense IFRS 2',
            'share-based payment reserve equity',
            'share-based payment liability cash-settled',
            'share capital ordinary shares',
            'bank cash payment asset',
        ], 10)
            ->map(fn (ChartOfAccount $a) => "{$a->id}: {$a->account_code} — {$a->account_name} ({$a->account_type})")
            ->implode("\n");
    }

    private function prompt(ShareBasedPaymentArrangement $arrangement, string $accountList, string $kind, array $extra = []): array
    {
        $details = "Arrangement: {$arrangement->name}\n"
            ."Type: {$arrangement->arrangement_type}\n"
            ."Grant date: ".optional($arrangement->grant_date)->format('Y-m-d')."\n"
            ."Vesting period: ".optional($arrangement->vesting_start_date)->format('Y-m-d')." to ".optional($arrangement->vesting_end_date)->format('Y-m-d')."\n"
            ."Instruments: {$arrangement->number_of_instruments}\n"
            ."Exercise price: ".($arrangement->exercise_price ?? 'N/A')."\n"
            ."Fair value at grant: {$arrangement->fair_value_at_grant}\n"
            ."Total expense recognised: {$arrangement->total_expense}\n"
            ."Status: {$arrangement->status}\n";

        $extraStr = empty($extra) ? '' : "\nAdditional context:\n".json_encode($extra, JSON_PRETTY_PRINT)."\n";

        $prompt = "Posting type: {$kind}\n\n{$details}{$extraStr}\nChart of accounts:\n{$accountList}\n\nPick the IFRS 2-appropriate accounts for this posting.";

        $history = $this->agentHistory->recall('share_based_payment_posting', 'share_based_payment', $arrangement->id);
        $fullPrompt = $history ? $history."\n\n".$prompt : $prompt;

        $response = (new ShareBasedPaymentPostingAgent)->prompt($fullPrompt)->toArray();
        $this->agentHistory->remember('share_based_payment_posting', 'share_based_payment', $arrangement->id, $prompt, json_encode($response));

        return $response;
    }
}
