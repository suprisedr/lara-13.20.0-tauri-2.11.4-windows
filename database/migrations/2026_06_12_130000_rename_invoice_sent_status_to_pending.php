<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            // Widen the enum first so existing 'sent' rows can be updated to 'pending'.
            DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('draft','sent','pending','paid') NOT NULL DEFAULT 'draft'");

            DB::table('invoices')->where('status', 'sent')->update(['status' => 'pending']);

            DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('draft','pending','paid') NOT NULL DEFAULT 'draft'");
        } else {
            DB::table('invoices')->where('status', 'sent')->update(['status' => 'pending']);

            // SQLite/others (e.g. test DB) can't ALTER an enum; relax it to a plain string.
            Schema::table('invoices', function (Blueprint $table) {
                $table->string('status')->default('draft')->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('draft','pending','paid','sent') NOT NULL DEFAULT 'draft'");

            DB::table('invoices')->where('status', 'pending')->update(['status' => 'sent']);

            DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('draft','sent','paid') NOT NULL DEFAULT 'draft'");
        } else {
            DB::table('invoices')->where('status', 'pending')->update(['status' => 'sent']);
        }
    }
};
