<?php

namespace App\Jobs;

use App\Events\PostingStatusUpdated;
use App\Models\PerformanceObligation;
use App\Models\RevenueContract;
use App\Models\RevenueContractEvent;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CircuitBreaker;
use App\Services\RevenuePostingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class PostRevenueActionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public array $backoff = [30, 90, 180];

    private const SERVICE = 'revenue_posting';

    public function __construct(
        public readonly int    $eventId,
        public readonly int    $contractId,
        public readonly int    $userId,
        public readonly string $action,
        public readonly array  $data = [],
    ) {
        $this->onConnection('ai');
        $this->queue = 'asset-postings';
    }

    public function handle(RevenuePostingService $posting): void
    {
        if (CircuitBreaker::isOpen(self::SERVICE)) {
            $this->release(120);
            return;
        }

        $context = ['event_id' => $this->eventId, 'contract_id' => $this->contractId, 'action' => $this->action];
        Log::info('PostRevenueActionJob: starting', $context);

        $contract = RevenueContract::with(['company.user', 'performanceObligations'])->findOrFail($this->contractId);
        $user     = User::findOrFail($this->userId);

        $start = microtime(true);

        try {
            $obligationId = $this->data['performance_obligation_id'] ?? null;
            $obligation   = $obligationId ? PerformanceObligation::findOrFail($obligationId) : null;

            match ($this->action) {
                'recognise_revenue' => $posting->postRevenueRecognitionWithAi(
                    $contract, $obligation, $user,
                    (float) $this->data['amount'],
                    $this->data['date'],
                ),
                'advance_receipt' => $posting->postContractLiabilityWithAi(
                    $contract, $user,
                    (float) $this->data['amount'],
                    $this->data['date'],
                ),
                'release_liability' => $posting->postContractLiabilityReleaseWithAi(
                    $contract, $obligation, $user,
                    (float) $this->data['amount'],
                    $this->data['date'],
                ),
            };

            $elapsed = round((microtime(true) - $start) * 1000);

            $txnId = Transaction::where('company_id', $contract->company_id)
                ->where('source_document', 'revenue_contract:'.$this->contractId)
                ->latest('id')
                ->value('id');

            RevenueContractEvent::where('id', $this->eventId)->update([
                'journal_status' => RevenueContractEvent::STATUS_POSTED,
                'transaction_id' => $txnId,
            ]);
            CircuitBreaker::recordSuccess(self::SERVICE);
            event(new PostingStatusUpdated($contract->company_id, 'revenue_contract_event', $this->eventId, 'posted', ucfirst(str_replace('_', ' ', $this->action)).' posted — '.$contract->name));

            Log::info('PostRevenueActionJob: posted successfully', $context + ['elapsed_ms' => $elapsed]);
        } catch (Throwable $e) {
            $elapsed = round((microtime(true) - $start) * 1000);
            CircuitBreaker::recordFailure(self::SERVICE);

            RevenueContractEvent::where('id', $this->eventId)->update(['journal_status' => RevenueContractEvent::STATUS_FAILED]);
            event(new PostingStatusUpdated($contract->company_id, 'revenue_contract_event', $this->eventId, 'failed', ucfirst(str_replace('_', ' ', $this->action)).' failed — '.$contract->name));

            Log::error('PostRevenueActionJob: failed', $context + ['error' => $e->getMessage(), 'elapsed_ms' => $elapsed]);

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
