<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            // null = legacy "all employees"; 'salary' or 'hourly' for separated runs
            $table->enum('employee_type', ['salary', 'hourly'])->nullable()->after('pay_frequency');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->dropColumn('employee_type');
        });
    }
};
