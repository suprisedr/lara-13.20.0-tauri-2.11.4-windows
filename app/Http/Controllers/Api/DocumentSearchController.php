<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\TransactionVector;
use App\Services\GeminiEmbeddingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentSearchController extends Controller
{
    public function __invoke(Request $request, GeminiEmbeddingService $gemini): JsonResponse
    {
        $data = $request->validate([
            'prompt' => ['required', 'string', 'max:2000'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'min_similarity' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'kinds' => ['nullable', 'array'],
            'kinds.*' => ['in:transaction,invoice'],
        ]);

        $limit = $data['limit'] ?? 10;
        $minSim = $data['min_similarity'] ?? 0.0;
        $kinds = array_unique($data['kinds'] ?? ['transaction', 'invoice']);

        $embedding = $gemini->embed($data['prompt'], 'RETRIEVAL_QUERY');
        $vectorLiteral = TransactionVector::formatVector($embedding);

        $rows = $this->unionSearch($kinds, $vectorLiteral, $limit * 3);
        $rows = array_values(array_filter($rows, fn ($r) => (float) $r->similarity >= $minSim));
        $rows = array_slice($rows, 0, $limit);

        $txIds = array_map(fn ($r) => (int) $r->source_id, array_filter($rows, fn ($r) => $r->kind === 'transaction'));
        $invIds = array_map(fn ($r) => (int) $r->source_id, array_filter($rows, fn ($r) => $r->kind === 'invoice'));

        $transactions = Transaction::with('company:id,registered_name')->whereIn('id', $txIds)->get()->keyBy('id');
        $invoices = Invoice::with('company:id,registered_name')->whereIn('id', $invIds)->get()->keyBy('id');

        $matches = array_map(function ($r) use ($transactions, $invoices) {
            $sourceModel = $r->kind === 'transaction'
                ? $transactions->get((int) $r->source_id)
                : $invoices->get((int) $r->source_id);

            return [
                'kind' => $r->kind,
                'source_id' => (int) $r->source_id,
                'similarity' => round((float) $r->similarity, 4),
                'amount' => (float) $r->amount,
                'currency' => $r->currency,
                'counterparty' => $r->counterparty,
                'reference' => $r->reference,
                'doc_date' => $r->doc_date,
                'description' => $sourceModel?->description ?? $sourceModel?->notes,
                'company' => $sourceModel?->company ? [
                    'id' => $sourceModel->company->id,
                    'name' => $sourceModel->company->registered_name,
                ] : null,
            ];
        }, $rows);

        return response()->json([
            'prompt' => $data['prompt'],
            'count' => count($matches),
            'matches' => $matches,
        ]);
    }

    /**
     * @param  string[]  $kinds
     */
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

        if (empty($parts)) {
            return [];
        }

        $sql = implode("\nUNION ALL\n", $parts)."\nORDER BY similarity DESC\nLIMIT ".(int) $limit;
        return DB::connection('pgsql')->select($sql, $bindings);
    }
}
