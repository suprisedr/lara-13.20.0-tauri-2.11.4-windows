<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            // When true, a child/sub-account is presented as its own line on the
            // financial statements (and its balance is excluded from the parent's
            // rolled-up total). When false, it is rolled into its parent account.
            $table->boolean('show_separately')->default(false)->after('parent_id');
        });
    }

    public function down(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropColumn('show_separately');
        });
    }
};
