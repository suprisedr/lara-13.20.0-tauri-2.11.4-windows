<?php

namespace App\Jobs;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Debounced batch embed.
 *
 * Each time a Transaction is created, this job is dispatched with a 60s delay
 * and a per-window unique key. While transactions keep being created, the
 * `last_seen_at` cache key is reset, so when this job finally runs it
 * re-schedules itself until 60s of quiet has elapsed, then enqueues
 * per-transaction EmbedTransactionJob runs for every not-embedded row.
 */
class EmbedPendingTransactionsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const QUIET_WINDOW_SECONDS = 60;
    public const LAST_SEEN_KEY = 'transactions:embed:last_seen_at';

    public int $uniqueFor = 120;

    public function __construct()
    {
        $this->onQueue(EmbedTransactionJob::QUEUE);
    }

    public function uniqueId(): string
    {
        return 'transactions:embed:debounce';
    }

    public function handle(): void
    {
        $last = (int) Cache::get(self::LAST_SEEN_KEY, 0);
        $elapsed = now()->timestamp - $last;

        if ($last > 0 && $elapsed < self::QUIET_WINDOW_SECONDS) {
            $remaining = self::QUIET_WINDOW_SECONDS - $elapsed;
            self::dispatch()->delay(now()->addSeconds($remaining));
            return;
        }

        $dispatcher = app(\App\Services\RoadRunnerEmbeddingDispatcher::class);
        $count = 0;
        Transaction::notEmbedded()
            ->orderBy('id')
            ->each(function (Transaction $tx) use (&$count, $dispatcher) {
                $dispatcher->embedTransaction($tx->id);
                $count++;
            });

        Cache::forget(self::LAST_SEEN_KEY);

        if ($count > 0) {
            Log::info("EmbedPendingTransactionsJob: queued {$count} transactions for embedding.");
        }
    }
}
