<?php

// Anything written to stdout breaks the goridge frame protocol RoadRunner uses
// to talk to this worker over pipes. Force PHP errors/warnings/notices to
// stderr, and open an output buffer that swallows any accidental echo/print
// from a service provider or config file loaded during bootstrap.
ini_set('display_errors', 'stderr');
ini_set('display_startup_errors', 'stderr');
error_reporting(E_ALL);
ob_start(fn () => '');

/**
 * Unified RoadRunner Jobs worker.
 *
 * Handles four pipelines:
 *   embeddings           — embed_transaction | embed_invoice | embed_asset
 *   asset_postings       — post_asset_* (revalue, impair, reverse, capitalise, dispose, acquire)
 *   intangible_postings  — post_intangible_* (same action strings)
 *   inventory_postings   — post_inventory_movement
 */

use App\Models\AssetEvent;
use App\Models\IntangibleAssetEvent;
use App\Models\InventoryMovement;
use App\Events\PostingStatusUpdated;
use App\Models\LeaseEvent;
use App\Services\AssetPostingService;
use App\Services\IntangibleAssetPostingService;
use App\Services\InventoryPostingService;
use App\Services\LeasePostingService;
use App\Services\EclPostingService;
use App\Services\CreditNotePostingService;
use App\Services\InvoicePostingService;
use App\Services\BiologicalAssetPostingService;
use App\Services\InvestmentPropertyPostingService;
use App\Models\BiologicalAssetEvent;
use App\Models\InvestmentPropertyEvent;

function broadcastStatus(int $companyId, string $entityType, int $entityId, string $status, string $label): void
{
    try {
        event(new PostingStatusUpdated($companyId, $entityType, $entityId, $status, $label));
    } catch (\Throwable) {
        // Reverb may not be running in all environments — broadcast failure is non-fatal.
    }
}
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spiral\RoadRunner\Jobs\Consumer;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Discard anything that leaked to the buffer during bootstrap so the very
// first frame we send to RoadRunner is clean.
if (ob_get_level() > 0) {
    ob_end_clean();
}

$consumer = new Consumer();

