<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Deletes pgvector rows whose source row no longer exists in MySQL.
 *
 * Catches drift from bulk deletes that bypass Eloquent (raw query builder
 * deletes, foreign-key cascades, manual SQL). Safe to run repeatedly — does
 * nothing when the stores are already in sync.
 */
class ReconcileVectorStore extends Command
{
    protected $signature = 'vectors:reconcile
        {--chunk=1000 : How many vector ids to check per round trip}
        {--dry-run : Report orphans without deleting them}';

    protected $description = 'Purge pgvector rows orphaned by raw/cascade MySQL deletes.';

    public function handle(): int
    {
        $chunk = max(50, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');

        $txOrphans = $this->reconcile(
            pgTable: 'transaction_vectors',
            pgIdColumn: 'mysql_transaction_id',
            mysqlTable: 'transactions',
            chunk: $chunk,
            dryRun: $dryRun,
            label: 'transactions',
        );

        $invOrphans = $this->reconcile(
            pgTable: 'invoice_vectors',
            pgIdColumn: 'mysql_invoice_id',
            mysqlTable: 'invoices',
            chunk: $chunk,
            dryRun: $dryRun,
            label: 'invoices',
        );

        $verb = $dryRun ? 'would purge' : 'purged';
        $this->info("Done. {$verb} {$txOrphans} transaction vector(s) and {$invOrphans} invoice vector(s).");

        return self::SUCCESS;
    }

    private function reconcile(
        string $pgTable,
        string $pgIdColumn,
        string $mysqlTable,
        int $chunk,
        bool $dryRun,
        string $label,
    ): int {
        $totalOrphans = 0;
        $offset = 0;

        $this->line("Scanning {$label}…");

        while (true) {
            $ids = DB::connection('pgsql')
                ->table($pgTable)
                ->orderBy($pgIdColumn)
                ->offset($offset)
                ->limit($chunk)
                ->pluck($pgIdColumn)
                ->all();

            if (empty($ids)) {
                break;
            }

            // IDs that DO exist in MySQL.
            $alive = DB::connection('mysql')
                ->table($mysqlTable)
                ->whereIn('id', $ids)
                ->pluck('id')
                ->all();
            $aliveSet = array_flip($alive);

            $orphans = array_values(array_filter($ids, fn ($id) => ! isset($aliveSet[$id])));

            if (! empty($orphans)) {
                $totalOrphans += count($orphans);
                if (! $dryRun) {
                    DB::connection('pgsql')
                        ->table($pgTable)
                        ->whereIn($pgIdColumn, $orphans)
                        ->delete();
                } else {
                    // Advance offset by the rows we left in place.
                    $offset += count($alive);
                    continue;
                }
            }

            if (count($ids) < $chunk) {
                break;
            }
            // When we deleted everything orphaned, we don't need to bump
            // offset — the next chunk starts naturally from the smaller table.
            if ($dryRun) {
                $offset += count($alive);
            }
        }

        return $totalOrphans;
    }
}
