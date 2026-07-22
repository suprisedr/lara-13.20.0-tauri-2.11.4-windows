<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->date('pay_cycle_anchor')->nullable()->after('pay_day_of_month');
        });

        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->string('pay_frequency', 20)->default('monthly')->after('employee_type');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('pay_cycle_anchor');
        });

        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->dropColumn('pay_frequency');
        });
    }
};
