<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\User;
use App\Services\EclPostingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;
use Throwable;

class PostEclProvisionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const QUEUE = 'ecl-postings';

    public int $tries = 3;

    public int $timeout = 120;

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [30, 90, 180];
    }

    public function __construct(
        public readonly int    $companyId,
        public readonly int    $userId,
        public readonly string $asOfDate,
    ) {
        $this->onQueue(self::QUEUE);
    }

    public function handle(EclPostingService $service): void
    {
        $company = Company::findOrFail($this->companyId);
        $user    = User::findOrFail($this->userId);

        Log::info('PostEclProvisionJob: starting', [
            'company_id' => $this->companyId,
            'as_of_date' => $this->asOfDate,
            'attempt'    => $this->attempts(),
        ]);

        try {
            $service->postProvision($company, $user, $this->asOfDate);

            Log::info('PostEclProvisionJob: posted', [
                'company_id' => $this->companyId,
                'as_of_date' => $this->asOfDate,
            ]);
        } catch (RateLimitedException | ProviderOverloadedException $e) {
            Log::warning('PostEclProvisionJob: transient AI failure, releasing', [
                'error' => $e->getMessage(),
            ]);
            $this->release(60);
        } catch (Throwable $e) {
            Log::error('PostEclProvisionJob: failed', [
                'company_id' => $this->companyId,
                'as_of_date' => $this->asOfDate,
                'error'      => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
