<?php

namespace App\Temporal\Workflows;

use App\Temporal\Activities\BackdateActivityInterface;
use Carbon\CarbonInterval;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Workflow;
use Temporal\Workflow\WorkflowInterface;

class BackdateDepreciationWorkflow implements BackdateDepreciationWorkflowInterface
{
    public function run(int $companyId): \Generator
    {
        $activity = Workflow::newActivityStub(
            BackdateActivityInterface::class,
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
            'assets_processed' => 0,
            'months_posted' => 0,
            'errors' => [],
        ];

        $assetsNeedingBackdate = yield $activity->getAssetsNeedingBackdate($companyId);

        foreach ($assetsNeedingBackdate as $item) {
            $assetId = $item['asset_id'];
            $months = $item['months'];

            foreach ($months as $i => $monthEnd) {
                if ($i > 0) {
                    yield Workflow::timer(CarbonInterval::seconds(8));
                }
                try {
                    $posted = yield $activity->postBackdatedDepreciation($assetId, $monthEnd);
                    if ($posted) {
                        $results['months_posted']++;
                    }
                } catch (\Throwable $e) {
                    $results['errors'][] = "Asset #{$assetId} ({$monthEnd}): {$e->getMessage()}";
                }
            }

            $results['assets_processed']++;
        }

        return $results;
    }
}
