<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add the reversal_of_id FK first (self-referential, nullable)
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('reversal_of_id')->nullable()->after('source_document');
            $table->foreign('reversal_of_id')->references('id')->on('transactions')->nullOnDelete();
        });

        // Widen the status enum to include 'reversed'
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE transactions MODIFY COLUMN status ENUM('draft','posted','reversed') NOT NULL DEFAULT 'draft'");
        } else {
            // SQLite/others (e.g. test DB) can't ALTER an enum; relax it to a plain string.
            Schema::table('transactions', function (Blueprint $table) {
                $table->string('status')->default('draft')->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['reversal_of_id']);
            $table->dropColumn('reversal_of_id');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE transactions MODIFY COLUMN status ENUM('draft','posted') NOT NULL DEFAULT 'draft'");
        }
    }
};