while ($task = $consumer->waitTask()) {
    try {
        $name    = $task->getName();
        $payload = json_decode($task->getPayload(), true);

        if (str_starts_with($name, 'embed_')) {
            handleEmbed($name, $payload, $app);
        } elseif (str_starts_with($name, 'post_asset_')) {
            handleAssetPosting($payload, $app);
        } elseif (str_starts_with($name, 'post_intangible_')) {
            handleIntangiblePosting($payload, $app);
        } elseif ($name === 'post_inventory_movement') {
            handleInventoryPosting($payload, $app);
        } elseif (str_starts_with($name, 'post_lease_')) {
            handleLeasePosting($payload, $app);
        } elseif (str_starts_with($name, 'post_biological_asset_')) {
            handleBiologicalAssetPosting($payload, $app);
        } elseif (str_starts_with($name, 'post_investment_property_')) {
            handleInvestmentPropertyPosting($payload, $app);
        } elseif ($name === 'post_ecl_provision') {
            handleEclPosting($payload, $app);
        } elseif ($name === 'post_credit_note') {
            handleCreditNotePosting($payload, $app);
        } elseif ($name === 'post_invoice') {
            handleInvoicePosting($payload, $app);
        } elseif ($name === 'post_invoice_payment') {
            handleInvoicePaymentPosting($payload, $app);
        } elseif ($name === 'generate_chart_of_accounts') {
            handleChartOfAccountsGeneration($payload, $app);
        } else {
            throw new RuntimeException("Unknown task: {$name}");
        }

        $task->complete();
    } catch (\Throwable $e) {
        if (isPgsqlError($e)) {
            // pgvector unavailable — complete so the worker stays alive; records
            // remain is_embedded=false for the next sync run.
            Log::warning("jobs_worker: pgvector unavailable, skipping {$task->getName()}");
            $task->complete();
        } else {
            $task->fail($e);
        }
    }
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function flagAction(int $companyId, string $title, string $body, string $priority, ?string $relatedType = null, ?int $relatedId = null): void
{
    try {
        \App\Models\CompanyAction::create([
            'company_id'   => $companyId,
            'title'        => $title,
            'body'         => $body,
            'priority'     => $priority,
            'source'       => \App\Models\CompanyAction::SOURCE_SYSTEM,
            'related_type' => $relatedType,
            'related_id'   => $relatedId,
        ]);
    } catch (\Throwable $ex) {
        Log::warning('flagAction: failed to create action', ['error' => $ex->getMessage()]);
    }
}

function isPgsqlError(\Throwable $e): bool
{
    do {
        if ($e instanceof \PDOException) return true;
        if ($e instanceof \Illuminate\Database\QueryException) {
            $code = (string) $e->getCode();
            // SQLSTATE 08006 = connection failure, 08001 = unable to connect
            if (str_starts_with($code, '08')) return true;
            if (str_contains($e->getMessage(), 'Connection refused')) return true;
        }
        $e = $e->getPrevious();
    } while ($e !== null);

    return false;
}

function pgsqlAvailable(): bool
{
    try {
        DB::connection('pgsql')->getPdo();
        return true;
    } catch (\Throwable) {
        return false;
    }
}

// ---------------------------------------------------------------------------
// Embedding handlers
// ---------------------------------------------------------------------------

function handleEmbed(string $name, array $payload, $app): void
{
    match ($name) {
        'embed_transaction' => handleEmbedTransaction($payload, $app),
        'embed_invoice'     => handleEmbedInvoice($payload, $app),
        'embed_asset'       => handleEmbedAsset($payload, $app),
        default             => throw new RuntimeException("Unknown embed task: {$name}"),
    };
}

function handleEmbedTransaction(array $payload, $app): void
{
    if (! pgsqlAvailable()) throw new \PDOException('pgvector unavailable');

    $gemini = $app->make(\App\Services\GeminiEmbeddingService::class);
    $tx     = \App\Models\Transaction::with(['company', 'journalLines'])->findOrFail($payload['id']);

    if ($tx->is_embedded) return;

    $embedding     = $gemini->embed($tx->toEmbeddableText(), 'RETRIEVAL_DOCUMENT');
    $vectorLiteral = \App\Models\TransactionVector::formatVector($embedding);

    DB::connection('pgsql')->transaction(function () use ($tx, $vectorLiteral) {
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
            $id = DB::connection('pgsql')->table('transaction_vectors')->insertGetId($p);
        }

        DB::connection('pgsql')->statement(
            'UPDATE transaction_vectors SET embedding = ?::vector WHERE id = ?',
            [$vectorLiteral, $id]
        );
    });

    $tx->forceFill(['is_embedded' => true, 'embedded_at' => now()])->save();
}

function handleEmbedInvoice(array $payload, $app): void
{
    if (! pgsqlAvailable()) throw new \PDOException('pgvector unavailable');

    $gemini  = $app->make(\App\Services\GeminiEmbeddingService::class);
    $invoice = \App\Models\Invoice::with(['company', 'items', 'customer'])->findOrFail($payload['id']);

    if ($invoice->is_embedded) return;

    $embedding     = $gemini->embed($invoice->toEmbeddableText(), 'RETRIEVAL_DOCUMENT');
    $vectorLiteral = \App\Models\TransactionVector::formatVector($embedding);

    DB::connection('pgsql')->transaction(function () use ($invoice, $vectorLiteral) {
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
            $id = DB::connection('pgsql')->table('invoice_vectors')->insertGetId($p);
        }

        DB::connection('pgsql')->statement(
            'UPDATE invoice_vectors SET embedding = ?::vector WHERE id = ?',
            [$vectorLiteral, $id]
        );
    });

    $invoice->forceFill(['is_embedded' => true, 'embedded_at' => now()])->save();
}

function handleEmbedAsset(array $payload, $app): void
{
    if (! pgsqlAvailable()) throw new \PDOException('pgvector unavailable');

    $gemini = $app->make(\App\Services\GeminiEmbeddingService::class);
    $asset  = \App\Models\Asset::with(['ppeClass', 'company'])->findOrFail($payload['id']);

    if ($asset->is_embedded) return;

    $embedding     = $gemini->embed($asset->toEmbeddableText(), 'RETRIEVAL_DOCUMENT');
    $vectorLiteral = \App\Models\AssetVector::formatVector($embedding);

    DB::connection('pgsql')->transaction(function () use ($asset, $vectorLiteral) {
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
            $id = DB::connection('pgsql')->table('asset_vectors')->insertGetId($p);
        }

        DB::connection('pgsql')->statement(
            'UPDATE asset_vectors SET embedding = ?::vector WHERE id = ?',
            [$vectorLiteral, $id]
        );
    });

    $asset->forceFill(['is_embedded' => true, 'embedded_at' => now()])->save();
}

