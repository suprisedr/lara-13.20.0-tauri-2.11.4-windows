<?php

namespace App\Services;

use App\Ai\Agents\AssetPostingAgent;
use App\Models\Asset;
use App\Models\AssetEvent;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\PpeClass;
use App\Models\PpeClassAccountLink;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Posts asset acquisitions, disposals and monthly depreciation to the GL.
 *
 * The AI agent picks accounts where possible. When an IFRS-required role is
 * missing (e.g. accumulated depreciation contra not yet on the COA), the
 * service deterministically creates the account, links it to the asset's
 * PPE class, and retries the role lookup once.
 */
class AssetPostingService
{
    /** Account codes used to seed missing PPE accounts (within the 1500–1999 PPE band). */
    private const PPE_COST_CODE_FALLBACK                  = '1006000';
    private const PPE_ACC_DEP_CODE_FALLBACK               = '1006099';
    private const PPE_DEP_EXPENSE_CODE_FALLBACK           = '6007000';
    private const PPE_DISPOSAL_GAIN_CODE_FALLBACK         = '4010000';
    private const PPE_DISPOSAL_LOSS_CODE_FALLBACK         = '6009090';
    private const PPE_REVALUATION_SURPLUS_CODE_FALLBACK   = '3009000';
    private const PPE_IMPAIRMENT_LOSS_CODE_FALLBACK       = '6008000';
    private const PPE_ACC_IMPAIRMENT_CODE_FALLBACK        = '1006098';
    private const PPE_IMPAIRMENT_REVERSAL_CODE_FALLBACK   = '4009000';
    private const DEFERRED_TAX_LIABILITY_CODE_FALLBACK    = '2009000';
    private const DEFERRED_TAX_EXPENSE_CODE_FALLBACK      = '7009000';

    private array $accountCache = [];

    public function __construct(
        private readonly TransactionService $transactions,
        private readonly AccountVectorSearchService $accountSearch,
    ) {}

    public function postAcquisitionWithAi(Asset $asset, User $user): Asset
    {
        if ($asset->posting_transaction_id) {
            throw new InvalidArgumentException('This asset has already been posted.');
        }

        $company = $asset->company;
        $ppeClass = $asset->ppeClass;
        if (! $ppeClass) {
            throw new InvalidArgumentException('Asset has no PPE class — cannot determine IFRS accounts.');
        }

        $accounts = $this->companyAccounts($company);
        $response = $this->prompt($asset, $accounts, 'acquisition');

        $costId = $this->resolveAccount(
            $company, $ppeClass,
            $response['cost_account_id'] ?? null,
            PpeClassAccountLink::ROLE_COST,
            fn () => $this->ensureCostAccount($company, $ppeClass),
        );
        $bankId = $response['bank_or_payable_account_id'] ?? null;
        if (! $bankId) {
            throw new RuntimeException('AI could not determine a bank/payable account for acquisition.');
        }

        $cost = (float) $asset->cost;
        $lines = [
            ['chart_of_account_id' => $costId, 'type' => 'debit',  'amount' => $cost, 'description' => 'Acquisition: '.$asset->name],
            ['chart_of_account_id' => $bankId, 'type' => 'credit', 'amount' => $cost, 'description' => 'Acquisition: '.$asset->name],
        ];

        return DB::transaction(function () use ($asset, $user, $company, $lines, $response) {
            $transaction = $this->transactions->record($company, $user, [
                'transaction_date' => optional($asset->acquisition_date)->format('Y-m-d') ?? now()->toDateString(),
                'description' => 'Asset acquisition — '.$asset->name,
                'reference' => $asset->asset_tag ?: 'ASSET-'.$asset->id,
                'status' => 'draft',
                'source_document' => 'asset:'.$asset->id,
                'notes' => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines' => $lines,
            ]);

            $asset->forceFill([
                'status' => Asset::STATUS_ACTIVE,
                'posted_at' => now(),
                'posting_transaction_id' => $transaction->id,
            ])->save();

            return $asset;
        });
    }

    public function postDisposalWithAi(Asset $asset, User $user): Asset
    {
        if ($asset->disposal_transaction_id) {
            throw new InvalidArgumentException('This asset disposal has already been posted.');
        }
        if (! $asset->disposal_date) {
            throw new InvalidArgumentException('Asset has no disposal date.');
        }

        $company = $asset->company;
        $ppeClass = $asset->ppeClass;
        if (! $ppeClass) {
            throw new InvalidArgumentException('Asset has no PPE class — cannot determine IFRS accounts.');
        }

        $accounts = $this->companyAccounts($company);
        $response = $this->prompt($asset, $accounts, 'disposal');

        $costId = $this->resolveAccount($company, $ppeClass,
            $response['cost_account_id'] ?? null, PpeClassAccountLink::ROLE_COST,
            fn () => $this->ensureCostAccount($company, $ppeClass));
        $accDepId = $this->resolveAccount($company, $ppeClass,
            $response['accumulated_depreciation_account_id'] ?? null, PpeClassAccountLink::ROLE_ACCUMULATED_DEPRECIATION,
            fn () => $this->ensureAccumulatedDepreciationAccount($company, $ppeClass));
        $proceedsId = $response['proceeds_account_id'] ?? null;
        if (! $proceedsId) {
            throw new RuntimeException('AI could not determine a proceeds (bank) account for disposal.');
        }

        $cost   = (float) $asset->cost;
        $accDep = $asset->accumulatedDepreciation($asset->disposal_date->format('Y-m-d'));
        $accImp = (float) ($asset->accumulated_impairment ?? 0);
        $carrying = round($cost - $accDep - $accImp, 2);
        $proceeds = (float) ($asset->disposal_proceeds ?? 0);
        $gainLoss = round($proceeds - $carrying, 2);

        $lines = [];
        if ($proceeds >= 0.01) {
            $lines[] = ['chart_of_account_id' => $proceedsId, 'type' => 'debit',  'amount' => $proceeds, 'description' => 'Disposal proceeds: '.$asset->name];
        }
        if (round($accDep, 2) >= 0.01) {
            $lines[] = ['chart_of_account_id' => $accDepId, 'type' => 'debit', 'amount' => round($accDep, 2), 'description' => 'Derecognise acc. depreciation: '.$asset->name];
        }
        if (round($accImp, 2) >= 0.01) {
            $accImpId = $this->resolveAccount($company, $ppeClass,
                $response['accumulated_impairment_account_id'] ?? null, PpeClassAccountLink::ROLE_ACCUMULATED_IMPAIRMENT,
                fn () => $this->ensureAccumulatedImpairmentAccount($company, $ppeClass));
            $lines[] = ['chart_of_account_id' => $accImpId, 'type' => 'debit', 'amount' => round($accImp, 2), 'description' => 'Derecognise acc. impairment: '.$asset->name];
        }
        $lines[] = ['chart_of_account_id' => $costId, 'type' => 'credit', 'amount' => $cost, 'description' => 'Derecognise cost: '.$asset->name];

        if (abs($gainLoss) >= 0.01) {
            $gainLossAccountId = $this->resolveAccount($company, $ppeClass,
                $response['gain_loss_account_id'] ?? null, PpeClassAccountLink::ROLE_DISPOSAL,
                fn () => $this->ensureGainLossAccount($company, $ppeClass, $gainLoss));

            $lines[] = $gainLoss > 0
                ? ['chart_of_account_id' => $gainLossAccountId, 'type' => 'credit', 'amount' => $gainLoss, 'description' => 'Gain on disposal: '.$asset->name]
                : ['chart_of_account_id' => $gainLossAccountId, 'type' => 'debit',  'amount' => abs($gainLoss), 'description' => 'Loss on disposal: '.$asset->name];
        }

        // IAS 16.41: transfer remaining revaluation surplus to retained earnings on disposal
        $surplus = (float) ($asset->revaluation_surplus ?? 0);
        if ($ppeClass && $ppeClass->accounting_policy === 'revaluation' && round($surplus, 2) >= 0.01) {
            $revSurplusId = $this->resolveAccount($company, $ppeClass,
                $response['revaluation_surplus_account_id'] ?? null, PpeClassAccountLink::ROLE_REVALUATION,
                fn () => $this->ensureRevaluationSurplusAccount($company, $ppeClass));
            $retainedId = $this->ensureRetainedEarningsAccount($company);
            $lines[] = ['chart_of_account_id' => $revSurplusId, 'type' => 'debit',  'amount' => round($surplus, 2), 'description' => 'Transfer revaluation surplus to retained earnings (IAS 16.41): '.$asset->name];
            $lines[] = ['chart_of_account_id' => $retainedId,   'type' => 'credit', 'amount' => round($surplus, 2), 'description' => 'Revaluation surplus transfer on disposal: '.$asset->name];
        }

        return DB::transaction(function () use ($asset, $user, $company, $lines, $response) {
            $transaction = $this->transactions->record($company, $user, [
                'transaction_date' => $asset->disposal_date->format('Y-m-d'),
                'description' => 'Asset disposal — '.$asset->name,
                'reference' => 'DISPOSAL-'.($asset->asset_tag ?: $asset->id),
                'status' => 'draft',
                'source_document' => 'asset:'.$asset->id,
                'notes' => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines' => $lines,
            ]);

            // Disposed assets are kept in the same table but flagged — the
            // `disposed()` scope on Asset is the "disposed assets register".
            $asset->forceFill([
                'status' => Asset::STATUS_DISPOSED,
                'disposal_transaction_id' => $transaction->id,
                'revaluation_surplus' => 0,
            ])->save();

            return $asset;
        });
    }

