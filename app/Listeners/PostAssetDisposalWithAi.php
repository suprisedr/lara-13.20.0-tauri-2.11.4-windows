<?php

namespace App\Listeners;
use App\Events\AssetDisposed;
use App\Models\AssetEvent;
use App\Models\CompanyAction;
use App\Queue\Middleware\CircuitBreakerMiddleware;
use App\Services\AssetPostingService;
use App\Services\ServiceSuspension;
use GabrielAnhaia\LaravelCircuitBreaker\Facades\CircuitBreaker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class PostAssetDisposalWithAi implements ShouldQueue
{
    use InteractsWithQueue;

    public string $connection = 'ai';

    private const SERVICE = 'asset-ai-posting';

    public int $tries = 15;

    public int $maxExceptions = 3;

    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(30);
    }

    public function viaQueue(): string
    {
        return 'asset-postings';
    }

    /** @return array<int, object> */
    public function middleware(): array
    {
        return [new CircuitBreakerMiddleware(self::SERVICE)];
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [30, 90, 180];
    }

    public function handle(AssetDisposed $event): void
    {
        $asset = $event->asset->fresh(['company.user', 'ppeClass']);
        if (! $asset || $asset->disposal_transaction_id) {
            return;
        }

        try {
            $posted = app(AssetPostingService::class)->postDisposalWithAi($asset, $asset->company->user);
            CircuitBreaker::recordSuccess(self::SERVICE);

            AssetEvent::where('asset_id', $asset->id)
                ->where('event_type', AssetEvent::TYPE_DISPOSAL)
                ->where('journal_status', AssetEvent::STATUS_PENDING)
                ->update([
                    'journal_status' => AssetEvent::STATUS_POSTED,
                    'transaction_id' => $posted->disposal_transaction_id,
                ]);

            Log::info('[AssetAI] Disposal posted', [
                'asset_id' => $asset->id,
                'asset_name' => $asset->name,
                'company_id' => $asset->company_id,
            ]);
        } catch (InvalidArgumentException $e) {
            Log::warning('[AssetAI] Disposal skipped — invalid argument', [
                'asset_id' => $asset->id,
                'error' => $e->getMessage(),
            ]);
            return;
        } catch (ConnectionException $e) {
            CircuitBreaker::recordFailure(self::SERVICE);
            ServiceSuspension::suspendFor(self::SERVICE, $this->backoffSecondsFromConfig());

            Log::error('[AssetAI] Connection failure on disposal — pipeline suspended', [
                'asset_id' => $asset->id,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
                'suspend_seconds' => $this->backoffSecondsFromConfig(),
            ]);

            throw $e;
        } catch (RequestException $e) {
            CircuitBreaker::recordFailure(self::SERVICE);

            $status = $e->response?->status();
            $body = $e->response?->json() ?? $e->response?->body();

            Log::error('[AssetAI] API error on disposal', [
                'asset_id' => $asset->id,
                'attempt' => $this->attempts(),
                'http_status' => $status,
                'response' => is_array($body) ? $body : mb_substr((string) $body, 0, 500),
                'error' => $e->getMessage(),
            ]);

            if (in_array($status, [429, 503])) {
                ServiceSuspension::suspendFor(self::SERVICE, $this->backoffSecondsFromConfig());
            }

            throw $e;
        } catch (Throwable $e) {
            CircuitBreaker::recordFailure(self::SERVICE);

            Log::error('[AssetAI] Unexpected disposal failure', [
                'asset_id' => $asset->id,
                'attempt' => $this->attempts(),
                'exception' => get_class($e),
                'error' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
            ]);

            throw $e;
        }
    }

    public function failed(AssetDisposed $event, Throwable $e): void
    {
        $asset = $event->asset;

        AssetEvent::where('asset_id', $asset->id)
            ->where('event_type', AssetEvent::TYPE_DISPOSAL)
            ->where('journal_status', AssetEvent::STATUS_PENDING)
            ->update(['journal_status' => AssetEvent::STATUS_FAILED]);

        Log::critical('[AssetAI] Disposal permanently failed', [
            'asset_id' => $asset->id,
            'asset_name' => $asset->name,
            'company_id' => $asset->company_id,
            'exception' => get_class($e),
            'error' => $e->getMessage(),
        ]);

        CompanyAction::create([
            'company_id' => $asset->company_id,
            'title' => "AI disposal posting failed — {$asset->name} (Asset #{$asset->id})",
            'body' => get_class($e).': '.$e->getMessage(),
            'priority' => CompanyAction::PRIORITY_HIGH,
            'source' => CompanyAction::SOURCE_SYSTEM,
            'related_type' => 'asset',
            'related_id' => $asset->id,
        ]);
    }

    private function backoffSecondsFromConfig(): int
    {
        return (int) config('circuit_breaker.services.'.self::SERVICE.'.open_timeout', 120);
    }
}
