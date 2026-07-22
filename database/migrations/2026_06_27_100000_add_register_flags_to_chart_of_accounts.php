<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->boolean('is_investment_property')->default(false)->after('is_inventory');
            $table->boolean('is_biological_asset')->default(false)->after('is_investment_property');
            $table->boolean('is_lease_asset')->default(false)->after('is_biological_asset');
        });
    }

    public function down(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropColumn(['is_investment_property', 'is_biological_asset', 'is_lease_asset']);
        });
    }
};
