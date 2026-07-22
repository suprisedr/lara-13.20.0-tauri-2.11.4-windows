<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollComponent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Company $company): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $employees = $company->employees()->orderBy('last_name')->orderBy('first_name')->get();

        return view('companies.payroll.employees.index', compact('company', 'employees'));
    }

    public function create(Company $company): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $nextNumber = Employee::generateEmployeeNumber($company->id);
        $components = $company->payrollComponents()->where('is_active', true)->orderBy('sort_order')->get();

        return view('companies.payroll.employees.create', compact('company', 'nextNumber', 'components'));
    }

    public function store(Company $company, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'employee_number'                  => ['required', 'string', 'max:20'],
            'first_name'                       => ['required', 'string', 'max:100'],
            'last_name'                        => ['required', 'string', 'max:100'],
            'id_number'                        => ['nullable', 'string', 'max:13'],
            'passport_number'                  => ['nullable', 'string', 'max:30'],
            'tax_reference_number'             => ['nullable', 'string', 'max:20'],
            'date_of_birth'                    => ['nullable', 'date'],
            'gender'                           => ['nullable', 'in:male,female,other,prefer_not_to_say'],
            'employment_type'                  => ['required', 'in:permanent,contract,part_time,casual'],
            'job_title'                        => ['nullable', 'string', 'max:100'],
            'department'                       => ['nullable', 'string', 'max:100'],
            'start_date'                       => ['required', 'date'],
            'end_date'                         => ['nullable', 'date', 'after_or_equal:start_date'],
            'pay_frequency'                    => ['required', 'in:monthly,fortnightly,weekly'],
            'pay_day_of_month'                 => ['nullable', 'integer', 'min:1', 'max:31'],
            'pay_cycle_anchor'                 => ['nullable', 'date'],
            'pay_type'                         => ['required', 'in:salary,hourly'],
            'basic_salary'                     => ['nullable', 'numeric', 'min:0'],
            'hourly_rate'                      => ['nullable', 'numeric', 'min:0'],
            'medical_aid_members'               => ['nullable', 'integer', 'min:1', 'max:20'],
            'medical_aid_employee_contribution' => ['nullable', 'numeric', 'min:0'],
            'medical_aid_employer_contribution' => ['nullable', 'numeric', 'min:0'],
            'retirement_fund_contribution'      => ['nullable', 'numeric', 'min:0'],
            'retirement_fund_type'              => ['nullable', 'in:defined_contribution,defined_benefit'],
            'employer_retirement_contribution'  => ['nullable', 'numeric', 'min:0'],
            'leave_days_per_year'               => ['nullable', 'numeric', 'min:0', 'max:365'],
            'leave_balance_days'                => ['nullable', 'numeric', 'min:0'],
            'bonus_months'                      => ['nullable', 'numeric', 'min:0', 'max:12'],
            'bank_name'                         => ['nullable', 'string', 'max:100'],
            'bank_account_number'               => ['nullable', 'string', 'max:30'],
            'bank_account_type'                 => ['nullable', 'in:current,savings'],
            'bank_branch_code'                  => ['nullable', 'string', 'max:10'],
            'address_line_1'                    => ['nullable', 'string', 'max:150'],
            'address_line_2'                    => ['nullable', 'string', 'max:150'],
            'city'                              => ['nullable', 'string', 'max:100'],
            'province'                          => ['nullable', 'string', 'max:100'],
            'postal_code'                       => ['nullable', 'string', 'max:10'],
            // Components
            'components'                        => ['nullable', 'array'],
            'components.*.id'                   => ['required', 'integer'],
            'components.*.amount'               => ['required', 'numeric', 'min:0'],
        ]);

        $employee = $company->employees()->create([
            'employee_number'                   => $validated['employee_number'],
            'first_name'                        => $validated['first_name'],
            'last_name'                         => $validated['last_name'],
            'id_number'                         => $validated['id_number'] ?? null,
            'passport_number'                   => $validated['passport_number'] ?? null,
            'tax_reference_number'              => $validated['tax_reference_number'] ?? null,
            'date_of_birth'                     => $validated['date_of_birth'] ?? null,
            'gender'                            => $validated['gender'] ?? null,
            'employment_type'                   => $validated['employment_type'],
            'job_title'                         => $validated['job_title'] ?? null,
            'department'                        => $validated['department'] ?? null,
            'start_date'                        => $validated['start_date'],
            'end_date'                          => $validated['end_date'] ?? null,
            'pay_frequency'                     => $validated['pay_frequency'],
            'pay_day_of_month'                  => $validated['pay_day_of_month'] ?? null,
            'pay_cycle_anchor'                  => $validated['pay_cycle_anchor'] ?? null,
            'pay_type'                          => $validated['pay_type'],
            'basic_salary'                      => $validated['basic_salary'] ?? 0,
            'hourly_rate'                       => $validated['hourly_rate'] ?? 0,
            'medical_aid_members'               => $validated['medical_aid_members'] ?? 1,
            'medical_aid_employee_contribution' => $validated['medical_aid_employee_contribution'] ?? 0,
            'medical_aid_employer_contribution' => $validated['medical_aid_employer_contribution'] ?? 0,
            'retirement_fund_contribution'      => $validated['retirement_fund_contribution'] ?? 0,
            'retirement_fund_type'              => $validated['retirement_fund_type'] ?? 'defined_contribution',
            'employer_retirement_contribution'  => $validated['employer_retirement_contribution'] ?? 0,
            'leave_days_per_year'               => $validated['leave_days_per_year'] ?? 15,
            'leave_balance_days'                => $validated['leave_balance_days'] ?? 0,
            'bonus_months'                      => $validated['bonus_months'] ?? 0,
            'bank_name'                         => $validated['bank_name'] ?? null,
            'bank_account_number'               => $validated['bank_account_number'] ?? null,
            'bank_account_type'                 => $validated['bank_account_type'] ?? null,
            'bank_branch_code'                  => $validated['bank_branch_code'] ?? null,
            'address_line_1'                    => $validated['address_line_1'] ?? null,
            'address_line_2'                    => $validated['address_line_2'] ?? null,
            'city'                              => $validated['city'] ?? null,
            'province'                          => $validated['province'] ?? null,
            'postal_code'                       => $validated['postal_code'] ?? null,
        ]);

        // Attach payroll components
        if (! empty($validated['components'])) {
            foreach ($validated['components'] as $comp) {
                $component = $company->payrollComponents()->find($comp['id']);
                if ($component) {
                    $employee->payrollComponents()->attach($component->id, [
                        'amount'    => $comp['amount'],
                        'is_active' => true,
                    ]);
                }
            }
        }

        return redirect()
            ->route('companies.payroll.employees.index', $company)
            ->with('success', 'Employee created successfully.');
    }

    public function show(Company $company, Employee $employee): View
    {
        abort_unless($company->id === $employee->company_id && $company->user_id === auth()->id(), 403);

        $payslips = $employee->payslips()
            ->with('payrollRun')
            ->get()
            ->sortByDesc(fn ($payslip) => $payslip->payrollRun->period_end)
            ->values();

        return view('companies.payroll.employees.show', compact('company', 'employee', 'payslips'));
    }

    public function edit(Company $company, Employee $employee): View
    {
        abort_unless($company->id === $employee->company_id && $company->user_id === auth()->id(), 403);

        $components         = $company->payrollComponents()->where('is_active', true)->orderBy('sort_order')->get();
        $assignedComponents = $employee->payrollComponents()->withPivot(['amount', 'is_active'])->get();

        return view('companies.payroll.employees.edit', compact('company', 'employee', 'components', 'assignedComponents'));
    }

    public function update(Company $company, Employee $employee, Request $request): RedirectResponse
    {
        abort_unless($company->id === $employee->company_id && $company->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'first_name'                       => ['required', 'string', 'max:100'],
            'last_name'                        => ['required', 'string', 'max:100'],
            'id_number'                        => ['nullable', 'string', 'max:13'],
            'passport_number'                  => ['nullable', 'string', 'max:30'],
            'tax_reference_number'             => ['nullable', 'string', 'max:20'],
            'date_of_birth'                    => ['nullable', 'date'],
            'gender'                           => ['nullable', 'in:male,female,other,prefer_not_to_say'],
            'employment_type'                  => ['required', 'in:permanent,contract,part_time,casual'],
            'job_title'                        => ['nullable', 'string', 'max:100'],
            'department'                       => ['nullable', 'string', 'max:100'],
            'start_date'                       => ['required', 'date'],
            'end_date'                         => ['nullable', 'date', 'after_or_equal:start_date'],
            'pay_frequency'                    => ['required', 'in:monthly,fortnightly,weekly'],
            'pay_day_of_month'                 => ['nullable', 'integer', 'min:1', 'max:31'],
            'pay_cycle_anchor'                 => ['nullable', 'date'],
            'pay_type'                         => ['required', 'in:salary,hourly'],
            'basic_salary'                     => ['nullable', 'numeric', 'min:0'],
            'hourly_rate'                      => ['nullable', 'numeric', 'min:0'],
            'medical_aid_members'               => ['nullable', 'integer', 'min:1', 'max:20'],
            'medical_aid_employee_contribution' => ['nullable', 'numeric', 'min:0'],
            'medical_aid_employer_contribution' => ['nullable', 'numeric', 'min:0'],
            'retirement_fund_contribution'      => ['nullable', 'numeric', 'min:0'],
            'retirement_fund_type'              => ['nullable', 'in:defined_contribution,defined_benefit'],
            'employer_retirement_contribution'  => ['nullable', 'numeric', 'min:0'],
            'leave_days_per_year'               => ['nullable', 'numeric', 'min:0', 'max:365'],
            'leave_balance_days'                => ['nullable', 'numeric', 'min:0'],
            'bonus_months'                      => ['nullable', 'numeric', 'min:0', 'max:12'],
            'bank_name'                         => ['nullable', 'string', 'max:100'],
            'bank_account_number'               => ['nullable', 'string', 'max:30'],
            'bank_account_type'                 => ['nullable', 'in:current,savings'],
            'bank_branch_code'                  => ['nullable', 'string', 'max:10'],
            'address_line_1'                    => ['nullable', 'string', 'max:150'],
            'address_line_2'                    => ['nullable', 'string', 'max:150'],
            'city'                              => ['nullable', 'string', 'max:100'],
            'province'                          => ['nullable', 'string', 'max:100'],
            'postal_code'                       => ['nullable', 'string', 'max:10'],
            'is_active'                         => ['sometimes', 'boolean'],
            'components'                        => ['nullable', 'array'],
            'components.*.id'                   => ['required', 'integer'],
            'components.*.amount'               => ['required', 'numeric', 'min:0'],
        ]);

        $employee->update([
            'first_name'                        => $validated['first_name'],
            'last_name'                         => $validated['last_name'],
            'id_number'                         => $validated['id_number'] ?? null,
            'passport_number'                   => $validated['passport_number'] ?? null,
            'tax_reference_number'              => $validated['tax_reference_number'] ?? null,
            'date_of_birth'                     => $validated['date_of_birth'] ?? null,
            'gender'                            => $validated['gender'] ?? null,
            'employment_type'                   => $validated['employment_type'],
            'job_title'                         => $validated['job_title'] ?? null,
            'department'                        => $validated['department'] ?? null,
            'start_date'                        => $validated['start_date'],
            'end_date'                          => $validated['end_date'] ?? null,
            'pay_frequency'                     => $validated['pay_frequency'],
            'pay_day_of_month'                  => $validated['pay_day_of_month'] ?? null,
            'pay_cycle_anchor'                  => $validated['pay_cycle_anchor'] ?? null,
            'pay_type'                          => $validated['pay_type'],
            'basic_salary'                      => $validated['basic_salary'] ?? 0,
            'hourly_rate'                       => $validated['hourly_rate'] ?? 0,
            'medical_aid_members'               => $validated['medical_aid_members'] ?? 1,
            'medical_aid_employee_contribution' => $validated['medical_aid_employee_contribution'] ?? 0,
            'medical_aid_employer_contribution' => $validated['medical_aid_employer_contribution'] ?? 0,
            'retirement_fund_contribution'      => $validated['retirement_fund_contribution'] ?? 0,
            'retirement_fund_type'              => $validated['retirement_fund_type'] ?? 'defined_contribution',
            'employer_retirement_contribution'  => $validated['employer_retirement_contribution'] ?? 0,
            'leave_days_per_year'               => $validated['leave_days_per_year'] ?? 15,
            'leave_balance_days'                => $validated['leave_balance_days'] ?? 0,
            'bonus_months'                      => $validated['bonus_months'] ?? 0,
            'bank_name'                         => $validated['bank_name'] ?? null,
            'bank_account_number'               => $validated['bank_account_number'] ?? null,
            'bank_account_type'                 => $validated['bank_account_type'] ?? null,
            'bank_branch_code'                  => $validated['bank_branch_code'] ?? null,
            'is_active'                         => $validated['is_active'] ?? true,
        ]);

        // Sync payroll components
        $employee->payrollComponents()->detach();
        if (! empty($validated['components'])) {
            foreach ($validated['components'] as $comp) {
                $component = $company->payrollComponents()->find($comp['id']);
                if ($component) {
                    $employee->payrollComponents()->attach($component->id, [
                        'amount'    => $comp['amount'],
                        'is_active' => true,
                    ]);
                }
            }
        }

        return redirect()
            ->route('companies.payroll.employees.index', $company)
            ->with('success', 'Employee updated successfully.');
    }

    public function destroy(Company $company, Employee $employee): RedirectResponse
    {
        abort_unless($company->id === $employee->company_id && $company->user_id === auth()->id(), 403);
        abort_if($employee->payslips()->exists(), 422, 'Cannot delete an employee with payslip history.');

        $employee->delete();

        return redirect()
            ->route('companies.payroll.employees.index', $company)
            ->with('success', 'Employee removed.');
    }
}