// ---------------------------------------------------------------------------
// Asset posting handler
// ---------------------------------------------------------------------------

function handleAssetPosting(array $payload, $app): void
{
    $eventId = $payload['eventId'];
    $assetId = $payload['assetId'];
    $userId  = $payload['userId'];
    $action  = $payload['action'];
    $data    = $payload['data'] ?? [];

    Log::info("jobs_worker: asset posting starting", [
        'asset_event_id' => $eventId,
        'asset_id'       => $assetId,
        'action'         => $action,
    ]);

    $asset   = \App\Models\Asset::with(['company.user', 'ppeClass'])->findOrFail($assetId);
    $user    = \App\Models\User::findOrFail($userId);
    $posting = $app->make(AssetPostingService::class);

    try {
        match ($action) {
            'acquire'    => $posting->postAcquisitionWithAi($asset, $user),
            'capitalise' => $posting->postSubsequentCostWithAi(
                $asset, $user,
                (float) $data['amount'],
                $data['date'],
                $data['description'] ?? '',
            ),
            'revalue'    => $posting->postRevaluationWithAi(
                $asset, $user,
                (float) $data['new_carrying_amount'],
                $data['date'],
            ),
            'impair'     => $posting->postImpairmentWithAi(
                $asset, $user,
                (float) $data['impairment_amount'],
                $data['date'],
                $data['reason'] ?? '',
            ),
            'reverse'    => $posting->postImpairmentReversalWithAi(
                $asset, $user,
                (float) $data['reversal_amount'],
                $data['date'],
            ),
            'dispose'    => $posting->postDisposalWithAi($asset->fresh(), $user),
            default      => throw new \RuntimeException("Unknown asset action: {$action}"),
        };

        AssetEvent::where('id', $eventId)->update(['journal_status' => AssetEvent::STATUS_POSTED]);
        broadcastStatus($asset->company_id, 'asset_event', $eventId, 'posted', ucfirst($action).' posted — '.$asset->name);

        Log::info("jobs_worker: asset posting succeeded", [
            'asset_event_id' => $eventId,
            'asset_id'       => $assetId,
            'action'         => $action,
        ]);
    } catch (\Throwable $e) {
        AssetEvent::where('id', $eventId)->update(['journal_status' => AssetEvent::STATUS_FAILED]);
        broadcastStatus($asset->company_id, 'asset_event', $eventId, 'failed', ucfirst($action).' failed — '.$asset->name);

        Log::error("jobs_worker: asset posting failed", [
            'asset_event_id' => $eventId,
            'asset_id'       => $assetId,
            'action'         => $action,
            'error'          => $e->getMessage(),
        ]);

        flagAction(
            $asset->company_id,
            ucfirst($action) . ' posting failed — ' . $asset->name,
            "The AI posting for asset \"{$asset->name}\" ({$action}) failed.\n\nError: {$e->getMessage()}\n\nPlease review and post the journal manually or retry.",
            'high',
            'asset',
            $assetId,
        );

    }
}

// ---------------------------------------------------------------------------
// Intangible asset posting handler
// ---------------------------------------------------------------------------

