<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionVector;
use App\Services\GeminiEmbeddingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionSearchController extends Controller
{
    public function __invoke(Request $request, GeminiEmbeddingService $gemini): JsonResponse
    {
        $data = $request->validate([
            'prompt' => ['required', 'string', 'max:2000'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'min_similarity' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'company_id' => ['nullable', 'integer'],
        ]);

        $limit = $data['limit'] ?? 10;
        $minSim = $data['min_similarity'] ?? 0.0;

        $embedding = $gemini->embed($data['prompt'], 'RETRIEVAL_QUERY');
        $vectorLiteral = TransactionVector::formatVector($embedding);

        $rows = DB::connection('pgsql')->select(
            'SELECT mysql_transaction_id, amount, currency, counterparty, reference, tx_date,
                    1 - (embedding <=> ?::vector) AS similarity
             FROM transaction_vectors
             WHERE embedding IS NOT NULL
             ORDER BY embedding <=> ?::vector
             LIMIT '.(int) ($limit * 3),
            [$vectorLiteral, $vectorLiteral]
        );

        $rows = array_values(array_filter($rows, fn ($r) => (float) $r->similarity >= $minSim));
        $rows = array_slice($rows, 0, $limit);

        $ids = array_map(fn ($r) => $r->mysql_transaction_id, $rows);
        $txQuery = Transaction::with(['company:id,registered_name'])->whereIn('id', $ids);
        if (! empty($data['company_id'])) {
            $txQuery->where('company_id', $data['company_id']);
        }
        $transactions = $txQuery->get()->keyBy('id');

        $matches = [];
        foreach ($rows as $r) {
            $tx = $transactions->get($r->mysql_transaction_id);
            if (! empty($data['company_id']) && ! $tx) {
                continue;
            }
            $matches[] = [
                'transaction_id' => $r->mysql_transaction_id,
                'similarity' => round((float) $r->similarity, 4),
                'amount' => (float) $r->amount,
                'currency' => $r->currency,
                'counterparty' => $r->counterparty,
                'reference' => $r->reference,
                'tx_date' => $r->tx_date,
                'description' => $tx?->description,
                'company' => $tx?->company ? [
                    'id' => $tx->company->id,
                    'name' => $tx->company->registered_name,
                ] : null,
            ];
        }

        return response()->json([
            'prompt' => $data['prompt'],
            'count' => count($matches),
            'matches' => $matches,
        ]);
    }
}
