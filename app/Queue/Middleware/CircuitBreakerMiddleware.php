<?php

namespace App\Queue\Middleware;

use App\Jobs\EmbedInvoiceJob;
use App\Jobs\EmbedTransactionJob;
use App\Services\EmbeddingRecovery;
use App\Services\EmbeddingSuspension;
use App\Services\ServiceSuspension;
use Closure;
use GabrielAnhaia\LaravelCircuitBreaker\Facades\CircuitBreaker;

/**
 * Queue middleware that suspends a job when its circuit breaker is open.
 *
 * Attach via the job's `middleware()` method:
 *
 *   public function middleware(): array
 *   {
 *       return [new CircuitBreakerMiddleware('gemini-embedding')];
 *   }
 *
 * When the breaker for the named service is open, the job is released back to
 * the queue for the configured `open_timeout` (default 60s). The worker
 * immediately moves on to the next job, so unrelated jobs are not blocked.
 */
class CircuitBreakerMiddleware
{
    public function __construct(
        private readonly string $service,
        private readonly ?int $releaseSeconds = null,
    ) {}

    public function handle(mixed $job, Closure $next): mixed
    {
        // For the embedding pipeline, a recovery pivot dominates everything
        // else: a single failed job becomes the pivot, and every other embed
        // job must yield until that specific job succeeds (or finally fails).
        if ($this->service === 'gemini-embedding' && EmbeddingRecovery::isSuspended()) {
            if (! $this->isRecoveryPivot($job)) {
                $this->yield($job, $this->releaseSeconds ?? 60);
                return null;
            }
        }

        // Hard suspension wins — one failure with a known back-off pauses
        // the entire queue for that service until the suggested resume time.
        if ($this->service === 'gemini-embedding' && ($remaining = EmbeddingSuspension::secondsRemaining()) > 0) {
            $this->yield($job, $remaining);
            return null;
        }
        if (($generic = ServiceSuspension::secondsRemaining($this->service)) > 0) {
            $this->yield($job, $generic);
            return null;
        }

        if (! CircuitBreaker::canPass($this->service)) {
            $delay = $this->releaseSeconds
                ?? (int) config("circuit_breaker.services.{$this->service}.open_timeout", 60);

            $this->yield($job, $delay);

            return null;
        }

        return $next($job);
    }

    /**
     * Defer the job without consuming a retry. Jobs that implement bounce()
     * re-dispatch a fresh copy so the attempt counter resets; otherwise we
     * fall back to release() (which DOES increment attempts).
     */
    private function yield(mixed $job, int $delay): void
    {
        if (method_exists($job, 'bounce')) {
            $job->bounce($delay);
            return;
        }
        $job->release($delay);
    }

    private function isRecoveryPivot(mixed $job): bool
    {
        if ($job instanceof EmbedTransactionJob) {
            return EmbeddingRecovery::isPivot('tx', $job->transactionId);
        }
        if ($job instanceof EmbedInvoiceJob) {
            return EmbeddingRecovery::isPivot('inv', $job->invoiceId);
        }
        return false;
    }
}
