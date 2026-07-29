<?php

namespace App\Services;

use App\Jobs\PostLeaseActionJob;
use Illuminate\Support\Facades\Log;
use Spiral\Goridge\RPC\RPC;
use Spiral\RoadRunner\Jobs\Jobs;

class RoadRunnerLeasePostingDispatcher
{
    private ?Jobs $jobs = null;

    private function jobs(): Jobs
    {
        if ($this->jobs === null) {
            $this->jobs = new Jobs(RPC::create('tcp://127.0.0.1:6001'));
        }
        return $this->jobs;
    }

    public function dispatch(int $eventId, int $leaseId, int $userId, string $action, array $data): void
    {
        $payload = json_encode(compact('eventId', 'leaseId', 'userId', 'action', 'data'));

        try {
            $queue = $this->jobs()->connect('lease-postings');
            $task  = $queue->create("post_lease_{$action}", $payload);
            $queue->dispatch($task);
        } catch (\Throwable $e) {
            Log::warning('RoadRunnerLeasePostingDispatcher: RR unavailable, falling back to Laravel queue', [
                'action'   => $action,
                'lease_id' => $leaseId,
                'error'    => $e->getMessage(),
            ]);
            PostLeaseActionJob::dispatch($eventId, $leaseId, $userId, $action, $data);
        }
    }
}
