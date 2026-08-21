<?php

namespace App\Jobs;

use App\Events\PostingStatusUpdated;
use App\Models\ShareBasedPaymentArrangement;
use App\Models\ShareBasedPaymentEvent;
use App\Models\User;
use App\Services\CircuitBreaker;
use App\Services\ShareBasedPaymentPostingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class PostShareBasedPaymentActionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public array $backoff = [30, 90, 180];

    private const SERVICE = 'sbp_posting';

    public function __construct(
        public readonly int    $eventId,
        public readonly int    $arrangementId,
        public readonly int    $userId,
        public readonly string $action,
        public readonly array  $data = [],
    ) {
        $this->onConnection('ai');
        $this->queue = 'asset-postings';
    }

    public function handle(ShareBasedPaymentPostingService $posting): void
    {
        if (CircuitBreaker::isOpen(self::SERVICE)) {
            $this->release(120);
            return;
        }

        $context = ['event_id' => $this->eventId, 'arrangement_id' => $this->arrangementId, 'action' => $this->action];
        Log::info('PostShareBasedPaymentActionJob: starting', $context);

        $arrangement = ShareBasedPaymentArrangement::with(['company.user'])->findOrFail($this->arrangementId);
        $user        = User::findOrFail($this->userId);

        $start = microtime(true);

        try {
            match ($this->action) {
                'vesting_expense' => $posting->postVestingExpenseWithAi(
                    $arrangement, $user,
                    (float) $this->data['amount'],
                    $this->data['date'],
                ),
                'exercise' => $posting->postExerciseWithAi(
                    $arrangement, $user,
                    (float) $this->data['amount'],
                    (int) $this->data['instruments'],
                    $this->data['date'],
                ),
                'forfeiture' => $posting->postForfeitureWithAi(
                    $arrangement, $user,
                    (float) $this->data['amount'],
                    (int) $this->data['instruments'],
                    $this->data['date'],
                ),
            };

            $elapsed = round((microtime(true) - $start) * 1000);

            $txnId = \App\Models\Transaction::where('company_id', $arrangement->company_id)
                ->where('source_document', 'sbp:'.$this->arrangementId)
                ->latest('id')
                ->value('id');

            ShareBasedPaymentEvent::where('id', $this->eventId)->update([
                'journal_status' => ShareBasedPaymentEvent::STATUS_POSTED,
                'transaction_id' => $txnId,
            ]);
            CircuitBreaker::recordSuccess(self::SERVICE);
            event(new PostingStatusUpdated($arrangement->company_id, 'sbp_event', $this->eventId, 'posted', ucfirst($this->action).' posted — '.$arrangement->name));

            Log::info('PostShareBasedPaymentActionJob: posted successfully', $context + ['elapsed_ms' => $elapsed]);
        } catch (Throwable $e) {
            $elapsed = round((microtime(true) - $start) * 1000);
            CircuitBreaker::recordFailure(self::SERVICE);

            ShareBasedPaymentEvent::where('id', $this->eventId)->update(['journal_status' => ShareBasedPaymentEvent::STATUS_FAILED]);
            event(new PostingStatusUpdated($arrangement->company_id, 'sbp_event', $this->eventId, 'failed', ucfirst($this->action).' failed — '.$arrangement->name));

            Log::error('PostShareBasedPaymentActionJob: failed', $context + [
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
