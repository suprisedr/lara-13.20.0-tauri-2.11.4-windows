<?php

namespace App\Services;

use App\Jobs\PostGovernmentGrantActionJob;
use Illuminate\Support\Facades\Log;
use Spiral\Goridge\RPC\RPC;
use Spiral\RoadRunner\Jobs\Jobs;

class RoadRunnerGovernmentGrantPostingDispatcher
{
    private ?Jobs $jobs = null;

    private function jobs(): Jobs
    {
        if ($this->jobs === null) {
            $this->jobs = new Jobs(RPC::create('tcp://127.0.0.1:6001'));
        }
        return $this->jobs;
    }

    public function dispatch(int $eventId, int $grantId, int $userId, string $action, array $data): void
    {
        $payload = json_encode(compact('eventId', 'grantId', 'userId', 'action', 'data'));

        try {
            $queue = $this->jobs()->connect('grant-postings');
            $task  = $queue->create("post_grant_{$action}", $payload);
            $queue->dispatch($task);
        } catch (\Throwable $e) {
            Log::warning('RoadRunnerGovernmentGrantPostingDispatcher: RR unavailable, falling back to Laravel queue', [
                'action'   => $action,
                'grant_id' => $grantId,
                'error'    => $e->getMessage(),
            ]);
            PostGovernmentGrantActionJob::dispatch($eventId, $grantId, $userId, $action, $data);
        }
    }
}
