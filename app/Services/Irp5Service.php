<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Payslip;
use App\Models\PayslipLine;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * IRP5/IT3(a) tax certificate data aggregation.
 *
 * Collects payslip data for an employee within a SA tax year
 * (1 March to 28/29 February) and maps amounts to SARS source codes.
 */
class Irp5Service
{
    /**
     * Map of payroll component categories to SARS IRP5 income source codes.
     */
    private const EARNING_SOURCE_CODES = [
        'basic'             => 3601,
        'bonus'             => 3605,
        'commission'        => 3606,
        'overtime'          => 3607,
        'travel_allowance'  => 3701,
        'housing_allowance' => 3702,
        'car_allowance'     => 3702,
        'meal_allowance'    => 3702,
        'other'             => 3601,
    ];

    /**
     * Determine the tax year start and end dates.
     * SA tax year runs 1 March to 28/29 February.
     *
     * @param  int  $taxYear  The ending calendar year, e.g. 2026 for 2025/2026
     */
    public static function taxYearDates(int $taxYear): array
    {
        $start = Carbon::create($taxYear - 1, 3, 1)->startOfDay();
        $end   = Carbon::create($taxYear, 2, 1)->endOfMonth()->endOfDay();

        return [$start, $end];
    }

    /**
     * Get the current tax year (ending year).
     */
    public static function currentTaxYear(): int
    {
        $now = Carbon::now();

        // If we are in Jan or Feb, the tax year ends this calendar year
        // If we are Mar–Dec, the tax year ends next calendar year
        return $now->month <= 2 ? $now->year : $now->year + 1;
    }

    /**
     * Get available tax years for a company based on its payroll runs.
     */
    public function availableTaxYears(Company $company): array
    {
        $earliest = $company->payrollRuns()
            ->where('status', 'posted')
            ->min('period_start');

        if (! $earliest) {
            return [self::currentTaxYear()];
        }

        $startDate = Carbon::parse($earliest);
        $startTaxYear = $startDate->month <= 2 ? $startDate->year : $startDate->year + 1;
        $currentTaxYear = self::currentTaxYear();

        $years = [];
        for ($y = $startTaxYear; $y <= $currentTaxYear; $y++) {
            $years[] = $y;
        }

        return $years ?: [self::currentTaxYear()];
    }

    /**
     * Build IRP5 certificate data for a single employee in a tax year.
     *
     * @return array{
     *     employee: Employee,
     *     tax_year: int,
     *     tax_year_start: string,
     *     tax_year_end: string,
     *     certificate_type: string,
     *     periods_employed: int,
     *     income_sources: array,
     *     deduction_codes: array,
     *     gross_remuneration: float,
     *     total_deductions: float,
     *     taxable_income: float,
     *     total_tax: float,
     *     payslip_count: int,
     * }
     */
    public function buildCertificate(Company $company, Employee $employee, int $taxYear): array
    {
        [$start, $end] = self::taxYearDates($taxYear);

        // Fetch all posted payslips for this employee within the tax year
        $payslips = Payslip::whereHas('payrollRun', function ($q) use ($company, $start, $end) {
            $q->where('company_id', $company->id)
              ->where('status', 'posted')
              ->where('period_end', '>=', $start)
              ->where('period_end', '<=', $end);
        })
        ->where('employee_id', $employee->id)
        ->with(['lines.payrollComponent', 'payrollRun'])
        ->get();

        // Determine certificate type: IRP5 if PAYE deducted, IT3(a) otherwise
        $totalPaye = $payslips->sum('paye');
        $certificateType = $totalPaye > 0 ? 'IRP5' : 'IT3(a)';

        // Aggregate income by source code
        $incomeSources = $this->aggregateIncomeSources($payslips);

        // Aggregate deductions by code
        $deductionCodes = $this->aggregateDeductions($payslips, $employee);

        // Count distinct months employed
        $periodsEmployed = $payslips->map(function ($p) {
            return $p->payrollRun->period_end->format('Y-m');
        })->unique()->count();

        $grossRemuneration = collect($incomeSources)->sum('amount');
        $totalDeductions = collect($deductionCodes)->sum('amount');

        return [
            'employee'           => $employee,
            'tax_year'           => $taxYear,
            'tax_year_start'     => $start->format('Y-m-d'),
            'tax_year_end'       => $end->format('Y-m-d'),
            'tax_year_label'     => ($taxYear - 1) . '/' . $taxYear,
            'certificate_type'   => $certificateType,
            'periods_employed'   => $periodsEmployed,
            'income_sources'     => $incomeSources,
            'deduction_codes'    => $deductionCodes,
            'gross_remuneration' => round($grossRemuneration, 2),
            'total_deductions'   => round($totalDeductions, 2),
            'taxable_income'     => round($grossRemuneration - $totalDeductions + $totalPaye, 2),
            'total_tax'          => round((float) $totalPaye, 2),
            'payslip_count'      => $payslips->count(),
        ];
    }

