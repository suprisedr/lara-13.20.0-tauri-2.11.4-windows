<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->date('pay_date')->nullable()->after('pay_frequency');
        });

        Schema::table('payslips', function (Blueprint $table) {
            $table->date('pay_date')->nullable()->after('employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('pay_date');
        });
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn('pay_date');
        });
    }
};
