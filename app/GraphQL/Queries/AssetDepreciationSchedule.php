<?php

namespace App\GraphQL\Queries;

use App\Models\Asset;

class AssetDepreciationSchedule
{
    /**
     * @param  array{asset_id:int|string}  $args
     * @return array<int, array<string, mixed>>
     */
    public function __invoke(mixed $root, array $args): array
    {
        $asset = Asset::find((int) $args['asset_id']);
        if (! $asset) {
            return [];
        }

        $start = $asset->acquisition_date?->startOfMonth();
        $life = $asset->effectiveUsefulLifeYears();
        if (! $start || ! $life) {
            return [];
        }

        $depreciable = (float) $asset->cost - (float) $asset->residual_value;
        if ($depreciable <= 0) {
            return [];
        }

        $months = (int) round($life * 12);
        $monthly = round($depreciable / $months, 2);

        $rows = [];
        $accum = 0.0;
        for ($i = 0; $i < $months; $i++) {
            $monthEnd = $start->copy()->addMonths($i)->endOfMonth();
            $accum = round($accum + $monthly, 2);
            $rows[] = [
                'period' => $monthEnd->format('Y-m'),
                'depreciation' => $monthly,
                'accumulated' => $accum,
                'carrying_value' => round((float) $asset->cost - $accum, 2),
                'posted' => $asset->last_depreciation_posted_on
                    && $asset->last_depreciation_posted_on->greaterThanOrEqualTo($monthEnd),
            ];
        }

        return $rows;
    }
}
