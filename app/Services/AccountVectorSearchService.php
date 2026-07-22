<?php

namespace App\Services;

use App\Models\ChartOfAccount;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Fork\Fork;

class AccountVectorSearchService
{
    public function __construct(private readonly GeminiEmbeddingService $gemini) {}

    /**
     * Find the top-N chart-of-account records for a company that best match $query.
     * Falls back to a code-ordered full list when pgvector is unavailable or no
     * vectors exist yet.
     *
     * @return Collection<int, ChartOfAccount>
     */
    public function search(int $companyId, string $query, int $limit = 20): Collection
    {
        try {
            $embedding = $this->gemini->embed($query, 'RETRIEVAL_QUERY');
            $vector    = '['.implode(',', array_map(fn ($v) => (float) $v, $embedding)).']';

            $rows = DB::connection('pgsql')
                ->select(
                    'SELECT mysql_account_id, 1 - (embedding <=> ?::vector) AS similarity
                     FROM account_vectors
                     WHERE mysql_company_id = ? AND embedding IS NOT NULL
                     ORDER BY embedding <=> ?::vector
                     LIMIT ?',
                    [$vector, $companyId, $vector, $limit]
                );

            if (empty($rows)) {
                return $this->fallback($companyId);
            }

            $ids = array_column($rows, 'mysql_account_id');

            return ChartOfAccount::whereIn('id', $ids)
                ->where('is_active', true)
                ->orderByRaw('FIELD(id, '.implode(',', $ids).')')
                ->get();
        } catch (\Throwable) {
            return $this->fallback($companyId);
        }
    }

    /**
     * Search for multiple intents at once, returning a deduplicated union of results.
     * Each query is forked into its own process so the Gemini embedding calls +
     * pgvector searches run in parallel.
     *
     * @param  array<string>  $queries
     * @return Collection<int, ChartOfAccount>
     */
    public function searchMultiple(int $companyId, array $queries, int $limitPerQuery = 15): Collection
    {
        $callables = array_map(
            fn (string $query) => function () use ($companyId, $query, $limitPerQuery): array {
                try {
                    $gemini    = new GeminiEmbeddingService();
                    $embedding = $gemini->embed($query, 'RETRIEVAL_QUERY');
                    $vector    = '[' . implode(',', array_map(fn ($v) => (float) $v, $embedding)) . ']';

                    $rows = DB::connection('pgsql')
                        ->select(
                            'SELECT mysql_account_id FROM account_vectors
                             WHERE mysql_company_id = ? AND embedding IS NOT NULL
                             ORDER BY embedding <=> ?::vector
                             LIMIT ?',
                            [$companyId, $vector, $limitPerQuery]
                        );

                    return array_column($rows, 'mysql_account_id');
                } catch (\Throwable) {
                    return [];
                }
            },
            $queries
        );

        $results = Fork::new()
            ->before(fn () => DB::connection('pgsql')->reconnect())
            ->run(...$callables);

        $ids = collect($results)->flatten()->unique()->values();

        if ($ids->isEmpty()) {
            return $this->fallback($companyId);
        }

        return ChartOfAccount::whereIn('id', $ids->all())
            ->where('is_active', true)
            ->postable()
            ->orderBy('account_code')
            ->get();
    }

    private function fallback(int $companyId): Collection
    {
        return ChartOfAccount::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereDoesntHave('items')
            ->orderBy('account_code')
            ->get(['id', 'account_code', 'account_name', 'account_type', 'category', 'company_id']);
    }
}
