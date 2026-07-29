<?php

namespace App\Services;

use App\Jobs\PostIntangibleAssetActionJob;
use Illuminate\Support\Facades\Log;
use Spiral\Goridge\RPC\RPC;
use Spiral\RoadRunner\Jobs\Jobs;

class RoadRunnerIntangiblePostingDispatcher
{
    private ?Jobs $jobs = null;

    private function jobs(): Jobs
    {
        if ($this->jobs === null) {
            $this->jobs = new Jobs(RPC::create('tcp://127.0.0.1:6001'));
        }
        return $this->jobs;
    }

    public function dispatch(int $eventId, int $intangibleId, int $userId, string $action, array $data): void
    {
        $payload = json_encode(compact('eventId', 'intangibleId', 'userId', 'action', 'data'));

        try {
            $queue = $this->jobs()->connect('intangible-postings');
            $task  = $queue->create("post_intangible_{$action}", $payload);
            $queue->dispatch($task);
        } catch (\Throwable $e) {
            Log::warning('RoadRunnerIntangiblePostingDispatcher: RR unavailable, falling back to Laravel queue', [
                'action'        => $action,
                'intangible_id' => $intangibleId,
                'error'         => $e->getMessage(),
            ]);
            PostIntangibleAssetActionJob::dispatch($eventId, $intangibleId, $userId, $action, $data);
        }
    }
}
