<?php

namespace App\Services;

use App\Jobs\PostProvisionActionJob;
use Illuminate\Support\Facades\Log;
use Spiral\Goridge\RPC\RPC;
use Spiral\RoadRunner\Jobs\Jobs;

class RoadRunnerProvisionPostingDispatcher
{
    private ?Jobs $jobs = null;

    private function jobs(): Jobs
    {
        if ($this->jobs === null) {
            $this->jobs = new Jobs(RPC::create('tcp://127.0.0.1:6001'));
        }
        return $this->jobs;
    }

    public function dispatch(int $eventId, int $provisionId, int $userId, string $action, array $data): void
    {
        $payload = json_encode(compact('eventId', 'provisionId', 'userId', 'action', 'data'));

        try {
            $queue = $this->jobs()->connect('provision-postings');
            $task  = $queue->create("post_provision_{$action}", $payload);
            $queue->dispatch($task);
        } catch (\Throwable $e) {
            Log::warning('RoadRunnerProvisionPostingDispatcher: RR unavailable, falling back to Laravel queue', [
                'action'       => $action,
                'provision_id' => $provisionId,
                'error'        => $e->getMessage(),
            ]);
            PostProvisionActionJob::dispatch($eventId, $provisionId, $userId, $action, $data);
        }
    }
}
