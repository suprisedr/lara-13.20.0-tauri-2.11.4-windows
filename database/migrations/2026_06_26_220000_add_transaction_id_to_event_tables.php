<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['asset_events', 'intangible_asset_events', 'lease_events'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'transaction_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->unsignedBigInteger('transaction_id')->nullable()->after('journal_status');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['asset_events', 'intangible_asset_events', 'lease_events'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'transaction_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('transaction_id');
                });
            }
        }
    }
};
