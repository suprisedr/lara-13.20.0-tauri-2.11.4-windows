<?php

namespace App\Console\Commands;

use App\Jobs\EmbedInvoiceJob;
use App\Models\Invoice;
use Illuminate\Console\Command;

class SyncInvoicesToVectorStore extends Command
{
    protected $signature = 'vectors:sync-invoices
        {--limit=500 : Max invoices to enqueue}
        {--sync : Run embeds inline instead of queueing}';

    protected $description = 'Embed un-synced MySQL invoices into the pgvector store via Gemini.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $sync = (bool) $this->option('sync');

        $dispatcher = app(\App\Services\RoadRunnerEmbeddingDispatcher::class);
        $count = 0;
        Invoice::notEmbedded()
            ->orderBy('id')
            ->limit($limit)
            ->each(function (Invoice $invoice) use (&$count, $sync, $dispatcher) {
                if ($sync) {
                    EmbedInvoiceJob::dispatchSync($invoice->id);
                } else {
                    $dispatcher->embedInvoice($invoice->id);
                }
                $count++;
            });

        $this->info(($sync ? 'Embedded' : 'Queued')." {$count} invoice(s).");
        return self::SUCCESS;
    }
}
