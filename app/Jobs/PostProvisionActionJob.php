<?php

namespace App\Jobs;

use App\Events\PostingStatusUpdated;
use App\Models\Provision;
use App\Models\ProvisionEvent;
use App\Models\User;
use App\Services\CircuitBreaker;
use App\Services\ProvisionPostingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class PostProvisionActionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public array $backoff = [30, 90, 180];

    private const SERVICE = 'provision_posting';

    public function __construct(
        public readonly int    $eventId,
        public readonly int    $provisionId,
        public readonly int    $userId,
        public readonly string $action,
        public readonly array  $data = [],
    ) {
        $this->onConnection('ai');
        $this->queue = 'provision-postings';
    }

    public function handle(ProvisionPostingService $posting): void
    {
        if (CircuitBreaker::isOpen(self::SERVICE)) {
            $this->release(120);
            return;
        }

        $context = ['event_id' => $this->eventId, 'provision_id' => $this->provisionId, 'action' => $this->action];
        Log::info('PostProvisionActionJob: starting', $context);

        $provision = Provision::with(['company.user', 'provisionClass'])->findOrFail($this->provisionId);
        $user      = User::findOrFail($this->userId);

        $start = microtime(true);

        try {
            match ($this->action) {
                'recognise' => $posting->postRecognitionWithAi($provision, $user),
                'remeasure' => $posting->postRemeasurementWithAi(
                    $provision, $user,
                    (float) $this->data['new_estimate'],
                    $this->data['date'],
                ),
                'unwind' => $posting->postUnwindingWithAi(
                    $provision, $user,
                    (float) $this->data['unwinding_amount'],
                    $this->data['date'],
                ),
                'utilise' => $posting->postUtilisationWithAi(
                    $provision, $user,
                    (float) $this->data['amount'],
                    $this->data['date'],
                ),
                'reverse' => $posting->postReversalWithAi(
                    $provision, $user,
                    $this->data['date'],
                ),
            };

            $elapsed = round((microtime(true) - $start) * 1000);

            $txnId = \App\Models\Transaction::where('company_id', $provision->company_id)
                ->where('source_document', 'provision:'.$this->provisionId)
                ->latest('id')
                ->value('id');

            ProvisionEvent::where('id', $this->eventId)->update([
                'journal_status' => ProvisionEvent::STATUS_POSTED,
                'transaction_id' => $txnId,
            ]);
            CircuitBreaker::recordSuccess(self::SERVICE);
            event(new PostingStatusUpdated($provision->company_id, 'provision_event', $this->eventId, 'posted', ucfirst($this->action).' posted — '.$provision->name));

            Log::info('PostProvisionActionJob: posted successfully', $context + ['elapsed_ms' => $elapsed]);
        } catch (Throwable $e) {
            $elapsed = round((microtime(true) - $start) * 1000);
            CircuitBreaker::recordFailure(self::SERVICE);

            ProvisionEvent::where('id', $this->eventId)->update(['journal_status' => ProvisionEvent::STATUS_FAILED]);
            event(new PostingStatusUpdated($provision->company_id, 'provision_event', $this->eventId, 'failed', ucfirst($this->action).' failed — '.$provision->name));

            Log::error('PostProvisionActionJob: failed', $context + [
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
