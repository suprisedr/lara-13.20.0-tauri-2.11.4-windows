<?php

namespace App\Services;

use App\Models\Company;
use App\Models\PayrollRun;
use Carbon\Carbon;

/**
 * EMP501 Annual Reconciliation — aggregates company-wide payroll data
 * for a tax year and computes the reconciliation between monthly
 * EMP201 declarations and annual IRP5 certificate totals.
 */
class Emp501Service
{
    public function __construct(private readonly Irp5Service $irp5Service) {}

    /**
     * Build the full EMP501 reconciliation report for a tax year.
     */
    public function buildReconciliation(Company $company, int $taxYear): array
    {
        [$start, $end] = Irp5Service::taxYearDates($taxYear);

        // All posted payroll runs within the tax year
        $runs = $company->payrollRuns()
            ->where('status', 'posted')
            ->where('period_end', '>=', $start)
            ->where('period_end', '<=', $end)
            ->with('payslips.employee')
            ->orderBy('period_end')
            ->get();

        // Monthly breakdown (EMP201 figures — what was declared per period)
        $monthlyBreakdown = $this->buildMonthlyBreakdown($runs, $start);

        // Company-wide totals from EMP201 declarations
        $emp201Totals = [
            'gross_remuneration' => $runs->sum('total_gross_earnings'),
            'paye'               => $runs->sum('total_paye'),
            'uif_employee'       => $runs->sum('total_uif_employee'),
            'uif_employer'       => $runs->sum('total_uif_employer'),
            'sdl'                => $runs->sum('total_sdl'),
        ];

        $emp201Totals['total_uif'] = round(
            (float) $emp201Totals['uif_employee'] + (float) $emp201Totals['uif_employer'],
            2
        );

        $emp201Totals['total_liability'] = round(
            (float) $emp201Totals['paye']
            + (float) $emp201Totals['uif_employee']
            + (float) $emp201Totals['uif_employer']
            + (float) $emp201Totals['sdl'],
            2
        );

        // IRP5 certificate totals — what certificates report
        $irp5Totals = $this->buildIrp5Totals($company, $taxYear);

        // Variance analysis
        $variance = $this->calculateVariance($emp201Totals, $irp5Totals);

        // Unique employees across all runs
        $employeeIds = collect();
        foreach ($runs as $run) {
            foreach ($run->payslips as $payslip) {
                $employeeIds->push($payslip->employee_id);
            }
        }
        $totalEmployees = $employeeIds->unique()->count();

        return [
            'tax_year'          => $taxYear,
            'tax_year_label'    => ($taxYear - 1) . '/' . $taxYear,
            'tax_year_start'    => $start->format('Y-m-d'),
            'tax_year_end'      => $end->format('Y-m-d'),
            'total_employees'   => $totalEmployees,
            'total_runs'        => $runs->count(),
            'emp201_totals'     => $emp201Totals,
            'irp5_totals'       => $irp5Totals,
            'variance'          => $variance,
            'monthly_breakdown' => $monthlyBreakdown,
        ];
    }

    /**
     * Build monthly breakdown of EMP201 data.
     */
    private function buildMonthlyBreakdown($runs, Carbon $taxYearStart): array
    {
        $months = [];

        // Initialize all 12 months of the tax year
        for ($i = 0; $i < 12; $i++) {
            $monthDate = $taxYearStart->copy()->addMonths($i);
            $key = $monthDate->format('Y-m');
            $months[$key] = [
                'month'          => $monthDate->format('M Y'),
                'month_key'      => $key,
                'employees'      => 0,
                'gross'          => 0,
                'paye'           => 0,
                'uif_employee'   => 0,
                'uif_employer'   => 0,
                'sdl'            => 0,
                'total_liability' => 0,
            ];
        }

        foreach ($runs as $run) {
            $key = $run->period_end->format('Y-m');

            if (! isset($months[$key])) {
                continue;
            }

            $months[$key]['employees']      += $run->payslips->count();
            $months[$key]['gross']           += (float) $run->total_gross_earnings;
            $months[$key]['paye']            += (float) $run->total_paye;
            $months[$key]['uif_employee']    += (float) $run->total_uif_employee;
            $months[$key]['uif_employer']    += (float) $run->total_uif_employer;
            $months[$key]['sdl']             += (float) $run->total_sdl;
            $months[$key]['total_liability'] += round(
                (float) $run->total_paye
                + (float) $run->total_uif_employee
                + (float) $run->total_uif_employer
                + (float) $run->total_sdl,
                2
            );
        }

        // Round all amounts
        foreach ($months as &$month) {
            $month['gross']           = round($month['gross'], 2);
            $month['paye']            = round($month['paye'], 2);
            $month['uif_employee']    = round($month['uif_employee'], 2);
            $month['uif_employer']    = round($month['uif_employer'], 2);
            $month['sdl']             = round($month['sdl'], 2);
            $month['total_liability'] = round($month['total_liability'], 2);
        }

        return array_values($months);
    }

    /**
     * Build IRP5 certificate totals by summing all employee certificates.
     */
    private function buildIrp5Totals(Company $company, int $taxYear): array
    {
        $employees = $company->employees()->get();

        $totals = [
            'gross_remuneration' => 0,
            'paye'               => 0,
            'uif'                => 0,
            'sdl'                => 0,
            'medical_aid'        => 0,
            'retirement'         => 0,
            'certificates'       => 0,
        ];

        foreach ($employees as $employee) {
            if (! $this->irp5Service->hasDataForTaxYear($company, $employee, $taxYear)) {
                continue;
            }

            $cert = $this->irp5Service->buildCertificate($company, $employee, $taxYear);

            $totals['gross_remuneration'] += $cert['gross_remuneration'];
            $totals['paye']               += $cert['total_tax'];
            $totals['certificates']++;

            // Sum specific deduction codes
            foreach ($cert['deduction_codes'] as $deduction) {
                match ($deduction['code']) {
                    4002    => $totals['uif'] += $deduction['amount'],
                    4003    => $totals['sdl'] += $deduction['amount'],
                    4005    => $totals['medical_aid'] += $deduction['amount'],
                    4006    => $totals['retirement'] += $deduction['amount'],
                    default => null,
                };
            }
        }

        // Round
        foreach (['gross_remuneration', 'paye', 'uif', 'sdl', 'medical_aid', 'retirement'] as $key) {
            $totals[$key] = round($totals[$key], 2);
        }

        return $totals;
    }

    /**
     * Calculate variance between EMP201 totals and IRP5 certificate totals.
     */
    private function calculateVariance(array $emp201, array $irp5): array
    {
        return [
            'gross_remuneration' => [
                'emp201' => $emp201['gross_remuneration'],
                'irp5'   => $irp5['gross_remuneration'],
                'diff'   => round($emp201['gross_remuneration'] - $irp5['gross_remuneration'], 2),
            ],
            'paye' => [
                'emp201' => $emp201['paye'],
                'irp5'   => $irp5['paye'],
                'diff'   => round((float) $emp201['paye'] - (float) $irp5['paye'], 2),
            ],
            'uif' => [
                'emp201' => $emp201['uif_employee'],
                'irp5'   => $irp5['uif'],
                'diff'   => round((float) $emp201['uif_employee'] - (float) $irp5['uif'], 2),
            ],
            'sdl' => [
                'emp201' => $emp201['sdl'],
                'irp5'   => $irp5['sdl'],
                'diff'   => round((float) $emp201['sdl'] - (float) $irp5['sdl'], 2),
            ],
        ];
    }
}
