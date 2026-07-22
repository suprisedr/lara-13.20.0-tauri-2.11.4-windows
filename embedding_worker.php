<?php

use Illuminate\Support\Facades\Log;
use Spiral\RoadRunner\Jobs\Consumer;

ini_set('display_errors', 'stderr');
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$consumer = new Consumer();

while ($task = $consumer->waitTask()) {
    try {
        $name    = $task->getName();
        $payload = json_decode($task->getPayload(), true);

        match ($name) {
            'embed_transaction' => handleEmbedTransaction($payload, $app),
            'embed_invoice'     => handleEmbedInvoice($payload, $app),
            'embed_asset'       => handleEmbedAsset($payload, $app),
            default             => throw new RuntimeException("Unknown task: {$name}"),
        };

        $task->complete();
    } catch (\PDOException $e) {
        // pgvector unavailable — skip without crashing the worker so it can
        // process future tasks once the DB is up. The record stays is_embedded=false
        // and will be picked up by the next sync command run.
        Log::warning("embedding_worker: pgvector unavailable, skipping {$task->getName()}", [
            'error' => $e->getMessage(),
        ]);
        $task->complete();
    } catch (Throwable $e) {
        $task->fail($e);
    }
}

function handleEmbedTransaction(array $payload, $app): void
{
    $gemini = $app->make(\App\Services\GeminiEmbeddingService::class);
    $tx = \App\Models\Transaction::with(['company', 'journalLines'])->findOrFail($payload['id']);

    if ($tx->is_embedded) return;

    $embedding = $gemini->embed($tx->toEmbeddableText(), 'RETRIEVAL_DOCUMENT');
    $vectorLiteral = \App\Models\TransactionVector::formatVector($embedding);

    \Illuminate\Support\Facades\DB::connection('pgsql')->transaction(function () use ($tx, $vectorLiteral) {
        $p = [
            'mysql_transaction_id' => $tx->id,
            'amount'               => $tx->embeddableAmount(),
            'currency'             => $tx->embeddableCurrency(),
            'counterparty'         => $tx->embeddableCounterparty(),
            'reference'            => $tx->reference,
            'tx_date'              => optional($tx->transaction_date)->format('Y-m-d'),
            'updated_at'           => now(),
        ];

        $existing = \App\Models\TransactionVector::where('mysql_transaction_id', $tx->id)->first();
        if ($existing) {
            \App\Models\TransactionVector::where('id', $existing->id)->update($p);
            $id = $existing->id;
        } else {
            $p['created_at'] = now();
            $id = \Illuminate\Support\Facades\DB::connection('pgsql')->table('transaction_vectors')->insertGetId($p);
        }

        \Illuminate\Support\Facades\DB::connection('pgsql')->statement(
            'UPDATE transaction_vectors SET embedding = ?::vector WHERE id = ?',
            [$vectorLiteral, $id]
        );
    });

    $tx->forceFill(['is_embedded' => true, 'embedded_at' => now()])->save();
}

function handleEmbedInvoice(array $payload, $app): void
{
    $gemini = $app->make(\App\Services\GeminiEmbeddingService::class);
    $invoice = \App\Models\Invoice::with(['company', 'items', 'customer'])->findOrFail($payload['id']);

    if ($invoice->is_embedded) return;

    $embedding = $gemini->embed($invoice->toEmbeddableText(), 'RETRIEVAL_DOCUMENT');
    $vectorLiteral = \App\Models\TransactionVector::formatVector($embedding);

    \Illuminate\Support\Facades\DB::connection('pgsql')->transaction(function () use ($invoice, $vectorLiteral) {
        $p = [
            'mysql_invoice_id' => $invoice->id,
            'amount'           => $invoice->embeddableAmount(),
            'currency'         => $invoice->embeddableCurrency(),
            'customer_name'    => $invoice->customer_name,
            'invoice_number'   => $invoice->invoice_number,
            'invoice_date'     => optional($invoice->invoice_date)->format('Y-m-d'),
            'updated_at'       => now(),
        ];

        $existing = \App\Models\InvoiceVector::where('mysql_invoice_id', $invoice->id)->first();
        if ($existing) {
            \App\Models\InvoiceVector::where('id', $existing->id)->update($p);
            $id = $existing->id;
        } else {
            $p['created_at'] = now();
            $id = \Illuminate\Support\Facades\DB::connection('pgsql')->table('invoice_vectors')->insertGetId($p);
        }

        \Illuminate\Support\Facades\DB::connection('pgsql')->statement(
            'UPDATE invoice_vectors SET embedding = ?::vector WHERE id = ?',
            [$vectorLiteral, $id]
        );
    });

    $invoice->forceFill(['is_embedded' => true, 'embedded_at' => now()])->save();
}

function handleEmbedAsset(array $payload, $app): void
{
    $gemini = $app->make(\App\Services\GeminiEmbeddingService::class);
    $asset = \App\Models\Asset::with(['ppeClass', 'company'])->findOrFail($payload['id']);

    if ($asset->is_embedded) return;

    $embedding = $gemini->embed($asset->toEmbeddableText(), 'RETRIEVAL_DOCUMENT');
    $vectorLiteral = \App\Models\AssetVector::formatVector($embedding);

    \Illuminate\Support\Facades\DB::connection('pgsql')->transaction(function () use ($asset, $vectorLiteral) {
        $p = [
            'mysql_asset_id'    => $asset->id,
            'cost'              => $asset->cost,
            'currency'          => $asset->company?->currency ?? 'ZAR',
            'asset_name'        => $asset->name,
            'asset_class'       => $asset->ppeClass?->name,
            'accounting_policy' => $asset->ppeClass?->accounting_policy ?? 'cost',
            'acquisition_date'  => optional($asset->acquisition_date)->format('Y-m-d'),
            'updated_at'        => now(),
        ];

        $existing = \App\Models\AssetVector::where('mysql_asset_id', $asset->id)->first();
        if ($existing) {
            \App\Models\AssetVector::where('id', $existing->id)->update($p);
            $id = $existing->id;
        } else {
            $p['created_at'] = now();
            $id = \Illuminate\Support\Facades\DB::connection('pgsql')->table('asset_vectors')->insertGetId($p);
        }

        \Illuminate\Support\Facades\DB::connection('pgsql')->statement(
            'UPDATE asset_vectors SET embedding = ?::vector WHERE id = ?',
            [$vectorLiteral, $id]
        );
    });

    $asset->forceFill(['is_embedded' => true, 'embedded_at' => now()])->save();
}
