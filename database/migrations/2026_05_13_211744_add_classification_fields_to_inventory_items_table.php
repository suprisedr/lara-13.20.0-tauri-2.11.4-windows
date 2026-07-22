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
            $table->string('inventory_type')->default('merchandise')->after('is_active')
                ->comment('IAS 2.6 classification: raw_material, wip, finished_goods, merchandise, consumable');

            $table->decimal('quantity_on_hand', 15, 4)->default(0)->after('inventory_type')
                ->comment('Current units held in stock');
            $table->decimal('quantity_reserved', 15, 4)->default(0)->after('quantity_on_hand')
                ->comment('Units committed to pending sales orders / invoices');

            $table->foreignId('inventory_account_id')->nullable()->after('quantity_reserved')
                ->constrained('chart_of_accounts')->nullOnDelete()
                ->comment('Balance-sheet asset GL account (IAS 2 disclosure)');
            $table->foreignId('cogs_account_id')->nullable()->after('inventory_account_id')
                ->constrained('chart_of_accounts')->nullOnDelete()
                ->comment('P&L cost-of-goods-sold GL account');
            $table->foreignId('write_down_account_id')->nullable()->after('cogs_account_id')
                ->constrained('chart_of_accounts')->nullOnDelete()
                ->comment('P&L write-down / write-off expense GL account');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropForeign(['inventory_account_id']);
            $table->dropForeign(['cogs_account_id']);
            $table->dropForeign(['write_down_account_id']);
            $table->dropColumn([
                'inventory_type',
                'quantity_on_hand',
                'quantity_reserved',
                'inventory_account_id',
                'cogs_account_id',
                'write_down_account_id',
            ]);
        });
    }
};
