<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->boolean('is_oci')->default(false)->after('is_ppe');
        });

        // Migrate existing OCI account IDs from afs_settings into the new column.
        $rows = DB::table('afs_settings')
            ->whereNotNull('oci_account_ids')
            ->get(['oci_account_ids']);

        $ids = collect();
        foreach ($rows as $row) {
            $decoded = json_decode($row->oci_account_ids, true);
            if (is_array($decoded)) {
                $ids = $ids->merge($decoded);
            }
        }

        $ids = $ids->map(fn ($id) => (int) $id)->unique()->filter()->values();

        if ($ids->isNotEmpty()) {
            DB::table('chart_of_accounts')
                ->whereIn('id', $ids->all())
                ->update(['is_oci' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropColumn('is_oci');
        });
    }
};
