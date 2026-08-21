<?php

namespace App\Services;

use App\Ai\Agents\RevenuePostingAgent;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\PerformanceObligation;
use App\Models\RevenueContract;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RevenuePostingService
{
    private const REVENUE_CODE            = '4001000';
    private const CONTRACT_ASSET_CODE     = '1008100';
    private const CONTRACT_LIABILITY_CODE = '2007000';
    private const FINANCING_CODE          = '6012000';

    private array $accountCache = [];

    public function __construct(
        private readonly TransactionService $transactions,
        private readonly AccountVectorSearchService $accountSearch,
        private readonly AgentHistoryService $agentHistory = new AgentHistoryService,
    ) {}

    public function postRevenueRecognitionWithAi(RevenueContract $contract, PerformanceObligation $obligation, User $user, float $amount, string $date): RevenueContract
    {
        $company  = $contract->company;
        $response = $this->prompt($contract, $this->companyAccounts($company), 'revenue_recognition', [
            'obligation_name' => $obligation->name,
            'amount'          => $amount,
        ]);

        $revenueId = $this->resolveAccount($company, $response['revenue_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::REVENUE_CODE, '4001000', '4001099', 'Revenue', 'income', 'Revenue'));
        $contractAssetId = $this->resolveAccount($company, $response['contract_asset_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::CONTRACT_ASSET_CODE, '1008100', '1008199', 'Contract Assets', 'assets', 'Current Assets'));

        $lines = [
            ['chart_of_account_id' => $contractAssetId, 'type' => 'debit',  'amount' => $amount, 'description' => "Revenue: {$obligation->name}"],
            ['chart_of_account_id' => $revenueId,       'type' => 'credit', 'amount' => $amount, 'description' => "Revenue: {$obligation->name}"],
        ];

        return DB::transaction(function () use ($contract, $obligation, $user, $company, $lines, $response, $amount, $date) {
            $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description'      => "IFRS 15 revenue recognition — {$contract->name} / {$obligation->name}",
                'reference'        => $contract->contract_reference,
                'status'           => 'draft',
                'source_document'  => 'revenue_contract:'.$contract->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);

            $obligation->increment('revenue_recognised', $amount);
            $newPct = $obligation->calculatePercentageComplete();
            $obligation->update([
                'percentage_complete' => $newPct,
                'status'              => $newPct >= 100
                    ? PerformanceObligation::STATUS_SATISFIED
                    : ($obligation->revenue_recognised > 0 ? PerformanceObligation::STATUS_PARTIALLY_SATISFIED : PerformanceObligation::STATUS_UNSATISFIED),
                'satisfaction_date'   => $newPct >= 100 ? $date : null,
            ]);

            return $contract;
        });
    }

    public function postContractLiabilityWithAi(RevenueContract $contract, User $user, float $amount, string $date): RevenueContract
    {
        $company  = $contract->company;
        $response = $this->prompt($contract, $this->companyAccounts($company), 'advance_receipt');

        $bankId      = $response['bank_account_id'] ?? null;
        if (!$bankId) throw new RuntimeException('AI could not determine a bank account.');
        $liabilityId = $this->resolveAccount($company, $response['contract_liability_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::CONTRACT_LIABILITY_CODE, '2007000', '2007099', 'Contract Liabilities', 'liabilities', 'Current Liabilities'));

        $lines = [
            ['chart_of_account_id' => $bankId,       'type' => 'debit',  'amount' => $amount, 'description' => "Advance received: {$contract->name}"],
            ['chart_of_account_id' => $liabilityId,  'type' => 'credit', 'amount' => $amount, 'description' => "Contract liability: {$contract->name}"],
        ];

        return DB::transaction(function () use ($contract, $user, $company, $lines, $response, $date) {
            $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description'      => "IFRS 15 advance receipt — {$contract->name}",
                'reference'        => $contract->contract_reference,
                'status'           => 'draft',
                'source_document'  => 'revenue_contract:'.$contract->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);
            return $contract;
        });
    }

    public function postContractLiabilityReleaseWithAi(RevenueContract $contract, PerformanceObligation $obligation, User $user, float $amount, string $date): RevenueContract
    {
        $company  = $contract->company;
        $response = $this->prompt($contract, $this->companyAccounts($company), 'liability_release');

        $liabilityId = $this->resolveAccount($company, $response['contract_liability_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::CONTRACT_LIABILITY_CODE, '2007000', '2007099', 'Contract Liabilities', 'liabilities', 'Current Liabilities'));
        $revenueId   = $this->resolveAccount($company, $response['revenue_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::REVENUE_CODE, '4001000', '4001099', 'Revenue', 'income', 'Revenue'));

        $lines = [
            ['chart_of_account_id' => $liabilityId, 'type' => 'debit',  'amount' => $amount, 'description' => "Release liability: {$obligation->name}"],
            ['chart_of_account_id' => $revenueId,   'type' => 'credit', 'amount' => $amount, 'description' => "Revenue: {$obligation->name}"],
        ];

        return DB::transaction(function () use ($contract, $obligation, $user, $company, $lines, $response, $amount, $date) {
            $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description'      => "IFRS 15 contract liability release — {$contract->name} / {$obligation->name}",
                'reference'        => $contract->contract_reference,
                'status'           => 'draft',
                'source_document'  => 'revenue_contract:'.$contract->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);

            $obligation->increment('revenue_recognised', $amount);

            return $contract;
        });
    }

    // ─── Private helpers (same pattern as BiologicalAssetPostingService) ─────

    private function prompt(RevenueContract $contract, array $accounts, string $action, array $extra = []): array
    {
        $agent = new RevenuePostingAgent();

        $prompt = "Company: {$contract->company->registered_name}\n"
            . "Revenue contract: {$contract->name} (ref: {$contract->contract_reference})\n"
            . "Total transaction price: R " . number_format((float) $contract->total_transaction_price, 2) . "\n"
            . "Action: {$action}\n";

        foreach ($extra as $k => $v) {
            $prompt .= ucfirst(str_replace('_', ' ', $k)) . ": {$v}\n";
        }

        $prompt .= "\nAvailable GL accounts:\n";
        foreach ($accounts as $a) {
            $prompt .= "  [{$a['id']}] {$a['code']} — {$a['name']} ({$a['category']})\n";
        }

        $history = $this->agentHistory->recall('revenue_posting', 'revenue_contract', $contract->id);
        $fullPrompt = $history ? $history."\n\n".$prompt : $prompt;

        $response = $agent->run($fullPrompt);
        $this->agentHistory->remember('revenue_posting', 'revenue_contract', $contract->id, $prompt, json_encode($response));

        return $response;
    }

    private function companyAccounts(Company $company): array
    {
        return $company->chartOfAccounts()
            ->select('id', 'code', 'name', 'category')
            ->orderBy('code')
            ->limit(200)
            ->get()
            ->toArray();
    }

    private function resolveAccount(Company $company, ?int $aiPick, callable $fallback): int
    {
        if ($aiPick && ChartOfAccount::where('id', $aiPick)->where('company_id', $company->id)->exists()) {
            return $aiPick;
        }
        return $fallback();
    }

    private function ensureAccount(Company $company, string $defaultCode, string $rangeStart, string $rangeEnd, string $name, string $category, string $subCategory): int
    {
        $key = $company->id . ':' . $defaultCode;
        if (isset($this->accountCache[$key])) return $this->accountCache[$key];

        $existing = ChartOfAccount::where('company_id', $company->id)
            ->where('code', $defaultCode)
            ->first();

        if ($existing) {
            return $this->accountCache[$key] = $existing->id;
        }

        $account = ChartOfAccount::create([
            'company_id'   => $company->id,
            'code'         => $defaultCode,
            'name'         => $name,
            'category'     => $category,
            'sub_category' => $subCategory,
            'is_system'    => true,
        ]);

        return $this->accountCache[$key] = $account->id;
    }
}
