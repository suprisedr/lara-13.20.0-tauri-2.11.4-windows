<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['biological_asset_events', 'investment_property_events', 'inventory_movements'];

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
        foreach (['biological_asset_events', 'investment_property_events', 'inventory_movements'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'transaction_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('transaction_id');
                });
            }
        }
    }
};
