<?php

namespace App\Services;

use App\Ai\Agents\IntangibleAssetPostingAgent;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\IntangibleAsset;
use App\Models\IntangibleAssetEvent;
use App\Models\IntangibleClass;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Posts intangible-asset acquisitions, disposals, revaluations, impairments
 * and amortisation to the GL per IAS 38 / IAS 36 / IFRS for SMEs §18.
 *
 * Mirrors AssetPostingService for PPE: the AI agent picks accounts where it
 * can, missing IFRS-required accounts are created deterministically inside the
 * intangible-asset code band (1700–1799 / 6650–6799 / 3850–3899 / 4850–4999).
 */
class IntangibleAssetPostingService
{
    private const INT_COST_CODE_FALLBACK                = '1008000';
    private const INT_ACC_AMORT_CODE_FALLBACK           = '1008090';
    private const INT_ACC_IMPAIRMENT_CODE_FALLBACK      = '1008080';
    private const INT_AMORT_EXPENSE_CODE_FALLBACK       = '6007050';
    private const INT_IMPAIRMENT_LOSS_CODE_FALLBACK     = '6008050';
    private const INT_IMPAIRMENT_REVERSAL_CODE_FALLBACK = '4009050';
    private const INT_REVALUATION_SURPLUS_CODE_FALLBACK = '3009050';
    private const INT_DISPOSAL_GAIN_CODE_FALLBACK       = '4010050';
    private const INT_DISPOSAL_LOSS_CODE_FALLBACK       = '6009095';

    private array $accountCache = [];

    public function __construct(
        private readonly TransactionService $transactions,
        private readonly AccountVectorSearchService $accountSearch,
        private readonly AgentHistoryService $agentHistory = new AgentHistoryService,
    ) {}