    /**
     * Post one month of straight-line depreciation for an asset.
     * Returns null if the asset is fully depreciated or already posted for the period.
     */
    public function postMonthlyDepreciationWithAi(Asset $asset, User $user, CarbonImmutable $monthEnd): ?Asset
    {
        if ($asset->status === Asset::STATUS_DISPOSED) {
            return null;
        }
        if ($asset->last_depreciation_posted_on
            && CarbonImmutable::parse($asset->last_depreciation_posted_on)->greaterThanOrEqualTo($monthEnd)
        ) {
            return null;
        }

        $monthly = $this->monthlyDepreciation($asset, $monthEnd);
        if ($monthly <= 0) {
            return null;
        }

        $company = $asset->company;
        $ppeClass = $asset->ppeClass;
        if (! $ppeClass) {
            return null;
        }

        $accounts = $this->companyAccounts($company);
        $response = $this->prompt($asset, $accounts, 'depreciation');

        $depExpenseId = $this->resolveAccount($company, $ppeClass,
            $response['depreciation_expense_account_id'] ?? null, PpeClassAccountLink::ROLE_DEPRECIATION_EXPENSE,
            fn () => $this->ensureDepreciationExpenseAccount($company, $ppeClass));
        $accDepId = $this->resolveAccount($company, $ppeClass,
            $response['accumulated_depreciation_account_id'] ?? null, PpeClassAccountLink::ROLE_ACCUMULATED_DEPRECIATION,
            fn () => $this->ensureAccumulatedDepreciationAccount($company, $ppeClass));

        $lines = [
            ['chart_of_account_id' => $depExpenseId, 'type' => 'debit',  'amount' => $monthly, 'description' => 'Depreciation '.$monthEnd->format('Y-m').': '.$asset->name],
            ['chart_of_account_id' => $accDepId,     'type' => 'credit', 'amount' => $monthly, 'description' => 'Depreciation '.$monthEnd->format('Y-m').': '.$asset->name],
        ];

        // IAS 12 — deferred tax on temporary difference between accounting and tax depreciation
        $deferredTaxLines = $this->buildDeferredTaxLines($asset, $company, $ppeClass, $monthEnd, $response);

        return DB::transaction(function () use ($asset, $user, $company, $lines, $deferredTaxLines, $monthEnd, $monthly, $response) {
            $txn = $this->transactions->record($company, $user, [
                'transaction_date' => $monthEnd->format('Y-m-d'),
                'description' => 'Monthly depreciation — '.$asset->name.' ('.$monthEnd->format('M Y').')',
                'reference' => 'DEP-'.$monthEnd->format('Ym').'-'.($asset->asset_tag ?: $asset->id),
                'status' => 'draft',
                'source_document' => 'asset:'.$asset->id,
                'notes' => 'Auto-posted depreciation. '.($response['reasoning'] ?? ''),
                'lines' => $lines,
            ]);

            if (! empty($deferredTaxLines)) {
                $this->transactions->record($company, $user, [
                    'transaction_date' => $monthEnd->format('Y-m-d'),
                    'description' => 'IAS 12 deferred tax — '.$asset->name.' ('.$monthEnd->format('M Y').')',
                    'reference' => 'DT-'.$monthEnd->format('Ym').'-'.($asset->asset_tag ?: $asset->id),
                    'status' => 'draft',
                    'source_document' => 'asset:'.$asset->id,
                    'notes' => 'IAS 12 deferred tax on temporary difference (accounting vs SARS depreciation).',
                    'lines' => $deferredTaxLines,
                ]);
            }

            AssetEvent::create([
                'asset_id'       => $asset->id,
                'event_type'     => AssetEvent::TYPE_DEPRECIATION,
                'event_date'     => $monthEnd->format('Y-m-d'),
                'amount'         => $monthly,
                'description'    => 'Depreciation '.$monthEnd->format('M Y').': R '.number_format($monthly, 2),
                'journal_status' => AssetEvent::STATUS_POSTED,
                'transaction_id' => $txn->id,
            ]);

            $asset->forceFill(['last_depreciation_posted_on' => $monthEnd->format('Y-m-d')])->save();
            return $asset;
        });
    }

