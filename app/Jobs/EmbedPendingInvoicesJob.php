<?php

namespace App\Jobs;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EmbedPendingInvoicesJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const QUIET_WINDOW_SECONDS = 60;
    public const LAST_SEEN_KEY = 'invoices:embed:last_seen_at';

    public int $uniqueFor = 120;

    public function __construct()
    {
        $this->onConnection('ai')->onQueue(EmbedInvoiceJob::QUEUE);
    }

    public function uniqueId(): string
    {
        return 'invoices:embed:debounce';
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
        Invoice::notEmbedded()
            ->orderBy('id')
            ->each(function (Invoice $invoice) use (&$count, $dispatcher) {
                $dispatcher->embedInvoice($invoice->id);
                $count++;
            });

        Cache::forget(self::LAST_SEEN_KEY);

        if ($count > 0) {
            Log::info("EmbedPendingInvoicesJob: queued {$count} invoices for embedding.");
        }
    }
}
