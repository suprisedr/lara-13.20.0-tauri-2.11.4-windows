<?php

namespace App\Listeners;

use App\Events\InvoiceCreated;
use App\Models\CompanyAction;
use App\Services\InvoicePostingService;
use GabrielAnhaia\LaravelCircuitBreaker\Facades\CircuitBreaker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Laravel\Ai\Exceptions\RateLimitedException;
use Throwable;

class PostInvoiceWithAi implements ShouldQueue
{
    use InteractsWithQueue;

    /** The circuit breaker service name used to guard the AI provider. */
    private const SERVICE = 'invoice-ai-posting';

    public int $tries = 15;

    public int $maxExceptions = 3;

    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(30);
    }

    /**
     * Calculate per-attempt backoff: 30s, 90s, 180s.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 90, 180];
    }

    /**
     * Handle the event.
     */
    public function handle(InvoiceCreated $event): void
    {
        $invoice = $event->invoice->fresh('items');

        if (! $invoice || $invoice->posting_transaction_id) {
            return;
        }

        $company = $invoice->company;
        $postingService = app(InvoicePostingService::class);

        if (! CircuitBreaker::canPass(self::SERVICE)) {
            // The AI provider is currently failing — wait for the circuit to
            // recover before trying this invoice again.
            $this->release(config('circuit_breaker.services.' . self::SERVICE . '.open_timeout', 120));

            return;
        }

        try {
            $postingService->postWithAi($company, $invoice, $company->user);
            CircuitBreaker::recordSuccess(self::SERVICE);

            Log::info('[InvoiceAI] Posted', [
                'invoice_id' => $invoice->id,
                'company_id' => $company->id,
            ]);
        } catch (InvalidArgumentException $e) {
            Log::warning('[InvoiceAI] Skipped — invalid argument', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            return;
        } catch (RequestException $e) {
            CircuitBreaker::recordFailure(self::SERVICE);

            $status = $e->response?->status();
            $body = $e->response?->json() ?? $e->response?->body();

            Log::error('[InvoiceAI] API error', [
                'invoice_id' => $invoice->id,
                'attempt' => $this->attempts(),
                'http_status' => $status,
                'response' => is_array($body) ? $body : mb_substr((string) $body, 0, 500),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } catch (Throwable $e) {
            CircuitBreaker::recordFailure(self::SERVICE);

            Log::error('[InvoiceAI] Unexpected failure', [
                'invoice_id' => $invoice->id,
                'attempt' => $this->attempts(),
                'exception' => get_class($e),
                'error' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
            ]);

            throw $e;
        }
    }

    public function failed(InvoiceCreated $event, Throwable $exception): void
    {
        $invoice = $event->invoice;

        Log::critical('[InvoiceAI] Permanently failed', [
            'invoice_id' => $invoice->id,
            'company_id' => $invoice->company_id,
            'exception' => get_class($exception),
            'error' => $exception->getMessage(),
        ]);

        CompanyAction::create([
            'company_id' => $invoice->company_id,
            'title' => "AI invoice posting failed — Invoice #{$invoice->invoice_number}",
            'body' => get_class($exception).': '.$exception->getMessage(),
            'priority' => CompanyAction::PRIORITY_HIGH,
            'source' => CompanyAction::SOURCE_SYSTEM,
            'related_type' => 'invoice',
            'related_id' => $invoice->id,
        ]);
    }
}
