<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->decimal('nrv_per_unit', 15, 2)->nullable()->after('trade_discount');
            $table->decimal('accumulated_write_down', 15, 2)->default(0)->after('nrv_per_unit');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn(['nrv_per_unit', 'accumulated_write_down']);
        });
    }
};