    /**
     * IAS 12: compute the movement in deferred tax for this month's depreciation.
     *
     * Temporary difference = carrying amount − tax base.
     * When tax depreciation is faster (e.g. SARS 3yr vs IFRS 5yr), carrying > tax base → DTL.
     * We post the monthly CHANGE in the deferred tax balance.
     */
    private function buildDeferredTaxLines(
        Asset $asset,
        Company $company,
        PpeClass $ppeClass,
        CarbonImmutable $monthEnd,
        array $aiResponse,
    ): array {
        $sarsLife = $asset->effectiveSarsWearTearYears();
        if ($sarsLife === null) {
            return [];
        }

        $taxRate = (float) ($company->corporate_tax_rate ?? 27) / 100;
        if ($taxRate <= 0) {
            return [];
        }

        // Monthly accounting depreciation vs monthly SARS wear & tear
        $accountingMonthly = $this->monthlyDepreciation($asset, $monthEnd);
        $sarsMonthly = $asset->monthlySarsDepreciation();
        $difference = $sarsMonthly - $accountingMonthly;

        if (abs($difference) < 0.01) {
            return [];
        }

        $deferredTaxMovement = round(abs($difference) * $taxRate, 2);
        if ($deferredTaxMovement < 0.01) {
            return [];
        }

        $dtlId = $this->resolveAccount($company, $ppeClass,
            $aiResponse['deferred_tax_liability_account_id'] ?? null,
            PpeClassAccountLink::ROLE_DEFERRED_TAX_LIABILITY,
            fn () => $this->ensureDeferredTaxLiabilityAccount($company));
        $dtExpenseId = $this->resolveAccount($company, $ppeClass,
            $aiResponse['deferred_tax_expense_account_id'] ?? null,
            PpeClassAccountLink::ROLE_DEFERRED_TAX_EXPENSE,
            fn () => $this->ensureDeferredTaxExpenseAccount($company));

        $desc = 'IAS 12 deferred tax '.$monthEnd->format('Y-m').': '.$asset->name;

        if ($difference > 0) {
            // Tax dep faster → taxable temporary difference → increase DTL
            return [
                ['chart_of_account_id' => $dtExpenseId, 'type' => 'debit',  'amount' => $deferredTaxMovement, 'description' => $desc],
                ['chart_of_account_id' => $dtlId,       'type' => 'credit', 'amount' => $deferredTaxMovement, 'description' => $desc],
            ];
        }

        // Tax dep slower → deductible temporary difference → decrease DTL (or create DTA)
        return [
            ['chart_of_account_id' => $dtlId,       'type' => 'debit',  'amount' => $deferredTaxMovement, 'description' => $desc],
            ['chart_of_account_id' => $dtExpenseId, 'type' => 'credit', 'amount' => $deferredTaxMovement, 'description' => $desc],
        ];
    }

    // ─── IAS 16 / IAS 36 event postings ──────────────────────────────────────

