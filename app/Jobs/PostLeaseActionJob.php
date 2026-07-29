<?php

namespace App\Jobs;
use App\Events\PostingStatusUpdated;
use App\Models\Lease;
use App\Models\LeaseEvent;
use App\Models\User;
use App\Queue\Middleware\CircuitBreakerMiddleware;
use App\Services\LeasePostingService;
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

class PostLeaseActionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const SERVICE = 'lease-ai-posting';
    public const QUEUE   = 'lease-postings';

    public int $tries = 3;

    public function backoff(): array
    {
        return [30, 90, 180];
    }

    public function __construct(
        public readonly int    $leaseEventId,
        public readonly int    $leaseId,
        public readonly int    $userId,
        public readonly string $action,
        public readonly array  $data,
    ) {
        $this->onConnection('ai')->onQueue(self::QUEUE);
    }

    public function middleware(): array
    {
        return [new CircuitBreakerMiddleware(self::SERVICE)];
    }

    public function handle(LeasePostingService $posting): void
    {
        $context = [
            'lease_event_id' => $this->leaseEventId,
            'lease_id'       => $this->leaseId,
            'action'         => $this->action,
            'attempt'        => $this->attempts(),
        ];

        Log::info('PostLeaseActionJob: starting', $context);

        $lease = Lease::with('company')->findOrFail($this->leaseId);
        $user  = User::findOrFail($this->userId);

        $start = microtime(true);

        try {
            match ($this->action) {
                'commencement' => $posting->postCommencementWithAi($lease, $user),
                'payment' => $posting->postPaymentWithAi(
                    $lease, $user,
                    $this->data['date'],
                    (float) $this->data['amount'],
                ),
                'modification' => $posting->postModificationWithAi(
                    $lease, $user,
                    (float) $this->data['adjustment_amount'],
                    $this->data['date'],
                ),
                'impairment' => $posting->postImpairmentWithAi(
                    $lease, $user,
                    (float) $this->data['impairment_amount'],
                    $this->data['date'],
                ),
                'reverse_impairment' => $posting->postImpairmentReversalWithAi(
                    $lease, $user,
                    (float) $this->data['reversal_amount'],
                    $this->data['date'],
                ),
                'termination' => $posting->postTerminationWithAi(
                    $lease, $user,
                    $this->data['date'],
                    (float) ($this->data['gain_loss'] ?? 0),
                ),
            };

            $elapsed = round((microtime(true) - $start) * 1000);

            $txnId = \App\Models\Transaction::where('company_id', $lease->company_id)
                ->where('source_document', 'lease:'.$this->leaseId)
                ->latest('id')
                ->value('id');

            LeaseEvent::where('id', $this->leaseEventId)->update([
                'journal_status' => LeaseEvent::STATUS_POSTED,
                'transaction_id' => $txnId,
            ]);
            CircuitBreaker::recordSuccess(self::SERVICE);
            event(new PostingStatusUpdated($lease->company_id, 'lease_event', $this->leaseEventId, 'posted', ucfirst($this->action).' posted — '.$lease->name));

            Log::info('PostLeaseActionJob: posted successfully', $context + [
                'lease_name' => $lease->name,
                'elapsed_ms' => $elapsed,
            ]);
        } catch (Throwable $e) {
            $elapsed = round((microtime(true) - $start) * 1000);

            CircuitBreaker::recordFailure(self::SERVICE);

            if ($this->isTransient($e) && $this->attempts() < $this->tries) {
                $delay = $this->retryAfter($e) ?? $this->backoffFor($this->attempts());

                Log::warning('PostLeaseActionJob: transient provider error, will retry', $context + [
                    'lease_name' => $lease->name ?? 'unknown',
                    'elapsed_ms' => $elapsed,
                    'error'      => $e->getMessage(),
                    'exception'  => get_class($e),
                    'retry_in_s' => $delay,
                ]);

                $this->release($delay);
                return;
            }

            LeaseEvent::where('id', $this->leaseEventId)->update(['journal_status' => LeaseEvent::STATUS_FAILED]);
            event(new PostingStatusUpdated($lease->company_id, 'lease_event', $this->leaseEventId, 'failed', ucfirst($this->action).' failed — '.$lease->name));

            Log::error('PostLeaseActionJob: failed', $context + [
                'lease_name' => $lease->name ?? 'unknown',
                'elapsed_ms' => $elapsed,
                'error'      => $e->getMessage(),
                'exception'  => get_class($e),
                'will_retry' => false,
            ]);

            throw $e;
        }
    }

    public function failed(?Throwable $e): void
    {
        LeaseEvent::where('id', $this->leaseEventId)
            ->update(['journal_status' => LeaseEvent::STATUS_FAILED]);

        Log::error('PostLeaseActionJob: exhausted retries', [
            'lease_event_id' => $this->leaseEventId,
            'lease_id'       => $this->leaseId,
            'action'         => $this->action,
            'error'          => $e?->getMessage(),
            'exception'      => $e ? get_class($e) : null,
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
        if ($header === '') return null;

        if (is_numeric($header)) return max(1, (int) $header);

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