function handleIntangiblePosting(array $payload, $app): void
{
    $eventId      = $payload['eventId'];
    $intangibleId = $payload['intangibleId'];
    $userId       = $payload['userId'];
    $action       = $payload['action'];
    $data         = $payload['data'] ?? [];

    Log::info("jobs_worker: intangible posting starting", [
        'intangible_event_id' => $eventId,
        'intangible_id'       => $intangibleId,
        'action'              => $action,
    ]);

    $intangible = \App\Models\IntangibleAsset::with(['company.user', 'intangibleClass'])->findOrFail($intangibleId);
    $user       = \App\Models\User::findOrFail($userId);
    $posting    = $app->make(IntangibleAssetPostingService::class);

    try {
        match ($action) {
            'acquire'    => $posting->postAcquisitionWithAi($intangible, $user),
            'capitalise' => $posting->postSubsequentCostWithAi(
                $intangible, $user,
                (float) $data['amount'],
                $data['date'],
                $data['description'] ?? '',
            ),
            'revalue'    => $posting->postRevaluationWithAi(
                $intangible, $user,
                (float) $data['new_carrying_amount'],
                $data['date'],
            ),
            'impair'     => $posting->postImpairmentWithAi(
                $intangible, $user,
                (float) $data['impairment_amount'],
                $data['date'],
                $data['reason'] ?? '',
            ),
            'reverse'    => $posting->postImpairmentReversalWithAi(
                $intangible, $user,
                (float) $data['reversal_amount'],
                $data['date'],
            ),
            'dispose'    => $posting->postDisposalWithAi($intangible->fresh(), $user),
            default      => throw new \RuntimeException("Unknown intangible action: {$action}"),
        };

        IntangibleAssetEvent::where('id', $eventId)->update(['journal_status' => IntangibleAssetEvent::STATUS_POSTED]);
        broadcastStatus($intangible->company_id, 'intangible_event', $eventId, 'posted', ucfirst($action).' posted — '.$intangible->name);

        Log::info("jobs_worker: intangible posting succeeded", [
            'intangible_event_id' => $eventId,
            'intangible_id'       => $intangibleId,
            'action'              => $action,
        ]);
    } catch (\Throwable $e) {
        IntangibleAssetEvent::where('id', $eventId)->update(['journal_status' => IntangibleAssetEvent::STATUS_FAILED]);
        broadcastStatus($intangible->company_id, 'intangible_event', $eventId, 'failed', ucfirst($action).' failed — '.$intangible->name);

        Log::error("jobs_worker: intangible posting failed", [
            'intangible_event_id' => $eventId,
            'intangible_id'       => $intangibleId,
            'action'              => $action,
            'error'               => $e->getMessage(),
        ]);

        flagAction(
            $intangible->company_id,
            ucfirst($action) . ' posting failed — ' . $intangible->name,
            "The AI posting for intangible asset \"{$intangible->name}\" ({$action}) failed.\n\nError: {$e->getMessage()}\n\nPlease review and post the journal manually or retry.",
            'high',
            'intangible_asset',
            $intangibleId,
        );
    }
}

// ---------------------------------------------------------------------------
// Inventory posting handler
// ---------------------------------------------------------------------------

function handleInventoryPosting(array $payload, $app): void
{
    $movementId = $payload['movementId'];
    $userId     = $payload['userId'];

    Log::info("jobs_worker: inventory posting starting", ['movement_id' => $movementId]);

    $movement = \App\Models\InventoryMovement::with(['inventoryItem', 'company'])->findOrFail($movementId);
    $user     = \App\Models\User::findOrFail($userId);
    $posting  = $app->make(InventoryPostingService::class);

    try {
        $posting->postMovementWithAi($movement, $user);

        InventoryMovement::where('id', $movementId)->update(['journal_status' => InventoryMovement::STATUS_POSTED]);
        broadcastStatus($movement->company_id, 'inventory_movement', $movementId, 'posted', ucfirst($movement->action->value).' posted — '.$movement->inventoryItem->name);

        Log::info("jobs_worker: inventory posting succeeded", [
            'movement_id' => $movementId,
            'action'      => $movement->action->value,
        ]);
    } catch (\Throwable $e) {
        InventoryMovement::where('id', $movementId)->update(['journal_status' => InventoryMovement::STATUS_FAILED]);
        broadcastStatus($movement->company_id, 'inventory_movement', $movementId, 'failed', ucfirst($movement->action->value).' failed — '.$movement->inventoryItem->name);

        Log::error("jobs_worker: inventory posting failed", [
            'movement_id' => $movementId,
            'error'       => $e->getMessage(),
        ]);

        flagAction(
            $movement->company_id,
            ucfirst($movement->action->value) . ' posting failed — ' . $movement->inventoryItem->name,
            "The AI posting for inventory movement \"{$movement->inventoryItem->name}\" ({$movement->action->value}) failed.\n\nError: {$e->getMessage()}\n\nPlease review and post the journal manually or retry.",
            'high',
            'inventory_movement',
            $movementId,
        );
    }
}

// ---------------------------------------------------------------------------
// Lease posting handler
// ---------------------------------------------------------------------------

