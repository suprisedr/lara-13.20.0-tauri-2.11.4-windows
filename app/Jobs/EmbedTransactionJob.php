<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Models\TransactionVector;
use App\Queue\Middleware\CircuitBreakerMiddleware;
use App\Services\EmbeddingRecovery;
use App\Services\EmbeddingSuspension;
use App\Services\GeminiEmbeddingService;
use App\Services\GeminiRateLimitedException;
use GabrielAnhaia\LaravelCircuitBreaker\Facades\CircuitBreaker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class EmbedTransactionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Circuit breaker service name guarding the Gemini embedding API. */
    public const SERVICE = 'gemini-embedding';

    /** Dedicated queue so embed failures cannot block unrelated jobs. */
    public const QUEUE = 'embeddings';

    public int $tries = 3;

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [30, 90, 180];
    }

    public function __construct(public readonly int $transactionId)
    {
        $this->onQueue(self::QUEUE);
    }

    /** @return array<int, object> */
    public function middleware(): array
    {
        return [new CircuitBreakerMiddleware(self::SERVICE)];
    }

    public function handle(GeminiEmbeddingService $gemini): void
    {
        $tx = Transaction::with(['company', 'journalLines'])->find($this->transactionId);
        if (! $tx || $tx->is_embedded) {
            return;
        }

        try {
            $embedding = $gemini->embed($tx->toEmbeddableText(), 'RETRIEVAL_DOCUMENT');
            CircuitBreaker::recordSuccess(self::SERVICE);
            // This job just proved the pipeline is healthy — if it was the
            // recovery pivot (or any stale pivot remains), clear the latch so
            // the rest of the backlog can flow again.
            EmbeddingRecovery::clear();
        } catch (GeminiRateLimitedException $e) {
            CircuitBreaker::recordFailure(self::SERVICE);
            EmbeddingSuspension::suspendFor($e->retryAfterSeconds);
            EmbeddingRecovery::set('tx', $this->transactionId);
            $this->release($e->retryAfterSeconds);
            return;
        } catch (Throwable $e) {
            CircuitBreaker::recordFailure(self::SERVICE);
            // Become the recovery pivot — every other embed job will yield
            // until this one succeeds (or finally fails).
            EmbeddingRecovery::set('tx', $this->transactionId);
            throw $e;
        }

        $vectorLiteral = TransactionVector::formatVector($embedding);

        DB::connection('pgsql')->transaction(function () use ($tx, $vectorLiteral) {
            $payload = [
                'mysql_transaction_id' => $tx->id,
                'amount' => $tx->embeddableAmount(),
                'currency' => $tx->embeddableCurrency(),
                'counterparty' => $tx->embeddableCounterparty(),
                'reference' => $tx->reference,
                'tx_date' => optional($tx->transaction_date)->format('Y-m-d'),
                'updated_at' => now(),
            ];

            $existing = TransactionVector::where('mysql_transaction_id', $tx->id)->first();
            if ($existing) {
                TransactionVector::where('id', $existing->id)->update($payload);
                $id = $existing->id;
            } else {
                $payload['created_at'] = now();
                $id = DB::connection('pgsql')->table('transaction_vectors')->insertGetId($payload);
            }

            DB::connection('pgsql')->statement(
                'UPDATE transaction_vectors SET embedding = ?::vector WHERE id = ?',
                [$vectorLiteral, $id]
            );
        });

        $tx->forceFill([
            'is_embedded' => true,
            'embedded_at' => now(),
        ])->save();
    }

    /**
     * Final failure (all tries exhausted). Release the pivot role so the next
     * failing job can take over — otherwise the whole queue stays suspended.
     */
    public function failed(Throwable $e): void
    {
        if (EmbeddingRecovery::isPivot('tx', $this->transactionId)) {
            EmbeddingRecovery::clear();
        }
    }

    /**
     * Defer this job WITHOUT consuming a retry. Called by the middleware when
     * the pipeline is suspended — the current queue entry is deleted and a
     * fresh dispatch with `$delay` is queued. The fresh copy gets a clean
     * `attempts = 0`, so routine bouncing does not exhaust $tries.
     */
    public function bounce(int $delay): void
    {
        self::dispatch($this->transactionId)
            ->onQueue(self::QUEUE)
            ->delay(now()->addSeconds(max($delay, 1)));

        $this->delete();
    }
}
