<?php

namespace App\Jobs;
use App\Events\PostingStatusUpdated;
use App\Models\InvestmentProperty;
use App\Models\InvestmentPropertyEvent;
use App\Models\User;
use App\Queue\Middleware\CircuitBreakerMiddleware;
use App\Services\InvestmentPropertyPostingService;
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

class PostInvestmentPropertyActionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const SERVICE = 'investment-property-ai-posting';
    public const QUEUE   = 'asset-postings';

    public int $tries = 3;

    public function backoff(): array
    {
        return [30, 90, 180];
    }

    public function __construct(
        public readonly int    $eventId,
        public readonly int    $propertyId,
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

    public function handle(InvestmentPropertyPostingService $posting): void
    {
        $context = [
            'event_id'    => $this->eventId,
            'property_id' => $this->propertyId,
            'action'      => $this->action,
            'attempt'     => $this->attempts(),
        ];

        Log::info('PostInvestmentPropertyActionJob: starting', $context);

        $property = InvestmentProperty::with(['company.user', 'investmentPropertyClass'])->findOrFail($this->propertyId);
        $user     = User::findOrFail($this->userId);

        $start = microtime(true);

        try {
            match ($this->action) {
                'acquire' => $posting->postAcquisitionWithAi($property, $user),
                'capitalise' => $posting->postSubsequentCostWithAi(
                    $property, $user,
                    (float) $this->data['amount'],
                    $this->data['date'],
                    $this->data['description'] ?? '',
                ),
                'fair_value_adjust' => $posting->postFairValueAdjustmentWithAi(
                    $property, $user,
                    (float) $this->data['new_fair_value'],
                    $this->data['date'],
                ),
                'impair' => $posting->postImpairmentWithAi(
                    $property, $user,
                    (float) $this->data['impairment_amount'],
                    $this->data['date'],
                    $this->data['reason'] ?? '',
                ),
                'reverse' => $posting->postImpairmentReversalWithAi(
                    $property, $user,
                    (float) $this->data['reversal_amount'],
                    $this->data['date'],
                ),
                'dispose' => $posting->postDisposalWithAi($property->fresh(), $user),
            };

            $elapsed = round((microtime(true) - $start) * 1000);

            $txnId = \App\Models\Transaction::where('company_id', $property->company_id)
                ->where('source_document', 'investment_property:'.$this->propertyId)
                ->latest('id')
                ->value('id');

            InvestmentPropertyEvent::where('id', $this->eventId)
                ->update(['journal_status' => InvestmentPropertyEvent::STATUS_POSTED, 'transaction_id' => $txnId]);
            CircuitBreaker::recordSuccess(self::SERVICE);
            event(new PostingStatusUpdated($property->company_id, 'investment_property_event', $this->eventId, 'posted', ucfirst($this->action).' posted — '.$property->name));

            Log::info('PostInvestmentPropertyActionJob: posted successfully', $context + [
                'property_name' => $property->name,
                'elapsed_ms'    => $elapsed,
            ]);
        } catch (Throwable $e) {
            $elapsed = round((microtime(true) - $start) * 1000);

            CircuitBreaker::recordFailure(self::SERVICE);

            if ($this->isTransient($e) && $this->attempts() < $this->tries) {
                $delay = $this->retryAfter($e) ?? $this->backoffFor($this->attempts());

                Log::warning('PostInvestmentPropertyActionJob: transient error, will retry', $context + [
                    'property_name' => $property->name ?? 'unknown',
                    'elapsed_ms'    => $elapsed,
                    'error'         => $e->getMessage(),
                    'exception'     => get_class($e),
                    'retry_in_s'    => $delay,
                ]);

                $this->release($delay);
                return;
            }

            InvestmentPropertyEvent::where('id', $this->eventId)
                ->update(['journal_status' => InvestmentPropertyEvent::STATUS_FAILED]);
            event(new PostingStatusUpdated($property->company_id, 'investment_property_event', $this->eventId, 'failed', ucfirst($this->action).' failed — '.$property->name));

            Log::error('PostInvestmentPropertyActionJob: failed', $context + [
                'property_name' => $property->name ?? 'unknown',
                'elapsed_ms'    => $elapsed,
                'error'         => $e->getMessage(),
                'exception'     => get_class($e),
                'will_retry'    => false,
            ]);

            throw $e;
        }
    }

    public function failed(?Throwable $e): void
    {
        InvestmentPropertyEvent::where('id', $this->eventId)
            ->update(['journal_status' => InvestmentPropertyEvent::STATUS_FAILED]);

        Log::error('PostInvestmentPropertyActionJob: exhausted retries', [
            'event_id'    => $this->eventId,
            'property_id' => $this->propertyId,
            'action'      => $this->action,
            'error'       => $e?->getMessage(),
            'exception'   => $e ? get_class($e) : null,
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
