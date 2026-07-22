<?php

namespace App\Temporal\Workflows;

use App\Temporal\Activities\DepreciationActivityInterface;
use Carbon\CarbonInterval;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Workflow;
use Temporal\Workflow\ChildWorkflowOptions;

class MonthEndWorkflow implements MonthEndWorkflowInterface
{
    public function run(int $companyId, string $monthEnd): \Generator
    {
        if ($monthEnd === 'auto') {
            $monthEnd = yield Workflow::sideEffect(
                fn () => now()->subMonthNoOverflow()->endOfMonth()->format('Y-m-d')
            );
        }

        $activity = Workflow::newActivityStub(
            DepreciationActivityInterface::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::minutes(5))
                ->withRetryOptions(
                    RetryOptions::new()
                        ->withMaximumAttempts(3)
                        ->withInitialInterval(CarbonInterval::seconds(30))
                        ->withBackoffCoefficient(2.0)
                ),
        );

        $results = [
            'company_id' => $companyId,
            'month_end' => $monthEnd,
            'backdate' => null,
            'assets_depreciated' => 0,
            'intangibles_amortised' => 0,
            'leases_depreciated' => 0,
            'ecl_posted' => false,
            'errors' => [],
        ];

        // Step 0: Catch up any missed depreciation months before posting the current month
        try {
            $backdateWorkflow = Workflow::newChildWorkflowStub(
                BackdateDepreciationWorkflowInterface::class,
                ChildWorkflowOptions::new()->withTaskQueue('month-end')
            );
            $results['backdate'] = yield $backdateWorkflow->run($companyId);
        } catch (\Throwable $e) {
            $results['errors'][] = "Backdate: {$e->getMessage()}";
        }

        // Step 1: Post asset depreciation for all active assets
        $assetIds = yield Workflow::sideEffect(fn () => $this->getActiveAssetIds($companyId));
        foreach ($assetIds as $i => $assetId) {
            if ($i > 0) {
                yield Workflow::timer(CarbonInterval::seconds(3));
            }
            try {
                $posted = yield $activity->postAssetDepreciation($assetId, $monthEnd);
                if ($posted) {
                    $results['assets_depreciated']++;
                }
            } catch (\Throwable $e) {
                $results['errors'][] = "Asset #{$assetId}: {$e->getMessage()}";
            }
        }

        // Step 2: Post intangible amortisation
        $intangibleIds = yield Workflow::sideEffect(fn () => $this->getActiveIntangibleIds($companyId));
        foreach ($intangibleIds as $i => $intangibleId) {
            if ($i > 0) {
                yield Workflow::timer(CarbonInterval::seconds(3));
            }
            try {
                $posted = yield $activity->postIntangibleAmortisation($intangibleId, $monthEnd);
                if ($posted) {
                    $results['intangibles_amortised']++;
                }
            } catch (\Throwable $e) {
                $results['errors'][] = "Intangible #{$intangibleId}: {$e->getMessage()}";
            }
        }

        // Step 3: Post lease ROU depreciation
        $leaseIds = yield Workflow::sideEffect(fn () => $this->getActiveLeaseIds($companyId));
        foreach ($leaseIds as $i => $leaseId) {
            if ($i > 0) {
                yield Workflow::timer(CarbonInterval::seconds(3));
            }
            try {
                $posted = yield $activity->postLeaseInterestAccrual($leaseId, $monthEnd);
                if ($posted) {
                    $results['leases_depreciated']++;
                }
            } catch (\Throwable $e) {
                $results['errors'][] = "Lease #{$leaseId}: {$e->getMessage()}";
            }
        }

        // Step 4: Post ECL provision
        yield Workflow::timer(CarbonInterval::seconds(3));
        try {
            $results['ecl_posted'] = yield $activity->postEclProvision($companyId, $monthEnd);
        } catch (\Throwable $e) {
            $results['errors'][] = "ECL: {$e->getMessage()}";
        }

        return $results;
    }

    private function getActiveAssetIds(int $companyId): array
    {
        return \App\Models\Asset::where('company_id', $companyId)
            ->where('status', '!=', \App\Models\Asset::STATUS_DISPOSED)
            ->whereHas('ppeClass')
            ->pluck('id')
            ->all();
    }

    private function getActiveIntangibleIds(int $companyId): array
    {
        return \App\Models\IntangibleAsset::where('company_id', $companyId)
            ->whereNull('disposal_date')
            ->where(function ($q) {
                $q->where('useful_life_indefinite', false)
                  ->orWhereNull('useful_life_indefinite');
            })
            ->pluck('id')
            ->all();
    }

    private function getActiveLeaseIds(int $companyId): array
    {
        return \App\Models\Lease::where('company_id', $companyId)
            ->where('role', 'lessee')
            ->where('status', '!=', 'terminated')
            ->pluck('id')
            ->all();
    }
}