    public function postAcquisitionWithAi(IntangibleAsset $asset, User $user): IntangibleAsset
    {
        if ($asset->posting_transaction_id) {
            throw new InvalidArgumentException('This intangible asset has already been posted.');
        }

        $company       = $asset->company;
        $intangibleClass = $asset->intangibleClass;

        $accounts = $this->companyAccounts($company);
        $response = $this->prompt($asset, $accounts, 'acquisition');

        $costId = $this->resolveCost($company, $intangibleClass, $response['cost_account_id'] ?? null);
        $bankId = $response['bank_or_payable_account_id'] ?? null;
        if (! $bankId) {
            throw new RuntimeException('AI could not determine a bank/payable account for acquisition.');
        }

        $cost  = (float) $asset->cost;
        $lines = [
            ['chart_of_account_id' => $costId, 'type' => 'debit',  'amount' => $cost, 'description' => 'Intangible acquisition: '.$asset->name],
            ['chart_of_account_id' => $bankId, 'type' => 'credit', 'amount' => $cost, 'description' => 'Intangible acquisition: '.$asset->name],
        ];

        return DB::transaction(function () use ($asset, $user, $company, $lines, $response) {
            $transaction = $this->transactions->record($company, $user, [
                'transaction_date' => optional($asset->acquisition_date)->format('Y-m-d') ?? now()->toDateString(),
                'description'      => 'Intangible asset acquisition — '.$asset->name,
                'reference'        => $asset->reference ?: 'INTANGIBLE-'.$asset->id,
                'status'           => 'draft',
                'source_document'  => 'intangible:'.$asset->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);

            $asset->forceFill([
                'status'                 => IntangibleAsset::STATUS_ACTIVE,
                'posted_at'              => now(),
                'posting_transaction_id' => $transaction->id,
            ])->save();

            return $asset;
        });
    }

    public function postDisposalWithAi(IntangibleAsset $asset, User $user): IntangibleAsset
    {
        if ($asset->disposal_transaction_id) {
            throw new InvalidArgumentException('This intangible asset disposal has already been posted.');
        }
        if (! $asset->disposal_date) {
            throw new InvalidArgumentException('Intangible asset has no disposal date.');
        }

        $company         = $asset->company;
        $intangibleClass = $asset->intangibleClass;

        $accounts = $this->companyAccounts($company);
        $response = $this->prompt($asset, $accounts, 'disposal');

        $costId   = $this->resolveCost($company, $intangibleClass, $response['cost_account_id'] ?? null);
        $accAmortId = $this->resolveAccumulatedAmortisation($company, $intangibleClass, $response['accumulated_amortisation_account_id'] ?? null);

        $proceedsId = $response['proceeds_account_id'] ?? null;
        if (! $proceedsId) {
            throw new RuntimeException('AI could not determine a proceeds account for disposal.');
        }

        $cost     = (float) $asset->cost;
        $accAmort = $asset->accumulatedAmortisation($asset->disposal_date->format('Y-m-d'));
        $accImp   = (float) ($asset->accumulated_impairment ?? 0);
        $carrying = $cost - $accAmort - $accImp;
        $proceeds = (float) ($asset->disposal_proceeds ?? 0);
        $gainLoss = round($proceeds - $carrying, 2);

        $lines = [
            ['chart_of_account_id' => $proceedsId, 'type' => 'debit',  'amount' => $proceeds, 'description' => 'Disposal proceeds: '.$asset->name],
        ];
        if ($accAmort > 0) {
            $lines[] = ['chart_of_account_id' => $accAmortId, 'type' => 'debit', 'amount' => round($accAmort, 2), 'description' => 'Derecognise acc. amortisation: '.$asset->name];
        }
        if ($accImp > 0) {
            $accImpId = $this->resolveAccumulatedImpairment($company, $intangibleClass, $response['accumulated_impairment_account_id'] ?? null);
            $lines[]  = ['chart_of_account_id' => $accImpId, 'type' => 'debit', 'amount' => round($accImp, 2), 'description' => 'Derecognise acc. impairment: '.$asset->name];
        }
        $lines[] = ['chart_of_account_id' => $costId, 'type' => 'credit', 'amount' => $cost, 'description' => 'Derecognise intangible cost: '.$asset->name];

        if (abs($gainLoss) >= 0.01) {
            $gainLossId = $this->resolveGainLoss($company, $response['gain_loss_account_id'] ?? null, $gainLoss);
            $lines[] = $gainLoss > 0
                ? ['chart_of_account_id' => $gainLossId, 'type' => 'credit', 'amount' => $gainLoss, 'description' => 'Gain on disposal: '.$asset->name]
                : ['chart_of_account_id' => $gainLossId, 'type' => 'debit',  'amount' => abs($gainLoss), 'description' => 'Loss on disposal: '.$asset->name];
        }

        // IAS 38.87: transfer remaining revaluation surplus to retained earnings on disposal
        $surplus = (float) ($asset->revaluation_surplus ?? 0);
        if ($intangibleClass && $intangibleClass->accounting_policy === IntangibleClass::POLICY_REVALUATION && round($surplus, 2) >= 0.01) {
            $revSurplusId = $this->resolveRevaluationSurplus($company, $intangibleClass, $response['revaluation_surplus_account_id'] ?? null);
            $retainedId = $this->ensureRetainedEarningsAccount($company);
            $lines[] = ['chart_of_account_id' => $revSurplusId, 'type' => 'debit',  'amount' => round($surplus, 2), 'description' => 'Transfer revaluation surplus to retained earnings (IAS 38.87): '.$asset->name];
            $lines[] = ['chart_of_account_id' => $retainedId,   'type' => 'credit', 'amount' => round($surplus, 2), 'description' => 'Revaluation surplus transfer on disposal: '.$asset->name];
        }

        return DB::transaction(function () use ($asset, $user, $company, $lines, $response) {
            $transaction = $this->transactions->record($company, $user, [
                'transaction_date' => $asset->disposal_date->format('Y-m-d'),
                'description'      => 'Intangible asset disposal — '.$asset->name,
                'reference'        => 'INT-DISPOSAL-'.($asset->reference ?: $asset->id),
                'status'           => 'draft',
                'source_document'  => 'intangible:'.$asset->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);

            $asset->forceFill([
                'status'                  => IntangibleAsset::STATUS_DISPOSED,
                'disposal_transaction_id' => $transaction->id,
                'revaluation_surplus'     => 0,
            ])->save();

            return $asset;
        });
    }

    /**
     * IAS 38.75 Revaluation Model — only permitted if an active market exists
     * for the intangible. Mirrors the PPE revaluation flow.
     */
    public function postRevaluationWithAi(
        IntangibleAsset $asset,
        User $user,
        float $newCarryingAmount,
        string $date,
    ): IntangibleAsset {
        if ($asset->status === IntangibleAsset::STATUS_DISPOSED) {
            throw new InvalidArgumentException('Cannot revalue a disposed intangible asset.');
        }
        if ($asset->isGoodwill()) {
            throw new InvalidArgumentException('Goodwill cannot be revalued (IAS 36 / IFRS 3).');
        }
        if ($asset->intangibleClass?->accounting_policy !== IntangibleClass::POLICY_REVALUATION) {
            throw new InvalidArgumentException("Intangible class uses the cost model — revaluation requires the revaluation model (IAS 38.75 — active market).");
        }

        $oldCarrying = $asset->netBookValue($date);
        $movement    = round($newCarryingAmount - $oldCarrying, 2);

        if (abs($movement) < 0.01) {
            throw new InvalidArgumentException('No revaluation movement — new carrying amount equals current carrying amount.');
        }

        $company         = $asset->company;
        $intangibleClass = $asset->intangibleClass;

        $accounts = $this->companyAccounts($company);
        $response = $this->prompt($asset, $accounts, 'revaluation', [
            'old_carrying' => $oldCarrying,
            'new_carrying' => $newCarryingAmount,
            'movement'     => $movement,
        ]);

        $costId       = $this->resolveCost($company, $intangibleClass, $response['cost_account_id'] ?? null);
        $accAmortId   = $this->resolveAccumulatedAmortisation($company, $intangibleClass, $response['accumulated_amortisation_account_id'] ?? null);
        $revSurplusId = $this->resolveRevaluationSurplus($company, $intangibleClass, $response['revaluation_surplus_account_id'] ?? null);

        $accAmort = $asset->accumulatedAmortisation($date);
        $oldCost  = (float) $asset->cost;
        $newCost  = round($newCarryingAmount + (float) ($asset->residual_value ?? 0), 2);

        $lines = [];

        if ($movement > 0) {
            // Upward: eliminate acc. amortisation, increase cost, credit OCI surplus.
            if ($accAmort > 0) {
                $lines[] = ['chart_of_account_id' => $accAmortId, 'type' => 'debit',  'amount' => round($accAmort, 2), 'description' => 'Eliminate acc. amortisation on revaluation: '.$asset->name];
                $lines[] = ['chart_of_account_id' => $costId,     'type' => 'credit', 'amount' => round($accAmort, 2), 'description' => 'Eliminate acc. amortisation on revaluation: '.$asset->name];
            }
            $lines[] = ['chart_of_account_id' => $costId,       'type' => 'debit',  'amount' => $movement, 'description' => 'Revaluation increment — '.$asset->name];
            $lines[] = ['chart_of_account_id' => $revSurplusId, 'type' => 'credit', 'amount' => $movement, 'description' => 'Revaluation surplus (OCI) — '.$asset->name];
        } else {
            $surplus   = (float) ($asset->revaluation_surplus ?? 0);
            $writeDown = abs($movement);
            $fromOci   = min($surplus, $writeDown);
            $fromPl    = round($writeDown - $fromOci, 2);

            if ($accAmort > 0) {
                $lines[] = ['chart_of_account_id' => $accAmortId, 'type' => 'debit',  'amount' => round($accAmort, 2), 'description' => 'Eliminate acc. amortisation on revaluation: '.$asset->name];
                $lines[] = ['chart_of_account_id' => $costId,     'type' => 'credit', 'amount' => round($accAmort, 2), 'description' => 'Eliminate acc. amortisation on revaluation: '.$asset->name];
            }
            if ($fromOci > 0) {
                $lines[] = ['chart_of_account_id' => $revSurplusId, 'type' => 'debit',  'amount' => round($fromOci, 2), 'description' => 'Revaluation decrease from OCI surplus — '.$asset->name];
                $lines[] = ['chart_of_account_id' => $costId,       'type' => 'credit', 'amount' => round($fromOci, 2), 'description' => 'Revaluation decrease — '.$asset->name];
            }
            if ($fromPl > 0) {
                $impLossId = $this->resolveImpairmentLoss($company, $intangibleClass, $response['impairment_loss_account_id'] ?? null);
                $lines[]   = ['chart_of_account_id' => $impLossId, 'type' => 'debit',  'amount' => $fromPl, 'description' => 'Revaluation decrease to P&L — '.$asset->name];
                $lines[]   = ['chart_of_account_id' => $costId,    'type' => 'credit', 'amount' => $fromPl, 'description' => 'Revaluation decrease — '.$asset->name];
            }
        }

        return DB::transaction(function () use ($asset, $user, $company, $lines, $response, $movement, $date, $newCost) {
            $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description'      => 'IAS 38 Revaluation — '.$asset->name,
                'reference'        => 'INT-REVAL-'.str_replace('-', '', $date).'-'.($asset->reference ?: $asset->id),
                'status'           => 'draft',
                'source_document'  => 'intangible:'.$asset->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);

            $surplusDelta = $movement > 0 ? $movement : -min(abs($movement), (float) ($asset->revaluation_surplus ?? 0));
            $asset->forceFill([
                'cost'                => $newCost,
                'revaluation_surplus' => max(0, round((float) ($asset->revaluation_surplus ?? 0) + $surplusDelta, 2)),
            ])->save();

            return $asset;
        });
    }

    /**
     * IAS 36 Impairment for intangible assets. Mirrors PPE: cost-model assets
     * route the full loss to P&L; revaluation-model assets first exhaust the
     * OCI surplus before routing the excess to P&L (IAS 36.60).
     */
    public function postImpairmentWithAi(
        IntangibleAsset $asset,
        User $user,
        float $impairmentAmount,
        string $date,
        string $reason = '',
    ): IntangibleAsset {
        if ($asset->status === IntangibleAsset::STATUS_DISPOSED) {
            throw new InvalidArgumentException('Cannot impair a disposed intangible asset.');
        }
        if ($impairmentAmount <= 0) {
            throw new InvalidArgumentException('Impairment amount must be positive.');
        }

        $carrying = $asset->netBookValue($date);
        if ($impairmentAmount > $carrying) {
            throw new InvalidArgumentException("Impairment amount ({$impairmentAmount}) exceeds carrying amount ({$carrying}).");
        }

        $company         = $asset->company;
        $intangibleClass = $asset->intangibleClass;

        $accounts = $this->companyAccounts($company);
        $response = $this->prompt($asset, $accounts, 'impairment', [
            'impairment_amount' => $impairmentAmount,
            'carrying_amount'   => $carrying,
            'reason'            => $reason,
        ]);

        $accImpId = $this->resolveAccumulatedImpairment($company, $intangibleClass, $response['accumulated_impairment_account_id'] ?? null);

        $lines   = [];
        $surplus = (float) ($asset->revaluation_surplus ?? 0);

        if ($intangibleClass && $intangibleClass->accounting_policy === IntangibleClass::POLICY_REVALUATION && $surplus > 0) {
            $revSurplusId = $this->resolveRevaluationSurplus($company, $intangibleClass, $response['revaluation_surplus_account_id'] ?? null);
            $fromOci      = min($surplus, $impairmentAmount);
            $fromPl       = round($impairmentAmount - $fromOci, 2);

            $lines[] = ['chart_of_account_id' => $revSurplusId, 'type' => 'debit',  'amount' => round($fromOci, 2), 'description' => 'Impairment reduces revaluation surplus — '.$asset->name];
            $lines[] = ['chart_of_account_id' => $accImpId,     'type' => 'credit', 'amount' => round($fromOci, 2), 'description' => 'Accumulated impairment — '.$asset->name];

            if ($fromPl > 0) {
                $impLossId = $this->resolveImpairmentLoss($company, $intangibleClass, $response['impairment_loss_account_id'] ?? null);
                $lines[]   = ['chart_of_account_id' => $impLossId, 'type' => 'debit',  'amount' => $fromPl, 'description' => 'Impairment loss (P&L) — '.$asset->name];
                $lines[]   = ['chart_of_account_id' => $accImpId,  'type' => 'credit', 'amount' => $fromPl, 'description' => 'Accumulated impairment — '.$asset->name];
            }
        } else {
            $impLossId = $this->resolveImpairmentLoss($company, $intangibleClass, $response['impairment_loss_account_id'] ?? null);
            $lines[]   = ['chart_of_account_id' => $impLossId, 'type' => 'debit',  'amount' => round($impairmentAmount, 2), 'description' => 'Impairment loss (IAS 36) — '.$asset->name.' '.$reason];
            $lines[]   = ['chart_of_account_id' => $accImpId,  'type' => 'credit', 'amount' => round($impairmentAmount, 2), 'description' => 'Accumulated impairment — '.$asset->name];
        }

        return DB::transaction(function () use ($asset, $user, $company, $lines, $response, $impairmentAmount, $date) {
            $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description'      => 'IAS 36 Impairment — '.$asset->name,
                'reference'        => 'INT-IMP-'.str_replace('-', '', $date).'-'.($asset->reference ?: $asset->id),
                'status'           => 'draft',
                'source_document'  => 'intangible:'.$asset->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);

            $surplusUsed = min((float) ($asset->revaluation_surplus ?? 0), $impairmentAmount);
            $asset->forceFill([
                'accumulated_impairment' => round((float) ($asset->accumulated_impairment ?? 0) + $impairmentAmount, 2),
                'revaluation_surplus'    => max(0, round((float) ($asset->revaluation_surplus ?? 0) - $surplusUsed, 2)),
            ])->save();

            return $asset;
        });
    }

