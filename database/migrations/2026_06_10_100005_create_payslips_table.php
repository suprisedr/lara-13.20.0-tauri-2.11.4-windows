<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('gross_earnings', 12, 2)->default(0);
            $table->decimal('paye', 12, 2)->default(0);
            $table->decimal('uif_employee', 12, 2)->default(0);
            $table->decimal('other_deductions', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('net_pay', 12, 2)->default(0);
            $table->decimal('uif_employer', 12, 2)->default(0);
            $table->decimal('sdl', 12, 2)->default(0);
            $table->decimal('total_employer_cost', 12, 2)->default(0);
            // Tax calculation cache
            $table->decimal('annual_equivalent_income', 12, 2)->default(0);
            $table->decimal('taxable_income', 12, 2)->default(0);
            $table->decimal('annual_tax_before_rebates', 12, 2)->default(0);
            $table->decimal('annual_tax_after_rebates', 12, 2)->default(0);
            $table->decimal('medical_aid_credit_monthly', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['payroll_run_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
