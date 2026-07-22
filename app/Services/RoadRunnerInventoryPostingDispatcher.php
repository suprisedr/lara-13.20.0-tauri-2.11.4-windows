<?php

namespace App\Services;

use App\Jobs\PostInventoryActionJob;
use Illuminate\Support\Facades\Log;
use Spiral\Goridge\RPC\RPC;
use Spiral\RoadRunner\Jobs\Jobs;

class RoadRunnerInventoryPostingDispatcher
{
    private ?Jobs $jobs = null;

    private function jobs(): Jobs
    {
        if ($this->jobs === null) {
            $this->jobs = new Jobs(RPC::create('tcp://127.0.0.1:6001'));
        }
        return $this->jobs;
    }

    public function dispatch(int $movementId, int $userId): void
    {
        $payload = json_encode(compact('movementId', 'userId'));

        try {
            $queue = $this->jobs()->connect('inventory_postings');
            $task  = $queue->create('post_inventory_movement', $payload);
            $queue->dispatch($task);
        } catch (\Throwable $e) {
            Log::warning('RoadRunnerInventoryPostingDispatcher: RR unavailable, falling back to Laravel queue', [
                'movement_id' => $movementId,
                'error'       => $e->getMessage(),
            ]);
            PostInventoryActionJob::dispatch($movementId, $userId);
        }
    }
}