    /**
     * Aggregate earning lines into SARS source codes.
     */
    private function aggregateIncomeSources(Collection $payslips): array
    {
        $codes = [];

        foreach ($payslips as $payslip) {
            // Basic salary / hourly wages (from payslip gross minus component earnings)
            $componentEarnings = $payslip->lines
                ->where('type', 'earning')
                ->whereNotNull('payroll_component_id')
                ->sum('amount');

            $basicAmount = (float) $payslip->gross_earnings - (float) $componentEarnings;

            // Only add positive basic amounts (component-only payslips would have 0)
            if ($basicAmount > 0) {
                $codes[3601] = ($codes[3601] ?? 0) + $basicAmount;
            }

            // Component-based earnings
            foreach ($payslip->lines->where('type', 'earning') as $line) {
                if (! $line->payroll_component_id) {
                    continue;
                }

                $category = $line->payrollComponent->category ?? 'other';
                $code = self::EARNING_SOURCE_CODES[$category] ?? 3601;

                // If it matches basic category, merge into 3601
                if ($code === 3601 && $basicAmount > 0) {
                    // Already counted in basic
                } else {
                    $codes[$code] = ($codes[$code] ?? 0) + (float) $line->amount;
                }
            }
        }

        // Build structured array
        $labels = [
            3601 => 'Gross remuneration',
            3605 => 'Annual bonus',
            3606 => 'Commission',
            3607 => 'Overtime payments',
            3701 => 'Travel allowance',
            3702 => 'Other allowances',
            3810 => 'Employer retirement contributions',
        ];

        // Add employer retirement contributions (3810)
        $employerRetirement = $payslips->sum('employer_retirement');
        if ($employerRetirement > 0) {
            $codes[3810] = (float) $employerRetirement;
        }

        $result = [];
        ksort($codes);
        foreach ($codes as $code => $amount) {
            if ($amount <= 0) {
                continue;
            }
            $result[] = [
                'code'        => $code,
                'description' => $labels[$code] ?? "Source code {$code}",
                'amount'      => round($amount, 2),
            ];
        }

        return $result;
    }

    /**
     * Aggregate deductions into SARS deduction codes.
     */
    private function aggregateDeductions(Collection $payslips, Employee $employee): array
    {
        $codes = [];

        // 4001: PAYE
        $paye = $payslips->sum('paye');
        if ($paye > 0) {
            $codes[4001] = (float) $paye;
        }

        // 4002: UIF employee
        $uif = $payslips->sum('uif_employee');
        if ($uif > 0) {
            $codes[4002] = (float) $uif;
        }

        // 4003: SDL
        $sdl = $payslips->sum('sdl');
        if ($sdl > 0) {
            $codes[4003] = (float) $sdl;
        }

        // 4005: Medical aid (employee contribution)
        $medicalAid = 0;
        foreach ($payslips as $payslip) {
            foreach ($payslip->lines as $line) {
                if ($line->description === 'Medical Aid (employee)') {
                    $medicalAid += (float) $line->amount;
                }
            }
        }
        if ($medicalAid > 0) {
            $codes[4005] = $medicalAid;
        }

        // 4006: Retirement fund (employee contribution)
        $retirement = 0;
        foreach ($payslips as $payslip) {
            foreach ($payslip->lines as $line) {
                if ($line->description === 'Retirement Fund (employee)') {
                    $retirement += (float) $line->amount;
                }
            }
        }
        if ($retirement > 0) {
            $codes[4006] = $retirement;
        }

        $labels = [
            4001 => 'PAYE (employees\' tax)',
            4002 => 'Employees\' UIF contribution',
            4003 => 'SDL',
            4005 => 'Medical aid contributions (employee)',
            4006 => 'Retirement fund contributions (employee)',
        ];

        $result = [];
        ksort($codes);
        foreach ($codes as $code => $amount) {
            if ($amount <= 0) {
                continue;
            }
            $result[] = [
                'code'        => $code,
                'description' => $labels[$code] ?? "Deduction code {$code}",
                'amount'      => round($amount, 2),
            ];
        }

        return $result;
    }

    /**
     * Check if an employee has any posted payslips in the given tax year.
     */
    public function hasDataForTaxYear(Company $company, Employee $employee, int $taxYear): bool
    {
        [$start, $end] = self::taxYearDates($taxYear);

        return Payslip::whereHas('payrollRun', function ($q) use ($company, $start, $end) {
            $q->where('company_id', $company->id)
              ->where('status', 'posted')
              ->where('period_end', '>=', $start)
              ->where('period_end', '<=', $end);
        })
        ->where('employee_id', $employee->id)
        ->exists();
    }
}
