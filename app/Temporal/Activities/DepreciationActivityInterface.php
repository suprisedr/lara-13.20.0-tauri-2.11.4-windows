<?php

namespace App\Temporal\Activities;

use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: 'MonthEnd.')]
interface DepreciationActivityInterface
{
    #[ActivityMethod(name: 'PostAssetDepreciation')]
    public function postAssetDepreciation(int $assetId, string $monthEnd): bool;

    #[ActivityMethod(name: 'PostIntangibleAmortisation')]
    public function postIntangibleAmortisation(int $assetId, string $monthEnd): bool;

    #[ActivityMethod(name: 'PostLeaseInterestAccrual')]
    public function postLeaseInterestAccrual(int $leaseId, string $monthEnd): bool;

    #[ActivityMethod(name: 'PostEclProvision')]
    public function postEclProvision(int $companyId, string $asOfDate): bool;
}
