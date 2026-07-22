<?php

namespace App\GraphQL\Mutations;

use App\Models\Asset;

class CreateAsset
{
    /** @param  array<string, mixed>  $args */
    public function __invoke(mixed $root, array $args): Asset
    {
        // Cast IDs in case they arrive as strings (GraphQL ID scalar).
        $args['company_id'] = (int) $args['company_id'];
        $args['ppe_class_id'] = (int) $args['ppe_class_id'];
        $args['residual_value'] ??= 0;
        $args['status'] = Asset::STATUS_ACTIVE;

        // Eloquent::create fires the observer's created() hook → AssetCreated
        // → PostAssetAcquisitionWithAi (queued, CB-guarded).
        return Asset::create($args);
    }
}