    /**
     * IAS 16.31-42 Revaluation — post the carrying-amount adjustment to OCI.
     *
     * $newCarryingAmount is the IFRS-fair-value after revaluation.
     * The method computes the gross-up (cost + revaluation increment vs old
     * depreciation reset) or write-down, splits into OCI surplus / P&L loss
     * where the surplus is exhausted, and posts deferred tax on the surplus
     * movement (IAS 12.20).
     */
    public function postRevaluationWithAi(
        Asset $asset,
        User $user,
        float $newCarryingAmount,
        string $date,
    ): Asset {
        if ($asset->status === Asset::STATUS_DISPOSED) {
            throw new InvalidArgumentException('Cannot revalue a disposed asset.');
        }
        if ($asset->ppeClass?->accounting_policy !== 'revaluation') {
            throw new InvalidArgumentException("Asset's PPE class uses the cost model — revaluation is only permitted under the revaluation model (IAS 16.31).");
        }

        $company  = $asset->company;
        $ppeClass = $asset->ppeClass;

        $oldCarrying = $asset->netBookValue($date);
        $movement    = round($newCarryingAmount - $oldCarrying, 2);

        if (abs($movement) < 0.01) {
            throw new InvalidArgumentException('No revaluation movement — new carrying amount equals current carrying amount.');
        }

        $accounts = $this->companyAccounts($company);
        $response = $this->prompt($asset, $accounts, 'revaluation', [
            'old_carrying' => $oldCarrying,
            'new_carrying' => $newCarryingAmount,
            'movement'     => $movement,
        ]);

        $costId = $this->resolveAccount($company, $ppeClass,
            $response['cost_account_id'] ?? null, PpeClassAccountLink::ROLE_COST,
            fn () => $this->ensureCostAccount($company, $ppeClass));
        $accDepId = $this->resolveAccount($company, $ppeClass,
            $response['accumulated_depreciation_account_id'] ?? null, PpeClassAccountLink::ROLE_ACCUMULATED_DEPRECIATION,
            fn () => $this->ensureAccumulatedDepreciationAccount($company, $ppeClass));
        $revSurplusId = $this->resolveAccount($company, $ppeClass,
            $response['revaluation_surplus_account_id'] ?? null, PpeClassAccountLink::ROLE_REVALUATION,
            fn () => $this->ensureRevaluationSurplusAccount($company, $ppeClass));

        $accDep     = $asset->accumulatedDepreciation($date);
        $oldCost    = (float) $asset->cost;
        $newCost    = round($newCarryingAmount + (float) ($asset->residual_value ?? 0), 2);
        $costChange = round($newCost - $oldCost, 2);

        // Under the revaluation model we eliminate accumulated depreciation on
        // restatement and restate cost to the new gross amount.
        $lines = [];
        if ($movement > 0) {
            // Upward revaluation: eliminate acc. dep and increase cost.
            if ($accDep > 0) {
                $lines[] = ['chart_of_account_id' => $accDepId, 'type' => 'debit',  'amount' => round($accDep, 2), 'description' => 'Eliminate acc. dep. on revaluation: '.$asset->name];
                $lines[] = ['chart_of_account_id' => $costId,   'type' => 'credit', 'amount' => round($accDep, 2), 'description' => 'Eliminate acc. dep. on revaluation: '.$asset->name];
            }
            $lines[] = ['chart_of_account_id' => $costId,       'type' => 'debit',  'amount' => $movement, 'description' => 'Revaluation increment — '.$asset->name.' ('.$date.')'];
            $lines[] = ['chart_of_account_id' => $revSurplusId, 'type' => 'credit', 'amount' => $movement, 'description' => 'Revaluation surplus (OCI) — '.$asset->name.' ('.$date.')'];

            // IAS 12.20: deferred tax on the revaluation surplus
            $taxRate = (float) ($company->corporate_tax_rate ?? 27) / 100;
            if ($taxRate > 0) {
                $dtMovement = round($movement * $taxRate, 2);
                if ($dtMovement >= 0.01) {
                    $dtlId = $this->resolveAccount($company, $ppeClass,
                        $response['deferred_tax_liability_account_id'] ?? null,
                        PpeClassAccountLink::ROLE_DEFERRED_TAX_LIABILITY,
                        fn () => $this->ensureDeferredTaxLiabilityAccount($company));
                    // Deferred tax reduces the OCI surplus (Dr OCI / Cr DTL)
                    $lines[] = ['chart_of_account_id' => $revSurplusId, 'type' => 'debit',  'amount' => $dtMovement, 'description' => 'IAS 12 deferred tax on revaluation surplus — '.$asset->name];
                    $lines[] = ['chart_of_account_id' => $dtlId,        'type' => 'credit', 'amount' => $dtMovement, 'description' => 'IAS 12 deferred tax on revaluation surplus — '.$asset->name];
                }
            }
        } else {
            // Downward revaluation — first exhaust any existing OCI surplus,
            // then route the excess to P&L impairment loss (IAS 16.40).
            $surplus     = (float) ($asset->revaluation_surplus ?? 0);
            $writeDown   = abs($movement);
            $fromOci     = min($surplus, $writeDown);
            $fromPl      = round($writeDown - $fromOci, 2);

            if ($accDep > 0) {
                $lines[] = ['chart_of_account_id' => $accDepId,    'type' => 'debit',  'amount' => round($accDep, 2), 'description' => 'Eliminate acc. dep. on revaluation: '.$asset->name];
                $lines[] = ['chart_of_account_id' => $costId,      'type' => 'credit', 'amount' => round($accDep, 2), 'description' => 'Eliminate acc. dep. on revaluation: '.$asset->name];
            }
            if ($fromOci > 0) {
                $lines[] = ['chart_of_account_id' => $revSurplusId, 'type' => 'debit',  'amount' => round($fromOci, 2), 'description' => 'Revaluation decrease from OCI surplus — '.$asset->name];
                $lines[] = ['chart_of_account_id' => $costId,       'type' => 'credit', 'amount' => round($fromOci, 2), 'description' => 'Revaluation decrease — '.$asset->name.' ('.$date.')'];
            }
            if ($fromPl > 0) {
                $impLossId = $this->resolveAccount($company, $ppeClass,
                    $response['impairment_loss_account_id'] ?? null, PpeClassAccountLink::ROLE_IMPAIRMENT_LOSS,
                    fn () => $this->ensureImpairmentLossAccount($company, $ppeClass));
                $lines[] = ['chart_of_account_id' => $impLossId, 'type' => 'debit',  'amount' => $fromPl, 'description' => 'Revaluation decrease to P&L — '.$asset->name.' ('.$date.')'];
                $lines[] = ['chart_of_account_id' => $costId,    'type' => 'credit', 'amount' => $fromPl, 'description' => 'Revaluation decrease — '.$asset->name.' ('.$date.')'];
            }
        }

        return DB::transaction(function () use (
            $asset, $user, $company, $lines, $response, $movement, $date, $newCost, $costChange,
        ) {
            $transaction = $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description'      => 'IAS 16 Revaluation — '.$asset->name,
                'reference'        => 'REVAL-'.str_replace('-', '', $date).'-'.($asset->asset_tag ?: $asset->id),
                'status'           => 'draft',
                'source_document'  => 'asset:'.$asset->id,
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
     * IAS 36 Impairment — recognise a write-down below recoverable amount.
     *
     * Posts Dr Impairment Loss / Cr Accumulated Impairment.
     * If the asset is under the revaluation model, first exhausts the OCI
     * surplus before routing to P&L (IAS 36.60).
     */
    public function postImpairmentWithAi(
        Asset $asset,
        User $user,
        float $impairmentAmount,
        string $date,
        string $reason = '',
    ): Asset {
        if ($asset->status === Asset::STATUS_DISPOSED) {
            throw new InvalidArgumentException('Cannot impair a disposed asset.');
        }
        if ($impairmentAmount <= 0) {
            throw new InvalidArgumentException('Impairment amount must be positive.');
        }

        $carrying = $asset->netBookValue($date);
        if ($impairmentAmount > $carrying) {
            throw new InvalidArgumentException("Impairment amount ({$impairmentAmount}) exceeds carrying amount ({$carrying}). Asset cannot be written below zero.");
        }

        $company  = $asset->company;
        $ppeClass = $asset->ppeClass;

        $accounts = $this->companyAccounts($company);
        $response = $this->prompt($asset, $accounts, 'impairment', [
            'impairment_amount' => $impairmentAmount,
            'carrying_amount'   => $carrying,
            'reason'            => $reason,
        ]);

        $accImpId = $this->resolveAccount($company, $ppeClass,
            $response['accumulated_impairment_account_id'] ?? null, PpeClassAccountLink::ROLE_ACCUMULATED_IMPAIRMENT,
            fn () => $this->ensureAccumulatedImpairmentAccount($company, $ppeClass));

        $lines = [];
        $surplus = (float) ($asset->revaluation_surplus ?? 0);

        if ($ppeClass && $ppeClass->accounting_policy === 'revaluation' && $surplus > 0) {
            // IAS 36.60: exhaust OCI surplus first for revaluation-model assets
            $revSurplusId = $this->resolveAccount($company, $ppeClass,
                $response['revaluation_surplus_account_id'] ?? null, PpeClassAccountLink::ROLE_REVALUATION,
                fn () => $this->ensureRevaluationSurplusAccount($company, $ppeClass));

            $fromOci = min($surplus, $impairmentAmount);
            $fromPl  = round($impairmentAmount - $fromOci, 2);

            $lines[] = ['chart_of_account_id' => $revSurplusId, 'type' => 'debit',  'amount' => round($fromOci, 2), 'description' => 'Impairment reduces revaluation surplus — '.$asset->name];
            $lines[] = ['chart_of_account_id' => $accImpId,     'type' => 'credit', 'amount' => round($fromOci, 2), 'description' => 'Accumulated impairment — '.$asset->name.' ('.$date.')'];

            if ($fromPl > 0) {
                $impLossId = $this->resolveAccount($company, $ppeClass,
                    $response['impairment_loss_account_id'] ?? null, PpeClassAccountLink::ROLE_IMPAIRMENT_LOSS,
                    fn () => $this->ensureImpairmentLossAccount($company, $ppeClass));
                $lines[] = ['chart_of_account_id' => $impLossId, 'type' => 'debit',  'amount' => $fromPl, 'description' => 'Impairment loss (P&L) — '.$asset->name.' ('.$date.')'];
                $lines[] = ['chart_of_account_id' => $accImpId,  'type' => 'credit', 'amount' => $fromPl, 'description' => 'Accumulated impairment — '.$asset->name.' ('.$date.')'];
            }
        } else {
            // Cost-model asset: full impairment to P&L
            $impLossId = $this->resolveAccount($company, $ppeClass,
                $response['impairment_loss_account_id'] ?? null, PpeClassAccountLink::ROLE_IMPAIRMENT_LOSS,
                fn () => $this->ensureImpairmentLossAccount($company, $ppeClass));
            $lines[] = ['chart_of_account_id' => $impLossId, 'type' => 'debit',  'amount' => round($impairmentAmount, 2), 'description' => 'Impairment loss (IAS 36) — '.$asset->name.' ('.$date.') '.$reason];
            $lines[] = ['chart_of_account_id' => $accImpId,  'type' => 'credit', 'amount' => round($impairmentAmount, 2), 'description' => 'Accumulated impairment — '.$asset->name.' ('.$date.')'];
        }

        return DB::transaction(function () use ($asset, $user, $company, $lines, $response, $impairmentAmount, $date) {
            $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description'      => 'IAS 36 Impairment — '.$asset->name,
                'reference'        => 'IMP-'.str_replace('-', '', $date).'-'.($asset->asset_tag ?: $asset->id),
                'status'           => 'draft',
                'source_document'  => 'asset:'.$asset->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);

            $surplusUsed = min((float) ($asset->revaluation_surplus ?? 0), $impairmentAmount);
            $asset->forceFill([
                'accumulated_impairment'      => round((float) ($asset->accumulated_impairment ?? 0) + $impairmentAmount, 2),
                'revaluation_surplus'         => max(0, round((float) ($asset->revaluation_surplus ?? 0) - $surplusUsed, 2)),
                'impairment_surplus_consumed' => round((float) ($asset->impairment_surplus_consumed ?? 0) + $surplusUsed, 2),
            ])->save();

            return $asset;
        });
    }

    /**
     * IAS 36.114 Impairment Reversal — reverse a prior impairment, but only to
     * the extent the carrying amount would have been without the impairment.
     *
     * Posts Dr Accumulated Impairment / Cr Impairment Reversal (P&L).
     */
    public function postImpairmentReversalWithAi(
        Asset $asset,
        User $user,
        float $reversalAmount,
        string $date,
    ): Asset {
        if ($asset->status === Asset::STATUS_DISPOSED) {
            throw new InvalidArgumentException('Cannot reverse impairment on a disposed asset.');
        }

        $totalImpairment = (float) ($asset->accumulated_impairment ?? 0);
        if ($totalImpairment <= 0) {
            throw new InvalidArgumentException('This asset has no accumulated impairment to reverse (IAS 36.114).');
        }

        $reversalAmount = min(round($reversalAmount, 2), $totalImpairment);
        if ($reversalAmount <= 0) {
            throw new InvalidArgumentException('Reversal amount must be positive.');
        }

        $company  = $asset->company;
        $ppeClass = $asset->ppeClass;

        $accounts = $this->companyAccounts($company);
        $response = $this->prompt($asset, $accounts, 'impairment_reversal', [
            'reversal_amount'        => $reversalAmount,
            'accumulated_impairment' => $totalImpairment,
        ]);

        $accImpId = $this->resolveAccount($company, $ppeClass,
            $response['accumulated_impairment_account_id'] ?? null, PpeClassAccountLink::ROLE_ACCUMULATED_IMPAIRMENT,
            fn () => $this->ensureAccumulatedImpairmentAccount($company, $ppeClass));
        $revIncomeId = $this->resolveAccount($company, $ppeClass,
            $response['impairment_reversal_account_id'] ?? null, PpeClassAccountLink::ROLE_IMPAIRMENT_REVERSAL,
            fn () => $this->ensureImpairmentReversalAccount($company, $ppeClass));

        $lines = [];
        $lines[] = ['chart_of_account_id' => $accImpId, 'type' => 'debit', 'amount' => $reversalAmount, 'description' => 'Impairment reversal — '.$asset->name.' ('.$date.')'];

        // IAS 36.119: for revaluation-model assets, the portion that was originally
        // charged against OCI surplus during impairment must be credited back to OCI
        $toOci = 0;
        if ($ppeClass && $ppeClass->accounting_policy === 'revaluation') {
            $surplusConsumed = (float) ($asset->impairment_surplus_consumed ?? 0);
            $toOci = round(min($reversalAmount, $surplusConsumed), 2);
        }
        $toPl = round($reversalAmount - $toOci, 2);

        if (round($toPl, 2) >= 0.01) {
            $lines[] = ['chart_of_account_id' => $revIncomeId, 'type' => 'credit', 'amount' => round($toPl, 2), 'description' => 'Impairment reversal income (IAS 36.114) — '.$asset->name.' ('.$date.')'];
        }
        if (round($toOci, 2) >= 0.01) {
            $revSurplusId = $this->resolveAccount($company, $ppeClass,
                $response['revaluation_surplus_account_id'] ?? null, PpeClassAccountLink::ROLE_REVALUATION,
                fn () => $this->ensureRevaluationSurplusAccount($company, $ppeClass));
            $lines[] = ['chart_of_account_id' => $revSurplusId, 'type' => 'credit', 'amount' => round($toOci, 2), 'description' => 'Impairment reversal to revaluation surplus (IAS 36.119) — '.$asset->name.' ('.$date.')'];
        }

        return DB::transaction(function () use ($asset, $user, $company, $lines, $response, $reversalAmount, $toOci, $date) {
            $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description'      => 'IAS 36 Impairment Reversal — '.$asset->name,
                'reference'        => 'IMPREV-'.str_replace('-', '', $date).'-'.($asset->asset_tag ?: $asset->id),
                'status'           => 'draft',
                'source_document'  => 'asset:'.$asset->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);

            $asset->forceFill([
                'accumulated_impairment'      => max(0, round((float) ($asset->accumulated_impairment ?? 0) - $reversalAmount, 2)),
                'revaluation_surplus'         => round((float) ($asset->revaluation_surplus ?? 0) + $toOci, 2),
                'impairment_surplus_consumed' => max(0, round((float) ($asset->impairment_surplus_consumed ?? 0) - $toOci, 2)),
            ])->save();

            return $asset;
        });
    }

    /**
     * IAS 16.7 Subsequent expenditure — capitalise costs that meet recognition
     * criteria (extend useful life, add capacity, or are significant replacements).
     *
     * Posts Dr PPE Cost / Cr Bank or Payable, and increases asset cost.
     */
    public function postSubsequentCostWithAi(
        Asset $asset,
        User $user,
        float $amount,
        string $date,
        string $description = '',
    ): Asset {
        if ($asset->status === Asset::STATUS_DISPOSED) {
            throw new InvalidArgumentException('Cannot capitalise costs on a disposed asset.');
        }
        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be positive.');
        }

        $company  = $asset->company;
        $ppeClass = $asset->ppeClass;

        $accounts = $this->companyAccounts($company);
        $response = $this->prompt($asset, $accounts, 'subsequent_cost', [
            'amount'      => $amount,
            'description' => $description,
        ]);

        $costId = $this->resolveAccount($company, $ppeClass,
            $response['cost_account_id'] ?? null, PpeClassAccountLink::ROLE_COST,
            fn () => $this->ensureCostAccount($company, $ppeClass));
        $bankId = $response['bank_or_payable_account_id'] ?? null;
        if (! $bankId) {
            throw new RuntimeException('AI could not determine a bank/payable account for subsequent capitalisation.');
        }

        $lines = [
            ['chart_of_account_id' => $costId,  'type' => 'debit',  'amount' => round($amount, 2), 'description' => ($description ?: 'Subsequent cost') .' — '.$asset->name.' ('.$date.')'],
            ['chart_of_account_id' => $bankId,  'type' => 'credit', 'amount' => round($amount, 2), 'description' => ($description ?: 'Subsequent cost') .' — '.$asset->name.' ('.$date.')'],
        ];

        return DB::transaction(function () use ($asset, $user, $company, $lines, $response, $amount, $date, $description) {
            $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description'      => 'IAS 16 Subsequent cost — '.($description ?: $asset->name),
                'reference'        => 'SUBCAP-'.str_replace('-', '', $date).'-'.($asset->asset_tag ?: $asset->id),
                'status'           => 'draft',
                'source_document'  => 'asset:'.$asset->id,
                'notes'            => 'Subsequent capitalisation posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);

            $asset->forceFill([
                'cost' => round((float) $asset->cost + $amount, 2),
            ])->save();

            return $asset;
        });
    }

