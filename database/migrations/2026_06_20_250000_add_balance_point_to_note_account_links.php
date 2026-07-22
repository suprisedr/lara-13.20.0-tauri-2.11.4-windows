<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('note_account_links', function (Blueprint $table) {
            $table->string('balance_point')->default('closing')->after('sign');
        });
    }

    public function down(): void
    {
        Schema::table('note_account_links', function (Blueprint $table) {
            $table->dropColumn('balance_point');
        });
    }
};
