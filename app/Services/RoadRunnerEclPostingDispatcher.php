<?php

namespace App\Services;

use App\Jobs\PostEclProvisionJob;
use Illuminate\Support\Facades\Log;
use Spiral\Goridge\RPC\RPC;
use Spiral\RoadRunner\Jobs\Jobs;

class RoadRunnerEclPostingDispatcher
{
    private ?Jobs $jobs = null;

    private function jobs(): Jobs
    {
        if ($this->jobs === null) {
            $this->jobs = new Jobs(RPC::create('tcp://127.0.0.1:6001'));
        }
        return $this->jobs;
    }

    public function dispatch(int $companyId, int $userId, string $asOfDate): void
    {
        $payload = json_encode(compact('companyId', 'userId', 'asOfDate'));

        try {
            $queue = $this->jobs()->connect('ecl_postings');
            $task  = $queue->create('post_ecl_provision', $payload);
            $queue->dispatch($task);
        } catch (\Throwable $e) {
            Log::warning('RoadRunnerEclPostingDispatcher: RR unavailable, falling back to Laravel queue', [
                'company_id'  => $companyId,
                'as_of_date'  => $asOfDate,
                'error'       => $e->getMessage(),
            ]);
            PostEclProvisionJob::dispatch($companyId, $userId, $asOfDate);
        }
    }
}