    private function monthlyDepreciation(Asset $asset, CarbonImmutable $monthEnd): float
    {
        $cost     = (float) $asset->cost;
        $residual = (float) $asset->residual_value;
        $depreciable = $cost - $residual;
        $life = $asset->effectiveUsefulLifeYears();

        if ($depreciable <= 0 || $life === null || $life <= 0) {
            return 0.0;
        }

        $startDate = $asset->depreciationStartDate();
        if ($monthEnd->lessThan($startDate)) {
            return 0.0;
        }

        if ($asset->effectiveDepreciationMethod() === 'reducing_balance') {
            $annualRate = ($residual > 0 && $cost > 0)
                ? 1 - pow($residual / $cost, 1 / $life)
                : min(1.0, 2 / $life);

            $monthlyFactor = pow(1 - $annualRate, 1 / 12);

            // NBV at the start of this month (end of prior month)
            $monthsElapsed = $startDate->diffInMonths($monthEnd->startOfMonth());
            $openingNbv = $cost * pow($monthlyFactor, $monthsElapsed);

            // NBV at the end of this month
            $closingNbv = $cost * pow($monthlyFactor, $monthsElapsed + 1);

            $monthly = $openingNbv - $closingNbv;

            // Never depreciate below the residual value
            $accDepSoFar = $asset->accumulatedDepreciation($monthEnd->subMonth()->endOfMonth()->format('Y-m-d'));
            $remaining = $depreciable - $accDepSoFar;
            $monthly = min($monthly, max($remaining, 0));

            return round($monthly, 2);
        }

        // Straight-line
        return round($depreciable / ($life * 12), 2);
    }

