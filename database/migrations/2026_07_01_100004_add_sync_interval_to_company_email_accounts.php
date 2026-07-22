<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_email_accounts', function (Blueprint $table) {
            $table->unsignedSmallInteger('sync_interval_minutes')->default(30)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('company_email_accounts', function (Blueprint $table) {
            $table->dropColumn('sync_interval_minutes');
        });
    }
};
