<?php

namespace App\Services;

use App\Jobs\PostDeferredTaxActionJob;
use Illuminate\Support\Facades\Log;
use Spiral\Goridge\RPC\RPC;
use Spiral\RoadRunner\Jobs\Jobs;

class RoadRunnerDeferredTaxPostingDispatcher
{
    private ?Jobs $jobs = null;

    private function jobs(): Jobs
    {
        if ($this->jobs === null) {
            $this->jobs = new Jobs(RPC::create('tcp://127.0.0.1:6001'));
        }
        return $this->jobs;
    }

    public function dispatch(int $eventId, int $itemId, int $userId, string $action, array $data): void
    {
        $payload = json_encode(compact('eventId', 'itemId', 'userId', 'action', 'data'));

        try {
            $queue = $this->jobs()->connect('tax-postings');
            $task  = $queue->create("post_deferred_tax_{$action}", $payload);
            $queue->dispatch($task);
        } catch (\Throwable $e) {
            Log::warning('RoadRunnerDeferredTaxPostingDispatcher: RR unavailable, falling back to Laravel queue', [
                'action'  => $action,
                'item_id' => $itemId,
                'error'   => $e->getMessage(),
            ]);
            PostDeferredTaxActionJob::dispatch($eventId, $itemId, $userId, $action, $data);
        }
    }
}
