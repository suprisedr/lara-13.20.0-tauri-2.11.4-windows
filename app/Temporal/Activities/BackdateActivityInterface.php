<?php

namespace App\Temporal\Activities;

use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface(prefix: 'Backdate.')]
interface BackdateActivityInterface
{
    #[ActivityMethod(name: 'GetAssetsNeedingBackdate')]
    public function getAssetsNeedingBackdate(int $companyId): array;

    #[ActivityMethod(name: 'PostBackdatedDepreciation')]
    public function postBackdatedDepreciation(int $assetId, string $monthEnd): bool;
}
