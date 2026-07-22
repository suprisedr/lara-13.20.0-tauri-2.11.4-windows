<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->enum('pay_type', ['salary', 'hourly'])->default('salary')->after('pay_frequency');
            $table->decimal('hourly_rate', 10, 2)->default(0)->after('basic_salary');
        });

        Schema::table('payslips', function (Blueprint $table) {
            $table->decimal('hours_worked', 8, 2)->nullable()->after('employee_id');
            $table->decimal('override_gross_earnings', 12, 2)->nullable()->after('hours_worked');
            $table->boolean('is_adjusted')->default(false)->after('override_gross_earnings');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['pay_type', 'hourly_rate']);
        });

        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn(['hours_worked', 'override_gross_earnings', 'is_adjusted']);
        });
    }
};
