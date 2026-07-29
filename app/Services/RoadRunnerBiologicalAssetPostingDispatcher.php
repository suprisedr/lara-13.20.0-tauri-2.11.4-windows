<?php

namespace App\Services;

use App\Jobs\PostBiologicalAssetActionJob;
use Illuminate\Support\Facades\Log;
use Spiral\Goridge\RPC\RPC;
use Spiral\RoadRunner\Jobs\Jobs;

class RoadRunnerBiologicalAssetPostingDispatcher
{
    private ?Jobs $jobs = null;

    private function jobs(): Jobs
    {
        if ($this->jobs === null) {
            $this->jobs = new Jobs(RPC::create('tcp://127.0.0.1:6001'));
        }
        return $this->jobs;
    }

    public function dispatch(int $eventId, int $assetId, int $userId, string $action, array $data): void
    {
        $payload = json_encode(compact('eventId', 'assetId', 'userId', 'action', 'data'));

        try {
            $queue = $this->jobs()->connect('biological-asset-postings');
            $task  = $queue->create("post_biological_asset_{$action}", $payload);
            $queue->dispatch($task);
        } catch (\Throwable $e) {
            Log::warning('RoadRunnerBiologicalAssetPostingDispatcher: RR unavailable, falling back to Laravel queue', [
                'action'   => $action,
                'asset_id' => $assetId,
                'error'    => $e->getMessage(),
            ]);
            PostBiologicalAssetActionJob::dispatch($eventId, $assetId, $userId, $action, $data);
        }
    }
}