    /** @return \Illuminate\Support\Collection<int, ChartOfAccount> */
    private function companyAccounts(Company $company)
    {
        if (isset($this->accountCache[$company->id])) {
            return $this->accountCache[$company->id];
        }

        return $this->accountCache[$company->id] = $this->accountSearch->searchMultiple($company->id, [
            'property plant equipment PPE asset cost',
            'accumulated depreciation contra asset',
            'depreciation expense operating',
            'revaluation surplus OCI equity reserve',
            'impairment loss expense',
            'accumulated impairment contra asset',
            'bank cash proceeds asset',
            'gain loss on disposal income expense',
            'accounts payable creditor liability',
        ], 10);
    }

    /**
     * Use the AI's pick if valid; otherwise check the PpeClass→COA link by role;
     * otherwise create the account deterministically and link it.
     */
    private function resolveAccount(
        Company $company,
        PpeClass $ppeClass,
        ?int $aiPick,
        string $role,
        \Closure $createMissing,
    ): int {
        if ($aiPick && ChartOfAccount::where('company_id', $company->id)->where('id', $aiPick)->exists()) {
            $this->ensureLink($ppeClass, $aiPick, $role);
            return $aiPick;
        }

        $linked = $ppeClass->accountLinks()
            ->where('role', $role)
            ->value('chart_of_account_id');
        if ($linked) {
            return (int) $linked;
        }

        $account = $createMissing();
        $this->ensureLink($ppeClass, $account->id, $role);
        return $account->id;
    }

    private function ensureLink(PpeClass $ppeClass, int $accountId, string $role): void
    {
        PpeClassAccountLink::firstOrCreate([
            'ppe_class_id' => $ppeClass->id,
            'role' => $role,
        ], ['chart_of_account_id' => $accountId]);
    }

    private function ensureCostAccount(Company $company, PpeClass $ppeClass): ChartOfAccount
    {
        return $this->ensureAccount($company, [
            'account_code' => $this->nextCode($company, '1006000', '1006089'),
            'account_name' => "PPE — {$ppeClass->name} (Cost)",
            'account_type' => 'assets',
            'category' => 'Property, Plant and Equipment',
            'cash_flow_category' => 'investing',
            'is_contra' => false,
            'is_ppe' => true,
        ], self::PPE_COST_CODE_FALLBACK);
    }

    private function ensureAccumulatedDepreciationAccount(Company $company, PpeClass $ppeClass): ChartOfAccount
    {
        return $this->ensureAccount($company, [
            'account_code' => $this->nextCode($company, '1010090', '1010099'),
            'account_name' => "Accumulated Depreciation — {$ppeClass->name}",
            'account_type' => 'assets',
            'category' => 'Property, Plant and Equipment',
            'cash_flow_category' => 'investing',
            'is_contra' => true,
            'is_ppe' => true,
        ], self::PPE_ACC_DEP_CODE_FALLBACK);
    }

    private function ensureDepreciationExpenseAccount(Company $company, PpeClass $ppeClass): ChartOfAccount
    {
        return $this->ensureAccount($company, [
            'account_code' => $this->nextCode($company, '6007000', '6007099'),
            'account_name' => "Depreciation Expense — {$ppeClass->name}",
            'account_type' => 'expenses',
            'category' => 'Depreciation & Amortisation',
            'cash_flow_category' => 'operating',
            'is_contra' => false,
        ], self::PPE_DEP_EXPENSE_CODE_FALLBACK);
    }

    private function ensureGainLossAccount(Company $company, PpeClass $ppeClass, float $gainLoss): ChartOfAccount
    {
        if ($gainLoss > 0) {
            return $this->ensureAccount($company, [
                'account_code' => $this->nextCode($company, '4010000', '4010049'),
                'account_name' => 'Gain on Disposal of PPE',
                'account_type' => 'income',
                'category' => 'Other Income',
                'cash_flow_category' => 'investing',
                'is_contra' => false,
            ], self::PPE_DISPOSAL_GAIN_CODE_FALLBACK);
        }
        return $this->ensureAccount($company, [
            'account_code' => $this->nextCode($company, '6009090', '6009099'),
            'account_name' => 'Loss on Disposal of PPE',
            'account_type' => 'expenses',
            'category' => 'Other Expenses',
            'cash_flow_category' => 'investing',
            'is_contra' => false,
        ], self::PPE_DISPOSAL_LOSS_CODE_FALLBACK);
    }

