<?php

namespace App\Observers;

use App\Events\AssetCreated;
use App\Events\AssetDisposed;
use App\Models\Asset;

class AssetObserver
{
    public function created(Asset $asset): void
    {
        AssetCreated::dispatch($asset);
    }

    public function updated(Asset $asset): void
    {
        // If a disposal_date was just set on a still-active asset, mark it as
        // disposed and emit the event. The disposed register is the
        // Asset::disposed() scope.
        if ($asset->wasChanged('disposal_date')
            && $asset->disposal_date
            && $asset->status !== Asset::STATUS_DISPOSED
        ) {
            $asset->forceFill(['status' => Asset::STATUS_DISPOSED])->saveQuietly();
            AssetDisposed::dispatch($asset);
        }
    }
}
