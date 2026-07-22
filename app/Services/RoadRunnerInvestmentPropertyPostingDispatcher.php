<?php

namespace App\Services;

use App\Jobs\PostInvestmentPropertyActionJob;
use Illuminate\Support\Facades\Log;
use Spiral\Goridge\RPC\RPC;
use Spiral\RoadRunner\Jobs\Jobs;

class RoadRunnerInvestmentPropertyPostingDispatcher
{
    private ?Jobs $jobs = null;

    private function jobs(): Jobs
    {
        if ($this->jobs === null) {
            $this->jobs = new Jobs(RPC::create('tcp://127.0.0.1:6001'));
        }
        return $this->jobs;
    }

    public function dispatch(int $eventId, int $propertyId, int $userId, string $action, array $data): void
    {
        $payload = json_encode(compact('eventId', 'propertyId', 'userId', 'action', 'data'));

        try {
            $queue = $this->jobs()->connect('investment_property_postings');
            $task  = $queue->create("post_investment_property_{$action}", $payload);
            $queue->dispatch($task);
        } catch (\Throwable $e) {
            Log::warning('RoadRunnerInvestmentPropertyPostingDispatcher: RR unavailable, falling back to Laravel queue', [
                'action'      => $action,
                'property_id' => $propertyId,
                'error'       => $e->getMessage(),
            ]);
            PostInvestmentPropertyActionJob::dispatch($eventId, $propertyId, $userId, $action, $data);
        }
    }
}
