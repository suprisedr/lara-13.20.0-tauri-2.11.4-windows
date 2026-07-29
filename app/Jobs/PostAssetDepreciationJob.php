<?php

namespace App\Jobs;
use App\Models\Asset;
use App\Queue\Middleware\CircuitBreakerMiddleware;
use App\Services\AssetPostingService;
use Carbon\CarbonImmutable;
use GabrielAnhaia\LaravelCircuitBreaker\Facades\CircuitBreaker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class PostAssetDepreciationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const SERVICE = 'asset-ai-posting';

    public const QUEUE = 'asset-postings';

    public int $tries = 3;

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [30, 90, 180];
    }

    public function __construct(
        public readonly int $assetId,
        public readonly string $monthEndDate,
    ) {
        $this->onConnection('ai')->onQueue(self::QUEUE);
    }

    /** @return array<int, object> */
    public function middleware(): array
    {
        return [new CircuitBreakerMiddleware(self::SERVICE)];
    }

    public function handle(AssetPostingService $service): void
    {
        $asset = Asset::with(['company.user', 'ppeClass'])->find($this->assetId);
        if (! $asset || $asset->status === Asset::STATUS_DISPOSED) {
            return;
        }

        try {
            $service->postMonthlyDepreciationWithAi(
                $asset,
                $asset->company->user,
                CarbonImmutable::parse($this->monthEndDate),
            );
            CircuitBreaker::recordSuccess(self::SERVICE);
        } catch (Throwable $e) {
            CircuitBreaker::recordFailure(self::SERVICE);
            throw $e;
        }
    }
}
