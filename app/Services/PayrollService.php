<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Payslip;
use App\Models\PayrollRun;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function __construct(private readonly SaTaxService $tax) {}

    /**
     * Calculate payslips for all active employees in a draft payroll run.
     * Deletes and recreates payslips so re-calculation is idempotent.
     */
    public function calculate(PayrollRun $run): void
    {
        abort_if($run->isPosted(), 422, 'Cannot recalculate a posted payroll run.');

        $employeeQuery = $run->company->employees()
            ->where('is_active', true)
            ->with(['activePayrollComponents']);

        if ($run->employee_type === 'salary') {
            $employeeQuery->where('pay_type', 'salary');
        } elseif ($run->employee_type === 'hourly') {
            $employeeQuery->where('pay_type', 'hourly');
        }

        if ($run->pay_frequency) {
            $employeeQuery->where('pay_frequency', $run->pay_frequency);
        }

        $employees = $employeeQuery->get();

        DB::transaction(function () use ($run, $employees) {
            $run->payslips()->delete();

            $totals = [
                'gross'               => 0,
                'paye'                => 0,
                'uif_employee'        => 0,
                'uif_employer'        => 0,
                'sdl'                 => 0,
                'other_deductions'    => 0,
                'net_pay'             => 0,
                'employer_cost'       => 0,
                'employer_retirement' => 0,
                'employer_medical_aid'=> 0,
                'leave_accrual'       => 0,
                'bonus_accrual'       => 0,
            ];

            foreach ($employees as $employee) {
                $payslip = $this->calculatePayslip($run, $employee);

                $totals['gross']                += (float) $payslip->gross_earnings;
                $totals['paye']                 += (float) $payslip->paye;
                $totals['uif_employee']         += (float) $payslip->uif_employee;
                $totals['uif_employer']         += (float) $payslip->uif_employer;
                $totals['sdl']                  += (float) $payslip->sdl;
                $totals['other_deductions']     += (float) $payslip->other_deductions;
                $totals['net_pay']              += (float) $payslip->net_pay;
                $totals['employer_cost']        += (float) $payslip->total_employer_cost;
                $totals['employer_retirement']  += (float) $payslip->employer_retirement;
                $totals['employer_medical_aid'] += (float) $payslip->employer_medical_aid;
                $totals['leave_accrual']        += (float) $payslip->leave_accrual;
                $totals['bonus_accrual']        += (float) $payslip->bonus_accrual;
            }

            $run->update([
                'total_gross_earnings'   => round($totals['gross'], 2),
                'total_paye'             => round($totals['paye'], 2),
                'total_uif_employee'     => round($totals['uif_employee'], 2),
                'total_uif_employer'     => round($totals['uif_employer'], 2),
                'total_sdl'              => round($totals['sdl'], 2),
                'total_other_deductions' => round($totals['other_deductions'], 2),
                'total_net_pay'              => round($totals['net_pay'], 2),
                'total_employer_cost'        => round($totals['employer_cost'], 2),
                'total_employer_retirement'  => round($totals['employer_retirement'], 2),
                'total_employer_medical_aid' => round($totals['employer_medical_aid'], 2),
                'total_leave_accrual'        => round($totals['leave_accrual'], 2),
                'total_bonus_accrual'        => round($totals['bonus_accrual'], 2),
            ]);
        });
    }

    /**
     * Build the journal entry lines for a run without posting anything.
     * Returns an array of ['type', 'description', 'amount', 'account'] rows.
     * 'account' is null when no GL account is mapped.
     */
    public function getJournalLines(PayrollRun $run): array
    {
        $run->loadMissing([
            'wagesExpenseAccount', 'payePayableAccount', 'uifPayableAccount',
            'sdlPayableAccount', 'netWagesPayableAccount',
        ]);

        $lines = [];

        $wage = $run->wagesExpenseAccount;
        $wageLabel = $wage ? $wage->account_code . ' – ' . $wage->account_name : null;

        // DR Wages Expense (gross earnings)
        $lines[] = ['type' => 'debit',  'description' => 'Gross wages',              'amount' => (float) $run->total_gross_earnings, 'account' => $wageLabel];

        // CR PAYE Payable
        if ((float) $run->total_paye > 0) {
            $acct = $run->payePayableAccount;
            $lines[] = ['type' => 'credit', 'description' => 'PAYE withheld',          'amount' => (float) $run->total_paye,           'account' => $acct ? $acct->account_code . ' – ' . $acct->account_name : null];
        }

        // UIF (employee + employer)
        $totalUif = round((float) $run->total_uif_employee + (float) $run->total_uif_employer, 2);
        if ($totalUif > 0) {
            $acct = $run->uifPayableAccount;
            $lines[] = ['type' => 'debit',  'description' => 'UIF employer contribution',           'amount' => (float) $run->total_uif_employer, 'account' => $wageLabel];
            $lines[] = ['type' => 'credit', 'description' => 'UIF payable (employee + employer)',    'amount' => $totalUif,                        'account' => $acct ? $acct->account_code . ' – ' . $acct->account_name : null];
        }

        // SDL
        if ((float) $run->total_sdl > 0) {
            $acct = $run->sdlPayableAccount;
            $lines[] = ['type' => 'debit',  'description' => 'SDL employer levy',  'amount' => (float) $run->total_sdl, 'account' => $wageLabel];
            $lines[] = ['type' => 'credit', 'description' => 'SDL payable',        'amount' => (float) $run->total_sdl, 'account' => $acct ? $acct->account_code . ' – ' . $acct->account_name : null];
        }

        // CR Net Wages Payable
        $acct = $run->netWagesPayableAccount;
        $lines[] = ['type' => 'credit', 'description' => 'Net wages payable', 'amount' => (float) $run->total_net_pay, 'account' => $acct ? $acct->account_code . ' – ' . $acct->account_name : null];

        // IAS 19 — Employer retirement
        if ((float) $run->total_employer_retirement > 0) {
            $acct = \App\Models\ChartOfAccount::find($run->retirement_payable_account_id);
            $lines[] = ['type' => 'debit',  'description' => 'Employer retirement fund contribution', 'amount' => (float) $run->total_employer_retirement, 'account' => $wageLabel];
            $lines[] = ['type' => 'credit', 'description' => 'Retirement fund payable (employer)',    'amount' => (float) $run->total_employer_retirement, 'account' => $acct ? $acct->account_code . ' – ' . $acct->account_name : null];
        }

        // IAS 19 — Employer medical aid
        if ((float) $run->total_employer_medical_aid > 0) {
            $acct = \App\Models\ChartOfAccount::find($run->medical_aid_payable_account_id);
            $lines[] = ['type' => 'debit',  'description' => 'Employer medical aid subsidy',    'amount' => (float) $run->total_employer_medical_aid, 'account' => $wageLabel];
            $lines[] = ['type' => 'credit', 'description' => 'Medical aid payable (employer)',  'amount' => (float) $run->total_employer_medical_aid, 'account' => $acct ? $acct->account_code . ' – ' . $acct->account_name : null];
        }

        // IAS 19 — Leave accrual
        if ((float) $run->total_leave_accrual > 0) {
            $acct = \App\Models\ChartOfAccount::find($run->leave_accrual_account_id);
            $lines[] = ['type' => 'debit',  'description' => 'Leave pay accrual (IAS 19)',  'amount' => (float) $run->total_leave_accrual, 'account' => $wageLabel];
            $lines[] = ['type' => 'credit', 'description' => 'Accrued leave payable',       'amount' => (float) $run->total_leave_accrual, 'account' => $acct ? $acct->account_code . ' – ' . $acct->account_name : null];
        }

        // IAS 19 — Bonus provision
        if ((float) $run->total_bonus_accrual > 0) {
            $acct = \App\Models\ChartOfAccount::find($run->bonus_provision_account_id);
            $lines[] = ['type' => 'debit',  'description' => 'Bonus provision (IAS 19)',  'amount' => (float) $run->total_bonus_accrual, 'account' => $wageLabel];
            $lines[] = ['type' => 'credit', 'description' => 'Bonus provision payable',   'amount' => (float) $run->total_bonus_accrual, 'account' => $acct ? $acct->account_code . ' – ' . $acct->account_name : null];
        }

        return $lines;
    }

    /**
     * Post the payroll run to accounting by creating a journal entry.
     * Requires GL accounts to be set on the run.
     */
    public function post(PayrollRun $run, \App\Models\User $user): void
    {
        abort_if($run->isPosted(), 422, 'This payroll run has already been posted.');
        abort_if($run->payslips()->count() === 0, 422, 'Calculate payroll before posting.');

        $required = [
            'wages_expense_account_id',
            'paye_payable_account_id',
            'uif_payable_account_id',
            'net_wages_payable_account_id',
        ];

        foreach ($required as $field) {
            abort_if(! $run->$field, 422, "GL account not set: {$field}");
        }

        DB::transaction(function () use ($run, $user) {
            $service = app(TransactionService::class);

            $description = 'Payroll – ' . $run->period_label;

            $lines = [];
            $sort  = 0;

            // DR Wages Expense (gross earnings)
            $lines[] = [
                'chart_of_account_id' => $run->wages_expense_account_id,
                'type'                => 'debit',
                'amount'              => (float) $run->total_gross_earnings,
                'description'         => 'Gross wages',
                'sort_order'          => $sort++,
            ];

            // CR PAYE Payable
            if ((float) $run->total_paye > 0) {
                $lines[] = [
                    'chart_of_account_id' => $run->paye_payable_account_id,
                    'type'                => 'credit',
                    'amount'              => (float) $run->total_paye,
                    'description'         => 'PAYE withheld',
                    'sort_order'          => $sort++,
                ];
            }

            // CR UIF Payable (employee + employer)
            $totalUif = round((float) $run->total_uif_employee + (float) $run->total_uif_employer, 2);
            if ($totalUif > 0 && $run->uif_payable_account_id) {
                // DR Wages Expense for employer UIF
                $lines[] = [
                    'chart_of_account_id' => $run->wages_expense_account_id,
                    'type'                => 'debit',
                    'amount'              => (float) $run->total_uif_employer,
                    'description'         => 'UIF employer contribution',
                    'sort_order'          => $sort++,
                ];
                $lines[] = [
                    'chart_of_account_id' => $run->uif_payable_account_id,
                    'type'                => 'credit',
                    'amount'              => $totalUif,
                    'description'         => 'UIF payable (employee + employer)',
                    'sort_order'          => $sort++,
                ];
            }

            // CR SDL Payable
            if ((float) $run->total_sdl > 0 && $run->sdl_payable_account_id) {
                $lines[] = [
                    'chart_of_account_id' => $run->wages_expense_account_id,
                    'type'                => 'debit',
                    'amount'              => (float) $run->total_sdl,
                    'description'         => 'SDL employer levy',
                    'sort_order'          => $sort++,
                ];
                $lines[] = [
                    'chart_of_account_id' => $run->sdl_payable_account_id,
                    'type'                => 'credit',
                    'amount'              => (float) $run->total_sdl,
                    'description'         => 'SDL payable',
                    'sort_order'          => $sort++,
                ];
            }

            // CR Net Wages Payable (take-home)
            $lines[] = [
                'chart_of_account_id' => $run->net_wages_payable_account_id,
                'type'                => 'credit',
                'amount'              => (float) $run->total_net_pay,
                'description'         => 'Net wages payable',
                'sort_order'          => $sort++,
            ];

            // IAS 19.51 — Employer retirement (defined contribution)
            if ((float) $run->total_employer_retirement > 0 && $run->retirement_payable_account_id) {
                $lines[] = [
                    'chart_of_account_id' => $run->wages_expense_account_id,
                    'type'                => 'debit',
                    'amount'              => (float) $run->total_employer_retirement,
                    'description'         => 'Employer retirement fund contribution',
                    'sort_order'          => $sort++,
                ];
                $lines[] = [
                    'chart_of_account_id' => $run->retirement_payable_account_id,
                    'type'                => 'credit',
                    'amount'              => (float) $run->total_employer_retirement,
                    'description'         => 'Retirement fund payable (employer)',
                    'sort_order'          => $sort++,
                ];
            }

            // IAS 19 — Employer medical aid subsidy
            if ((float) $run->total_employer_medical_aid > 0 && $run->medical_aid_payable_account_id) {
                $lines[] = [
                    'chart_of_account_id' => $run->wages_expense_account_id,
                    'type'                => 'debit',
                    'amount'              => (float) $run->total_employer_medical_aid,
                    'description'         => 'Employer medical aid subsidy',
                    'sort_order'          => $sort++,
                ];
                $lines[] = [
                    'chart_of_account_id' => $run->medical_aid_payable_account_id,
                    'type'                => 'credit',
                    'amount'              => (float) $run->total_employer_medical_aid,
                    'description'         => 'Medical aid payable (employer)',
                    'sort_order'          => $sort++,
                ];
            }

            // IAS 19.13-16 — Accumulating compensated absences (leave pay liability)
            if ((float) $run->total_leave_accrual > 0 && $run->leave_accrual_account_id) {
                $lines[] = [
                    'chart_of_account_id' => $run->wages_expense_account_id,
                    'type'                => 'debit',
                    'amount'              => (float) $run->total_leave_accrual,
                    'description'         => 'Leave pay accrual (IAS 19)',
                    'sort_order'          => $sort++,
                ];
                $lines[] = [
                    'chart_of_account_id' => $run->leave_accrual_account_id,
                    'type'                => 'credit',
                    'amount'              => (float) $run->total_leave_accrual,
                    'description'         => 'Accrued leave payable',
                    'sort_order'          => $sort++,
                ];
            }

            // IAS 19.19 — Bonus / 13th cheque provision
            if ((float) $run->total_bonus_accrual > 0 && $run->bonus_provision_account_id) {
                $lines[] = [
                    'chart_of_account_id' => $run->wages_expense_account_id,
                    'type'                => 'debit',
                    'amount'              => (float) $run->total_bonus_accrual,
                    'description'         => 'Bonus provision (IAS 19)',
                    'sort_order'          => $sort++,
                ];
                $lines[] = [
                    'chart_of_account_id' => $run->bonus_provision_account_id,
                    'type'                => 'credit',
                    'amount'              => (float) $run->total_bonus_accrual,
                    'description'         => 'Bonus provision payable',
                    'sort_order'          => $sort++,
                ];
            }

            $transaction = $service->record($run->company, $user, [
                'transaction_date' => $run->period_end->toDateString(),
                'description'      => $description,
                'reference'        => 'PAYROLL-' . $run->id,
                'lines'            => $lines,
            ]);

            // Auto-post the transaction
            $transaction->update(['status' => 'posted']);

            $run->update([
                'status'         => 'posted',
                'transaction_id' => $transaction->id,
            ]);
        });
    }

    /**
     * Recalculate a single payslip in-place, preserving any override values.
     * Used for mid-period adjustments (hours change, salary increase, etc.).
     */
    public function adjustPayslip(
        Payslip $payslip,
        ?float $hoursWorked,
        ?float $overrideGross,
    ): void {
        abort_if($payslip->payrollRun->isPosted(), 422, 'Cannot adjust a payslip on a posted run.');

        $run      = $payslip->payrollRun;
        $employee = $payslip->employee->loadMissing('activePayrollComponents');

        $periodsPerYear = match ($employee->pay_frequency) {
            'weekly'      => 52,
            'fortnightly' => 26,
            default       => 12,
        };

        DB::transaction(function () use ($payslip, $run, $employee, $periodsPerYear, $hoursWorked, $overrideGross) {
            // Wipe old lines then recalculate
            $payslip->lines()->delete();

            $newPayslip = $this->buildPayslipData(
                employee: $employee,
                periodsPerYear: $periodsPerYear,
                hoursWorked: $hoursWorked,
                overrideGross: $overrideGross,
            );

            $payslip->update(array_merge($newPayslip['attrs'], [
                'hours_worked'            => $hoursWorked,
                'override_gross_earnings' => $overrideGross,
                'is_adjusted'             => true,
            ]));

            foreach ($newPayslip['lines'] as $line) {
                $payslip->lines()->create($line);
            }

            $this->recalculateRunTotals($run);
        });
    }

    private function calculatePayslip(PayrollRun $run, Employee $employee): Payslip
    {
        $periodsPerYear = match ($employee->pay_frequency) {
            'weekly'      => 52,
            'fortnightly' => 26,
            default       => 12,
        };

        $data = $this->buildPayslipData(
            employee: $employee,
            periodsPerYear: $periodsPerYear,
            hoursWorked: null,
            overrideGross: null,
        );

        $payslip = $run->payslips()->create(array_merge($data['attrs'], [
            'employee_id'             => $employee->id,
            'pay_date'                => $this->resolvePayDate($employee, $run),
            'hours_worked'            => null,
            'override_gross_earnings' => null,
            'is_adjusted'             => false,
        ]));

        foreach ($data['lines'] as $line) {
            $payslip->lines()->create($line);
        }

        return $payslip;
    }

    /**
     * Core calculation logic — shared by initial calculation and per-payslip adjustment.
     *
     * @return array{attrs: array<string, mixed>, lines: array<int, array<string, mixed>>}
     */
    private function buildPayslipData(
        Employee $employee,
        int $periodsPerYear,
        ?float $hoursWorked,
        ?float $overrideGross,
    ): array {
        $earningLines    = [];
        $deductionLines  = [];
        $grossEarnings   = 0;
        $otherDeductions = 0;
        $sort            = 0;

        if ($overrideGross !== null) {
            // Manual override: use the provided gross (handles mid-period raises, pro-rata, etc.)
            $earningLines[] = [
                'type'         => 'earning',
                'description'  => 'Adjusted Gross Earnings',
                'amount'       => $overrideGross,
                'is_statutory' => false,
                'sort_order'   => $sort++,
            ];
            $grossEarnings = $overrideGross;
        } elseif ($employee->isHourly()) {
            // Hourly employee: hours × rate
            $hours  = $hoursWorked ?? 0;
            $amount = round($hours * (float) $employee->hourly_rate, 2);
            $earningLines[] = [
                'type'         => 'earning',
                'description'  => number_format($hours, 2) . ' hrs @ R' . number_format($employee->hourly_rate, 2),
                'amount'       => $amount,
                'is_statutory' => false,
                'sort_order'   => $sort++,
            ];
            $grossEarnings += $amount;
        } else {
            // Fixed salary
            $basicSalary = (float) $employee->basic_salary;
            if ($basicSalary > 0) {
                $earningLines[] = [
                    'type'         => 'earning',
                    'description'  => 'Basic Salary',
                    'amount'       => $basicSalary,
                    'is_statutory' => false,
                    'sort_order'   => $sort++,
                ];
                $grossEarnings += $basicSalary;
            }
        }

        // Additional payroll components (allowances, deductions, etc.)
        foreach ($employee->activePayrollComponents as $component) {
            $amount = (float) $component->pivot->amount;
            if ($amount <= 0) {
                continue;
            }

            if ($component->type === 'earning') {
                $earningLines[] = [
                    'payroll_component_id' => $component->id,
                    'type'                 => 'earning',
                    'description'          => $component->name,
                    'amount'               => $amount,
                    'is_statutory'         => false,
                    'sort_order'           => $sort++,
                ];
                if ($component->is_taxable) {
                    $grossEarnings += $amount;
                }
            } elseif ($component->type === 'deduction') {
                $deductionLines[] = [
                    'payroll_component_id' => $component->id,
                    'type'                 => 'deduction',
                    'description'          => $component->name,
                    'amount'               => $amount,
                    'is_statutory'         => false,
                    'sort_order'           => $sort++,
                ];
                $otherDeductions += $amount;
            }
        }

        $age = $employee->age ?? 30;

        $payeResult  = $this->tax->calculatePaye(
            monthlyGrossEarnings: $grossEarnings,
            monthlyRetirementFund: (float) $employee->retirement_fund_contribution,
            medicalAidMembers: (int) $employee->medical_aid_members,
            age: $age,
            periodsPerYear: $periodsPerYear,
        );
        $monthlyPaye = $payeResult['monthly_paye'];
        $uif         = $this->tax->calculateUif($grossEarnings);
        $sdl         = $this->tax->calculateSdl($grossEarnings);

        // Medical aid employee deduction
        $medicalAidDeduction = (float) $employee->medical_aid_employee_contribution;
        if ($medicalAidDeduction > 0) {
            $deductionLines[] = [
                'type'         => 'deduction',
                'description'  => 'Medical Aid (employee)',
                'amount'       => $medicalAidDeduction,
                'is_statutory' => false,
                'sort_order'   => $sort++,
            ];
            $otherDeductions += $medicalAidDeduction;
        }

        // Retirement fund employee deduction
        $retirementDeduction = (float) $employee->retirement_fund_contribution;
        if ($retirementDeduction > 0) {
            $deductionLines[] = [
                'type'         => 'deduction',
                'description'  => 'Retirement Fund (employee)',
                'amount'       => $retirementDeduction,
                'is_statutory' => false,
                'sort_order'   => $sort++,
            ];
            $otherDeductions += $retirementDeduction;
        }

        if ($monthlyPaye > 0) {
            $deductionLines[] = [
                'type'         => 'statutory_deduction',
                'description'  => 'PAYE',
                'amount'       => $monthlyPaye,
                'is_statutory' => true,
                'sort_order'   => $sort++,
            ];
        }

        $deductionLines[] = [
            'type'         => 'statutory_deduction',
            'description'  => 'UIF (employee)',
            'amount'       => $uif['employee'],
            'is_statutory' => true,
            'sort_order'   => $sort++,
        ];

        // IAS 19.51 — Defined contribution: employer retirement contribution
        $employerRetirement = (float) $employee->employer_retirement_contribution;

        // IAS 19 short-term — employer medical aid subsidy
        $employerMedicalAid = (float) $employee->medical_aid_employer_contribution;

        // IAS 19.13-16 — Accumulating compensated absences (leave pay accrual per period)
        $dailyRate    = $grossEarnings / 21.67; // average working days per month
        $leaveAccrual = round($dailyRate * ((float) $employee->leave_days_per_year / $periodsPerYear), 2);

        // IAS 19.19 — Bonus / 13th cheque provision (1/periodsPerYear of annual bonus per period)
        $bonusAccrual = $employee->bonus_months > 0
            ? round($grossEarnings * (float) $employee->bonus_months / $periodsPerYear, 2)
            : 0.0;

        $employerLines   = [];
        $employerLines[] = [
            'type'         => 'employer_contribution',
            'description'  => 'UIF (employer)',
            'amount'       => $uif['employer'],
            'is_statutory' => true,
            'sort_order'   => $sort++,
        ];
        $employerLines[] = [
            'type'         => 'employer_contribution',
            'description'  => 'SDL (employer)',
            'amount'       => $sdl,
            'is_statutory' => true,
            'sort_order'   => $sort++,
        ];
        if ($employerRetirement > 0) {
            $employerLines[] = [
                'type'         => 'employer_contribution',
                'description'  => 'Retirement Fund (employer)',
                'amount'       => $employerRetirement,
                'is_statutory' => false,
                'sort_order'   => $sort++,
            ];
        }
        if ($employerMedicalAid > 0) {
            $employerLines[] = [
                'type'         => 'employer_contribution',
                'description'  => 'Medical Aid (employer subsidy)',
                'amount'       => $employerMedicalAid,
                'is_statutory' => false,
                'sort_order'   => $sort++,
            ];
        }
        if ($leaveAccrual > 0) {
            $employerLines[] = [
                'type'         => 'employer_contribution',
                'description'  => 'Leave Pay Accrual (IAS 19)',
                'amount'       => $leaveAccrual,
                'is_statutory' => false,
                'sort_order'   => $sort++,
            ];
        }
        if ($bonusAccrual > 0) {
            $employerLines[] = [
                'type'         => 'employer_contribution',
                'description'  => 'Bonus Provision (IAS 19)',
                'amount'       => $bonusAccrual,
                'is_statutory' => false,
                'sort_order'   => $sort++,
            ];
        }

        $totalDeductions   = round($monthlyPaye + $uif['employee'] + $otherDeductions, 2);
        $netPay            = round($grossEarnings - $totalDeductions, 2);
        $totalEmployerCost = round(
            $grossEarnings + $uif['employer'] + $sdl
            + $employerRetirement + $employerMedicalAid
            + $leaveAccrual + $bonusAccrual,
            2
        );

        return [
            'attrs' => [
                'gross_earnings'             => round($grossEarnings, 2),
                'paye'                       => $monthlyPaye,
                'uif_employee'               => $uif['employee'],
                'other_deductions'           => round($otherDeductions, 2),
                'total_deductions'           => $totalDeductions,
                'net_pay'                    => $netPay,
                'uif_employer'               => $uif['employer'],
                'sdl'                        => $sdl,
                'total_employer_cost'        => $totalEmployerCost,
                'employer_retirement'        => $employerRetirement,
                'employer_medical_aid'       => $employerMedicalAid,
                'leave_accrual'              => $leaveAccrual,
                'bonus_accrual'              => $bonusAccrual,
                'annual_equivalent_income'   => $payeResult['annual_equivalent_income'],
                'taxable_income'             => $payeResult['taxable_income'],
                'annual_tax_before_rebates'  => $payeResult['annual_tax_before_rebates'],
                'annual_tax_after_rebates'   => $payeResult['annual_tax_after_rebates'],
                'medical_aid_credit_monthly' => $payeResult['medical_aid_credit_monthly'],
            ],
            'lines' => array_merge($earningLines, $deductionLines, $employerLines),
        ];
    }

    private function resolvePayDate(Employee $employee, PayrollRun $run): ?string
    {
        $frequency = $run->pay_frequency ?? $employee->pay_frequency ?? 'monthly';

        if ($frequency === 'monthly') {
            if (! $employee->pay_day_of_month) {
                return null;
            }

            $year  = $run->period_end->year;
            $month = $run->period_end->month;
            $day   = min((int) $employee->pay_day_of_month, \Carbon\Carbon::create($year, $month)->daysInMonth);

            return \Carbon\Carbon::create($year, $month, $day)->toDateString();
        }

        // Fortnightly (14 days) or weekly (7 days): step forward from the anchor date
        $anchor = $employee->pay_cycle_anchor;

        if (! $anchor) {
            return $run->period_end->toDateString();
        }

        $intervalDays = $frequency === 'fortnightly' ? 14 : 7;
        $periodEnd    = $run->period_end;
        $daysDiff     = $anchor->diffInDays($periodEnd, false);

        if ($daysDiff < 0) {
            return $anchor->toDateString();
        }

        $periodsElapsed = (int) floor($daysDiff / $intervalDays);
        $payDate        = $anchor->copy()->addDays($periodsElapsed * $intervalDays);

        if ($payDate->lt($periodEnd)) {
            $payDate->addDays($intervalDays);
        }

        return $payDate->toDateString();
    }

    private function recalculateRunTotals(PayrollRun $run): void
    {
        $payslips = $run->payslips()->get();

        $run->update([
            'total_gross_earnings'       => round($payslips->sum('gross_earnings'), 2),
            'total_paye'                 => round($payslips->sum('paye'), 2),
            'total_uif_employee'         => round($payslips->sum('uif_employee'), 2),
            'total_uif_employer'         => round($payslips->sum('uif_employer'), 2),
            'total_sdl'                  => round($payslips->sum('sdl'), 2),
            'total_other_deductions'     => round($payslips->sum('other_deductions'), 2),
            'total_net_pay'              => round($payslips->sum('net_pay'), 2),
            'total_employer_cost'        => round($payslips->sum('total_employer_cost'), 2),
            'total_employer_retirement'  => round($payslips->sum('employer_retirement'), 2),
            'total_employer_medical_aid' => round($payslips->sum('employer_medical_aid'), 2),
            'total_leave_accrual'        => round($payslips->sum('leave_accrual'), 2),
            'total_bonus_accrual'        => round($payslips->sum('bonus_accrual'), 2),
        ]);
    }
}
