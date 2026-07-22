<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // IAS 19.13-16  Accumulating compensated absences (leave)
        // IAS 19.51     Defined contribution plan (employer retirement)
        // IAS 19.19     Profit-sharing / bonus accrual
        Schema::table('employees', function (Blueprint $table) {
            $table->enum('retirement_fund_type', ['defined_contribution', 'defined_benefit'])
                  ->default('defined_contribution')
                  ->after('retirement_fund_contribution');
            $table->decimal('employer_retirement_contribution', 10, 2)->default(0)->after('retirement_fund_type');
            $table->decimal('medical_aid_employer_contribution', 10, 2)->default(0)->after('medical_aid_employee_contribution');
            $table->decimal('leave_days_per_year', 5, 1)->default(15)->after('employer_retirement_contribution');
            $table->decimal('leave_balance_days', 7, 2)->default(0)->after('leave_days_per_year');
            $table->decimal('bonus_months', 4, 2)->default(0)->after('leave_balance_days');
        });

        // Per-payslip IAS 19 accrual amounts
        Schema::table('payslips', function (Blueprint $table) {
            $table->decimal('employer_retirement', 10, 2)->default(0)->after('sdl');
            $table->decimal('employer_medical_aid', 10, 2)->default(0)->after('employer_retirement');
            $table->decimal('leave_accrual', 10, 2)->default(0)->after('employer_medical_aid');
            $table->decimal('bonus_accrual', 10, 2)->default(0)->after('leave_accrual');
        });

        // Run-level totals + GL accounts for IAS 19 postings
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->decimal('total_employer_retirement', 14, 2)->default(0)->after('total_employer_cost');
            $table->decimal('total_employer_medical_aid', 14, 2)->default(0)->after('total_employer_retirement');
            $table->decimal('total_leave_accrual', 14, 2)->default(0)->after('total_employer_medical_aid');
            $table->decimal('total_bonus_accrual', 14, 2)->default(0)->after('total_leave_accrual');

            $table->unsignedBigInteger('retirement_payable_account_id')->nullable()->after('sdl_payable_account_id');
            $table->unsignedBigInteger('medical_aid_payable_account_id')->nullable()->after('retirement_payable_account_id');
            $table->unsignedBigInteger('leave_accrual_account_id')->nullable()->after('medical_aid_payable_account_id');
            $table->unsignedBigInteger('bonus_provision_account_id')->nullable()->after('leave_accrual_account_id');

            $table->foreign('retirement_payable_account_id')->references('id')->on('chart_of_accounts')->nullOnDelete();
            $table->foreign('medical_aid_payable_account_id')->references('id')->on('chart_of_accounts')->nullOnDelete();
            $table->foreign('leave_accrual_account_id')->references('id')->on('chart_of_accounts')->nullOnDelete();
            $table->foreign('bonus_provision_account_id')->references('id')->on('chart_of_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->dropForeign(['retirement_payable_account_id']);
            $table->dropForeign(['medical_aid_payable_account_id']);
            $table->dropForeign(['leave_accrual_account_id']);
            $table->dropForeign(['bonus_provision_account_id']);
            $table->dropColumn([
                'total_employer_retirement', 'total_employer_medical_aid',
                'total_leave_accrual', 'total_bonus_accrual',
                'retirement_payable_account_id', 'medical_aid_payable_account_id',
                'leave_accrual_account_id', 'bonus_provision_account_id',
            ]);
        });

        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn(['employer_retirement', 'employer_medical_aid', 'leave_accrual', 'bonus_accrual']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'retirement_fund_type', 'employer_retirement_contribution',
                'medical_aid_employer_contribution', 'leave_days_per_year',
                'leave_balance_days', 'bonus_months',
            ]);
        });
    }
};
