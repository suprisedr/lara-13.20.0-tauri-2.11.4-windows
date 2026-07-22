<?php

namespace App\Console\Commands;

use App\Jobs\EmbedAssetJob;
use App\Models\Asset;
use Illuminate\Console\Command;

class SyncAssetsToVectorStore extends Command
{
    protected $signature = 'vectors:sync-assets
        {--limit=500 : Max assets to enqueue}
        {--sync : Run embeds inline instead of queueing}';

    protected $description = 'Embed un-synced assets into the pgvector store via Gemini.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $sync  = (bool) $this->option('sync');

        $dispatcher = app(\App\Services\RoadRunnerEmbeddingDispatcher::class);
        $count = 0;
        Asset::notEmbedded()
            ->orderBy('id')
            ->limit($limit)
            ->each(function (Asset $asset) use (&$count, $sync, $dispatcher) {
                if ($sync) {
                    EmbedAssetJob::dispatchSync($asset->id);
                } else {
                    $dispatcher->embedAsset($asset->id);
                }
                $count++;
            });

        $this->info(($sync ? 'Embedded' : 'Queued')." {$count} asset(s).");
        return self::SUCCESS;
    }
}
