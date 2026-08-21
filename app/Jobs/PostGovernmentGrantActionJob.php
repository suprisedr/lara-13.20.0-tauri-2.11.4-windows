<?php

namespace App\Jobs;

use App\Events\PostingStatusUpdated;
use App\Models\GovernmentGrant;
use App\Models\GovernmentGrantEvent;
use App\Models\User;
use App\Services\CircuitBreaker;
use App\Services\GovernmentGrantPostingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class PostGovernmentGrantActionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public array $backoff = [30, 90, 180];

    private const SERVICE = 'government_grant_posting';

    public function __construct(
        public readonly int    $eventId,
        public readonly int    $grantId,
        public readonly int    $userId,
        public readonly string $action,
        public readonly array  $data = [],
    ) {
        $this->onConnection('ai');
        $this->queue = 'asset-postings';
    }

    public function handle(GovernmentGrantPostingService $posting): void
    {
        if (CircuitBreaker::isOpen(self::SERVICE)) {
            $this->release(120);
            return;
        }

        $context = ['event_id' => $this->eventId, 'grant_id' => $this->grantId, 'action' => $this->action];
        Log::info('PostGovernmentGrantActionJob: starting', $context);

        $grant = GovernmentGrant::with(['company.user'])->findOrFail($this->grantId);
        $user  = User::findOrFail($this->userId);

        $start = microtime(true);

        try {
            match ($this->action) {
                'recognise' => $posting->postGrantRecognitionWithAi(
                    $grant, $user,
                    (float) $this->data['amount'],
                    $this->data['date'],
                ),
                'amortise' => $posting->postAmortisationWithAi(
                    $grant, $user,
                    (float) $this->data['amount'],
                    $this->data['date'],
                ),
                'refund' => $posting->postRefundWithAi(
                    $grant, $user,
                    (float) $this->data['amount'],
                    $this->data['date'],
                ),
            };

            $elapsed = round((microtime(true) - $start) * 1000);

            $txnId = \App\Models\Transaction::where('company_id', $grant->company_id)
                ->where('source_document', 'government_grant:'.$this->grantId)
                ->latest('id')
                ->value('id');

            GovernmentGrantEvent::where('id', $this->eventId)->update([
                'journal_status' => GovernmentGrantEvent::STATUS_POSTED,
                'transaction_id' => $txnId,
            ]);
            CircuitBreaker::recordSuccess(self::SERVICE);
            event(new PostingStatusUpdated($grant->company_id, 'government_grant_event', $this->eventId, 'posted', ucfirst($this->action).' posted — '.$grant->name));

            Log::info('PostGovernmentGrantActionJob: posted successfully', $context + ['elapsed_ms' => $elapsed]);
        } catch (Throwable $e) {
            $elapsed = round((microtime(true) - $start) * 1000);
            CircuitBreaker::recordFailure(self::SERVICE);

            GovernmentGrantEvent::where('id', $this->eventId)->update(['journal_status' => GovernmentGrantEvent::STATUS_FAILED]);
            event(new PostingStatusUpdated($grant->company_id, 'government_grant_event', $this->eventId, 'failed', ucfirst($this->action).' failed — '.$grant->name));

            Log::error('PostGovernmentGrantActionJob: failed', $context + [
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
