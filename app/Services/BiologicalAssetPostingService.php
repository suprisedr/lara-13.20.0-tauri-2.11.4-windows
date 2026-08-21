<?php

namespace App\Services;

use App\Ai\Agents\BiologicalAssetPostingAgent;
use App\Models\BiologicalAsset;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class BiologicalAssetPostingService
{
    private const BA_COST_CODE       = '1008000';
    private const BA_FV_GAIN_CODE    = '4005060';
    private const BA_FV_LOSS_CODE    = '6009070';
    private const BA_HARVEST_CODE    = '1004050';
    private const BA_GAIN_CODE       = '4010020';
    private const BA_LOSS_CODE       = '6009096';
    private const BA_IMP_LOSS_CODE   = '6008020';
    private const BA_ACC_IMP_CODE    = '1008099';

    private array $accountCache = [];

    public function __construct(
        private readonly TransactionService $transactions,
        private readonly AccountVectorSearchService $accountSearch,
        private readonly AgentHistoryService $agentHistory = new AgentHistoryService,
    ) {}

    public function postAcquisitionWithAi(BiologicalAsset $asset, User $user): BiologicalAsset
    {
        $company  = $asset->company;
        $response = $this->prompt($asset, $this->companyAccounts($company), 'acquisition');

        $baId   = $this->resolveAccount($company, $response['biological_asset_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::BA_COST_CODE, '1008000', '1008089', 'Biological Assets', 'assets', 'Biological Assets'));
        $bankId = $response['bank_or_payable_account_id'] ?? null;
        if (!$bankId) throw new RuntimeException('AI could not determine a bank/payable account.');

        $cost  = (float) $asset->cost;
        $lines = [
            ['chart_of_account_id' => $baId,   'type' => 'debit',  'amount' => $cost, 'description' => 'Acquisition: '.$asset->name],
            ['chart_of_account_id' => $bankId, 'type' => 'credit', 'amount' => $cost, 'description' => 'Acquisition: '.$asset->name],
        ];

        return DB::transaction(function () use ($asset, $user, $company, $lines, $response) {
            $this->transactions->record($company, $user, [
                'transaction_date' => optional($asset->acquisition_date)->format('Y-m-d') ?? now()->toDateString(),
                'description'      => 'Biological asset acquisition — '.$asset->name,
                'reference'        => $asset->reference ?: 'BA-'.$asset->id,
                'status'           => 'draft',
                'source_document'  => 'biological_asset:'.$asset->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);
            return $asset;
        });
    }

    public function postFairValueAdjustmentWithAi(BiologicalAsset $asset, User $user, float $newFairValue, string $date): BiologicalAsset
    {
        $company  = $asset->company;
        $response = $this->prompt($asset, $this->companyAccounts($company), 'fair_value_adjustment', [
            'new_fair_value' => $newFairValue,
        ]);

        $baId = $this->resolveAccount($company, $response['biological_asset_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::BA_COST_CODE, '1008000', '1008089', 'Biological Assets', 'assets', 'Biological Assets'));

        $oldValue = (float) ($asset->fair_value ?? $asset->cost);
        $change   = $newFairValue - $oldValue;

        if (abs($change) < 0.01) return $asset;

        if ($change > 0) {
            $fvAccountId = $this->resolveAccount($company, $response['fair_value_gain_account_id'] ?? null,
                fn () => $this->ensureAccount($company, self::BA_FV_GAIN_CODE, '4005060', '4005099', 'FV Gain — Biological Assets', 'income', 'Other Income'));
            $lines = [
                ['chart_of_account_id' => $baId,          'type' => 'debit',  'amount' => abs($change), 'description' => 'FV gain: '.$asset->name],
                ['chart_of_account_id' => $fvAccountId,   'type' => 'credit', 'amount' => abs($change), 'description' => 'FV gain: '.$asset->name],
            ];
        } else {
            $fvAccountId = $this->resolveAccount($company, $response['fair_value_loss_account_id'] ?? null,
                fn () => $this->ensureAccount($company, self::BA_FV_LOSS_CODE, '6009070', '6009099', 'FV Loss — Biological Assets', 'expenses', 'Other Expenses'));
            $lines = [
                ['chart_of_account_id' => $fvAccountId, 'type' => 'debit',  'amount' => abs($change), 'description' => 'FV loss: '.$asset->name],
                ['chart_of_account_id' => $baId,        'type' => 'credit', 'amount' => abs($change), 'description' => 'FV loss: '.$asset->name],
            ];
        }

        return DB::transaction(function () use ($asset, $user, $company, $lines, $response, $date, $change) {
            $label = $change >= 0 ? 'gain' : 'loss';
            $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description'      => "IAS 41 FV {$label} — ".$asset->name,
                'reference'        => 'BAFV-'.str_replace('-', '', $date).'-'.$asset->id,
                'status'           => 'draft',
                'source_document'  => 'biological_asset:'.$asset->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);
            return $asset;
        });
    }

    public function postHarvestWithAi(BiologicalAsset $asset, User $user, float $fairValueAtHarvest, float $quantity, string $date, string $description = ''): BiologicalAsset
    {
        $company  = $asset->company;
        $response = $this->prompt($asset, $this->companyAccounts($company), 'harvest', [
            'harvest_fair_value' => $fairValueAtHarvest,
            'quantity'           => $quantity,
            'description'        => $description,
        ]);

        $baId      = $this->resolveAccount($company, $response['biological_asset_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::BA_COST_CODE, '1008000', '1008089', 'Biological Assets', 'assets', 'Biological Assets'));
        $harvestId = $this->resolveAccount($company, $response['harvest_inventory_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::BA_HARVEST_CODE, '1004050', '1004099', 'Agricultural Produce', 'assets', 'Inventory'));

        $lines = [
            ['chart_of_account_id' => $harvestId, 'type' => 'debit',  'amount' => $fairValueAtHarvest, 'description' => 'Harvest: '.($description ?: $asset->name)],
            ['chart_of_account_id' => $baId,      'type' => 'credit', 'amount' => $fairValueAtHarvest, 'description' => 'Harvest: '.($description ?: $asset->name)],
        ];

        return DB::transaction(function () use ($asset, $user, $company, $lines, $response, $date, $description) {
            $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description'      => 'IAS 41 Harvest — '.($description ?: $asset->name),
                'reference'        => 'BAHRVST-'.str_replace('-', '', $date).'-'.$asset->id,
                'status'           => 'draft',
                'source_document'  => 'biological_asset:'.$asset->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);
            return $asset;
        });
    }

    public function postDisposalWithAi(BiologicalAsset $asset, User $user): BiologicalAsset
    {
        $company  = $asset->company;
        $response = $this->prompt($asset, $this->companyAccounts($company), 'disposal');

        $baId     = $this->resolveAccount($company, $response['biological_asset_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::BA_COST_CODE, '1008000', '1008089', 'Biological Assets', 'assets', 'Biological Assets'));
        $bankId   = $response['bank_or_payable_account_id'] ?? null;
        if (!$bankId) throw new RuntimeException('AI could not determine a bank/payable account for disposal.');

        $carrying = $asset->carryingAmount();
        $proceeds = (float) ($asset->disposal_proceeds ?? 0);
        $gainLoss = $proceeds - $carrying;

        $lines = [
            ['chart_of_account_id' => $bankId, 'type' => 'debit',  'amount' => $proceeds,  'description' => 'Disposal proceeds: '.$asset->name],
            ['chart_of_account_id' => $baId,   'type' => 'credit', 'amount' => $carrying,   'description' => 'Derecognise: '.$asset->name],
        ];

        if (abs($gainLoss) >= 0.01) {
            if ($gainLoss > 0) {
                $glId = $this->resolveAccount($company, $response['gain_loss_account_id'] ?? null,
                    fn () => $this->ensureAccount($company, self::BA_GAIN_CODE, '4010020', '4010049', 'Gain on Disposal — Biological Assets', 'income', 'Other Income'));
                $lines[] = ['chart_of_account_id' => $glId, 'type' => 'credit', 'amount' => abs($gainLoss), 'description' => 'Gain on disposal: '.$asset->name];
            } else {
                $glId = $this->resolveAccount($company, $response['gain_loss_account_id'] ?? null,
                    fn () => $this->ensureAccount($company, self::BA_LOSS_CODE, '6009096', '6009099', 'Loss on Disposal — Biological Assets', 'expenses', 'Other Expenses'));
                $lines[] = ['chart_of_account_id' => $glId, 'type' => 'debit', 'amount' => abs($gainLoss), 'description' => 'Loss on disposal: '.$asset->name];
            }
        }

        return DB::transaction(function () use ($asset, $user, $company, $lines, $response) {
            $this->transactions->record($company, $user, [
                'transaction_date' => optional($asset->disposal_date)->format('Y-m-d') ?? now()->toDateString(),
                'description'      => 'Biological asset disposal — '.$asset->name,
                'reference'        => 'BADISP-'.$asset->id,
                'status'           => 'draft',
                'source_document'  => 'biological_asset:'.$asset->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);
            return $asset;
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
            'is_biological_asset' => $type === 'assets',
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
            'biological asset IAS 41 fair value',
            'fair value gain loss biological asset income expense',
            'agricultural produce inventory harvest',
            'bank cash proceeds asset',
            'accounts payable creditor liability',
            'impairment loss accumulated biological',
        ], 10)
            ->map(fn (ChartOfAccount $a) => "{$a->id}: {$a->account_code} — {$a->account_name} ({$a->account_type})")
            ->implode("\n");
    }

    private function prompt(BiologicalAsset $asset, string $accountList, string $kind, array $extra = []): array
    {
        $details = "Biological Asset: {$asset->name} (ref {$asset->reference})\n"
            ."Class: {$asset->biologicalAssetClass?->name} (category: {$asset->biologicalAssetClass?->category})\n"
            ."Quantity: {$asset->quantity} {$asset->unit}\n"
            ."Cost: {$asset->cost}, Fair value: ".($asset->fair_value ?? 'N/A')."\n"
            ."Accumulated impairment: ".($asset->accumulated_impairment ?? 0)."\n"
            ."Acquired: ".optional($asset->acquisition_date)->format('Y-m-d')."\n"
            .($asset->disposal_date ? "Disposed: {$asset->disposal_date->format('Y-m-d')}, proceeds: {$asset->disposal_proceeds}\n" : '');

        $extraStr = empty($extra) ? '' : "\nAdditional context:\n".json_encode($extra, JSON_PRETTY_PRINT)."\n";

        $prompt = "Posting type: {$kind}\n\n{$details}{$extraStr}\nChart of accounts:\n{$accountList}\n\nPick the IAS 41-appropriate accounts for this posting.";

        $history = $this->agentHistory->recall('biological_asset_posting', 'biological_asset', $asset->id);
        $fullPrompt = $history ? $history."\n\n".$prompt : $prompt;

        $response = (new BiologicalAssetPostingAgent)->prompt($fullPrompt)->toArray();
        $this->agentHistory->remember('biological_asset_posting', 'biological_asset', $asset->id, $prompt, json_encode($response));

        return $response;
    }
}
