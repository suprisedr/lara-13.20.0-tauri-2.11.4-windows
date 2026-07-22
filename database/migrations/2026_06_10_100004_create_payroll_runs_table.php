<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->date('pay_date');
            $table->enum('pay_frequency', ['monthly', 'weekly', 'fortnightly'])->default('monthly');
            $table->enum('status', ['draft', 'approved', 'posted'])->default('draft');
            $table->text('notes')->nullable();
            $table->decimal('total_gross_earnings', 14, 2)->default(0);
            $table->decimal('total_paye', 14, 2)->default(0);
            $table->decimal('total_uif_employee', 14, 2)->default(0);
            $table->decimal('total_uif_employer', 14, 2)->default(0);
            $table->decimal('total_sdl', 14, 2)->default(0);
            $table->decimal('total_other_deductions', 14, 2)->default(0);
            $table->decimal('total_net_pay', 14, 2)->default(0);
            $table->decimal('total_employer_cost', 14, 2)->default(0);
            // GL accounts for posting
            $table->unsignedBigInteger('wages_expense_account_id')->nullable();
            $table->unsignedBigInteger('paye_payable_account_id')->nullable();
            $table->unsignedBigInteger('uif_payable_account_id')->nullable();
            $table->unsignedBigInteger('sdl_payable_account_id')->nullable();
            $table->unsignedBigInteger('net_wages_payable_account_id')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->foreign('wages_expense_account_id')->references('id')->on('chart_of_accounts')->nullOnDelete();
            $table->foreign('paye_payable_account_id')->references('id')->on('chart_of_accounts')->nullOnDelete();
            $table->foreign('uif_payable_account_id')->references('id')->on('chart_of_accounts')->nullOnDelete();
            $table->foreign('sdl_payable_account_id')->references('id')->on('chart_of_accounts')->nullOnDelete();
            $table->foreign('net_wages_payable_account_id')->references('id')->on('chart_of_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};
