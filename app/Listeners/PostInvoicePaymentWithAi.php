<?php

namespace App\Listeners;
use App\Events\InvoiceMarkedPaid;
use App\Events\PostingStatusUpdated;
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

class PostInvoicePaymentWithAi implements ShouldQueue
{
    use InteractsWithQueue;

    public string $connection = 'ai';

    private const SERVICE = 'invoice-payment-ai-posting';

    public int $tries = 15;

    public int $maxExceptions = 3;

    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(30);
    }

    public function backoff(): array
    {
        return [30, 90, 180];
    }

    public function handle(InvoiceMarkedPaid $event): void
    {
        $invoice = $event->invoice->fresh('items', 'payments', 'postingTransaction.journalLines');
        $user    = $event->user;

        if (! $invoice) {
            return;
        }

        // Skip if the invoice is already fully paid (no balance remaining).
        $postedTotal = (float) $invoice->payments()->whereNotNull('transaction_id')->sum('amount');
        if (round($invoice->total() - $postedTotal, 2) <= 0) {
            return;
        }

        $company = $invoice->company;

        event(new PostingStatusUpdated(
            $company->id, 'invoice_payment', $invoice->id, 'pending',
            "AI is posting payment for invoice {$invoice->invoice_number}…"
        ));

        if (! CircuitBreaker::canPass(self::SERVICE)) {
            $this->release(config('circuit_breaker.services.' . self::SERVICE . '.open_timeout', 120));
            return;
        }

        try {
            app(InvoicePostingService::class)->postPaymentWithAi($company, $invoice, $user, $event->amount);
            CircuitBreaker::recordSuccess(self::SERVICE);

            event(new PostingStatusUpdated(
                $company->id, 'invoice_payment', $invoice->id, 'posted',
                "Payment for invoice {$invoice->invoice_number} posted successfully"
            ));

            Log::info('[InvoicePaymentAI] Posted', [
                'invoice_id' => $invoice->id,
                'company_id' => $company->id,
            ]);
        } catch (InvalidArgumentException $e) {
            Log::warning('[InvoicePaymentAI] Skipped', [
                'invoice_id' => $invoice->id,
                'error'      => $e->getMessage(),
            ]);
            return;
        } catch (RequestException $e) {
            CircuitBreaker::recordFailure(self::SERVICE);

            Log::error('[InvoicePaymentAI] API error', [
                'invoice_id'  => $invoice->id,
                'attempt'     => $this->attempts(),
                'http_status' => $e->response?->status(),
                'error'       => $e->getMessage(),
            ]);

            throw $e;
        } catch (Throwable $e) {
            CircuitBreaker::recordFailure(self::SERVICE);

            Log::error('[InvoicePaymentAI] Unexpected failure', [
                'invoice_id' => $invoice->id,
                'attempt'    => $this->attempts(),
                'exception'  => get_class($e),
                'error'      => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(InvoiceMarkedPaid $event, Throwable $exception): void
    {
        $invoice = $event->invoice;

        event(new PostingStatusUpdated(
            $invoice->company_id, 'invoice_payment', $invoice->id, 'failed',
            "Payment posting failed for invoice {$invoice->invoice_number}"
        ));

        Log::critical('[InvoicePaymentAI] Permanently failed', [
            'invoice_id' => $invoice->id,
            'company_id' => $invoice->company_id,
            'error'      => $exception->getMessage(),
        ]);

        CompanyAction::create([
            'company_id'   => $invoice->company_id,
            'title'        => "AI payment posting failed — Invoice #{$invoice->invoice_number}",
            'body'         => 'The AI could not post the payment receipt entry. Please use Record Payment manually. Error: ' . $exception->getMessage(),
            'priority'     => CompanyAction::PRIORITY_HIGH,
            'source'       => CompanyAction::SOURCE_SYSTEM,
            'related_type' => 'invoice',
            'related_id'   => $invoice->id,
        ]);
    }
}