function handleLeasePosting(array $payload, $app): void
{
    $eventId = $payload['eventId'];
    $leaseId = $payload['leaseId'];
    $userId  = $payload['userId'];
    $action  = $payload['action'];
    $data    = $payload['data'] ?? [];

    Log::info("jobs_worker: lease posting starting", [
        'lease_event_id' => $eventId,
        'lease_id'       => $leaseId,
        'action'         => $action,
    ]);

    $lease   = \App\Models\Lease::with('company')->findOrFail($leaseId);
    $user    = \App\Models\User::findOrFail($userId);
    $posting = $app->make(LeasePostingService::class);

    try {
        match ($action) {
            'commencement'       => $posting->postCommencementWithAi($lease, $user),
            'payment'            => $posting->postPaymentWithAi($lease, $user, $data['date'], (float) $data['amount']),
            'modification'       => $posting->postModificationWithAi($lease, $user, (float) $data['adjustment_amount'], $data['date']),
            'impairment'         => $posting->postImpairmentWithAi($lease, $user, (float) $data['impairment_amount'], $data['date']),
            'reverse_impairment' => $posting->postImpairmentReversalWithAi($lease, $user, (float) $data['reversal_amount'], $data['date']),
            'termination'        => $posting->postTerminationWithAi($lease, $user, $data['date'], (float) ($data['gain_loss'] ?? 0)),
            default              => throw new \RuntimeException("Unknown lease action: {$action}"),
        };

        LeaseEvent::where('id', $eventId)->update(['journal_status' => LeaseEvent::STATUS_POSTED]);
        broadcastStatus($lease->company_id, 'lease_event', $eventId, 'posted', ucfirst($action).' posted — '.$lease->name);

        Log::info("jobs_worker: lease posting succeeded", [
            'lease_event_id' => $eventId,
            'lease_id'       => $leaseId,
            'action'         => $action,
        ]);
    } catch (\Throwable $e) {
        LeaseEvent::where('id', $eventId)->update(['journal_status' => LeaseEvent::STATUS_FAILED]);
        broadcastStatus($lease->company_id, 'lease_event', $eventId, 'failed', ucfirst($action).' failed — '.$lease->name);

        Log::error("jobs_worker: lease posting failed", [
            'lease_event_id' => $eventId,
            'lease_id'       => $leaseId,
            'action'         => $action,
            'error'          => $e->getMessage(),
        ]);

        flagAction(
            $lease->company_id,
            ucfirst($action) . ' posting failed — ' . $lease->name,
            "The AI posting for lease \"{$lease->name}\" ({$action}) failed.\n\nError: {$e->getMessage()}\n\nPlease review and post the journal manually or retry.",
            'high',
            'lease',
            $leaseId,
        );
    }
}


// ---------------------------------------------------------------------------
// ECL provision posting handler
// ---------------------------------------------------------------------------

function handleEclPosting(array $payload, $app): void
{
    $companyId = $payload['companyId'];
    $userId    = $payload['userId'];
    $asOfDate  = $payload['asOfDate'];

    Log::info("jobs_worker: ecl posting starting", [
        'company_id' => $companyId,
        'as_of_date' => $asOfDate,
    ]);

    $company = \App\Models\Company::findOrFail($companyId);
    $user    = \App\Models\User::findOrFail($userId);
    $service = $app->make(EclPostingService::class);

    try {
        $service->postProvision($company, $user, $asOfDate);

        Log::info("jobs_worker: ecl posting succeeded", [
            'company_id' => $companyId,
            'as_of_date' => $asOfDate,
        ]);
    } catch (\Throwable $e) {
        flagAction(
            $companyId,
            'ECL provision posting failed — ' . $asOfDate,
            "The AI posting for the IFRS 9 ECL provision (as of {$asOfDate}) failed.\n\nError: {$e->getMessage()}\n\nPlease review and post the journal manually or retry.",
            'high',
        );

        Log::error("jobs_worker: ecl posting failed", [
            'company_id' => $companyId,
            'as_of_date' => $asOfDate,
            'error'      => $e->getMessage(),
        ]);
    }
}

// ---------------------------------------------------------------------------
// Credit note posting handler
// ---------------------------------------------------------------------------

