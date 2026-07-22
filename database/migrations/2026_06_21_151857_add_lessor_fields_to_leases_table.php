<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            $table->string('role', 10)->default('lessee')->after('company_id');
            $table->string('classification', 10)->nullable()->after('role');

            // Lessor finance-lease fields
            $table->decimal('net_investment', 15, 2)->nullable()->after('rou_asset_cost');
            $table->decimal('unearned_finance_income', 15, 2)->nullable()->after('net_investment');
            $table->decimal('asset_fair_value', 15, 2)->nullable()->after('unearned_finance_income');
            $table->decimal('unguaranteed_residual', 15, 2)->default(0)->after('asset_fair_value');

            $table->index(['company_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'role']);
            $table->dropColumn([
                'role',
                'classification',
                'net_investment',
                'unearned_finance_income',
                'asset_fair_value',
                'unguaranteed_residual',
            ]);
        });
    }
};
