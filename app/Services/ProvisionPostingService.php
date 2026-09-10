<?php

namespace App\Services;

use App\Ai\Agents\ProvisionPostingAgent;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Provision;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProvisionPostingService
{
    private const LIABILITY_CODE  = '2005000';
    private const EXPENSE_CODE   = '6010000';
    private const UNWINDING_CODE = '6011000';
    private const REVERSAL_CODE  = '4010030';

    private array $accountCache = [];

    public function __construct(
        private readonly TransactionService $transactions,
        private readonly AccountVectorSearchService $accountSearch,
        private readonly AgentHistoryService $agentHistory = new AgentHistoryService,
    ) {}

    // ─── Recognition (IAS 37.14) ─────────────────────────────────────────

    public function postRecognitionWithAi(Provision $provision, User $user): Provision
    {
        $company  = $provision->company;
        $response = $this->prompt($provision, $this->companyAccounts($company), 'recognition');

        $liabilityId = $this->resolveAccount($company, $response['provision_liability_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::LIABILITY_CODE, '2005000', '2005099', 'Provisions', 'liabilities', 'Current Liabilities'));
        $expenseId = $this->resolveAccount($company, $response['provision_expense_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::EXPENSE_CODE, '6010000', '6010099', 'Provision Expense', 'expenses', 'Operating Expenses'));

        $amount = (float) $provision->current_estimate;
        $lines  = [
            ['chart_of_account_id' => $expenseId,   'type' => 'debit',  'amount' => $amount, 'description' => 'Provision recognised: '.$provision->name],
            ['chart_of_account_id' => $liabilityId, 'type' => 'credit', 'amount' => $amount, 'description' => 'Provision recognised: '.$provision->name],
        ];

        return DB::transaction(function () use ($provision, $user, $company, $lines, $response) {
            $this->transactions->record($company, $user, [
                'transaction_date' => optional($provision->recognition_date)->format('Y-m-d') ?? now()->toDateString(),
                'description'      => 'IAS 37 provision recognised — '.$provision->name,
                'reference'        => 'PROV-'.$provision->id,
                'status'           => 'draft',
                'source_document'  => 'provision:'.$provision->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);
            return $provision;
        });
    }

    // ─── Remeasurement (IAS 37.36, 37.59) ────────────────────────────────

    public function postRemeasurementWithAi(Provision $provision, User $user, float $newEstimate, string $date): Provision
    {
        $company  = $provision->company;
        $response = $this->prompt($provision, $this->companyAccounts($company), 'remeasurement', [
            'new_estimate' => $newEstimate,
        ]);

        $liabilityId = $this->resolveAccount($company, $response['provision_liability_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::LIABILITY_CODE, '2005000', '2005099', 'Provisions', 'liabilities', 'Current Liabilities'));
        $expenseId = $this->resolveAccount($company, $response['provision_expense_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::EXPENSE_CODE, '6010000', '6010099', 'Provision Expense', 'expenses', 'Operating Expenses'));

        $oldEstimate = (float) $provision->current_estimate;
        $change      = $newEstimate - $oldEstimate;

        if (abs($change) < 0.01) return $provision;

        if ($change > 0) {
            $lines = [
                ['chart_of_account_id' => $expenseId,   'type' => 'debit',  'amount' => abs($change), 'description' => 'Provision increase: '.$provision->name],
                ['chart_of_account_id' => $liabilityId, 'type' => 'credit', 'amount' => abs($change), 'description' => 'Provision increase: '.$provision->name],
            ];
        } else {
            $lines = [
                ['chart_of_account_id' => $liabilityId, 'type' => 'debit',  'amount' => abs($change), 'description' => 'Provision decrease: '.$provision->name],
                ['chart_of_account_id' => $expenseId,   'type' => 'credit', 'amount' => abs($change), 'description' => 'Provision decrease: '.$provision->name],
            ];
        }

        return DB::transaction(function () use ($provision, $user, $company, $lines, $response, $date, $change) {
            $label = $change > 0 ? 'increase' : 'decrease';
            $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description'      => "IAS 37 remeasurement ({$label}) — ".$provision->name,
                'reference'        => 'PROVREM-'.str_replace('-', '', $date).'-'.$provision->id,
                'status'           => 'draft',
                'source_document'  => 'provision:'.$provision->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);
            return $provision;
        });
    }

    // ─── Unwinding of discount (IAS 37.60) ───────────────────────────────

    public function postUnwindingWithAi(Provision $provision, User $user, float $unwindingAmount, string $date): Provision
    {
        $company  = $provision->company;
        $response = $this->prompt($provision, $this->companyAccounts($company), 'unwinding', [
            'unwinding_amount' => $unwindingAmount,
        ]);

        $liabilityId = $this->resolveAccount($company, $response['provision_liability_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::LIABILITY_CODE, '2005000', '2005099', 'Provisions', 'liabilities', 'Current Liabilities'));
        $unwindingId = $this->resolveAccount($company, $response['unwinding_expense_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::UNWINDING_CODE, '6011000', '6011099', 'Unwinding of Discount — Provisions', 'expenses', 'Finance Costs'));

        $lines = [
            ['chart_of_account_id' => $unwindingId,  'type' => 'debit',  'amount' => $unwindingAmount, 'description' => 'Unwinding of discount: '.$provision->name],
            ['chart_of_account_id' => $liabilityId,  'type' => 'credit', 'amount' => $unwindingAmount, 'description' => 'Unwinding of discount: '.$provision->name],
        ];

        return DB::transaction(function () use ($provision, $user, $company, $lines, $response, $date) {
            $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description'      => 'IAS 37 unwinding of discount — '.$provision->name,
                'reference'        => 'PROVUNW-'.str_replace('-', '', $date).'-'.$provision->id,
                'status'           => 'draft',
                'source_document'  => 'provision:'.$provision->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);
            return $provision;
        });
    }

    // ─── Utilisation (IAS 37.61) ─────────────────────────────────────────

    public function postUtilisationWithAi(Provision $provision, User $user, float $amount, string $date): Provision
    {
        $company  = $provision->company;
        $response = $this->prompt($provision, $this->companyAccounts($company), 'utilisation', [
            'utilisation_amount' => $amount,
        ]);

        $liabilityId = $this->resolveAccount($company, $response['provision_liability_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::LIABILITY_CODE, '2005000', '2005099', 'Provisions', 'liabilities', 'Current Liabilities'));
        $bankId = $response['bank_or_payable_account_id'] ?? null;
        if (!$bankId) throw new RuntimeException('AI could not determine a bank/payable account for utilisation.');

        $lines = [
            ['chart_of_account_id' => $liabilityId, 'type' => 'debit',  'amount' => $amount, 'description' => 'Provision utilised: '.$provision->name],
            ['chart_of_account_id' => $bankId,      'type' => 'credit', 'amount' => $amount, 'description' => 'Provision utilised: '.$provision->name],
        ];

        return DB::transaction(function () use ($provision, $user, $company, $lines, $response, $date) {
            $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description'      => 'IAS 37 utilisation — '.$provision->name,
                'reference'        => 'PROVUTIL-'.str_replace('-', '', $date).'-'.$provision->id,
                'status'           => 'draft',
                'source_document'  => 'provision:'.$provision->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);
            return $provision;
        });
    }

    // ─── Reversal (IAS 37.59) ────────────────────────────────────────────

    public function postReversalWithAi(Provision $provision, User $user, string $date): Provision
    {
        $company  = $provision->company;
        $response = $this->prompt($provision, $this->companyAccounts($company), 'reversal');

        $liabilityId = $this->resolveAccount($company, $response['provision_liability_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::LIABILITY_CODE, '2005000', '2005099', 'Provisions', 'liabilities', 'Current Liabilities'));
        $reversalId = $this->resolveAccount($company, $response['reversal_income_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::REVERSAL_CODE, '4010030', '4010059', 'Reversal of Provision', 'income', 'Other Income'));

        $amount = (float) $provision->current_estimate;
        $lines  = [
            ['chart_of_account_id' => $liabilityId, 'type' => 'debit',  'amount' => $amount, 'description' => 'Provision reversed: '.$provision->name],
            ['chart_of_account_id' => $reversalId,  'type' => 'credit', 'amount' => $amount, 'description' => 'Provision reversed: '.$provision->name],
        ];

        return DB::transaction(function () use ($provision, $user, $company, $lines, $response, $date) {
            $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description'      => 'IAS 37 provision reversal — '.$provision->name,
                'reference'        => 'PROVREV-'.$provision->id,
                'status'           => 'draft',
                'source_document'  => 'provision:'.$provision->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);
            return $provision;
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
            'cash_flow_category' => $type === 'liabilities' ? 'operating' : 'operating',
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
            'provision liability IAS 37',
            'provision expense operating cost',
            'unwinding discount finance cost',
            'reversal provision income',
            'bank cash payment asset',
            'accounts payable creditor liability',
        ], 10)
            ->map(fn (ChartOfAccount $a) => "{$a->id}: {$a->account_code} — {$a->account_name} ({$a->account_type})")
            ->implode("\n");
    }

    private function prompt(Provision $provision, string $accountList, string $kind, array $extra = []): array
    {
        $details = "Provision: {$provision->name}\n"
            ."Type: {$provision->provision_type}, Probability: ".($provision->probability ?? 'N/A')."\n"
            ."Class: {$provision->provisionClass?->name}\n"
            ."Initial estimate: {$provision->initial_estimate}, Current estimate: {$provision->current_estimate}\n"
            ."Discount rate: ".($provision->discount_rate ?? 'N/A').", Present value: ".($provision->present_value ?? 'N/A')."\n"
            ."Recognition date: ".optional($provision->recognition_date)->format('Y-m-d')."\n"
            ."Expected settlement: ".optional($provision->expected_settlement_date)->format('Y-m-d')."\n"
            .($provision->settlement_date ? "Settled: {$provision->settlement_date->format('Y-m-d')}, amount: {$provision->settlement_amount}\n" : '');

        $extraStr = empty($extra) ? '' : "\nAdditional context:\n".json_encode($extra, JSON_PRETTY_PRINT)."\n";

        $prompt = "Posting type: {$kind}\n\n{$details}{$extraStr}\nChart of accounts:\n{$accountList}\n\nPick the IAS 37-appropriate accounts for this posting.";

        $history = $this->agentHistory->recall('provision_posting', 'provision', $provision->id);
        $fullPrompt = $history ? $history."\n\n".$prompt : $prompt;

        $response = (new ProvisionPostingAgent)->prompt($fullPrompt)->toArray();
        $this->agentHistory->remember('provision_posting', 'provision', $provision->id, $prompt, json_encode($response));

        return $response;
    }
}