function handleCreditNotePosting(array $payload, $app): void
{
    $companyId    = $payload['companyId'];
    $userId       = $payload['userId'];
    $creditNoteId = $payload['creditNoteId'];

    Log::info("jobs_worker: credit note posting starting", [
        'credit_note_id' => $creditNoteId,
    ]);

    $company    = \App\Models\Company::findOrFail($companyId);
    $user       = \App\Models\User::findOrFail($userId);
    $creditNote = \App\Models\CreditNote::findOrFail($creditNoteId);
    $service    = $app->make(CreditNotePostingService::class);

    try {
        $service->post($company, $user, $creditNote);

        Log::info("jobs_worker: credit note posting succeeded", [
            'credit_note_id' => $creditNoteId,
        ]);
    } catch (\Throwable $e) {
        Log::error("jobs_worker: credit note posting failed", [
            'credit_note_id' => $creditNoteId,
            'error'          => $e->getMessage(),
        ]);

        flagAction(
            $companyId,
            'Credit note posting failed — ' . $creditNote->credit_note_number,
            "The AI posting for credit note {$creditNote->credit_note_number} failed.\n\nError: {$e->getMessage()}\n\nPlease review and post the journal manually or retry.",
            'high',
            'credit_note',
            $creditNoteId,
        );
    }
}

// ---------------------------------------------------------------------------
// Biological asset posting handler
// ---------------------------------------------------------------------------

function handleBiologicalAssetPosting(array $payload, $app): void
{
    $eventId = $payload['eventId'];
    $assetId = $payload['assetId'];
    $userId  = $payload['userId'];
    $action  = $payload['action'];
    $data    = $payload['data'] ?? [];

    Log::info('jobs_worker: biological asset posting starting', [
        'event_id' => $eventId, 'asset_id' => $assetId, 'action' => $action,
    ]);

    $asset   = \App\Models\BiologicalAsset::with(['company.user', 'biologicalAssetClass'])->findOrFail($assetId);
    $user    = \App\Models\User::findOrFail($userId);
    $posting = $app->make(BiologicalAssetPostingService::class);

    try {
        match ($action) {
            'acquire'           => $posting->postAcquisitionWithAi($asset, $user),
            'fair_value_adjust' => $posting->postFairValueAdjustmentWithAi(
                $asset, $user,
                (float) $data['new_fair_value'],
                $data['date'],
            ),
            'harvest'           => $posting->postHarvestWithAi(
                $asset, $user,
                (float) $data['fair_value_at_harvest'],
                (float) $data['quantity'],
                $data['date'],
                $data['description'] ?? '',
            ),
            'dispose'           => $posting->postDisposalWithAi($asset->fresh(), $user),
            default             => throw new \RuntimeException("Unknown biological asset action: {$action}"),
        };

        BiologicalAssetEvent::where('id', $eventId)->update(['journal_status' => BiologicalAssetEvent::STATUS_POSTED]);
        broadcastStatus($asset->company_id, 'biological_asset_event', $eventId, 'posted', ucfirst($action).' posted — '.$asset->name);

        Log::info('jobs_worker: biological asset posting succeeded', [
            'event_id' => $eventId, 'asset_id' => $assetId, 'action' => $action,
        ]);
    } catch (\Throwable $e) {
        BiologicalAssetEvent::where('id', $eventId)->update(['journal_status' => BiologicalAssetEvent::STATUS_FAILED]);
        broadcastStatus($asset->company_id, 'biological_asset_event', $eventId, 'failed', ucfirst($action).' failed — '.$asset->name);

        Log::error('jobs_worker: biological asset posting failed', [
            'event_id' => $eventId, 'asset_id' => $assetId, 'action' => $action,
            'error'    => $e->getMessage(),
        ]);

        flagAction(
            $asset->company_id,
            ucfirst($action) . ' posting failed — ' . $asset->name,
            "The AI posting for biological asset \"{$asset->name}\" ({$action}) failed.\n\nError: {$e->getMessage()}\n\nPlease review and post the journal manually or retry.",
            'high',
            'biological_asset',
            $assetId,
        );
    }
}

// ---------------------------------------------------------------------------
// Investment property posting handler
// ---------------------------------------------------------------------------

