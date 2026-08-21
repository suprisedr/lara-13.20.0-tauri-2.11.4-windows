<?php

namespace App\Services;

use App\Jobs\PostBorrowingCostActionJob;
use Illuminate\Support\Facades\Log;
use Spiral\Goridge\RPC\RPC;
use Spiral\RoadRunner\Jobs\Jobs;

class RoadRunnerBorrowingCostPostingDispatcher
{
    private ?Jobs $jobs = null;

    private function jobs(): Jobs
    {
        if ($this->jobs === null) {
            $this->jobs = new Jobs(RPC::create('tcp://127.0.0.1:6001'));
        }
        return $this->jobs;
    }

    public function dispatch(int $eventId, int $capitalisationId, int $userId, string $action, array $data): void
    {
        $payload = json_encode(compact('eventId', 'capitalisationId', 'userId', 'action', 'data'));

        try {
            $queue = $this->jobs()->connect('borrowing-cost-postings');
            $task  = $queue->create("post_borrowing_cost_{$action}", $payload);
            $queue->dispatch($task);
        } catch (\Throwable $e) {
            Log::warning('RoadRunnerBorrowingCostPostingDispatcher: RR unavailable, falling back to Laravel queue', [
                'action'            => $action,
                'capitalisation_id' => $capitalisationId,
                'error'             => $e->getMessage(),
            ]);
            PostBorrowingCostActionJob::dispatch($eventId, $capitalisationId, $userId, $action, $data);
        }
    }
}
