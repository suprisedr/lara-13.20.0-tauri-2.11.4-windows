<?php

namespace App\GraphQL\Queries;

use App\Models\Asset;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\TransactionVector;
use App\Services\GeminiEmbeddingService;
use Illuminate\Support\Facades\DB;

class SemanticSearchDocuments
{
    public function __construct(private readonly GeminiEmbeddingService $gemini) {}

    /**
     * @param  array{prompt:string, limit?:int, min_similarity?:float, kinds?:string[]|null}  $args
     * @return array<int, array<string, mixed>>
     */
    public function __invoke(mixed $root, array $args): array
    {
        $limit = (int) ($args['limit'] ?? 10);
        $minSim = (float) ($args['min_similarity'] ?? 0.0);
        $kinds = ! empty($args['kinds']) ? array_unique($args['kinds']) : ['transaction', 'invoice', 'asset'];

        $embedding = $this->gemini->embed($args['prompt'], 'RETRIEVAL_QUERY');
        $vectorLiteral = TransactionVector::formatVector($embedding);

        $rows = $this->unionSearch($kinds, $vectorLiteral, $limit * 3);
        $rows = array_values(array_filter($rows, fn ($r) => (float) $r->similarity >= $minSim));
        $rows = array_slice($rows, 0, $limit);

        $txIds     = array_map(fn ($r) => (int) $r->source_id, array_filter($rows, fn ($r) => $r->kind === 'transaction'));
        $invIds    = array_map(fn ($r) => (int) $r->source_id, array_filter($rows, fn ($r) => $r->kind === 'invoice'));
        $assetIds  = array_map(fn ($r) => (int) $r->source_id, array_filter($rows, fn ($r) => $r->kind === 'asset'));

        $transactions = Transaction::whereIn('id', $txIds)->get()->keyBy('id');
        $invoices     = Invoice::whereIn('id', $invIds)->get()->keyBy('id');
        $assets       = Asset::with('ppeClass')->whereIn('id', $assetIds)->get()->keyBy('id');

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'kind'        => $r->kind,
                'source_id'   => (int) $r->source_id,
                'similarity'  => round((float) $r->similarity, 4),
                'amount'      => (float) $r->amount,
                'currency'    => $r->currency,
                'counterparty' => $r->counterparty,
                'reference'   => $r->reference,
                'doc_date'    => $r->doc_date,
                'transaction' => $r->kind === 'transaction' ? $transactions->get((int) $r->source_id) : null,
                'invoice'     => $r->kind === 'invoice'     ? $invoices->get((int) $r->source_id)     : null,
                'asset'       => $r->kind === 'asset'       ? $assets->get((int) $r->source_id)       : null,
            ];
        }

        return $out;
    }

    /** @param  string[]  $kinds */
    private function unionSearch(array $kinds, string $vectorLiteral, int $limit): array
    {
        $parts = [];
        $bindings = [];

        if (in_array('transaction', $kinds, true)) {
            $parts[] = "SELECT 'transaction' AS kind, mysql_transaction_id AS source_id, amount, currency,
                              counterparty, reference, tx_date AS doc_date,
                              1 - (embedding <=> ?::vector) AS similarity
                       FROM transaction_vectors WHERE embedding IS NOT NULL";
            $bindings[] = $vectorLiteral;
        }
        if (in_array('invoice', $kinds, true)) {
            $parts[] = "SELECT 'invoice' AS kind, mysql_invoice_id AS source_id, amount, currency,
                              customer_name AS counterparty, invoice_number AS reference, invoice_date AS doc_date,
                              1 - (embedding <=> ?::vector) AS similarity
                       FROM invoice_vectors WHERE embedding IS NOT NULL";
            $bindings[] = $vectorLiteral;
        }
        if (in_array('asset', $kinds, true)) {
            $parts[] = "SELECT 'asset' AS kind, mysql_asset_id AS source_id, cost AS amount, currency,
                              asset_name AS counterparty, asset_class AS reference, acquisition_date AS doc_date,
                              1 - (embedding <=> ?::vector) AS similarity
                       FROM asset_vectors WHERE embedding IS NOT NULL";
            $bindings[] = $vectorLiteral;
        }

        if (empty($parts)) {
            return [];
        }

        $sql = implode("\nUNION ALL\n", $parts)."\nORDER BY similarity DESC\nLIMIT ".(int) $limit;
        return DB::connection('pgsql')->select($sql, $bindings);
    }
}
