<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Expands chart-of-accounts codes from 4-digit (1000–8999) to 7-digit (1000000–8999999).
 *
 * Formula (bijective, reversible):
 *   type_thousands = FLOOR(old / 1000)          e.g. 1 for 1xxx, 6 for 6xxx
 *   within_type    = old MOD 1000               e.g. 500 for 1500
 *   parent_num     = FLOOR(within_type / 100) + 1   e.g. 6 for 1500
 *   child_off      = within_type MOD 100        e.g. 0 for 1500
 *   new            = type_thousands * 1000000 + parent_num * 1000 + child_off
 *
 * Examples:
 *   1000 → 1001000 (assets first parent)
 *   1100 → 1002000 (assets second parent)
 *   1199 → 1002099 (child 99 of second parent)
 *   1500 → 1006000 (PPE parent)
 *   6850 → 6009050 (ECL provision)
 *   3100 → 3002000 (retained earnings parent)
 */
return new class extends Migration
{
    private function uniqueIndexExists(): bool
    {
        $driver = DB::getDriverName();
        $name   = 'chart_of_accounts_company_id_account_code_unique';
        if ($driver === 'sqlite') {
            $rows = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='chart_of_accounts'");
            return in_array($name, array_column($rows, 'name'), true);
        }
        // MySQL / MariaDB
        $rows = DB::select("SHOW INDEX FROM chart_of_accounts WHERE Key_name = ?", [$name]);
        return count($rows) > 0;
    }

    private function formula(string $col): string
    {
        $isSqlite = DB::getDriverName() === 'sqlite';
        $mod      = $isSqlite ? '%' : 'MOD';
        $cast     = $isSqlite ? "CAST({$col} AS INTEGER)" : "CAST({$col} AS UNSIGNED)";
        $castChar = $isSqlite ? 'TEXT' : 'CHAR';

        return "
            (FLOOR({$cast} / 1000) * 1000000)
            + ((FLOOR(({$cast} {$mod} 1000) / 100) + 1) * 1000)
            + ({$cast} {$mod} 100)
        ";
    }

    public function up(): void
    {
        // Drop unique index so we can batch-update without row-by-row collisions.
        if ($this->uniqueIndexExists()) {
            Schema::table('chart_of_accounts', fn (Blueprint $t) => $t->dropUnique(['company_id', 'account_code']));
        }

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->string('account_code', 15)->change();
            $table->string('parent_code', 15)->nullable()->change();
        });

        $isSqlite = DB::getDriverName() === 'sqlite';
        $castChar = $isSqlite ? 'TEXT' : 'CHAR';
        $ac = $this->formula('account_code');
        $pc = $this->formula('parent_code');

        DB::statement("
            UPDATE chart_of_accounts
            SET
                account_code = CAST(({$ac}) AS {$castChar}),
                parent_code  = CASE
                    WHEN parent_code IS NULL OR parent_code = '' THEN parent_code
                    ELSE CAST(({$pc}) AS {$castChar})
                END
        ");

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->unique(['company_id', 'account_code']);
        });

        // Update account_vectors in pgvector (best-effort; will re-sync on next embed).
        if (config('database.default') !== 'sqlite') {
            try {
                DB::connection('pgsql')->statement("
                    UPDATE account_vectors
                    SET account_code = CAST(
                        (FLOOR(account_code::bigint / 1000) * 1000000)
                        + ((FLOOR((account_code::bigint % 1000) / 100) + 1) * 1000)
                        + (account_code::bigint % 100)
                    AS TEXT)
                    WHERE account_code ~ '^[0-9]+$'
                      AND LENGTH(account_code) = 4
                ");
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('account_vectors code migration skipped: '.$e->getMessage());
            }
        }
    }

    public function down(): void
    {
        // Reverse formula: old = type_thousands * 1000 + (parent_num - 1) * 100 + child_off
        // where type_thousands = FLOOR(new / 1000000)
        //       parent_num     = FLOOR((new MOD 1000000) / 1000)
        //       child_off      = new MOD 1000
        if ($this->uniqueIndexExists()) {
            Schema::table('chart_of_accounts', fn (Blueprint $t) => $t->dropUnique(['company_id', 'account_code']));
        }

        $isSqlite = DB::getDriverName() === 'sqlite';
        $mod      = $isSqlite ? '%' : 'MOD';
        $castChar = $isSqlite ? 'TEXT' : 'CHAR';
        $rev = fn(string $col) => "CAST(
            (FLOOR(CAST({$col} AS " . ($isSqlite ? 'INTEGER' : 'UNSIGNED') . ") / 1000000) * 1000)
            + ((FLOOR((CAST({$col} AS " . ($isSqlite ? 'INTEGER' : 'UNSIGNED') . ") {$mod} 1000000) / 1000) - 1) * 100)
            + (CAST({$col} AS " . ($isSqlite ? 'INTEGER' : 'UNSIGNED') . ") {$mod} 1000)
        AS {$castChar})";

        DB::statement("
            UPDATE chart_of_accounts
            SET
                account_code = {$rev('account_code')},
                parent_code  = CASE
                    WHEN parent_code IS NULL OR parent_code = '' THEN parent_code
                    ELSE {$rev('parent_code')}
                END
        ");

        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->string('account_code', 10)->change();
            $table->string('parent_code', 10)->nullable()->change();
            $table->unique(['company_id', 'account_code']);
        });
    }
};
