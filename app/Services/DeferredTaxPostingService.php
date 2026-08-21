<?php

namespace App\Services;

use App\Ai\Agents\DeferredTaxPostingAgent;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\DeferredTaxItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DeferredTaxPostingService
{
    private const DT_ASSET_CODE     = '1009000';
    private const DT_LIABILITY_CODE = '2010000';
    private const TAX_EXPENSE_CODE  = '7001000';

    private array $accountCache = [];

    public function __construct(
        private readonly TransactionService $transactions,
        private readonly AccountVectorSearchService $accountSearch,
        private readonly AgentHistoryService $agentHistory = new AgentHistoryService,
    ) {}

    // ─── Initial Recognition (IAS 12.15 / 12.24) ────────────────────────

    public function postInitialRecognitionWithAi(DeferredTaxItem $item, User $user): DeferredTaxItem
    {
        $company  = $item->company;
        $response = $this->prompt($item, $this->companyAccounts($company), 'initial_recognition');

        $amount = $item->calculateDeferredTax();
        if ($amount < 0.01) return $item;

        if ($item->isLiability()) {
            $liabilityId = $this->resolveAccount($company, $response['deferred_tax_liability_account_id'] ?? null,
                fn () => $this->ensureAccount($company, self::DT_LIABILITY_CODE, '2010000', '2010099', 'Deferred Tax Liability', 'liabilities', 'Non-Current Liabilities'));
            $expenseId = $this->resolveAccount($company, $response['tax_expense_account_id'] ?? null,
                fn () => $this->ensureAccount($company, self::TAX_EXPENSE_CODE, '7001000', '7001099', 'Deferred Tax Expense', 'expenses', 'Tax'));

            $lines = [
                ['chart_of_account_id' => $expenseId,   'type' => 'debit',  'amount' => $amount, 'description' => 'Deferred tax liability recognised: '.$item->name],
                ['chart_of_account_id' => $liabilityId, 'type' => 'credit', 'amount' => $amount, 'description' => 'Deferred tax liability recognised: '.$item->name],
            ];
        } else {
            $assetId = $this->resolveAccount($company, $response['deferred_tax_asset_account_id'] ?? null,
                fn () => $this->ensureAccount($company, self::DT_ASSET_CODE, '1009000', '1009099', 'Deferred Tax Asset', 'assets', 'Non-Current Assets'));
            $expenseId = $this->resolveAccount($company, $response['tax_expense_account_id'] ?? null,
                fn () => $this->ensureAccount($company, self::TAX_EXPENSE_CODE, '7001000', '7001099', 'Deferred Tax Expense', 'expenses', 'Tax'));

            $lines = [
                ['chart_of_account_id' => $assetId,   'type' => 'debit',  'amount' => $amount, 'description' => 'Deferred tax asset recognised: '.$item->name],
                ['chart_of_account_id' => $expenseId, 'type' => 'credit', 'amount' => $amount, 'description' => 'Deferred tax asset recognised: '.$item->name],
            ];
        }

        return DB::transaction(function () use ($item, $user, $company, $lines, $response) {
            $this->transactions->record($company, $user, [
                'transaction_date' => optional($item->measurement_date)->format('Y-m-d') ?? now()->toDateString(),
                'description'      => 'IAS 12 deferred tax recognised — '.$item->name,
                'reference'        => 'DT-'.$item->id,
                'status'           => 'draft',
                'source_document'  => 'deferred_tax:'.$item->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);
            return $item;
        });
    }

    // ─── Remeasurement (IAS 12.37 / 12.56) ──────────────────────────────

    public function postRemeasurementWithAi(DeferredTaxItem $item, User $user, float $newTaxBase, float $newCarryingAmount, float $newRate, string $date): DeferredTaxItem
    {
        $company  = $item->company;
        $response = $this->prompt($item, $this->companyAccounts($company), 'remeasurement', [
            'new_tax_base'        => $newTaxBase,
            'new_carrying_amount' => $newCarryingAmount,
            'new_rate'            => $newRate,
        ]);

        $oldDT = (float) $item->deferred_tax_liability - (float) $item->deferred_tax_asset;
        $newTempDiff = $newCarryingAmount - $newTaxBase;
        $newDTAmount = abs($newTempDiff) * $newRate / 100;
        $newDT = $newTempDiff > 0 ? $newDTAmount : -$newDTAmount;
        $change = $newDT - $oldDT;

        if (abs($change) < 0.01) return $item;

        $liabilityId = $this->resolveAccount($company, $response['deferred_tax_liability_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::DT_LIABILITY_CODE, '2010000', '2010099', 'Deferred Tax Liability', 'liabilities', 'Non-Current Liabilities'));
        $assetId = $this->resolveAccount($company, $response['deferred_tax_asset_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::DT_ASSET_CODE, '1009000', '1009099', 'Deferred Tax Asset', 'assets', 'Non-Current Assets'));
        $expenseId = $this->resolveAccount($company, $response['tax_expense_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::TAX_EXPENSE_CODE, '7001000', '7001099', 'Deferred Tax Expense', 'expenses', 'Tax'));

        if ($change > 0) {
            // Net DT liability increased (or DT asset decreased) — debit expense, credit liability
            $lines = [
                ['chart_of_account_id' => $expenseId,   'type' => 'debit',  'amount' => abs($change), 'description' => 'Deferred tax remeasurement increase: '.$item->name],
                ['chart_of_account_id' => $liabilityId, 'type' => 'credit', 'amount' => abs($change), 'description' => 'Deferred tax remeasurement increase: '.$item->name],
            ];
        } else {
            // Net DT liability decreased (or DT asset increased) — debit asset, credit expense
            $lines = [
                ['chart_of_account_id' => $assetId,   'type' => 'debit',  'amount' => abs($change), 'description' => 'Deferred tax remeasurement decrease: '.$item->name],
                ['chart_of_account_id' => $expenseId, 'type' => 'credit', 'amount' => abs($change), 'description' => 'Deferred tax remeasurement decrease: '.$item->name],
            ];
        }

        return DB::transaction(function () use ($item, $user, $company, $lines, $response, $date) {
            $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description'      => 'IAS 12 deferred tax remeasurement — '.$item->name,
                'reference'        => 'DTREM-'.str_replace('-', '', $date).'-'.$item->id,
                'status'           => 'draft',
                'source_document'  => 'deferred_tax:'.$item->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);
            return $item;
        });
    }

    // ─── Reversal ────────────────────────────────────────────────────────

    public function postReversalWithAi(DeferredTaxItem $item, User $user, string $date): DeferredTaxItem
    {
        $company  = $item->company;
        $response = $this->prompt($item, $this->companyAccounts($company), 'reversal');

        $liabilityId = $this->resolveAccount($company, $response['deferred_tax_liability_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::DT_LIABILITY_CODE, '2010000', '2010099', 'Deferred Tax Liability', 'liabilities', 'Non-Current Liabilities'));
        $assetId = $this->resolveAccount($company, $response['deferred_tax_asset_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::DT_ASSET_CODE, '1009000', '1009099', 'Deferred Tax Asset', 'assets', 'Non-Current Assets'));
        $expenseId = $this->resolveAccount($company, $response['tax_expense_account_id'] ?? null,
            fn () => $this->ensureAccount($company, self::TAX_EXPENSE_CODE, '7001000', '7001099', 'Deferred Tax Expense', 'expenses', 'Tax'));

        $dtLiability = (float) $item->deferred_tax_liability;
        $dtAsset     = (float) $item->deferred_tax_asset;
        $lines       = [];

        if ($dtLiability > 0.01) {
            $lines[] = ['chart_of_account_id' => $liabilityId, 'type' => 'debit',  'amount' => $dtLiability, 'description' => 'Deferred tax liability reversed: '.$item->name];
            $lines[] = ['chart_of_account_id' => $expenseId,   'type' => 'credit', 'amount' => $dtLiability, 'description' => 'Deferred tax liability reversed: '.$item->name];
        }

        if ($dtAsset > 0.01) {
            $lines[] = ['chart_of_account_id' => $expenseId, 'type' => 'debit',  'amount' => $dtAsset, 'description' => 'Deferred tax asset reversed: '.$item->name];
            $lines[] = ['chart_of_account_id' => $assetId,   'type' => 'credit', 'amount' => $dtAsset, 'description' => 'Deferred tax asset reversed: '.$item->name];
        }

        if (empty($lines)) return $item;

        return DB::transaction(function () use ($item, $user, $company, $lines, $response, $date) {
            $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description'      => 'IAS 12 deferred tax reversal — '.$item->name,
                'reference'        => 'DTREV-'.$item->id,
                'status'           => 'draft',
                'source_document'  => 'deferred_tax:'.$item->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);
            return $item;
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
            'deferred tax asset non-current',
            'deferred tax liability non-current',
            'tax expense income tax deferred',
            'other comprehensive income tax OCI',
        ], 10)
            ->map(fn (ChartOfAccount $a) => "{$a->id}: {$a->account_code} — {$a->account_name} ({$a->account_type})")
            ->implode("\n");
    }

    private function prompt(DeferredTaxItem $item, string $accountList, string $kind, array $extra = []): array
    {
        $details = "Deferred Tax Item: {$item->name}\n"
            ."Source: {$item->source_type}" . ($item->source_id ? " (ID: {$item->source_id})" : '') . "\n"
            ."Tax base: {$item->tax_base}, Carrying amount: {$item->carrying_amount}\n"
            ."Temporary difference: {$item->temporary_difference}\n"
            ."DT Asset: {$item->deferred_tax_asset}, DT Liability: {$item->deferred_tax_liability}\n"
            ."Tax rate: {$item->tax_rate}%\n"
            ."Is taxable: ".($item->is_taxable ? 'Yes (liability)' : 'No (asset)')."\n"
            ."Measurement date: ".optional($item->measurement_date)->format('Y-m-d')."\n";

        $extraStr = empty($extra) ? '' : "\nAdditional context:\n".json_encode($extra, JSON_PRETTY_PRINT)."\n";

        $prompt = "Posting type: {$kind}\n\n{$details}{$extraStr}\nChart of accounts:\n{$accountList}\n\nPick the IAS 12-appropriate accounts for this deferred tax posting.";

        $history = $this->agentHistory->recall('deferred_tax_posting', 'deferred_tax_item', $item->id);
        $fullPrompt = $history ? $history."\n\n".$prompt : $prompt;

        $response = (new DeferredTaxPostingAgent)->prompt($fullPrompt)->toArray();
        $this->agentHistory->remember('deferred_tax_posting', 'deferred_tax_item', $item->id, $prompt, json_encode($response));

        return $response;
    }
}
