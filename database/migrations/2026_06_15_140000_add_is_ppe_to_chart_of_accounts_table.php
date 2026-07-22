<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            // Marks an account as a Property, Plant & Equipment control account so the
            // balance sheet presents it as a single PPE line sourced from the fixed
            // asset register's carrying amount (and suppresses the ledger account).
            $table->boolean('is_ppe')->default(false)->after('is_contra');
        });
    }

    public function down(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropColumn('is_ppe');
        });
    }
};
