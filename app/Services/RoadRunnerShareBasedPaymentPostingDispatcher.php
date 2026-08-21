<?php

namespace App\Services;

use App\Jobs\PostShareBasedPaymentActionJob;
use Illuminate\Support\Facades\Log;
use Spiral\Goridge\RPC\RPC;
use Spiral\RoadRunner\Jobs\Jobs;

class RoadRunnerShareBasedPaymentPostingDispatcher
{
    private ?Jobs $jobs = null;

    private function jobs(): Jobs
    {
        if ($this->jobs === null) {
            $this->jobs = new Jobs(RPC::create('tcp://127.0.0.1:6001'));
        }
        return $this->jobs;
    }

    public function dispatch(int $eventId, int $arrangementId, int $userId, string $action, array $data): void
    {
        $payload = json_encode(compact('eventId', 'arrangementId', 'userId', 'action', 'data'));

        try {
            $queue = $this->jobs()->connect('sbp-postings');
            $task  = $queue->create("post_sbp_{$action}", $payload);
            $queue->dispatch($task);
        } catch (\Throwable $e) {
            Log::warning('RoadRunnerShareBasedPaymentPostingDispatcher: RR unavailable, falling back to Laravel queue', [
                'action'         => $action,
                'arrangement_id' => $arrangementId,
                'error'          => $e->getMessage(),
            ]);
            PostShareBasedPaymentActionJob::dispatch($eventId, $arrangementId, $userId, $action, $data);
        }
    }
}
