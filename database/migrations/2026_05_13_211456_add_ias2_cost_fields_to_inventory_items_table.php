<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->decimal('purchase_cost', 15, 2)->default(0)->after('unit_price')
                ->comment('Base invoice price from supplier (IAS 2 cost of purchase)');
            $table->decimal('freight_in', 15, 2)->default(0)->after('purchase_cost')
                ->comment('Inbound freight and transportation costs');
            $table->decimal('import_duties', 15, 2)->default(0)->after('freight_in')
                ->comment('Import duties and irrecoverable taxes');
            $table->decimal('handling_costs', 15, 2)->default(0)->after('import_duties')
                ->comment('Direct handling costs incurred in acquisition');
            $table->decimal('trade_discount', 15, 2)->default(0)->after('handling_costs')
                ->comment('Trade discounts deducted from purchase cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn([
                'purchase_cost',
                'freight_in',
                'import_duties',
                'handling_costs',
                'trade_discount',
            ]);
        });
    }
};
