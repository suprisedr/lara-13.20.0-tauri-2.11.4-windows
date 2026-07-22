<?php

namespace App\Jobs;

use App\Events\PostingStatusUpdated;
use App\Models\Asset;
use App\Models\AssetEvent;
use App\Models\User;
use App\Queue\Middleware\CircuitBreakerMiddleware;
use App\Services\AssetPostingService;
use GabrielAnhaia\LaravelCircuitBreaker\Facades\CircuitBreaker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;
use Throwable;

class PostAssetActionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const SERVICE = 'asset-ai-posting';
    public const QUEUE   = 'asset-postings';

    public int $tries = 3;

    public int $timeout = 300;

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [30, 90, 180];
    }

    public function __construct(
        public readonly int    $assetEventId,
        public readonly int    $assetId,
        public readonly int    $userId,
        public readonly string $action,   // capitalise | revalue | impair | reverse | dispose
        public readonly array  $data,
    ) {
        $this->onQueue(self::QUEUE);
    }

    /** @return array<int, object> */
    public function middleware(): array
    {
        return [new CircuitBreakerMiddleware(self::SERVICE)];
    }

    public function handle(AssetPostingService $posting): void
    {
        $context = [
            'asset_event_id' => $this->assetEventId,
            'asset_id'       => $this->assetId,
            'action'         => $this->action,
            'attempt'        => $this->attempts(),
        ];

        Log::info('PostAssetActionJob: starting', $context);

        $asset = Asset::with(['company.user', 'ppeClass'])->findOrFail($this->assetId);
        $user  = User::findOrFail($this->userId);

        $start = microtime(true);

        try {
            match ($this->action) {
                'acquire' => $posting->postAcquisitionWithAi($asset, $user),
                'capitalise' => $posting->postSubsequentCostWithAi(
                    $asset, $user,
                    (float) $this->data['amount'],
                    $this->data['date'],
                    $this->data['description'] ?? '',
                ),
                'revalue' => $posting->postRevaluationWithAi(
                    $asset, $user,
                    (float) $this->data['new_carrying_amount'],
                    $this->data['date'],
                ),
                'impair' => $posting->postImpairmentWithAi(
                    $asset, $user,
                    (float) $this->data['impairment_amount'],
                    $this->data['date'],
                    $this->data['reason'] ?? '',
                ),
                'reverse' => $posting->postImpairmentReversalWithAi(
                    $asset, $user,
                    (float) $this->data['reversal_amount'],
                    $this->data['date'],
                ),
                'dispose' => $posting->postDisposalWithAi($asset->fresh(), $user),
                'held_for_sale' => $posting->postHeldForSaleWithAi(
                    $asset, $user,
                    (float) $this->data['carrying_amount'],
                    (float) $this->data['impairment'],
                    $this->data['reclassification_date'],
                ),
                'held_for_sale_reversal' => $posting->postHeldForSaleReversalWithAi(
                    $asset, $user,
                    (float) $this->data['carrying_amount_at_reclassification'],
                    (float) $this->data['impairment_on_reclassification'],
                    $this->data['reversal_date'],
                ),
            };

            $elapsed = round((microtime(true) - $start) * 1000);

            $txnId = \App\Models\Transaction::where('company_id', $asset->company_id)
                ->where('source_document', 'asset:'.$this->assetId)
                ->latest('id')
                ->value('id');

            AssetEvent::where('id', $this->assetEventId)->update([
                'journal_status' => AssetEvent::STATUS_POSTED,
                'transaction_id' => $txnId,
            ]);
            CircuitBreaker::recordSuccess(self::SERVICE);
            event(new PostingStatusUpdated($asset->company_id, 'asset_event', $this->assetEventId, 'posted', ucfirst($this->action).' posted — '.$asset->name));

            Log::info('PostAssetActionJob: posted successfully', $context + [
                'asset_name' => $asset->name,
                'elapsed_ms' => $elapsed,
            ]);
        } catch (Throwable $e) {
            $elapsed = round((microtime(true) - $start) * 1000);

            // Every provider error trips the circuit breaker so the queue can
            // back off as a whole.
            CircuitBreaker::recordFailure(self::SERVICE);

            // Transient provider errors (rate-limited / overloaded) are worth
            // retrying. Honour the provider's Retry-After hint when present,
            // keep the event 'pending' (not 'failed'), and release the job back
            // to the queue without consuming the throw-path retry. The event is
            // only marked 'failed' once retries are exhausted — see failed().
            if ($this->isTransient($e) && $this->attempts() < $this->tries) {
                $delay = $this->retryAfter($e) ?? $this->backoffFor($this->attempts());

                Log::warning('PostAssetActionJob: transient provider error, will retry', $context + [
                    'asset_name'  => $asset->name ?? 'unknown',
                    'elapsed_ms'  => $elapsed,
                    'error'       => $e->getMessage(),
                    'exception'   => get_class($e),
                    'retry_in_s'  => $delay,
                ]);

                $this->release($delay);

                return;
            }

            // Non-transient (or final transient) failure: mark the event failed.
            AssetEvent::where('id', $this->assetEventId)->update(['journal_status' => AssetEvent::STATUS_FAILED]);
            event(new PostingStatusUpdated($asset->company_id, 'asset_event', $this->assetEventId, 'failed', ucfirst($this->action).' failed — '.$asset->name));

            Log::error('PostAssetActionJob: failed', $context + [
                'asset_name'  => $asset->name ?? 'unknown',
                'elapsed_ms'  => $elapsed,
                'error'       => $e->getMessage(),
                'exception'   => get_class($e),
                'will_retry'  => false,
            ]);

            throw $e;
        }
    }

    /**
     * Mark the underlying asset event as failed once the queue has exhausted
     * all retry attempts (this fires after the final transient release too).
     */
    public function failed(?Throwable $e): void
    {
        AssetEvent::where('id', $this->assetEventId)
            ->update(['journal_status' => AssetEvent::STATUS_FAILED]);

        Log::error('PostAssetActionJob: exhausted retries', [
            'asset_event_id' => $this->assetEventId,
            'asset_id'       => $this->assetId,
            'action'         => $this->action,
            'error'          => $e?->getMessage(),
            'exception'      => $e ? get_class($e) : null,
        ]);
    }

    /** Rate-limited (429) and overloaded (503) provider errors are retryable. */
    private function isTransient(Throwable $e): bool
    {
        return $e instanceof RateLimitedException
            || $e instanceof ProviderOverloadedException;
    }

    /**
     * Extract a Retry-After hint (seconds) from the wrapped HTTP response, if
     * the provider supplied one. Supports both delta-seconds and HTTP-date.
     */
    private function retryAfter(Throwable $e): ?int
    {
        $previous = $e->getPrevious();
        if (! $previous instanceof RequestException || ! $previous->response) {
            return null;
        }

        $header = $previous->response->header('Retry-After');
        if ($header === '') {
            return null;
        }

        if (is_numeric($header)) {
            return max(1, (int) $header);
        }

        $timestamp = strtotime($header);

        return $timestamp ? max(1, $timestamp - time()) : null;
    }

    /** Resolve the configured backoff delay for the given attempt number. */
    private function backoffFor(int $attempt): int
    {
        $schedule = $this->backoff();
        $index = max(0, $attempt - 1);

        return $schedule[$index] ?? end($schedule);
    }
}
