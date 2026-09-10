<?php

namespace App\Jobs;

use App\Events\PostingStatusUpdated;
use App\Models\BorrowingCostCapitalisation;
use App\Models\BorrowingCostEvent;
use App\Models\User;
use App\Services\BorrowingCostPostingService;
use App\Services\CircuitBreaker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class PostBorrowingCostActionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public array $backoff = [30, 90, 180];

    private const SERVICE = 'borrowing_cost_posting';

    public function __construct(
        public readonly int    $eventId,
        public readonly int    $capitalisationId,
        public readonly int    $userId,
        public readonly string $action,
        public readonly array  $data = [],
    ) {
        $this->onConnection('ai');
        $this->queue = 'asset-postings';
    }

    public function handle(BorrowingCostPostingService $posting): void
    {
        if (CircuitBreaker::isOpen(self::SERVICE)) {
            $this->release(120);
            return;
        }

        $context = ['event_id' => $this->eventId, 'capitalisation_id' => $this->capitalisationId, 'action' => $this->action];
        Log::info('PostBorrowingCostActionJob: starting', $context);

        $capitalisation = BorrowingCostCapitalisation::with(['company.user'])->findOrFail($this->capitalisationId);
        $user           = User::findOrFail($this->userId);

        $start = microtime(true);

        try {
            match ($this->action) {
                'capitalise' => $posting->postCapitalisationWithAi(
                    $capitalisation, $user,
                    (float) $this->data['amount'],
                    $this->data['date'],
                ),
                'suspend'  => $posting->postSuspensionWithAi($capitalisation, $user, $this->data['date'] ?? now()->toDateString()),
                'complete' => $posting->postCompletionWithAi($capitalisation, $user, $this->data['date'] ?? now()->toDateString()),
            };

            $elapsed = round((microtime(true) - $start) * 1000);

            $txnId = \App\Models\Transaction::where('company_id', $capitalisation->company_id)
                ->where('source_document', 'borrowing_cost:'.$this->capitalisationId)
                ->latest('id')
                ->value('id');

            BorrowingCostEvent::where('id', $this->eventId)->update([
                'journal_status' => BorrowingCostEvent::STATUS_POSTED,
                'transaction_id' => $txnId,
            ]);
            CircuitBreaker::recordSuccess(self::SERVICE);
            event(new PostingStatusUpdated($capitalisation->company_id, 'borrowing_cost_event', $this->eventId, 'posted', ucfirst($this->action).' posted — '.$capitalisation->borrowing_source));

            Log::info('PostBorrowingCostActionJob: posted successfully', $context + ['elapsed_ms' => $elapsed]);
        } catch (Throwable $e) {
            $elapsed = round((microtime(true) - $start) * 1000);
            CircuitBreaker::recordFailure(self::SERVICE);

            BorrowingCostEvent::where('id', $this->eventId)->update(['journal_status' => BorrowingCostEvent::STATUS_FAILED]);
            event(new PostingStatusUpdated($capitalisation->company_id, 'borrowing_cost_event', $this->eventId, 'failed', ucfirst($this->action).' failed — '.$capitalisation->borrowing_source));

            Log::error('PostBorrowingCostActionJob: failed', $context + [
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
