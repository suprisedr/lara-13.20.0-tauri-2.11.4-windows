<?php

namespace App\Jobs;

use App\Events\PostingStatusUpdated;
use App\Models\IntangibleAsset;
use App\Models\IntangibleAssetEvent;
use App\Models\User;
use App\Queue\Middleware\CircuitBreakerMiddleware;
use App\Services\IntangibleAssetPostingService;
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

class PostIntangibleAssetActionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const SERVICE = 'intangible-ai-posting';
    public const QUEUE   = 'asset-postings';

    public int $tries = 3;

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [30, 90, 180];
    }

    public function __construct(
        public readonly int    $assetEventId,
        public readonly int    $intangibleAssetId,
        public readonly int    $userId,
        public readonly string $action,   // acquire | capitalise | revalue | impair | reverse | dispose
        public readonly array  $data,
    ) {
        $this->onQueue(self::QUEUE);
    }

    /** @return array<int, object> */
    public function middleware(): array
    {
        return [new CircuitBreakerMiddleware(self::SERVICE)];
    }

    public function handle(IntangibleAssetPostingService $posting): void
    {
        $context = [
            'intangible_event_id' => $this->assetEventId,
            'intangible_asset_id' => $this->intangibleAssetId,
            'action'              => $this->action,
            'attempt'             => $this->attempts(),
        ];

        Log::info('PostIntangibleAssetActionJob: starting', $context);

        $asset = IntangibleAsset::with(['company.user', 'intangibleClass'])->findOrFail($this->intangibleAssetId);
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
            };

            $elapsed = round((microtime(true) - $start) * 1000);

            $txnId = \App\Models\Transaction::where('company_id', $asset->company_id)
                ->where('source_document', 'intangible:'.$this->assetId)
                ->latest('id')
                ->value('id');

            IntangibleAssetEvent::where('id', $this->assetEventId)
                ->update(['journal_status' => IntangibleAssetEvent::STATUS_POSTED, 'transaction_id' => $txnId]);
            CircuitBreaker::recordSuccess(self::SERVICE);
            event(new PostingStatusUpdated($asset->company_id, 'intangible_event', $this->assetEventId, 'posted', ucfirst($this->action).' posted — '.$asset->name));

            Log::info('PostIntangibleAssetActionJob: posted successfully', $context + [
                'asset_name' => $asset->name,
                'elapsed_ms' => $elapsed,
            ]);
        } catch (Throwable $e) {
            $elapsed = round((microtime(true) - $start) * 1000);

            CircuitBreaker::recordFailure(self::SERVICE);

            if ($this->isTransient($e) && $this->attempts() < $this->tries) {
                $delay = $this->retryAfter($e) ?? $this->backoffFor($this->attempts());

                Log::warning('PostIntangibleAssetActionJob: transient provider error, will retry', $context + [
                    'asset_name'  => $asset->name ?? 'unknown',
                    'elapsed_ms'  => $elapsed,
                    'error'       => $e->getMessage(),
                    'exception'   => get_class($e),
                    'retry_in_s'  => $delay,
                ]);

                $this->release($delay);

                return;
            }

            IntangibleAssetEvent::where('id', $this->assetEventId)
                ->update(['journal_status' => IntangibleAssetEvent::STATUS_FAILED]);
            event(new PostingStatusUpdated($asset->company_id, 'intangible_event', $this->assetEventId, 'failed', ucfirst($this->action).' failed — '.$asset->name));

            Log::error('PostIntangibleAssetActionJob: failed', $context + [
                'asset_name'  => $asset->name ?? 'unknown',
                'elapsed_ms'  => $elapsed,
                'error'       => $e->getMessage(),
                'exception'   => get_class($e),
                'will_retry'  => false,
            ]);

            throw $e;
        }
    }

    public function failed(?Throwable $e): void
    {
        IntangibleAssetEvent::where('id', $this->assetEventId)
            ->update(['journal_status' => IntangibleAssetEvent::STATUS_FAILED]);

        Log::error('PostIntangibleAssetActionJob: exhausted retries', [
            'intangible_event_id' => $this->assetEventId,
            'intangible_asset_id' => $this->intangibleAssetId,
            'action'              => $this->action,
            'error'               => $e?->getMessage(),
            'exception'           => $e ? get_class($e) : null,
        ]);
    }

    private function isTransient(Throwable $e): bool
    {
        return $e instanceof RateLimitedException
            || $e instanceof ProviderOverloadedException;
    }

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

    private function backoffFor(int $attempt): int
    {
        $schedule = $this->backoff();
        $index = max(0, $attempt - 1);

        return $schedule[$index] ?? end($schedule);
    }
}
