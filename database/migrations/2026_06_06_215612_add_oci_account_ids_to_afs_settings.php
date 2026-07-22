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
        Schema::table('afs_settings', function (Blueprint $table) {
            $table->json('oci_account_ids')->nullable()->after('level_of_assurance');
        });
    }

    public function down(): void
    {
        Schema::table('afs_settings', function (Blueprint $table) {
            $table->dropColumn('oci_account_ids');
        });
    }
};
