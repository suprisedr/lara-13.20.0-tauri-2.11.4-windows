<?php

namespace App\Temporal\Activities;

use App\Models\Asset;
use App\Models\IntangibleAsset;
use App\Models\Lease;
use App\Services\AssetPostingService;
use App\Services\EclPostingService;
use App\Services\IntangibleAssetPostingService;
use App\Services\LeasePostingService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

class MonthEndActivity implements DepreciationActivityInterface
{
    public function postAssetDepreciation(int $assetId, string $monthEnd): bool
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

        Log::info('Temporal: asset depreciation posted', ['asset_id' => $assetId, 'month_end' => $monthEnd, 'success' => $result !== null]);
        return $result !== null;
    }

    public function postIntangibleAmortisation(int $assetId, string $monthEnd): bool
    {
        $asset = IntangibleAsset::with(['company.user', 'intangibleClass'])->find($assetId);
        if (! $asset || $asset->isDisposed() || $asset->isIndefiniteLife()) {
            return false;
        }

        $service = app(IntangibleAssetPostingService::class);
        $result = $service->postMonthlyAmortisationWithAi(
            $asset,
            $asset->company->user,
            CarbonImmutable::parse($monthEnd),
        );

        Log::info('Temporal: intangible amortisation posted', ['asset_id' => $assetId, 'month_end' => $monthEnd, 'success' => $result !== null]);
        return $result !== null;
    }

    public function postLeaseInterestAccrual(int $leaseId, string $monthEnd): bool
    {
        $lease = Lease::with(['company.user'])->find($leaseId);
        if (! $lease || $lease->status === 'terminated') {
            return false;
        }

        $service = app(LeasePostingService::class);
        $result = $service->postMonthlyRouDepreciationWithAi(
            $lease,
            $lease->company->user,
            CarbonImmutable::parse($monthEnd),
        );

        Log::info('Temporal: lease ROU depreciation posted', ['lease_id' => $leaseId, 'month_end' => $monthEnd, 'success' => $result !== null]);
        return $result !== null;
    }

    public function postEclProvision(int $companyId, string $asOfDate): bool
    {
        $company = \App\Models\Company::with('user')->find($companyId);
        if (! $company) {
            return false;
        }

        $service = app(EclPostingService::class);
        $service->postProvision($company, $company->user, $asOfDate);

        Log::info('Temporal: ECL provision posted', ['company_id' => $companyId, 'as_of' => $asOfDate]);
        return true;
    }
}
