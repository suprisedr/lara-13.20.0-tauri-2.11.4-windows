<?php

namespace App\Services;

use App\Ai\Agents\GovernmentGrantPostingAgent;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\GovernmentGrant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GovernmentGrantPostingService
{
    private const DEFERRED_INCOME = '2008000';
    private const GRANT_INCOME    = '4005000';
    private const REFUND_PAYABLE  = '2008100';

    private array $accountCache = [];

    public function __construct(
        private readonly TransactionService $transactions,
        private readonly AccountVectorSearchService $accountSearch,
        private readonly AgentHistoryService $agentHistory = new AgentHistoryService,
    ) {}

    // ─── Recognition (IAS 20.7) ─────────────────────────────────────────

    public function postGrantRecognitionWithAi(GovernmentGrant $grant, User $user, float $amount, string $date): GovernmentGrant
    {
        $company  = $grant->company;
        $response = $this->prompt($grant, $this->companyAccounts($company), 'recognition', [
            'amount' => $amount,
        ]);

        $bankId = $response['bank_account_id'] ?? null;
        if (!$bankId) throw new RuntimeException('AI could not determine a bank account for grant recognition.');

        $deferredId = $this->resolveAccount($company, $response['deferred_income_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::DEFERRED_INCOME, '2008000', '2008099', 'Deferred Grant Income', 'liabilities', 'Current Liabilities'));

        $lines = [
            ['chart_of_account_id' => $bankId,      'type' => 'debit',  'amount' => $amount, 'description' => 'Grant received: '.$grant->name],
            ['chart_of_account_id' => $deferredId,   'type' => 'credit', 'amount' => $amount, 'description' => 'Deferred grant income: '.$grant->name],
        ];

        return DB::transaction(function () use ($grant, $user, $company, $lines, $response, $date) {
            $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description'      => 'IAS 20 grant recognised — '.$grant->name,
                'reference'        => 'GRANT-'.$grant->id,
                'status'           => 'draft',
                'source_document'  => 'government_grant:'.$grant->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);
            return $grant;
        });
    }

    // ─── Amortisation (IAS 20.12 / 20.26) ───────────────────────────────

    public function postAmortisationWithAi(GovernmentGrant $grant, User $user, float $amount, string $date): GovernmentGrant
    {
        $company  = $grant->company;
        $response = $this->prompt($grant, $this->companyAccounts($company), 'amortisation', [
            'amount' => $amount,
        ]);

        $deferredId = $this->resolveAccount($company, $response['deferred_income_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::DEFERRED_INCOME, '2008000', '2008099', 'Deferred Grant Income', 'liabilities', 'Current Liabilities'));
        $incomeId = $this->resolveAccount($company, $response['grant_income_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::GRANT_INCOME, '4005000', '4005099', 'Government Grant Income', 'income', 'Other Income'));

        $lines = [
            ['chart_of_account_id' => $deferredId, 'type' => 'debit',  'amount' => $amount, 'description' => 'Amortise deferred grant: '.$grant->name],
            ['chart_of_account_id' => $incomeId,   'type' => 'credit', 'amount' => $amount, 'description' => 'Grant income recognised: '.$grant->name],
        ];

        return DB::transaction(function () use ($grant, $user, $company, $lines, $response, $date) {
            $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description'      => 'IAS 20 grant amortisation — '.$grant->name,
                'reference'        => 'GRANTAMORT-'.str_replace('-', '', $date).'-'.$grant->id,
                'status'           => 'draft',
                'source_document'  => 'government_grant:'.$grant->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);
            return $grant;
        });
    }

    // ─── Refund (IAS 20.32) ─────────────────────────────────────────────

    public function postRefundWithAi(GovernmentGrant $grant, User $user, float $amount, string $date): GovernmentGrant
    {
        $company  = $grant->company;
        $response = $this->prompt($grant, $this->companyAccounts($company), 'refund', [
            'amount' => $amount,
        ]);

        $deferredId = $this->resolveAccount($company, $response['deferred_income_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::DEFERRED_INCOME, '2008000', '2008099', 'Deferred Grant Income', 'liabilities', 'Current Liabilities'));
        $incomeId = $this->resolveAccount($company, $response['grant_income_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::GRANT_INCOME, '4005000', '4005099', 'Government Grant Income', 'income', 'Other Income'));
        $refundPayableId = $this->resolveAccount($company, $response['refund_payable_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::REFUND_PAYABLE, '2008100', '2008199', 'Grant Refund Payable', 'liabilities', 'Current Liabilities'));

        // Reverse any remaining deferred balance first, then expense the excess
        $deferredBalance = (float) $grant->deferred_amount;
        $excessOverDeferred = max($amount - $deferredBalance, 0);
        $deferredPortion    = min($amount, $deferredBalance);

        $lines = [];
        if ($deferredPortion > 0) {
            $lines[] = ['chart_of_account_id' => $deferredId,       'type' => 'debit',  'amount' => $deferredPortion, 'description' => 'Reverse deferred grant: '.$grant->name];
        }
        if ($excessOverDeferred > 0) {
            $lines[] = ['chart_of_account_id' => $incomeId,         'type' => 'debit',  'amount' => $excessOverDeferred, 'description' => 'Reverse recognised grant income: '.$grant->name];
        }
        $lines[] = ['chart_of_account_id' => $refundPayableId, 'type' => 'credit', 'amount' => $amount, 'description' => 'Grant refund payable: '.$grant->name];

        return DB::transaction(function () use ($grant, $user, $company, $lines, $response, $date) {
            $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description'      => 'IAS 20 grant refund — '.$grant->name,
                'reference'        => 'GRANTREF-'.$grant->id,
                'status'           => 'draft',
                'source_document'  => 'government_grant:'.$grant->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);
            return $grant;
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
            'deferred income government grant liability',
            'grant income other income',
            'bank cash asset',
            'refund payable liability',
            'asset property plant equipment',
        ], 10)
            ->map(fn (ChartOfAccount $a) => "{$a->id}: {$a->account_code} — {$a->account_name} ({$a->account_type})")
            ->implode("\n");
    }

    private function prompt(GovernmentGrant $grant, string $accountList, string $kind, array $extra = []): array
    {
        $details = "Grant: {$grant->name}\n"
            ."Type: {$grant->grant_type}, Recognition method: {$grant->recognition_method}\n"
            ."Granting authority: ".($grant->granting_authority ?? 'N/A')."\n"
            ."Reference: ".($grant->grant_reference ?? 'N/A')."\n"
            ."Grant date: ".optional($grant->grant_date)->format('Y-m-d')."\n"
            ."Total amount: {$grant->total_amount}, Recognised: {$grant->recognised_amount}, Deferred: {$grant->deferred_amount}\n"
            ."Related asset: ".($grant->related_asset_type ?? 'N/A')."\n"
            ."Conditions: ".($grant->conditions_text ?? 'None specified')."\n";

        $extraStr = empty($extra) ? '' : "\nAdditional context:\n".json_encode($extra, JSON_PRETTY_PRINT)."\n";

        $prompt = "Posting type: {$kind}\n\n{$details}{$extraStr}\nChart of accounts:\n{$accountList}\n\nPick the IAS 20-appropriate accounts for this posting.";

        $related = [];
        if ($grant->related_asset_id && $grant->related_asset_type) {
            $morphMap = [
                'App\\Models\\Asset'              => ['asset_posting', 'asset'],
                'App\\Models\\IntangibleAsset'     => ['intangible_asset_posting', 'intangible_asset'],
                'App\\Models\\InvestmentProperty'  => ['investment_property_posting', 'investment_property'],
            ];
            $mapped = $morphMap[$grant->related_asset_type] ?? null;
            if ($mapped) {
                $related[] = [
                    'agent_type'  => $mapped[0],
                    'entity_type' => $mapped[1],
                    'entity_id'   => $grant->related_asset_id,
                    'label'       => "Related {$mapped[1]} #{$grant->related_asset_id}",
                ];
            }
        }
        $history = $this->agentHistory->recallWithRelated('government_grant_posting', 'government_grant', $grant->id, $related);
        $fullPrompt = $history ? $history."\n\n".$prompt : $prompt;

        $response = (new GovernmentGrantPostingAgent)->prompt($fullPrompt)->toArray();
        $this->agentHistory->remember('government_grant_posting', 'government_grant', $grant->id, $prompt, json_encode($response));

        return $response;
    }
}
