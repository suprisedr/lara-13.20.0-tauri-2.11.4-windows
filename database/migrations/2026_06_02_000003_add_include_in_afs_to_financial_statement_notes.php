<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('financial_statement_notes', function (Blueprint $table) {
            $table->boolean('include_in_afs')->default(true)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('financial_statement_notes', function (Blueprint $table) {
            $table->dropColumn('include_in_afs');
        });
    }
};