function handleInvestmentPropertyPosting(array $payload, $app): void
{
    $eventId    = $payload['eventId'];
    $propertyId = $payload['propertyId'];
    $userId     = $payload['userId'];
    $action     = $payload['action'];
    $data       = $payload['data'] ?? [];

    Log::info('jobs_worker: investment property posting starting', [
        'event_id' => $eventId, 'property_id' => $propertyId, 'action' => $action,
    ]);

    $property = \App\Models\InvestmentProperty::with(['company.user', 'investmentPropertyClass'])->findOrFail($propertyId);
    $user     = \App\Models\User::findOrFail($userId);
    $posting  = $app->make(InvestmentPropertyPostingService::class);

    try {
        match ($action) {
            'acquire'           => $posting->postAcquisitionWithAi($property, $user),
            'capitalise'        => $posting->postSubsequentCostWithAi(
                $property, $user,
                (float) $data['amount'],
                $data['date'],
                $data['description'] ?? '',
            ),
            'fair_value_adjust' => $posting->postFairValueAdjustmentWithAi(
                $property, $user,
                (float) $data['new_fair_value'],
                $data['date'],
            ),
            'impair'            => $posting->postImpairmentWithAi(
                $property, $user,
                (float) $data['impairment_amount'],
                $data['date'],
                $data['reason'] ?? '',
            ),
            'reverse'           => $posting->postImpairmentReversalWithAi(
                $property, $user,
                (float) $data['reversal_amount'],
                $data['date'],
            ),
            'dispose'           => $posting->postDisposalWithAi($property->fresh(), $user),
            default             => throw new \RuntimeException("Unknown investment property action: {$action}"),
        };

        InvestmentPropertyEvent::where('id', $eventId)->update(['journal_status' => InvestmentPropertyEvent::STATUS_POSTED]);
        broadcastStatus($property->company_id, 'investment_property_event', $eventId, 'posted', ucfirst($action).' posted — '.$property->name);

        Log::info('jobs_worker: investment property posting succeeded', [
            'event_id' => $eventId, 'property_id' => $propertyId, 'action' => $action,
        ]);
    } catch (\Throwable $e) {
        InvestmentPropertyEvent::where('id', $eventId)->update(['journal_status' => InvestmentPropertyEvent::STATUS_FAILED]);
        broadcastStatus($property->company_id, 'investment_property_event', $eventId, 'failed', ucfirst($action).' failed — '.$property->name);

        Log::error('jobs_worker: investment property posting failed', [
            'event_id' => $eventId, 'property_id' => $propertyId, 'action' => $action,
            'error'    => $e->getMessage(),
        ]);

        flagAction(
            $property->company_id,
            ucfirst($action) . ' posting failed — ' . $property->name,
            "The AI posting for investment property \"{$property->name}\" ({$action}) failed.\n\nError: {$e->getMessage()}\n\nPlease review and post the journal manually or retry.",
            'high',
            'investment_property',
            $propertyId,
        );
    }
}

// ---------------------------------------------------------------------------
// Invoice posting handler
// ---------------------------------------------------------------------------

function handleInvoicePosting(array $payload, $app): void
{
    $invoiceId = $payload['invoiceId'];
    $companyId = $payload['companyId'];

    Log::info('jobs_worker: invoice posting starting', ['invoice_id' => $invoiceId]);

    $invoice = \App\Models\Invoice::with(['items', 'company.user'])->find($invoiceId);
    if (! $invoice || $invoice->posting_transaction_id) {
        return;
    }

    $company = $invoice->company;
    $service = $app->make(InvoicePostingService::class);

    broadcastStatus($companyId, 'invoice', $invoiceId, 'pending',
        "AI is posting invoice {$invoice->invoice_number}…");

    try {
        $service->postWithAi($company, $invoice, $company->user);

        broadcastStatus($companyId, 'invoice', $invoiceId, 'posted',
            "Invoice {$invoice->invoice_number} posted successfully");

        Log::info('jobs_worker: invoice posting succeeded', ['invoice_id' => $invoiceId]);
    } catch (\Throwable $e) {
        broadcastStatus($companyId, 'invoice', $invoiceId, 'failed',
            "Invoice {$invoice->invoice_number} posting failed");

        Log::error('jobs_worker: invoice posting failed', [
            'invoice_id' => $invoiceId,
            'error'      => $e->getMessage(),
        ]);

        flagAction(
            $companyId,
            "AI invoice posting failed — Invoice #{$invoice->invoice_number}",
            'The AI could not post this invoice. ' . get_class($e) . ': ' . $e->getMessage(),
            'high',
            'invoice',
            $invoiceId,
        );
    }
}

// ---------------------------------------------------------------------------
// Invoice payment posting handler
// ---------------------------------------------------------------------------

