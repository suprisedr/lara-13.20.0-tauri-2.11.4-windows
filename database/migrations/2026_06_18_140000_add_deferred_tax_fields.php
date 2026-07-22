<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->decimal('corporate_tax_rate', 5, 2)->default(27.00)->after('sdl_number');
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->decimal('sars_wear_tear_years', 5, 2)->nullable()->after('useful_life_years');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('corporate_tax_rate');
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('sars_wear_tear_years');
        });
    }
};
