<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('pay_date');
            $table->unsignedTinyInteger('pay_day_of_month')->nullable()->after('pay_frequency');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('pay_day_of_month');
            $table->date('pay_date')->nullable()->after('pay_frequency');
        });
    }
};
