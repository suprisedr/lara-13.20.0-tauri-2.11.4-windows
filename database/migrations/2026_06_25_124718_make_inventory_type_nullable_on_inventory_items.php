<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill nulls on physical items; service items legitimately stay null
        DB::table('inventory_items')
            ->whereNull('inventory_type')
            ->where('is_service', false)
            ->update(['inventory_type' => 'merchandise']);

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->string('inventory_type')->default('merchandise')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->string('inventory_type')->default('merchandise')->nullable(false)->change();
        });
    }
};
