<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionVector;
use App\Services\GeminiEmbeddingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionMatchController extends Controller
{
    public function __invoke(Request $request, GeminiEmbeddingService $gemini): JsonResponse
    {
        $data = $request->validate([
            'query' => ['required', 'string', 'max:2000'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $limit = $data['limit'] ?? 10;

        $embedding = $gemini->embed($data['query'], 'RETRIEVAL_QUERY');
        $vectorLiteral = TransactionVector::formatVector($embedding);

        $rows = DB::connection('pgsql')->select(
            'SELECT mysql_transaction_id, amount, currency, counterparty, reference, tx_date,
                    1 - (embedding <=> ?::vector) AS similarity
             FROM transaction_vectors
             ORDER BY embedding <=> ?::vector
             LIMIT '.(int) $limit,
            [$vectorLiteral, $vectorLiteral]
        );

        $ids = array_map(fn ($r) => $r->mysql_transaction_id, $rows);
        $transactions = Transaction::with(['company:id,name', 'journalLines'])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $matches = array_map(function ($r) use ($transactions) {
            $tx = $transactions->get($r->mysql_transaction_id);
            return [
                'transaction_id' => $r->mysql_transaction_id,
                'similarity' => round((float) $r->similarity, 4),
                'amount' => (float) $r->amount,
                'currency' => $r->currency,
                'counterparty' => $r->counterparty,
                'reference' => $r->reference,
                'tx_date' => $r->tx_date,
                'description' => $tx?->description,
            ];
        }, $rows);

        return response()->json([
            'query' => $data['query'],
            'matches' => $matches,
        ]);
    }
}
