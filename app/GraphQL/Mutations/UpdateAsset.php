<?php

namespace App\GraphQL\Mutations;

use App\Models\Asset;
use RuntimeException;

class UpdateAsset
{
    /** @param  array<string, mixed>  $args */
    public function __invoke(mixed $root, array $args): Asset
    {
        $asset = Asset::find((int) $args['id']);
        if (! $asset) {
            throw new RuntimeException('Asset not found.');
        }

        unset($args['id']);
        // Only descriptive fields go through this mutation — disposal is
        // handled by the dedicated disposeAsset mutation so the AssetDisposed
        // event fires through the observer.
        $asset->update(array_filter($args, fn ($v) => $v !== null));

        return $asset->refresh();
    }
}
