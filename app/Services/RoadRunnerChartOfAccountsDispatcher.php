<?php

namespace App\Services;

use App\Events\CompanyOnboarded;
use Illuminate\Support\Facades\Log;
use Spiral\Goridge\RPC\RPC;
use Spiral\RoadRunner\Jobs\Jobs;

class RoadRunnerChartOfAccountsDispatcher
{
    private ?Jobs $jobs = null;

    private function jobs(): Jobs
    {
        if ($this->jobs === null) {
            $this->jobs = new Jobs(RPC::create('tcp://127.0.0.1:6001'));
        }
        return $this->jobs;
    }

    public function dispatch(int $companyId): void
    {
        $payload = json_encode(compact('companyId'));

        try {
            $queue = $this->jobs()->connect('chart-of-accounts');
            $task  = $queue->create('generate_chart_of_accounts', $payload);
            $queue->dispatch($task);
        } catch (\Throwable $e) {
            Log::warning('RoadRunnerChartOfAccountsDispatcher: RR unavailable, falling back to event', [
                'company_id' => $companyId,
                'error'      => $e->getMessage(),
            ]);
            CompanyOnboarded::dispatch(\App\Models\Company::findOrFail($companyId));
        }
    }
}
