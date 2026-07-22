<?php

namespace App\Jobs;

use App\Events\PostingStatusUpdated;
use App\Models\BiologicalAsset;
use App\Models\BiologicalAssetEvent;
use App\Models\User;
use App\Services\BiologicalAssetPostingService;
use App\Services\CircuitBreaker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class PostBiologicalAssetActionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public array $backoff = [30, 90, 180];

    private const SERVICE = 'biological_asset_posting';

    public function __construct(
        public readonly int    $eventId,
        public readonly int    $assetId,
        public readonly int    $userId,
        public readonly string $action,
        public readonly array  $data = [],
    ) {
        $this->queue = 'asset-postings';
    }

    public function handle(BiologicalAssetPostingService $posting): void
    {
        if (CircuitBreaker::isOpen(self::SERVICE)) {
            $this->release(120);
            return;
        }

        $context = ['event_id' => $this->eventId, 'asset_id' => $this->assetId, 'action' => $this->action];
        Log::info('PostBiologicalAssetActionJob: starting', $context);

        $asset = BiologicalAsset::with(['company.user', 'biologicalAssetClass'])->findOrFail($this->assetId);
        $user  = User::findOrFail($this->userId);

        $start = microtime(true);

        try {
            match ($this->action) {
                'acquire' => $posting->postAcquisitionWithAi($asset, $user),
                'fair_value_adjust' => $posting->postFairValueAdjustmentWithAi(
                    $asset, $user,
                    (float) $this->data['new_fair_value'],
                    $this->data['date'],
                ),
                'harvest' => $posting->postHarvestWithAi(
                    $asset, $user,
                    (float) $this->data['fair_value_at_harvest'],
                    (float) $this->data['quantity'],
                    $this->data['date'],
                    $this->data['description'] ?? '',
                ),
                'dispose' => $posting->postDisposalWithAi($asset->fresh(), $user),
            };

            $elapsed = round((microtime(true) - $start) * 1000);

            $txnId = \App\Models\Transaction::where('company_id', $asset->company_id)
                ->where('source_document', 'biological_asset:'.$this->assetId)
                ->latest('id')
                ->value('id');

            BiologicalAssetEvent::where('id', $this->eventId)->update([
                'journal_status' => BiologicalAssetEvent::STATUS_POSTED,
                'transaction_id' => $txnId,
            ]);
            CircuitBreaker::recordSuccess(self::SERVICE);
            event(new PostingStatusUpdated($asset->company_id, 'biological_asset_event', $this->eventId, 'posted', ucfirst($this->action).' posted — '.$asset->name));

            Log::info('PostBiologicalAssetActionJob: posted successfully', $context + ['elapsed_ms' => $elapsed]);
        } catch (Throwable $e) {
            $elapsed = round((microtime(true) - $start) * 1000);
            CircuitBreaker::recordFailure(self::SERVICE);

            BiologicalAssetEvent::where('id', $this->eventId)->update(['journal_status' => BiologicalAssetEvent::STATUS_FAILED]);
            event(new PostingStatusUpdated($asset->company_id, 'biological_asset_event', $this->eventId, 'failed', ucfirst($this->action).' failed — '.$asset->name));

            Log::error('PostBiologicalAssetActionJob: failed', $context + [
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
