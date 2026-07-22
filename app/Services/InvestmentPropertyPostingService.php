<?php

namespace App\Services;

use App\Ai\Agents\InvestmentPropertyPostingAgent;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\InvestmentProperty;
use App\Models\InvestmentPropertyClass;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class InvestmentPropertyPostingService
{
    private const IP_COST_CODE_FALLBACK          = '1005000';
    private const IP_ACC_DEP_CODE_FALLBACK       = '1005099';
    private const IP_DEP_EXPENSE_CODE_FALLBACK   = '6007050';
    private const IP_FV_GAIN_CODE_FALLBACK       = '4005050';
    private const IP_FV_LOSS_CODE_FALLBACK       = '6009060';
    private const IP_DISPOSAL_GAIN_CODE_FALLBACK = '4010010';
    private const IP_DISPOSAL_LOSS_CODE_FALLBACK = '6009090';
    private const IP_IMP_LOSS_CODE_FALLBACK      = '6008010';
    private const IP_ACC_IMP_CODE_FALLBACK       = '1005098';
    private const IP_IMP_REVERSAL_CODE_FALLBACK  = '4009010';

    private array $accountCache = [];

    public function __construct(
        private readonly TransactionService $transactions,
        private readonly AccountVectorSearchService $accountSearch,
    ) {}

    public function postAcquisitionWithAi(InvestmentProperty $property, User $user): InvestmentProperty
    {
        if ($property->posting_transaction_id) {
            throw new InvalidArgumentException('This property has already been posted.');
        }

        $company  = $property->company;
        $accounts = $this->companyAccounts($company);
        $response = $this->prompt($property, $accounts, 'acquisition');

        $costId = $this->resolveAccount($company, $response['cost_account_id'] ?? null,
            fn () => $this->ensureCostAccount($company));
        $bankId = $response['bank_or_payable_account_id'] ?? null;
        if (! $bankId) {
            throw new RuntimeException('AI could not determine a bank/payable account for acquisition.');
        }

        $cost  = (float) $property->cost;
        $lines = [
            ['chart_of_account_id' => $costId, 'type' => 'debit',  'amount' => $cost, 'description' => 'Acquisition: '.$property->name],
            ['chart_of_account_id' => $bankId, 'type' => 'credit', 'amount' => $cost, 'description' => 'Acquisition: '.$property->name],
        ];

        return DB::transaction(function () use ($property, $user, $company, $lines, $response) {
            $transaction = $this->transactions->record($company, $user, [
                'transaction_date' => optional($property->acquisition_date)->format('Y-m-d') ?? now()->toDateString(),
                'description'      => 'Investment property acquisition — '.$property->name,
                'reference'        => $property->property_reference ?: 'IP-'.$property->id,
                'status'           => 'draft',
                'source_document'  => 'investment_property:'.$property->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);

            $property->forceFill([
                'status'                 => InvestmentProperty::STATUS_ACTIVE,
                'posted_at'              => now(),
                'posting_transaction_id' => $transaction->id,
            ])->save();

            return $property;
        });
    }

    public function postDisposalWithAi(InvestmentProperty $property, User $user): InvestmentProperty
    {
        if ($property->disposal_transaction_id) {
            throw new InvalidArgumentException('This disposal has already been posted.');
        }
        if (! $property->disposal_date) {
            throw new InvalidArgumentException('Property has no disposal date.');
        }

        $company  = $property->company;
        $accounts = $this->companyAccounts($company);
        $response = $this->prompt($property, $accounts, 'disposal');

        $costId = $this->resolveAccount($company, $response['cost_account_id'] ?? null,
            fn () => $this->ensureCostAccount($company));
        $proceedsId = $response['proceeds_account_id'] ?? null;
        if (! $proceedsId) {
            throw new RuntimeException('AI could not determine a proceeds (bank) account for disposal.');
        }

        $cost     = (float) $property->cost;
        $isFV     = $property->isFairValueModel();
        $accDep   = $isFV ? 0 : $property->accumulatedDepreciation($property->disposal_date->format('Y-m-d'));
        $carrying = $property->carryingAmount($property->disposal_date->format('Y-m-d'));
        $proceeds = (float) ($property->disposal_proceeds ?? 0);
        $gainLoss = round($proceeds - $carrying, 2);

        $lines = [];
        if ($proceeds >= 0.01) {
            $lines[] = ['chart_of_account_id' => $proceedsId, 'type' => 'debit', 'amount' => $proceeds, 'description' => 'Disposal proceeds: '.$property->name];
        }
        if (round($accDep, 2) >= 0.01) {
            $accDepId = $this->resolveAccount($company, $response['accumulated_depreciation_account_id'] ?? null,
                fn () => $this->ensureAccDepAccount($company));
            $lines[] = ['chart_of_account_id' => $accDepId, 'type' => 'debit', 'amount' => round($accDep, 2), 'description' => 'Derecognise acc. depreciation: '.$property->name];
        }
        $accImp = (float) ($property->accumulated_impairment ?? 0);
        if (round($accImp, 2) >= 0.01) {
            $accImpId = $this->resolveAccount($company, $response['accumulated_impairment_account_id'] ?? null,
                fn () => $this->ensureAccImpairmentAccount($company));
            $lines[] = ['chart_of_account_id' => $accImpId, 'type' => 'debit', 'amount' => round($accImp, 2), 'description' => 'Derecognise acc. impairment: '.$property->name];
        }
        $lines[] = ['chart_of_account_id' => $costId, 'type' => 'credit', 'amount' => $cost, 'description' => 'Derecognise cost: '.$property->name];

        if (abs($gainLoss) >= 0.01) {
            $glId = $this->resolveAccount($company, $response['gain_loss_account_id'] ?? null,
                fn () => $this->ensureGainLossAccount($company, $gainLoss));
            $lines[] = $gainLoss > 0
                ? ['chart_of_account_id' => $glId, 'type' => 'credit', 'amount' => $gainLoss, 'description' => 'Gain on disposal: '.$property->name]
                : ['chart_of_account_id' => $glId, 'type' => 'debit',  'amount' => abs($gainLoss), 'description' => 'Loss on disposal: '.$property->name];
        }

        return DB::transaction(function () use ($property, $user, $company, $lines, $response) {
            $transaction = $this->transactions->record($company, $user, [
                'transaction_date' => $property->disposal_date->format('Y-m-d'),
                'description'      => 'Investment property disposal — '.$property->name,
                'reference'        => 'DISPOSAL-'.($property->property_reference ?: $property->id),
                'status'           => 'draft',
                'source_document'  => 'investment_property:'.$property->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);

            $property->forceFill([
                'status'                   => InvestmentProperty::STATUS_DISPOSED,
                'disposal_transaction_id'  => $transaction->id,
            ])->save();

            return $property;
        });
    }

    public function postFairValueAdjustmentWithAi(
        InvestmentProperty $property,
        User $user,
        float $newFairValue,
        string $date,
    ): InvestmentProperty {
        if ($property->status === InvestmentProperty::STATUS_DISPOSED) {
            throw new InvalidArgumentException('Cannot adjust fair value of a disposed property.');
        }

        $company  = $property->company;
        $oldValue = (float) ($property->fair_value ?? $property->cost);
        $change   = round($newFairValue - $oldValue, 2);

        if (abs($change) < 0.01) {
            throw new InvalidArgumentException('No fair value change.');
        }

        $accounts = $this->companyAccounts($company);
        $response = $this->prompt($property, $accounts, 'fair_value_adjustment', [
            'old_fair_value' => $oldValue,
            'new_fair_value' => $newFairValue,
            'change'         => $change,
        ]);

        $costId = $this->resolveAccount($company, $response['cost_account_id'] ?? null,
            fn () => $this->ensureCostAccount($company));

        $lines = [];
        if ($change > 0) {
            $fvGainId = $this->resolveAccount($company, $response['fair_value_gain_account_id'] ?? null,
                fn () => $this->ensureFairValueGainAccount($company));
            $lines[] = ['chart_of_account_id' => $costId,    'type' => 'debit',  'amount' => $change, 'description' => 'FV gain — '.$property->name.' ('.$date.')'];
            $lines[] = ['chart_of_account_id' => $fvGainId,  'type' => 'credit', 'amount' => $change, 'description' => 'FV gain (P&L) — '.$property->name.' ('.$date.')'];
        } else {
            $fvLossId = $this->resolveAccount($company, $response['fair_value_loss_account_id'] ?? null,
                fn () => $this->ensureFairValueLossAccount($company));
            $lines[] = ['chart_of_account_id' => $fvLossId, 'type' => 'debit',  'amount' => abs($change), 'description' => 'FV loss (P&L) — '.$property->name.' ('.$date.')'];
            $lines[] = ['chart_of_account_id' => $costId,   'type' => 'credit', 'amount' => abs($change), 'description' => 'FV loss — '.$property->name.' ('.$date.')'];
        }

        return DB::transaction(function () use ($property, $user, $company, $lines, $response, $newFairValue, $change, $date) {
            $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description'      => 'IAS 40 Fair value adjustment — '.$property->name,
                'reference'        => 'FV-'.str_replace('-', '', $date).'-'.($property->property_reference ?: $property->id),
                'status'           => 'draft',
                'source_document'  => 'investment_property:'.$property->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);

            $property->forceFill([
                'fair_value'           => $newFairValue,
                'fair_value_date'      => $date,
                'fair_value_gain_loss' => round((float) ($property->fair_value_gain_loss ?? 0) + $change, 2),
            ])->save();

            return $property;
        });
    }

    public function postImpairmentWithAi(
        InvestmentProperty $property,
        User $user,
        float $impairmentAmount,
        string $date,
        string $reason = '',
    ): InvestmentProperty {
        if ($property->status === InvestmentProperty::STATUS_DISPOSED) {
            throw new InvalidArgumentException('Cannot impair a disposed property.');
        }

        $company  = $property->company;
        $accounts = $this->companyAccounts($company);
        $response = $this->prompt($property, $accounts, 'impairment', [
            'impairment_amount' => $impairmentAmount,
            'reason'            => $reason,
        ]);

        $impLossId = $this->resolveAccount($company, $response['impairment_loss_account_id'] ?? null,
            fn () => $this->ensureImpairmentLossAccount($company));
        $accImpId = $this->resolveAccount($company, $response['accumulated_impairment_account_id'] ?? null,
            fn () => $this->ensureAccImpairmentAccount($company));

        $lines = [
            ['chart_of_account_id' => $impLossId, 'type' => 'debit',  'amount' => round($impairmentAmount, 2), 'description' => 'Impairment loss (IAS 36) — '.$property->name.' ('.$date.') '.$reason],
            ['chart_of_account_id' => $accImpId,  'type' => 'credit', 'amount' => round($impairmentAmount, 2), 'description' => 'Accumulated impairment — '.$property->name.' ('.$date.')'],
        ];

        return DB::transaction(function () use ($property, $user, $company, $lines, $response, $impairmentAmount, $date) {
            $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description'      => 'IAS 36 Impairment — '.$property->name,
                'reference'        => 'IMP-'.str_replace('-', '', $date).'-'.($property->property_reference ?: $property->id),
                'status'           => 'draft',
                'source_document'  => 'investment_property:'.$property->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);

            $property->forceFill([
                'accumulated_impairment' => round((float) ($property->accumulated_impairment ?? 0) + $impairmentAmount, 2),
            ])->save();

            return $property;
        });
    }

    public function postImpairmentReversalWithAi(
        InvestmentProperty $property,
        User $user,
        float $reversalAmount,
        string $date,
    ): InvestmentProperty {
        if ($property->status === InvestmentProperty::STATUS_DISPOSED) {
            throw new InvalidArgumentException('Cannot reverse impairment on a disposed property.');
        }

        $company  = $property->company;
        $accounts = $this->companyAccounts($company);
        $response = $this->prompt($property, $accounts, 'impairment_reversal', [
            'reversal_amount' => $reversalAmount,
        ]);

        $accImpId = $this->resolveAccount($company, $response['accumulated_impairment_account_id'] ?? null,
            fn () => $this->ensureAccImpairmentAccount($company));
        $revIncomeId = $this->resolveAccount($company, $response['impairment_reversal_account_id'] ?? null,
            fn () => $this->ensureImpairmentReversalAccount($company));

        $lines = [
            ['chart_of_account_id' => $accImpId,    'type' => 'debit',  'amount' => $reversalAmount, 'description' => 'Impairment reversal — '.$property->name.' ('.$date.')'],
            ['chart_of_account_id' => $revIncomeId, 'type' => 'credit', 'amount' => $reversalAmount, 'description' => 'Impairment reversal income (IAS 36.114) — '.$property->name.' ('.$date.')'],
        ];

        return DB::transaction(function () use ($property, $user, $company, $lines, $response, $reversalAmount, $date) {
            $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description'      => 'IAS 36 Impairment Reversal — '.$property->name,
                'reference'        => 'IMPREV-'.str_replace('-', '', $date).'-'.($property->property_reference ?: $property->id),
                'status'           => 'draft',
                'source_document'  => 'investment_property:'.$property->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);

            $property->forceFill([
                'accumulated_impairment' => max(0, round((float) ($property->accumulated_impairment ?? 0) - $reversalAmount, 2)),
            ])->save();

            return $property;
        });
    }

    public function postSubsequentCostWithAi(
        InvestmentProperty $property,
        User $user,
        float $amount,
        string $date,
        string $description = '',
    ): InvestmentProperty {
        if ($property->status === InvestmentProperty::STATUS_DISPOSED) {
            throw new InvalidArgumentException('Cannot capitalise costs on a disposed property.');
        }

        $company  = $property->company;
        $accounts = $this->companyAccounts($company);
        $response = $this->prompt($property, $accounts, 'subsequent_cost', [
            'amount'      => $amount,
            'description' => $description,
        ]);

        $costId = $this->resolveAccount($company, $response['cost_account_id'] ?? null,
            fn () => $this->ensureCostAccount($company));
        $bankId = $response['bank_or_payable_account_id'] ?? null;
        if (! $bankId) {
            throw new RuntimeException('AI could not determine a bank/payable account for subsequent capitalisation.');
        }

        $lines = [
            ['chart_of_account_id' => $costId, 'type' => 'debit',  'amount' => round($amount, 2), 'description' => ($description ?: 'Subsequent cost').' — '.$property->name.' ('.$date.')'],
            ['chart_of_account_id' => $bankId, 'type' => 'credit', 'amount' => round($amount, 2), 'description' => ($description ?: 'Subsequent cost').' — '.$property->name.' ('.$date.')'],
        ];

        return DB::transaction(function () use ($property, $user, $company, $lines, $response, $amount, $date, $description) {
            $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description'      => 'IAS 40 Subsequent cost — '.($description ?: $property->name),
                'reference'        => 'SUBCAP-'.str_replace('-', '', $date).'-'.($property->property_reference ?: $property->id),
                'status'           => 'draft',
                'source_document'  => 'investment_property:'.$property->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);

            $property->forceFill([
                'cost' => round((float) $property->cost + $amount, 2),
            ])->save();

            return $property;
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

    private function ensureCostAccount(Company $company): ChartOfAccount
    {
        return $this->ensureAccount($company, [
            'account_code' => $this->nextCode($company, '1005000', '1005089'),
            'account_name' => 'Investment Property (Cost)',
            'account_type' => 'assets',
            'category'     => 'Investment Property',
            'cash_flow_category' => 'investing',
            'is_contra' => false,
        ], self::IP_COST_CODE_FALLBACK);
    }

    private function ensureAccDepAccount(Company $company): ChartOfAccount
    {
        return $this->ensureAccount($company, [
            'account_code' => $this->nextCode($company, '1005090', '1005094'),
            'account_name' => 'Accumulated Depreciation — Investment Property',
            'account_type' => 'assets',
            'category'     => 'Investment Property',
            'cash_flow_category' => 'investing',
            'is_contra' => true,
        ], self::IP_ACC_DEP_CODE_FALLBACK);
    }

    private function ensureFairValueGainAccount(Company $company): ChartOfAccount
    {
        return $this->ensureAccount($company, [
            'account_code' => $this->nextCode($company, '4005000', '4005059'),
            'account_name' => 'Fair Value Gain — Investment Property',
            'account_type' => 'income',
            'category'     => 'Other Income',
            'cash_flow_category' => 'operating',
            'is_contra' => false,
        ], self::IP_FV_GAIN_CODE_FALLBACK);
    }

    private function ensureFairValueLossAccount(Company $company): ChartOfAccount
    {
        return $this->ensureAccount($company, [
            'account_code' => $this->nextCode($company, '6009050', '6009069'),
            'account_name' => 'Fair Value Loss — Investment Property',
            'account_type' => 'expenses',
            'category'     => 'Other Expenses',
            'cash_flow_category' => 'operating',
            'is_contra' => false,
        ], self::IP_FV_LOSS_CODE_FALLBACK);
    }

    private function ensureGainLossAccount(Company $company, float $gainLoss): ChartOfAccount
    {
        if ($gainLoss > 0) {
            return $this->ensureAccount($company, [
                'account_code' => $this->nextCode($company, '4010010', '4010019'),
                'account_name' => 'Gain on Disposal of Investment Property',
                'account_type' => 'income',
                'category'     => 'Other Income',
                'cash_flow_category' => 'investing',
                'is_contra' => false,
            ], self::IP_DISPOSAL_GAIN_CODE_FALLBACK);
        }
        return $this->ensureAccount($company, [
            'account_code' => $this->nextCode($company, '6009090', '6009094'),
            'account_name' => 'Loss on Disposal of Investment Property',
            'account_type' => 'expenses',
            'category'     => 'Other Expenses',
            'cash_flow_category' => 'investing',
            'is_contra' => false,
        ], self::IP_DISPOSAL_LOSS_CODE_FALLBACK);
    }

    private function ensureImpairmentLossAccount(Company $company): ChartOfAccount
    {
        return $this->ensureAccount($company, [
            'account_code' => $this->nextCode($company, '6008010', '6008019'),
            'account_name' => 'Impairment Loss — Investment Property',
            'account_type' => 'expenses',
            'category'     => 'Depreciation & Amortisation',
            'cash_flow_category' => 'operating',
            'is_contra' => false,
        ], self::IP_IMP_LOSS_CODE_FALLBACK);
    }

    private function ensureAccImpairmentAccount(Company $company): ChartOfAccount
    {
        return $this->ensureAccount($company, [
            'account_code' => $this->nextCode($company, '1005095', '1005099'),
            'account_name' => 'Accumulated Impairment — Investment Property',
            'account_type' => 'assets',
            'category'     => 'Investment Property',
            'cash_flow_category' => 'investing',
            'is_contra' => true,
        ], self::IP_ACC_IMP_CODE_FALLBACK);
    }

    private function ensureImpairmentReversalAccount(Company $company): ChartOfAccount
    {
        return $this->ensureAccount($company, [
            'account_code' => $this->nextCode($company, '4009010', '4009019'),
            'account_name' => 'Impairment Reversal — Investment Property',
            'account_type' => 'income',
            'category'     => 'Other Income',
            'cash_flow_category' => 'operating',
            'is_contra' => false,
        ], self::IP_IMP_REVERSAL_CODE_FALLBACK);
    }

    private function ensureAccount(Company $company, array $attrs, string $fallbackCode): ChartOfAccount
    {
        $existing = ChartOfAccount::where('company_id', $company->id)
            ->where('account_name', $attrs['account_name'])
            ->first();
        if ($existing) {
            return $existing;
        }
        if (! $attrs['account_code']) {
            $attrs['account_code'] = $fallbackCode;
        }
        $account = ChartOfAccount::create(array_merge([
            'company_id'      => $company->id,
            'is_active'       => true,
            'opening_balance' => 0,
        ], $attrs));
        if ($account->account_type === 'assets') {
            $account->update(['is_investment_property' => true]);
        }
        return $account;
    }

    private function nextCode(Company $company, string $from, string $to): ?string
    {
        $taken = ChartOfAccount::where('company_id', $company->id)
            ->whereBetween('account_code', [$from, $to])
            ->pluck('account_code')
            ->all();
        for ($i = (int) $from; $i <= (int) $to; $i++) {
            $code = (string) $i;
            if (! in_array($code, $taken, true)) {
                return $code;
            }
        }
        return null;
    }

    private function companyAccounts(Company $company)
    {
        if (isset($this->accountCache[$company->id])) {
            return $this->accountCache[$company->id];
        }

        return $this->accountCache[$company->id] = $this->accountSearch->searchMultiple($company->id, [
            'investment property IAS 40 asset',
            'fair value gain loss investment property income expense',
            'depreciation investment property cost model',
            'accumulated depreciation investment property contra',
            'impairment loss investment property expense',
            'bank cash proceeds asset',
            'accounts payable creditor liability',
            'rental income investment property revenue',
        ], 10);
    }

    private function prompt(InvestmentProperty $property, $accounts, string $kind, array $extra = []): array
    {
        $list = $accounts
            ->map(fn (ChartOfAccount $a) => "- id={$a->id}, code={$a->account_code}, name=\"{$a->account_name}\", type={$a->account_type}".($a->is_contra ? ' (contra)' : ''))
            ->implode("\n");

        $model = $property->isFairValueModel() ? 'fair_value' : 'cost';

        $details = "Investment Property: {$property->name} (ref {$property->property_reference})\n"
            ."Class: ".($property->investmentPropertyClass?->name ?? 'Unclassified')." (measurement model: {$model})\n"
            ."Cost: {$property->cost}, residual: {$property->residual_value}, useful life: {$property->useful_life_years} years\n"
            ."Fair value: ".($property->fair_value ?? 'N/A').", FV gain/loss cumulative: ".($property->fair_value_gain_loss ?? 0)."\n"
            ."Accumulated impairment: ".($property->accumulated_impairment ?? 0)."\n"
            ."Acquired: ".optional($property->acquisition_date)->format('Y-m-d')."\n"
            .($property->disposal_date ? "Disposed: {$property->disposal_date->format('Y-m-d')}, proceeds: {$property->disposal_proceeds}\n" : '');

        $extraStr = empty($extra) ? '' : "\nAdditional context:\n".json_encode($extra, JSON_PRETTY_PRINT)."\n";

        $prompt = "Posting type: {$kind}\n\n{$details}{$extraStr}\nChart of accounts:\n{$list}\n\nPick the IFRS-appropriate accounts for this posting.";

        return (new InvestmentPropertyPostingAgent)->prompt($prompt)->toArray();
    }
}
