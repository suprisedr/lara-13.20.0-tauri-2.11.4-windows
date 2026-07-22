<?php

namespace App\GraphQL\Queries;

use App\Models\Transaction;
use App\Models\TransactionVector;
use App\Services\GeminiEmbeddingService;
use Illuminate\Support\Facades\DB;

class SemanticSearchTransactions
{
    public function __construct(private readonly GeminiEmbeddingService $gemini) {}

    /**
     * @param  array{prompt:string, limit?:int, min_similarity?:float, company_id?:int|string|null}  $args
     * @return array<int, array<string, mixed>>
     */
    public function __invoke(mixed $root, array $args): array
    {
        $limit = (int) ($args['limit'] ?? 10);
        $minSim = (float) ($args['min_similarity'] ?? 0.0);
        $companyId = isset($args['company_id']) ? (int) $args['company_id'] : null;

        $embedding = $this->gemini->embed($args['prompt'], 'RETRIEVAL_QUERY');
        $vectorLiteral = TransactionVector::formatVector($embedding);

        $rows = DB::connection('pgsql')->select(
            'SELECT mysql_transaction_id, amount, currency, counterparty, reference, tx_date,
                    1 - (embedding <=> ?::vector) AS similarity
             FROM transaction_vectors
             WHERE embedding IS NOT NULL
             ORDER BY embedding <=> ?::vector
             LIMIT '.($limit * 3),
            [$vectorLiteral, $vectorLiteral]
        );

        $rows = array_values(array_filter($rows, fn ($r) => (float) $r->similarity >= $minSim));
        $rows = array_slice($rows, 0, $limit);

        $ids = array_map(fn ($r) => (int) $r->mysql_transaction_id, $rows);
        $txQuery = Transaction::whereIn('id', $ids);
        if ($companyId !== null) {
            $txQuery->where('company_id', $companyId);
        }
        $transactions = $txQuery->get()->keyBy('id');

        $matches = [];
        foreach ($rows as $r) {
            $tx = $transactions->get((int) $r->mysql_transaction_id);
            if ($companyId !== null && ! $tx) {
                continue;
            }
            $matches[] = [
                'transaction' => $tx,
                'transaction_id' => (int) $r->mysql_transaction_id,
                'similarity' => round((float) $r->similarity, 4),
                'amount' => (float) $r->amount,
                'currency' => $r->currency,
                'counterparty' => $r->counterparty,
                'reference' => $r->reference,
                'tx_date' => $r->tx_date,
            ];
        }

        return $matches;
    }
}