    private function ensureRevaluationSurplusAccount(Company $company, PpeClass $ppeClass): ChartOfAccount
    {
        return $this->ensureAccount($company, [
            'account_code' => $this->nextCode($company, '3009000', '3009049'),
            'account_name' => "Revaluation Surplus — {$ppeClass->name}",
            'account_type' => 'equity',
            'category'     => 'Other Comprehensive Income',
            'cash_flow_category' => null,
            'is_contra'    => false,
            'is_oci'       => true,
        ], self::PPE_REVALUATION_SURPLUS_CODE_FALLBACK);
    }

    private function ensureImpairmentLossAccount(Company $company, PpeClass $ppeClass): ChartOfAccount
    {
        return $this->ensureAccount($company, [
            'account_code' => $this->nextCode($company, '6008000', '6008049'),
            'account_name' => "Impairment Loss — {$ppeClass->name}",
            'account_type' => 'expenses',
            'category'     => 'Depreciation & Amortisation',
            'cash_flow_category' => 'operating',
            'is_contra'    => false,
        ], self::PPE_IMPAIRMENT_LOSS_CODE_FALLBACK);
    }

    private function ensureAccumulatedImpairmentAccount(Company $company, PpeClass $ppeClass): ChartOfAccount
    {
        return $this->ensureAccount($company, [
            'account_code' => $this->nextCode($company, '1006090', '1006097'),
            'account_name' => "Accumulated Impairment — {$ppeClass->name}",
            'account_type' => 'assets',
            'category'     => 'Property, Plant and Equipment',
            'cash_flow_category' => 'investing',
            'is_contra'    => true,
            'is_ppe'       => true,
        ], self::PPE_ACC_IMPAIRMENT_CODE_FALLBACK);
    }

    private function ensureImpairmentReversalAccount(Company $company, PpeClass $ppeClass): ChartOfAccount
    {
        return $this->ensureAccount($company, [
            'account_code' => $this->nextCode($company, '4009000', '4009049'),
            'account_name' => "Impairment Reversal — {$ppeClass->name}",
            'account_type' => 'income',
            'category'     => 'Other Income',
            'cash_flow_category' => 'operating',
            'is_contra'    => false,
        ], self::PPE_IMPAIRMENT_REVERSAL_CODE_FALLBACK);
    }

    private function ensureDeferredTaxLiabilityAccount(Company $company): ChartOfAccount
    {
        return $this->ensureAccount($company, [
            'account_code' => $this->nextCode($company, '2009000', '2009099'),
            'account_name' => 'Deferred Tax Liability',
            'account_type' => 'liabilities',
            'category' => 'Non-Current Liabilities',
            'cash_flow_category' => 'operating',
            'is_contra' => false,
        ], self::DEFERRED_TAX_LIABILITY_CODE_FALLBACK);
    }

