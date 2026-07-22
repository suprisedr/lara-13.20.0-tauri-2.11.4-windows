<?php

namespace App\Temporal\Activities;

use App\Models\Asset;
use App\Services\AssetPostingService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

class BackdateActivity implements BackdateActivityInterface
{
    public function getAssetsNeedingBackdate(int $companyId): array
    {
        return Asset::where('company_id', $companyId)
            ->where('status', '!=', Asset::STATUS_DISPOSED)
            ->whereHas('ppeClass')
            ->get()
            ->filter(function (Asset $asset) {
                $startDate = $asset->depreciationStartDate();
                $lastPosted = $asset->last_depreciation_posted_on;
                $effectiveStart = $lastPosted
                    ? CarbonImmutable::parse($lastPosted)->addMonth()->startOfMonth()
                    : $startDate->toImmutable()->startOfMonth();

                $now = now()->subMonth()->endOfMonth();
                return $effectiveStart->lessThanOrEqualTo($now);
            })
            ->map(function (Asset $asset) {
                $startDate = $asset->depreciationStartDate();
                $lastPosted = $asset->last_depreciation_posted_on;
                $from = $lastPosted
                    ? CarbonImmutable::parse($lastPosted)->startOfMonth()->addMonth()
                    : $startDate->toImmutable()->startOfMonth();
                $until = now()->subMonth()->endOfMonth()->toImmutable();

                $months = [];
                $cursor = $from;
                while ($cursor->lessThanOrEqualTo($until)) {
                    $months[] = $cursor->endOfMonth()->format('Y-m-d');
                    $cursor = $cursor->addMonth();
                }

                return [
                    'asset_id' => $asset->id,
                    'asset_name' => $asset->name,
                    'months' => $months,
                ];
            })
            ->values()
            ->all();
    }

    public function postBackdatedDepreciation(int $assetId, string $monthEnd): bool
    {
        $asset = Asset::with(['company.user', 'ppeClass'])->find($assetId);
        if (! $asset || $asset->status === Asset::STATUS_DISPOSED) {
            return false;
        }

        $service = app(AssetPostingService::class);
        $result = $service->postMonthlyDepreciationWithAi(
            $asset,
            $asset->company->user,
            CarbonImmutable::parse($monthEnd),
        );

        Log::info('Temporal: backdated depreciation posted', [
            'asset_id' => $assetId,
            'month_end' => $monthEnd,
            'success' => $result !== null,
        ]);

        return $result !== null;
    }
}
