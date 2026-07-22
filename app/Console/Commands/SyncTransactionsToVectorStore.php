<?php

namespace App\Console\Commands;

use App\Jobs\EmbedTransactionJob;
use App\Models\Transaction;
use Illuminate\Console\Command;

class SyncTransactionsToVectorStore extends Command
{
    protected $signature = 'vectors:sync-transactions
        {--limit=500 : Max transactions to enqueue}
        {--sync : Run embeds inline instead of queueing}';

    protected $description = 'Embed un-synced MySQL transactions into the pgvector store via Gemini.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $sync = (bool) $this->option('sync');

        $dispatcher = app(\App\Services\RoadRunnerEmbeddingDispatcher::class);
        $count = 0;
        Transaction::notEmbedded()
            ->orderBy('id')
            ->limit($limit)
            ->each(function (Transaction $tx) use (&$count, $sync, $dispatcher) {
                if ($sync) {
                    EmbedTransactionJob::dispatchSync($tx->id);
                } else {
                    $dispatcher->embedTransaction($tx->id);
                }
                $count++;
            });

        $this->info(($sync ? 'Embedded' : 'Queued')." {$count} transaction(s).");
        return self::SUCCESS;
    }
}
