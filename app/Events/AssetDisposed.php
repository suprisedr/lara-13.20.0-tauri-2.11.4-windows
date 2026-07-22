<?php

namespace App\Events;

use App\Models\Asset;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssetDisposed
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Asset $asset) {}
}
