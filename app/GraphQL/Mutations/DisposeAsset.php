<?php

namespace App\GraphQL\Mutations;

use App\Models\Asset;
use InvalidArgumentException;
use RuntimeException;

class DisposeAsset
{
    /** @param  array{id:int|string, disposal_date:string, disposal_proceeds?:float|null}  $args */
    public function __invoke(mixed $root, array $args): Asset
    {
        $asset = Asset::find((int) $args['id']);
        if (! $asset) {
            throw new RuntimeException('Asset not found.');
        }
        if ($asset->status === Asset::STATUS_DISPOSED) {
            throw new InvalidArgumentException('Asset already disposed.');
        }

        // Setting disposal_date triggers AssetObserver::updated → status
        // flips to "disposed" and AssetDisposed event fires → posting job.
        $asset->fill([
            'disposal_date' => $args['disposal_date'],
            'disposal_proceeds' => $args['disposal_proceeds'] ?? 0,
        ])->save();

        return $asset->refresh();
    }
}
