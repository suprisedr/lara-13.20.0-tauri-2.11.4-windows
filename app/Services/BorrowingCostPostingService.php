<?php

namespace App\Services;

use App\Ai\Agents\BorrowingCostPostingAgent;
use App\Models\BorrowingCostCapitalisation;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BorrowingCostPostingService
{
    private const ASSET_COST       = '1001000';
    private const INTEREST_EXPENSE = '6010000';

    private array $accountCache = [];

    public function __construct(
        private readonly TransactionService $transactions,
        private readonly AccountVectorSearchService $accountSearch,
        private readonly AgentHistoryService $agentHistory = new AgentHistoryService,
    ) {}

    public function postCapitalisationWithAi(BorrowingCostCapitalisation $capitalisation, User $user, float $amount, string $date): BorrowingCostCapitalisation
    {
        $company  = $capitalisation->company;
        $response = $this->prompt($capitalisation, $this->companyAccounts($company), 'capitalisation', [
            'amount' => $amount,
        ]);

        $assetId = $this->resolveAccount($company, $response['asset_cost_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::ASSET_COST, '1001000', '1001099', 'Qualifying Asset Cost', 'assets', 'Property, Plant & Equipment'));
        $interestId = $this->resolveAccount($company, $response['interest_expense_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::INTEREST_EXPENSE, '6010000', '6010099', 'Interest / Finance Costs', 'expenses', 'Finance Costs'));

        $lines = [
            ['chart_of_account_id' => $assetId,    'type' => 'debit',  'amount' => $amount, 'description' => 'IAS 23 borrowing cost capitalised: '.$capitalisation->borrowing_source],
            ['chart_of_account_id' => $interestId, 'type' => 'credit', 'amount' => $amount, 'description' => 'IAS 23 borrowing cost capitalised: '.$capitalisation->borrowing_source],
        ];

        return DB::transaction(function () use ($capitalisation, $user, $company, $lines, $response, $date, $amount) {
            $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description'      => 'IAS 23 borrowing cost capitalisation — '.$capitalisation->borrowing_source,
                'reference'        => 'BC-'.str_replace('-', '', $date).'-'.$capitalisation->id,
                'status'           => 'draft',
                'source_document'  => 'borrowing_cost:'.$capitalisation->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);

            $capitalisation->increment('total_capitalised', $amount);
            $capitalisation->update(['last_capitalisation_posted_on' => $date]);

            return $capitalisation;
        });
    }

    public function postSuspensionWithAi(BorrowingCostCapitalisation $capitalisation, User $user, string $date): BorrowingCostCapitalisation
    {
        return DB::transaction(function () use ($capitalisation, $date) {
            $capitalisation->update(['status' => BorrowingCostCapitalisation::STATUS_SUSPENDED]);
            return $capitalisation;
        });
    }

    public function postCompletionWithAi(BorrowingCostCapitalisation $capitalisation, User $user, string $date): BorrowingCostCapitalisation
    {
        return DB::transaction(function () use ($capitalisation, $date) {
            $capitalisation->update([
                'status'                  => BorrowingCostCapitalisation::STATUS_COMPLETED,
                'capitalisation_end_date' => $date,
            ]);
            return $capitalisation;
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
            'cash_flow_category' => $type === 'assets' ? 'investing' : 'operating',
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
            'qualifying asset property plant equipment IAS 23',
            'interest expense finance cost borrowing',
            'capitalised borrowing cost asset under construction',
            'bank cash proceeds asset',
        ], 10)
            ->map(fn (ChartOfAccount $a) => "{$a->id}: {$a->account_code} — {$a->account_name} ({$a->account_type})")
            ->implode("\n");
    }

    private function prompt(BorrowingCostCapitalisation $capitalisation, string $accountList, string $kind, array $extra = []): array
    {
        $details = "Borrowing Cost Capitalisation: {$capitalisation->borrowing_source}\n"
            ."Qualifying asset: {$capitalisation->qualifying_asset_type} (ID: {$capitalisation->qualifying_asset_id})\n"
            ."Borrowing rate: {$capitalisation->borrowing_rate}%\n"
            ."Weighted average rate: ".($capitalisation->weighted_average_rate ?? 'N/A')."\n"
            ."Total capitalised to date: {$capitalisation->total_capitalised}\n"
            ."Start date: ".optional($capitalisation->capitalisation_start_date)->format('Y-m-d')."\n"
            ."Status: {$capitalisation->status}\n";

        $extraStr = empty($extra) ? '' : "\nAdditional context:\n".json_encode($extra, JSON_PRETTY_PRINT)."\n";

        $prompt = "Posting type: {$kind}\n\n{$details}{$extraStr}\nChart of accounts:\n{$accountList}\n\nPick the IAS 23-appropriate accounts for this posting.";

        $related = [];
        if ($capitalisation->qualifying_asset_id && $capitalisation->qualifying_asset_type) {
            $morphMap = [
                'App\\Models\\Asset'              => ['asset_posting', 'asset'],
                'App\\Models\\IntangibleAsset'     => ['intangible_asset_posting', 'intangible_asset'],
                'App\\Models\\InvestmentProperty'  => ['investment_property_posting', 'investment_property'],
            ];
            $mapped = $morphMap[$capitalisation->qualifying_asset_type] ?? null;
            if ($mapped) {
                $related[] = [
                    'agent_type'  => $mapped[0],
                    'entity_type' => $mapped[1],
                    'entity_id'   => $capitalisation->qualifying_asset_id,
                    'label'       => "Qualifying {$mapped[1]} #{$capitalisation->qualifying_asset_id}",
                ];
            }
        }
        $history = $this->agentHistory->recallWithRelated('borrowing_cost_posting', 'borrowing_cost_capitalisation', $capitalisation->id, $related);
        $fullPrompt = $history ? $history."\n\n".$prompt : $prompt;

        $response = (new BorrowingCostPostingAgent)->prompt($fullPrompt)->toArray();
        $this->agentHistory->remember('borrowing_cost_posting', 'borrowing_cost_capitalisation', $capitalisation->id, $prompt, json_encode($response));

        return $response;
    }
}
