<?php

namespace App\Jobs;

use App\Events\PostingStatusUpdated;
use App\Models\DeferredTaxItem;
use App\Models\DeferredTaxEvent;
use App\Models\User;
use App\Services\CircuitBreaker;
use App\Services\DeferredTaxPostingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class PostDeferredTaxActionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public array $backoff = [30, 90, 180];

    private const SERVICE = 'deferred_tax_posting';

    public function __construct(
        public readonly int    $eventId,
        public readonly int    $itemId,
        public readonly int    $userId,
        public readonly string $action,
        public readonly array  $data = [],
    ) {
        $this->onConnection('ai');
        $this->queue = 'asset-postings';
    }

    public function handle(DeferredTaxPostingService $posting): void
    {
        if (CircuitBreaker::isOpen(self::SERVICE)) {
            $this->release(120);
            return;
        }

        $context = ['event_id' => $this->eventId, 'item_id' => $this->itemId, 'action' => $this->action];
        Log::info('PostDeferredTaxActionJob: starting', $context);

        $item = DeferredTaxItem::with(['company.user'])->findOrFail($this->itemId);
        $user = User::findOrFail($this->userId);

        $start = microtime(true);

        try {
            match ($this->action) {
                'initial_recognition' => $posting->postInitialRecognitionWithAi($item, $user),
                'remeasure' => $posting->postRemeasurementWithAi(
                    $item, $user,
                    (float) $this->data['new_tax_base'],
                    (float) $this->data['new_carrying_amount'],
                    (float) $this->data['new_rate'],
                    $this->data['date'],
                ),
                'reverse' => $posting->postReversalWithAi(
                    $item, $user,
                    $this->data['date'],
                ),
            };

            $elapsed = round((microtime(true) - $start) * 1000);

            $txnId = \App\Models\Transaction::where('company_id', $item->company_id)
                ->where('source_document', 'deferred_tax:'.$this->itemId)
                ->latest('id')
                ->value('id');

            DeferredTaxEvent::where('id', $this->eventId)->update([
                'journal_status' => DeferredTaxEvent::STATUS_POSTED,
                'transaction_id' => $txnId,
            ]);
            CircuitBreaker::recordSuccess(self::SERVICE);
            event(new PostingStatusUpdated($item->company_id, 'deferred_tax_event', $this->eventId, 'posted', ucfirst(str_replace('_', ' ', $this->action)).' posted — '.$item->name));

            Log::info('PostDeferredTaxActionJob: posted successfully', $context + ['elapsed_ms' => $elapsed]);
        } catch (Throwable $e) {
            $elapsed = round((microtime(true) - $start) * 1000);
            CircuitBreaker::recordFailure(self::SERVICE);

            DeferredTaxEvent::where('id', $this->eventId)->update(['journal_status' => DeferredTaxEvent::STATUS_FAILED]);
            event(new PostingStatusUpdated($item->company_id, 'deferred_tax_event', $this->eventId, 'failed', ucfirst(str_replace('_', ' ', $this->action)).' failed — '.$item->name));

            Log::error('PostDeferredTaxActionJob: failed', $context + [
                'error'      => $e->getMessage(),
                'elapsed_ms' => $elapsed,
            ]);

            if ($this->isTransient($e)) {
                throw $e;
            }
        }
    }

    private function isTransient(Throwable $e): bool
    {
        $msg = strtolower($e->getMessage());
        return str_contains($msg, 'timeout') || str_contains($msg, '429') || str_contains($msg, '503') || str_contains($msg, 'rate limit');
    }
}
