<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('draft','pending','partially_paid','paid','overdue','voided','write_off') NOT NULL DEFAULT 'draft'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::table('invoices')
                ->whereIn('status', ['partially_paid', 'overdue', 'voided', 'write_off'])
                ->update(['status' => 'pending']);

            DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('draft','pending','paid') NOT NULL DEFAULT 'draft'");
        }
    }
};