    /**
     * IAS 36.114 Impairment Reversal. IAS 36.124 prohibits reversing goodwill
     * impairments — explicitly blocked here.
     */
    public function postImpairmentReversalWithAi(
        IntangibleAsset $asset,
        User $user,
        float $reversalAmount,
        string $date,
    ): IntangibleAsset {
        if ($asset->status === IntangibleAsset::STATUS_DISPOSED) {
            throw new InvalidArgumentException('Cannot reverse impairment on a disposed intangible asset.');
        }
        if ($asset->isGoodwill()) {
            throw new InvalidArgumentException('IAS 36.124 prohibits reversing impairments recognised for goodwill.');
        }

        $totalImpairment = (float) ($asset->accumulated_impairment ?? 0);
        if ($totalImpairment <= 0) {
            throw new InvalidArgumentException('This intangible has no accumulated impairment to reverse.');
        }

        $reversalAmount = min(round($reversalAmount, 2), $totalImpairment);
        if ($reversalAmount <= 0) {
            throw new InvalidArgumentException('Reversal amount must be positive.');
        }

        $company         = $asset->company;
        $intangibleClass = $asset->intangibleClass;

        $accounts = $this->companyAccounts($company);
        $response = $this->prompt($asset, $accounts, 'impairment_reversal', [
            'reversal_amount'        => $reversalAmount,
            'accumulated_impairment' => $totalImpairment,
        ]);

        $accImpId    = $this->resolveAccumulatedImpairment($company, $intangibleClass, $response['accumulated_impairment_account_id'] ?? null);
        $revIncomeId = $this->resolveImpairmentReversal($company, $intangibleClass, $response['impairment_reversal_account_id'] ?? null);

        $lines = [
            ['chart_of_account_id' => $accImpId,    'type' => 'debit',  'amount' => $reversalAmount, 'description' => 'Impairment reversal — '.$asset->name],
            ['chart_of_account_id' => $revIncomeId, 'type' => 'credit', 'amount' => $reversalAmount, 'description' => 'Impairment reversal income (IAS 36.114) — '.$asset->name],
        ];

        return DB::transaction(function () use ($asset, $user, $company, $lines, $response, $reversalAmount, $date) {
            $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description'      => 'IAS 36 Impairment Reversal — '.$asset->name,
                'reference'        => 'INT-IMPREV-'.str_replace('-', '', $date).'-'.($asset->reference ?: $asset->id),
                'status'           => 'draft',
                'source_document'  => 'intangible:'.$asset->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);

            $asset->forceFill([
                'accumulated_impairment' => max(0, round((float) ($asset->accumulated_impairment ?? 0) - $reversalAmount, 2)),
            ])->save();

            return $asset;
        });
    }

    /**
     * IAS 38.18 Subsequent Expenditure — most subsequent costs on intangibles
     * are expensed (IAS 38.20). Use this only when the cost extends the future
     * economic benefits (e.g. software major upgrade).
     */
    public function postSubsequentCostWithAi(
        IntangibleAsset $asset,
        User $user,
        float $amount,
        string $date,
        string $description = '',
    ): IntangibleAsset {
        if ($asset->status === IntangibleAsset::STATUS_DISPOSED) {
            throw new InvalidArgumentException('Cannot capitalise costs on a disposed intangible asset.');
        }
        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be positive.');
        }

        $company         = $asset->company;
        $intangibleClass = $asset->intangibleClass;

        $accounts = $this->companyAccounts($company);
        $response = $this->prompt($asset, $accounts, 'subsequent_cost', [
            'amount'      => $amount,
            'description' => $description,
        ]);

        $costId = $this->resolveCost($company, $intangibleClass, $response['cost_account_id'] ?? null);
        $bankId = $response['bank_or_payable_account_id'] ?? null;
        if (! $bankId) {
            throw new RuntimeException('AI could not determine a bank/payable account for subsequent capitalisation.');
        }

        $lines = [
            ['chart_of_account_id' => $costId, 'type' => 'debit',  'amount' => round($amount, 2), 'description' => ($description ?: 'Subsequent cost').' — '.$asset->name],
            ['chart_of_account_id' => $bankId, 'type' => 'credit', 'amount' => round($amount, 2), 'description' => ($description ?: 'Subsequent cost').' — '.$asset->name],
        ];

        return DB::transaction(function () use ($asset, $user, $company, $lines, $response, $amount, $date, $description) {
            $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description'      => 'IAS 38 Subsequent cost — '.($description ?: $asset->name),
                'reference'        => 'INT-SUBCAP-'.str_replace('-', '', $date).'-'.($asset->reference ?: $asset->id),
                'status'           => 'draft',
                'source_document'  => 'intangible:'.$asset->id,
                'notes'            => 'Subsequent capitalisation posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);

            $asset->forceFill([
                'cost' => round((float) $asset->cost + $amount, 2),
            ])->save();

            return $asset;
        });
    }

    // ─── Account resolution helpers ──────────────────────────────────────

    private function resolveCost(Company $company, ?IntangibleClass $class, ?int $aiPick): int
    {
        if ($aiPick && ChartOfAccount::where('company_id', $company->id)->where('id', $aiPick)->exists()) {
            return $aiPick;
        }
        $name = $class
            ? "Intangible Assets — {$class->name} (Cost)"
            : "Intangible Assets (Cost)";
        return $this->ensureAccount($company, [
            'account_code'       => $this->nextCode($company, '1008000', '1008079'),
            'account_name'       => $name,
            'account_type'       => 'assets',
            'category'           => 'Intangible Assets',
            'cash_flow_category' => 'investing',
            'is_contra'          => false,
            'is_intangible'      => true,
        ], self::INT_COST_CODE_FALLBACK)->id;
    }

    private function resolveAccumulatedAmortisation(Company $company, ?IntangibleClass $class, ?int $aiPick): int
    {
        if ($aiPick && ChartOfAccount::where('company_id', $company->id)->where('id', $aiPick)->exists()) {
            return $aiPick;
        }
        $name = $class
            ? "Accumulated Amortisation — {$class->name}"
            : "Accumulated Amortisation — Intangibles";
        return $this->ensureAccount($company, [
            'account_code'       => $this->nextCode($company, '1008090', '1008098'),
            'account_name'       => $name,
            'account_type'       => 'assets',
            'category'           => 'Intangible Assets',
            'cash_flow_category' => 'investing',
            'is_contra'          => true,
            'is_intangible'      => true,
        ], self::INT_ACC_AMORT_CODE_FALLBACK)->id;
    }

    private function resolveAccumulatedImpairment(Company $company, ?IntangibleClass $class, ?int $aiPick): int
    {
        if ($aiPick && ChartOfAccount::where('company_id', $company->id)->where('id', $aiPick)->exists()) {
            return $aiPick;
        }
        $name = $class
            ? "Accumulated Impairment — Intangible {$class->name}"
            : "Accumulated Impairment — Intangibles";
        return $this->ensureAccount($company, [
            'account_code'       => $this->nextCode($company, '1008080', '1008089'),
            'account_name'       => $name,
            'account_type'       => 'assets',
            'category'           => 'Intangible Assets',
            'cash_flow_category' => 'investing',
            'is_contra'          => true,
            'is_intangible'      => true,
        ], self::INT_ACC_IMPAIRMENT_CODE_FALLBACK)->id;
    }

    private function resolveImpairmentLoss(Company $company, ?IntangibleClass $class, ?int $aiPick): int
    {
        if ($aiPick && ChartOfAccount::where('company_id', $company->id)->where('id', $aiPick)->exists()) {
            return $aiPick;
        }
        return $this->ensureAccount($company, [
            'account_code'       => $this->nextCode($company, '6008050', '6008099'),
            'account_name'       => 'Impairment Loss — Intangible Assets',
            'account_type'       => 'expenses',
            'category'           => 'Depreciation & Amortisation',
            'cash_flow_category' => 'operating',
            'is_contra'          => false,
        ], self::INT_IMPAIRMENT_LOSS_CODE_FALLBACK)->id;
    }

    private function resolveImpairmentReversal(Company $company, ?IntangibleClass $class, ?int $aiPick): int
    {
        if ($aiPick && ChartOfAccount::where('company_id', $company->id)->where('id', $aiPick)->exists()) {
            return $aiPick;
        }
        return $this->ensureAccount($company, [
            'account_code'       => $this->nextCode($company, '4009050', '4009099'),
            'account_name'       => 'Impairment Reversal — Intangible Assets',
            'account_type'       => 'income',
            'category'           => 'Other Income',
            'cash_flow_category' => 'operating',
            'is_contra'          => false,
        ], self::INT_IMPAIRMENT_REVERSAL_CODE_FALLBACK)->id;
    }

    private function resolveRevaluationSurplus(Company $company, ?IntangibleClass $class, ?int $aiPick): int
    {
        if ($aiPick && ChartOfAccount::where('company_id', $company->id)->where('id', $aiPick)->exists()) {
            return $aiPick;
        }
        return $this->ensureAccount($company, [
            'account_code'       => $this->nextCode($company, '3009050', '3009099'),
            'account_name'       => 'Revaluation Surplus — Intangible Assets',
            'account_type'       => 'equity',
            'category'           => 'Other Comprehensive Income',
            'cash_flow_category' => null,
            'is_contra'          => false,
            'is_oci'             => true,
        ], self::INT_REVALUATION_SURPLUS_CODE_FALLBACK)->id;
    }

    private function resolveGainLoss(Company $company, ?int $aiPick, float $gainLoss): int
    {
        if ($aiPick && ChartOfAccount::where('company_id', $company->id)->where('id', $aiPick)->exists()) {
            return $aiPick;
        }
        if ($gainLoss > 0) {
            return $this->ensureAccount($company, [
                'account_code'       => $this->nextCode($company, '4010050', '4010099'),
                'account_name'       => 'Gain on Disposal of Intangibles',
                'account_type'       => 'income',
                'category'           => 'Other Income',
                'cash_flow_category' => 'investing',
                'is_contra'          => false,
            ], self::INT_DISPOSAL_GAIN_CODE_FALLBACK)->id;
        }
        return $this->ensureAccount($company, [
            'account_code'       => $this->nextCode($company, '6009095', '6009099'),
            'account_name'       => 'Loss on Disposal of Intangibles',
            'account_type'       => 'expenses',
            'category'           => 'Other Expenses',
            'cash_flow_category' => 'investing',
            'is_contra'          => false,
        ], self::INT_DISPOSAL_LOSS_CODE_FALLBACK)->id;
    }

    public function postMonthlyAmortisationWithAi(IntangibleAsset $asset, User $user, \Carbon\CarbonImmutable $monthEnd): ?IntangibleAsset
    {
        if ($asset->isDisposed() || $asset->isIndefiniteLife()) {
            return null;
        }

        if ($asset->last_amortisation_posted_on
            && \Carbon\CarbonImmutable::parse($asset->last_amortisation_posted_on)->greaterThanOrEqualTo($monthEnd)
        ) {
            return null;
        }

        $life = $asset->effectiveUsefulLifeYears();
        if ($life === null || $life <= 0) {
            return null;
        }

        $depreciable = (float) $asset->cost - (float) $asset->residual_value;
        if ($depreciable <= 0) {
            return null;
        }

        $monthly = round($depreciable / ($life * 12), 2);
        if ($monthly <= 0) {
            return null;
        }

        $company = $asset->company;
        $accounts = $this->companyAccounts($company);
        $response = $this->prompt($asset, $accounts, 'amortisation', [
            'month_end' => $monthEnd->format('Y-m-d'),
            'monthly_amount' => $monthly,
        ]);

        $amortExpenseId = $response['amortisation_expense_account_id']
            ?? $this->ensureAccount($company, [
                'account_code'  => $this->nextCode($company, '6007050', '6007099'),
                'account_name'  => 'Amortisation Expense — Intangibles',
                'account_type'  => 'expenses',
                'category'      => 'Depreciation & Amortisation',
                'cash_flow_category' => 'operating',
                'is_contra'     => false,
            ], self::INT_AMORT_EXPENSE_CODE_FALLBACK)->id;

        $accAmortId = $response['accumulated_amortisation_account_id']
            ?? $this->ensureAccount($company, [
                'account_code'  => $this->nextCode($company, '1008090', '1008099'),
                'account_name'  => 'Accumulated Amortisation — Intangibles',
                'account_type'  => 'assets',
                'is_contra'     => true,
                'cash_flow_category' => 'operating',
            ], self::INT_ACC_AMORT_CODE_FALLBACK)->id;

        $lines = [
            ['chart_of_account_id' => $amortExpenseId, 'type' => 'debit',  'amount' => $monthly, 'description' => 'Amortisation '.$monthEnd->format('Y-m').': '.$asset->name],
            ['chart_of_account_id' => $accAmortId,     'type' => 'credit', 'amount' => $monthly, 'description' => 'Amortisation '.$monthEnd->format('Y-m').': '.$asset->name],
        ];

        return DB::transaction(function () use ($asset, $user, $company, $lines, $monthEnd, $monthly, $response) {
            $txn = $this->transactions->record($company, $user, [
                'transaction_date' => $monthEnd->format('Y-m-d'),
                'description' => 'Monthly amortisation — '.$asset->name.' ('.$monthEnd->format('M Y').')',
                'reference' => 'AMORT-'.$monthEnd->format('Ym').'-'.($asset->reference ?: $asset->id),
                'status' => 'draft',
                'source_document' => 'intangible:'.$asset->id,
                'notes' => 'Auto-posted amortisation. '.($response['reasoning'] ?? ''),
                'lines' => $lines,
            ]);

            IntangibleAssetEvent::create([
                'intangible_asset_id' => $asset->id,
                'event_type'          => IntangibleAssetEvent::TYPE_AMORTISATION,
                'event_date'          => $monthEnd->format('Y-m-d'),
                'amount'              => $monthly,
                'description'         => 'Amortisation '.$monthEnd->format('M Y').': R '.number_format($monthly, 2),
                'journal_status'      => IntangibleAssetEvent::STATUS_POSTED,
                'transaction_id'      => $txn->id,
            ]);

            $asset->forceFill(['last_amortisation_posted_on' => $monthEnd->format('Y-m-d')])->save();
            return $asset;
        });
    }

    /** @return \Illuminate\Support\Collection<int, ChartOfAccount> */
    private function companyAccounts(Company $company)
    {
        if (isset($this->accountCache[$company->id])) {
            return $this->accountCache[$company->id];
        }

        return $this->accountCache[$company->id] = $this->accountSearch->searchMultiple($company->id, [
            'intangible asset cost IAS 38',
            'accumulated amortisation contra intangible',
            'amortisation expense operating',
            'accumulated impairment intangible contra',
            'impairment loss intangible expense',
            'revaluation surplus OCI equity intangible',
            'bank cash proceeds asset',
            'accounts payable creditor liability',
            'gain loss disposal intangible',
        ], 10);
    }

    /** @param array<string, mixed> $attrs */
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

        return ChartOfAccount::create(array_merge([
            'company_id'      => $company->id,
            'is_active'       => true,
            'opening_balance' => 0,
        ], $attrs));
    }

    private function ensureRetainedEarningsAccount(Company $company): int
    {
        $existing = ChartOfAccount::where('company_id', $company->id)
            ->where('account_name', 'like', '%retained%earn%')
            ->first();
        if ($existing) return $existing->id;

        return ChartOfAccount::create([
            'company_id'    => $company->id,
            'account_code'  => '3002000',
            'account_name'  => 'Retained Earnings',
            'account_type'  => 'equity',
            'is_active'     => true,
            'is_contra'     => false,
        ])->id;
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

    /** @param \Illuminate\Support\Collection<int, ChartOfAccount> $accounts */
    private function prompt(IntangibleAsset $asset, $accounts, string $kind, array $extra = []): array
    {
        $list = $accounts
            ->map(fn (ChartOfAccount $a) => "- id={$a->id}, code={$a->account_code}, name=\"{$a->account_name}\", type={$a->account_type}".($a->is_contra ? ' (contra)' : ''))
            ->implode("\n");

        $policy        = $asset->intangibleClass?->accounting_policy ?? 'cost';
        $indefinite    = $asset->isIndefiniteLife() ? 'yes (no amortisation, IAS 38.107)' : 'no';

        $details = "Intangible asset: {$asset->name} (ref {$asset->reference})\n"
            ."Class: {$asset->intangibleClass?->name} (accounting policy: {$policy})\n"
            ."Indefinite life: {$indefinite}\n"
            ."Cost: {$asset->cost}, residual: {$asset->residual_value}, useful life: {$asset->useful_life_years} years\n"
            ."Accumulated impairment: ".($asset->accumulated_impairment ?? 0)."\n"
            ."Revaluation surplus (OCI): ".($asset->revaluation_surplus ?? 0)."\n"
            ."Acquired: ".optional($asset->acquisition_date)->format('Y-m-d')."\n"
            .($asset->disposal_date ? "Disposed: {$asset->disposal_date->format('Y-m-d')}, proceeds: {$asset->disposal_proceeds}\n" : '');

        $extraStr = empty($extra) ? '' : "\nAdditional context:\n".json_encode($extra, JSON_PRETTY_PRINT)."\n";

        $prompt = "Posting type: {$kind}\n\n{$details}{$extraStr}\nChart of accounts:\n{$list}\n\nPick the IFRS-appropriate accounts for this IAS 38 posting.";

        $history = $this->agentHistory->recall('intangible_asset_posting', 'intangible_asset', $asset->id);
        $fullPrompt = $history ? $history."\n\n".$prompt : $prompt;

        $response = (new IntangibleAssetPostingAgent)->prompt($fullPrompt)->toArray();
        $this->agentHistory->remember('intangible_asset_posting', 'intangible_asset', $asset->id, $prompt, json_encode($response));

        return $response;
    }
}
