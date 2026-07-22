<?php

namespace App\Jobs;

use App\Events\PostingStatusUpdated;
use App\Models\InventoryMovement;
use App\Models\User;
use App\Queue\Middleware\CircuitBreakerMiddleware;
use App\Services\InventoryPostingService;
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

class PostInventoryActionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const SERVICE = 'inventory-ai-posting';
    public const QUEUE   = 'inventory-postings';

    public int $tries = 3;

    public function backoff(): array
    {
        return [30, 90, 180];
    }

    public function __construct(
        public readonly int $movementId,
        public readonly int $userId,
    ) {
        $this->onQueue(self::QUEUE);
    }

    public function middleware(): array
    {
        return [new CircuitBreakerMiddleware(self::SERVICE)];
    }

    public function handle(InventoryPostingService $posting): void
    {
        $context = [
            'movement_id' => $this->movementId,
            'attempt'     => $this->attempts(),
        ];

        Log::info('PostInventoryActionJob: starting', $context);

        $movement = InventoryMovement::with(['inventoryItem', 'company'])->findOrFail($this->movementId);
        $user = User::findOrFail($this->userId);

        $start = microtime(true);

        try {
            $posting->postMovementWithAi($movement, $user);

            $elapsed = round((microtime(true) - $start) * 1000);

            $txnId = \App\Models\Transaction::where('company_id', $movement->company_id)
                ->where('source_document', 'inventory_movement:'.$movement->id)
                ->latest('id')
                ->value('id');

            $movement->update(['journal_status' => InventoryMovement::STATUS_POSTED, 'transaction_id' => $txnId]);
            CircuitBreaker::recordSuccess(self::SERVICE);
            event(new PostingStatusUpdated($movement->company_id, 'inventory_movement', $movement->id, 'posted', ucfirst($movement->action->value).' posted — '.($movement->inventoryItem->name ?? '')));

            Log::info('PostInventoryActionJob: posted successfully', $context + [
                'item_name'  => $movement->inventoryItem->name ?? 'unknown',
                'action'     => $movement->action->value,
                'elapsed_ms' => $elapsed,
            ]);
        } catch (Throwable $e) {
            $elapsed = round((microtime(true) - $start) * 1000);

            CircuitBreaker::recordFailure(self::SERVICE);

            if ($this->isTransient($e) && $this->attempts() < $this->tries) {
                $delay = $this->retryAfter($e) ?? $this->backoffFor($this->attempts());

                Log::warning('PostInventoryActionJob: transient provider error, will retry', $context + [
                    'item_name'  => $movement->inventoryItem->name ?? 'unknown',
                    'elapsed_ms' => $elapsed,
                    'error'      => $e->getMessage(),
                    'exception'  => get_class($e),
                    'retry_in_s' => $delay,
                ]);

                $this->release($delay);
                return;
            }

            $movement->update(['journal_status' => InventoryMovement::STATUS_FAILED]);
            event(new PostingStatusUpdated($movement->company_id, 'inventory_movement', $movement->id, 'failed', ucfirst($movement->action->value).' failed — '.($movement->inventoryItem->name ?? '')));

            Log::error('PostInventoryActionJob: failed', $context + [
                'item_name'  => $movement->inventoryItem->name ?? 'unknown',
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
        InventoryMovement::where('id', $this->movementId)
            ->update(['journal_status' => InventoryMovement::STATUS_FAILED]);

        Log::error('PostInventoryActionJob: exhausted retries', [
            'movement_id' => $this->movementId,
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
