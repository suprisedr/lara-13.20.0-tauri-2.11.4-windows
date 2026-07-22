<?php

namespace App\Services;

use Spiral\Goridge\RPC\RPC;
use Spiral\RoadRunner\Jobs\Jobs;
use Spiral\RoadRunner\Jobs\Queue\MemoryCreateInfo;

class RoadRunnerEmbeddingDispatcher
{
    private ?Jobs $jobs = null;

    private function jobs(): Jobs
    {
        if ($this->jobs === null) {
            $this->jobs = new Jobs(RPC::create('tcp://127.0.0.1:6001'));
        }
        return $this->jobs;
    }

    public function embedTransaction(int $transactionId): void
    {
        $this->dispatch('embed_transaction', ['id' => $transactionId]);
    }

    public function embedInvoice(int $invoiceId): void
    {
        $this->dispatch('embed_invoice', ['id' => $invoiceId]);
    }

    public function embedAsset(int $assetId): void
    {
        $this->dispatch('embed_asset', ['id' => $assetId]);
    }

    private function dispatch(string $taskName, array $payload): void
    {
        try {
            $queue = $this->jobs()->connect('embeddings');
            $task = $queue->create($taskName, json_encode($payload));
            $queue->dispatch($task);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("RoadRunner embedding dispatch failed, falling back to Laravel queue: {$e->getMessage()}");
            $this->fallback($taskName, $payload);
        }
    }

    private function fallback(string $taskName, array $payload): void
    {
        match ($taskName) {
            'embed_transaction' => \App\Jobs\EmbedTransactionJob::dispatch($payload['id']),
            'embed_invoice' => \App\Jobs\EmbedInvoiceJob::dispatch($payload['id']),
            'embed_asset' => \App\Jobs\EmbedAssetJob::dispatch($payload['id']),
        };
    }
}