function handleInvoicePaymentPosting(array $payload, $app): void
{
    $invoiceId = $payload['invoiceId'];
    $companyId = $payload['companyId'];
    $userId    = $payload['userId'];

    Log::info('jobs_worker: invoice payment posting starting', ['invoice_id' => $invoiceId]);

    $invoice = \App\Models\Invoice::with(['items', 'payments', 'postingTransaction.journalLines'])->find($invoiceId);
    if (! $invoice) {
        return;
    }

    // Another path already posted a payment entry — skip.
    if ($invoice->payments()->whereNotNull('transaction_id')->exists()) {
        return;
    }

    if ($invoice->balanceDue() <= 0) {
        return;
    }

    $user    = \App\Models\User::findOrFail($userId);
    $company = $invoice->company;
    $service = $app->make(InvoicePostingService::class);

    broadcastStatus($companyId, 'invoice_payment', $invoiceId, 'pending',
        "AI is posting payment for invoice {$invoice->invoice_number}…");

    try {
        $service->postPaymentWithAi($company, $invoice, $user);

        broadcastStatus($companyId, 'invoice_payment', $invoiceId, 'posted',
            "Payment for invoice {$invoice->invoice_number} posted successfully");

        Log::info('jobs_worker: invoice payment posting succeeded', ['invoice_id' => $invoiceId]);
    } catch (\Throwable $e) {
        broadcastStatus($companyId, 'invoice_payment', $invoiceId, 'failed',
            "Payment posting failed for invoice {$invoice->invoice_number}");

        Log::error('jobs_worker: invoice payment posting failed', [
            'invoice_id' => $invoiceId,
            'error'      => $e->getMessage(),
        ]);

        flagAction(
            $companyId,
            "AI payment posting failed — Invoice #{$invoice->invoice_number}",
            'The AI could not post the payment receipt entry. Please use Record Payment manually. Error: ' . $e->getMessage(),
            'high',
            'invoice',
            $invoiceId,
        );
    }
}

// ---------------------------------------------------------------------------
// Chart of accounts generation handler
// ---------------------------------------------------------------------------

function handleChartOfAccountsGeneration(array $payload, $app): void
{
    $companyId = $payload['companyId'];

    Log::info("jobs_worker: chart of accounts generation starting", [
        'company_id' => $companyId,
    ]);

    $company = \App\Models\Company::findOrFail($companyId);

    if ($company->chartOfAccounts()->exists()) {
        Log::info("jobs_worker: chart of accounts already exists, skipping", [
            'company_id' => $companyId,
        ]);
        return;
    }

    try {
        $response = (new \App\Ai\Agents\ChartOfAccountsAgent($company))->prompt(
            'Generate a complete, industry-appropriate chart of accounts for this business.'
        );

        $accounts = collect($response['accounts'])->map(fn(array $account) => [
            'company_id'   => $company->id,
            'account_code' => $account['account_code'],
            'account_name' => $account['account_name'],
            'account_type' => $account['account_type'],
            'category'     => $account['category'] ?? null,
            'description'  => $account['description'] ?? null,
            'parent_code'     => $account['parent_code'] ?? null,
            'opening_balance' => $account['opening_balance'] ?? 0.00,
            'is_active'       => true,
            'created_at'   => now(),
            'updated_at'   => now(),
        ])->all();

        \App\Models\ChartOfAccount::insert($accounts);

        $codeToId = \App\Models\ChartOfAccount::where('company_id', $company->id)
            ->pluck('id', 'account_code');

        \App\Models\ChartOfAccount::where('company_id', $company->id)
            ->whereNotNull('parent_code')
            ->get()
            ->each(function (\App\Models\ChartOfAccount $account) use ($codeToId): void {
                $parentId = $codeToId->get($account->parent_code);
                if ($parentId) {
                    $account->updateQuietly(['parent_id' => $parentId]);
                }
            });

        broadcastStatus($companyId, 'chart_of_accounts', $companyId, 'posted', 'Chart of accounts generated');

        Log::info("jobs_worker: chart of accounts generation succeeded", [
            'company_id' => $companyId,
            'count'      => count($accounts),
        ]);
    } catch (\Throwable $e) {
        Log::error("jobs_worker: chart of accounts generation failed", [
            'company_id' => $companyId,
            'error'      => $e->getMessage(),
        ]);

        flagAction(
            $companyId,
            'Chart of accounts generation failed',
            "The AI-generated chart of accounts for company #{$companyId} ({$company->registered_name}) failed.\n\nError: {$e->getMessage()}\n\nPlease generate the chart of accounts manually or retry onboarding.",
            'high',
        );
    }
}
