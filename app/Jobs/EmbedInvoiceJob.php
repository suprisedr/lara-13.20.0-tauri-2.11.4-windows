<?php

namespace App\Jobs;
use App\Models\Invoice;
use App\Models\InvoiceVector;
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

class EmbedInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const SERVICE = 'gemini-embedding';

    public const QUEUE = 'embeddings';

    public int $tries = 3;

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [30, 90, 180];
    }

    public function __construct(public readonly int $invoiceId)
    {
        $this->onConnection('ai')->onQueue(self::QUEUE);
    }

    /** @return array<int, object> */
    public function middleware(): array
    {
        return [new CircuitBreakerMiddleware(self::SERVICE)];
    }

    public function handle(GeminiEmbeddingService $gemini): void
    {
        $invoice = Invoice::with(['company', 'items', 'customer'])->find($this->invoiceId);
        if (! $invoice || $invoice->is_embedded) {
            return;
        }

        try {
            $embedding = $gemini->embed($invoice->toEmbeddableText(), 'RETRIEVAL_DOCUMENT');
            CircuitBreaker::recordSuccess(self::SERVICE);
            EmbeddingRecovery::clear();
        } catch (GeminiRateLimitedException $e) {
            CircuitBreaker::recordFailure(self::SERVICE);
            EmbeddingSuspension::suspendFor($e->retryAfterSeconds);
            EmbeddingRecovery::set('inv', $this->invoiceId);
            $this->release($e->retryAfterSeconds);
            return;
        } catch (Throwable $e) {
            CircuitBreaker::recordFailure(self::SERVICE);
            EmbeddingRecovery::set('inv', $this->invoiceId);
            throw $e;
        }

        $vectorLiteral = TransactionVector::formatVector($embedding);

        DB::connection('pgsql')->transaction(function () use ($invoice, $vectorLiteral) {
            $payload = [
                'mysql_invoice_id' => $invoice->id,
                'amount' => $invoice->embeddableAmount(),
                'currency' => $invoice->embeddableCurrency(),
                'customer_name' => $invoice->customer_name,
                'invoice_number' => $invoice->invoice_number,
                'invoice_date' => optional($invoice->invoice_date)->format('Y-m-d'),
                'updated_at' => now(),
            ];

            $existing = InvoiceVector::where('mysql_invoice_id', $invoice->id)->first();
            if ($existing) {
                InvoiceVector::where('id', $existing->id)->update($payload);
                $id = $existing->id;
            } else {
                $payload['created_at'] = now();
                $id = DB::connection('pgsql')->table('invoice_vectors')->insertGetId($payload);
            }

            DB::connection('pgsql')->statement(
                'UPDATE invoice_vectors SET embedding = ?::vector WHERE id = ?',
                [$vectorLiteral, $id]
            );
        });

        $invoice->forceFill([
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
        if (EmbeddingRecovery::isPivot('inv', $this->invoiceId)) {
            EmbeddingRecovery::clear();
        }
    }

    /**
     * Defer this job WITHOUT consuming a retry. The current queue entry is
     * deleted and a fresh dispatch with `$delay` is queued — fresh copies get
     * `attempts = 0`, so routine bouncing does not exhaust $tries.
     */
    public function bounce(int $delay): void
    {
        self::dispatch($this->invoiceId)
            ->onQueue(self::QUEUE)
            ->delay(now()->addSeconds(max($delay, 1)));

        $this->delete();
    }
}