    private function ensureDeferredTaxExpenseAccount(Company $company): ChartOfAccount
    {
        return $this->ensureAccount($company, [
            'account_code' => $this->nextCode($company, '7009000', '7009099'),
            'account_name' => 'Deferred Tax Expense',
            'account_type' => 'expenses',
            'category' => 'Tax Expense',
            'cash_flow_category' => 'operating',
            'is_contra' => false,
        ], self::DEFERRED_TAX_EXPENSE_CODE_FALLBACK);
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
            'company_id' => $company->id,
            'is_active' => true,
            'opening_balance' => 0,
        ], $attrs));
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
    private function prompt(Asset $asset, $accounts, string $kind, array $extra = []): array
    {
        $list = $accounts
            ->map(fn (ChartOfAccount $a) => "- id={$a->id}, code={$a->account_code}, name=\"{$a->account_name}\", type={$a->account_type}".($a->is_contra ? ' (contra)' : ''))
            ->implode("\n");

        $policy = $asset->ppeClass?->accounting_policy ?? 'cost';

        $details = "Asset: {$asset->name} (tag {$asset->asset_tag})\n"
            ."PPE class: {$asset->ppeClass?->name} (accounting policy: {$policy})\n"
            ."Cost: {$asset->cost}, residual: {$asset->residual_value}, useful life: {$asset->useful_life_years} years\n"
            ."Accumulated impairment: ".($asset->accumulated_impairment ?? 0)."\n"
            ."Revaluation surplus (OCI): ".($asset->revaluation_surplus ?? 0)."\n"
            ."Acquired: ".optional($asset->acquisition_date)->format('Y-m-d')."\n"
            .($asset->disposal_date ? "Disposed: {$asset->disposal_date->format('Y-m-d')}, proceeds: {$asset->disposal_proceeds}\n" : '');

        $extraStr = empty($extra) ? '' : "\nAdditional context:\n".json_encode($extra, JSON_PRETTY_PRINT)."\n";

        $prompt = "Posting type: {$kind}\n\n{$details}{$extraStr}\nChart of accounts:\n{$list}\n\nPick the IFRS-appropriate accounts for this posting.";

        return (new AssetPostingAgent)->prompt($prompt)->toArray();
    }

    public function postHeldForSaleWithAi(Asset $asset, User $user, float $carryingAmount, float $impairment, string $date): void
    {
        $company  = $asset->company;
        $ppeClass = $asset->ppeClass;
        if (!$ppeClass) {
            throw new InvalidArgumentException('Asset has no PPE class — cannot determine IFRS accounts.');
        }

        $accounts = $this->companyAccounts($company);
        $response = $this->prompt($asset, $accounts, 'held_for_sale_reclassification', [
            'carrying_amount' => $carryingAmount,
            'impairment'      => $impairment,
        ]);

        $costId   = $this->resolveAccount($company, $ppeClass,
            $response['cost_account_id'] ?? null, PpeClassAccountLink::ROLE_COST,
            fn () => $this->ensureCostAccount($company, $ppeClass));
        $accDepId = $this->resolveAccount($company, $ppeClass,
            $response['accumulated_depreciation_account_id'] ?? null, PpeClassAccountLink::ROLE_ACCUMULATED_DEPRECIATION,
            fn () => $this->ensureAccumulatedDepreciationAccount($company, $ppeClass));
        $hfsId    = $this->ensureHfsAccount($company);

        $accDep   = (float) $asset->accumulatedDepreciation($date);
        $accImp   = (float) ($asset->accumulated_impairment ?? 0);
        $cost     = (float) $asset->cost;

        // Reclassify: Dr HFS / Dr Acc Dep / Dr Acc Imp / Cr PPE Cost
        $lines = [];
        $lines[] = ['chart_of_account_id' => $hfsId,    'type' => 'debit',  'amount' => $carryingAmount, 'description' => 'Reclassify to HFS: '.$asset->name];
        if (round($accDep, 2) >= 0.01) {
            $lines[] = ['chart_of_account_id' => $accDepId, 'type' => 'debit',  'amount' => round($accDep, 2), 'description' => 'Derecognise acc. dep: '.$asset->name];
        }
        if (round($accImp, 2) >= 0.01) {
            $accImpId = $this->resolveAccount($company, $ppeClass,
                $response['accumulated_impairment_account_id'] ?? null, PpeClassAccountLink::ROLE_ACCUMULATED_IMPAIRMENT,
                fn () => $this->ensureAccumulatedImpairmentAccount($company, $ppeClass));
            $lines[] = ['chart_of_account_id' => $accImpId, 'type' => 'debit',  'amount' => round($accImp, 2), 'description' => 'Derecognise acc. impairment: '.$asset->name];
        }
        $lines[] = ['chart_of_account_id' => $costId,   'type' => 'credit', 'amount' => $cost, 'description' => 'Derecognise PPE cost: '.$asset->name];

        // IFRS 5.15 write-down if FVLCTS < carrying amount
        if ($impairment > 0) {
            $impLossId = $this->resolveAccount($company, $ppeClass,
                $response['impairment_loss_account_id'] ?? null, PpeClassAccountLink::ROLE_IMPAIRMENT_LOSS,
                fn () => $this->ensureImpairmentLossAccount($company, $ppeClass));
            $lines[] = ['chart_of_account_id' => $impLossId, 'type' => 'debit',  'amount' => $impairment, 'description' => 'IFRS 5 write-down to FVLCTS: '.$asset->name];
            $lines[] = ['chart_of_account_id' => $hfsId,     'type' => 'credit', 'amount' => $impairment, 'description' => 'IFRS 5 write-down: '.$asset->name];
        }

        DB::transaction(function () use ($asset, $user, $company, $lines, $response, $date) {
            $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description'      => 'IFRS 5 HFS reclassification — '.$asset->name,
                'reference'        => 'HFS-'.str_replace('-', '', $date).'-'.($asset->asset_tag ?: $asset->id),
                'status'           => 'draft',
                'source_document'  => 'asset:'.$asset->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);
        });
    }

    public function postHeldForSaleReversalWithAi(Asset $asset, User $user, float $carryingAmount, float $impairment, string $date): void
    {
        $company  = $asset->company;
        $ppeClass = $asset->ppeClass;
        if (!$ppeClass) {
            throw new InvalidArgumentException('Asset has no PPE class — cannot determine IFRS accounts.');
        }

        $accounts = $this->companyAccounts($company);
        $response = $this->prompt($asset, $accounts, 'held_for_sale_reversal', [
            'carrying_amount' => $carryingAmount,
            'impairment'      => $impairment,
        ]);

        $costId = $this->resolveAccount($company, $ppeClass,
            $response['cost_account_id'] ?? null, PpeClassAccountLink::ROLE_COST,
            fn () => $this->ensureCostAccount($company, $ppeClass));
        $hfsId  = $this->ensureHfsAccount($company);

        // Reverse: Dr PPE Cost / Cr HFS Asset
        $lines = [];
        $lines[] = ['chart_of_account_id' => $costId, 'type' => 'debit',  'amount' => (float) $asset->cost, 'description' => 'Restore PPE cost: '.$asset->name];
        $lines[] = ['chart_of_account_id' => $hfsId,  'type' => 'credit', 'amount' => $carryingAmount, 'description' => 'Derecognise HFS: '.$asset->name];

        // Reverse IFRS 5 impairment if one was recognised on reclassification
        if ($impairment > 0) {
            $impLossId = $this->resolveAccount($company, $ppeClass,
                $response['impairment_loss_account_id'] ?? null, PpeClassAccountLink::ROLE_IMPAIRMENT_LOSS,
                fn () => $this->ensureImpairmentLossAccount($company, $ppeClass));
            $lines[] = ['chart_of_account_id' => $hfsId,     'type' => 'debit',  'amount' => $impairment, 'description' => 'Reverse IFRS 5 write-down: '.$asset->name];
            $lines[] = ['chart_of_account_id' => $impLossId, 'type' => 'credit', 'amount' => $impairment, 'description' => 'Reverse IFRS 5 write-down: '.$asset->name];
        }

        // Reinstate accumulated depreciation (what would have been without HFS)
        $accDepId = $this->resolveAccount($company, $ppeClass,
            $response['accumulated_depreciation_account_id'] ?? null, PpeClassAccountLink::ROLE_ACCUMULATED_DEPRECIATION,
            fn () => $this->ensureAccumulatedDepreciationAccount($company, $ppeClass));
        $accDep = (float) $asset->accumulatedDepreciation($date);
        if (round($accDep, 2) >= 0.01) {
            $lines[] = ['chart_of_account_id' => $costId,   'type' => 'credit', 'amount' => round($accDep, 2), 'description' => 'Reinstate acc. dep: '.$asset->name];
            $lines[] = ['chart_of_account_id' => $accDepId, 'type' => 'debit',  'amount' => round($accDep, 2), 'description' => 'Reinstate acc. dep: '.$asset->name];
        }

        DB::transaction(function () use ($asset, $user, $company, $lines, $response, $date) {
            $this->transactions->record($company, $user, [
                'transaction_date' => $date,
                'description'      => 'IFRS 5 HFS reversal — '.$asset->name,
                'reference'        => 'HFSREV-'.str_replace('-', '', $date).'-'.($asset->asset_tag ?: $asset->id),
                'status'           => 'draft',
                'source_document'  => 'asset:'.$asset->id,
                'notes'            => 'Posted by AI agent: '.($response['reasoning'] ?? ''),
                'lines'            => $lines,
            ]);
        });
    }

    private function ensureHfsAccount(Company $company): int
    {
        $existing = ChartOfAccount::where('company_id', $company->id)
            ->where('account_name', 'like', '%held for sale%')
            ->first();
        if ($existing) return $existing->id;

        $code = $this->nextAvailableCode($company, '1007000', '1007099');
        return ChartOfAccount::create([
            'company_id'         => $company->id,
            'account_code'       => $code ?? '1007000',
            'account_name'       => 'Non-current Assets Held for Sale',
            'account_type'       => 'assets',
            'category'           => 'Current Assets',
            'cash_flow_category' => 'investing',
            'is_contra'          => false,
        ])->id;
    }

    private function ensureRetainedEarningsAccount(Company $company): int
    {
        $existing = ChartOfAccount::where('company_id', $company->id)
            ->where('account_name', 'like', '%retained%earn%')
            ->first();
        if ($existing) return $existing->id;

        return ChartOfAccount::create([
            'company_id'         => $company->id,
            'account_code'       => '3002000',
            'account_name'       => 'Retained Earnings',
            'account_type'       => 'equity',
            'category'           => 'Equity',
            'cash_flow_category' => null,
            'is_contra'          => false,
        ])->id;
    }

    private function nextAvailableCode(Company $company, string $start, string $end): ?string
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
}
