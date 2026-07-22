<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lease_events', function (Blueprint $table) {
            $table->string('journal_status', 10)->default('pending')->after('details');
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->string('journal_status', 10)->default('pending')->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('lease_events', function (Blueprint $table) {
            $table->dropColumn('journal_status');
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropColumn('journal_status');
        });
    }
};
