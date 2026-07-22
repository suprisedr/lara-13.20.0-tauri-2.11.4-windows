<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ppe_classes', function (Blueprint $table) {
            $table->string('accounting_policy', 20)->default('cost')->after('depreciation_method');
        });
    }

    public function down(): void
    {
        Schema::table('ppe_classes', function (Blueprint $table) {
            $table->dropColumn('accounting_policy');
        });
    }
};
