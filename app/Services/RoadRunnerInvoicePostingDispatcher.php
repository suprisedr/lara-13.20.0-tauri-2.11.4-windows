<?php

namespace App\Services;

use App\Events\InvoiceCreated;
use App\Events\InvoiceMarkedPaid;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Spiral\Goridge\RPC\RPC;
use Spiral\RoadRunner\Jobs\Jobs;

class RoadRunnerInvoicePostingDispatcher
{
    private ?Jobs $jobs = null;

    private function jobs(): Jobs
    {
        if ($this->jobs === null) {
            $this->jobs = new Jobs(RPC::create('tcp://127.0.0.1:6001'));
        }
        return $this->jobs;
    }

    public function dispatchPosting(Invoice $invoice): void
    {
        $payload = json_encode([
            'invoiceId' => $invoice->id,
            'companyId' => $invoice->company_id,
        ]);

        try {
            $queue = $this->jobs()->connect('invoice-postings');
            $task  = $queue->create('post_invoice', $payload);
            $queue->dispatch($task);
        } catch (\Throwable $e) {
            Log::warning('RoadRunnerInvoicePostingDispatcher: RR unavailable, falling back to Laravel event', [
                'invoice_id' => $invoice->id,
                'error'      => $e->getMessage(),
            ]);
            InvoiceCreated::dispatch($invoice);
        }
    }

    public function dispatchPayment(Invoice $invoice, User $user): void
    {
        $payload = json_encode([
            'invoiceId' => $invoice->id,
            'companyId' => $invoice->company_id,
            'userId'    => $user->id,
        ]);

        try {
            $queue = $this->jobs()->connect('invoice-postings');
            $task  = $queue->create('post_invoice_payment', $payload);
            $queue->dispatch($task);
        } catch (\Throwable $e) {
            Log::warning('RoadRunnerInvoicePostingDispatcher: RR unavailable, falling back to Laravel event', [
                'invoice_id' => $invoice->id,
                'error'      => $e->getMessage(),
            ]);
            InvoiceMarkedPaid::dispatch($invoice, $user);
        }
    }
}
