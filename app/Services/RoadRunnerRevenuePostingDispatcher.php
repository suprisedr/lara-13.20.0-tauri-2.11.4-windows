<?php

namespace App\Services;

use App\Jobs\PostRevenueActionJob;
use Illuminate\Support\Facades\Log;
use Spiral\Goridge\RPC\RPC;
use Spiral\RoadRunner\Jobs\Jobs;

class RoadRunnerRevenuePostingDispatcher
{
    private ?Jobs $jobs = null;

    private function jobs(): Jobs
    {
        if ($this->jobs === null) {
            $this->jobs = new Jobs(RPC::create('tcp://127.0.0.1:6001'));
        }
        return $this->jobs;
    }

    public function dispatch(int $eventId, int $contractId, int $userId, string $action, array $data): void
    {
        $payload = json_encode(compact('eventId', 'contractId', 'userId', 'action', 'data'));

        try {
            $queue = $this->jobs()->connect('revenue-postings');
            $task  = $queue->create("post_revenue_{$action}", $payload);
            $queue->dispatch($task);
        } catch (\Throwable $e) {
            Log::warning('RoadRunnerRevenuePostingDispatcher: RR unavailable, falling back to Laravel queue', [
                'action'      => $action,
                'contract_id' => $contractId,
                'error'       => $e->getMessage(),
            ]);
            PostRevenueActionJob::dispatch($eventId, $contractId, $userId, $action, $data);
        }
    }
}
