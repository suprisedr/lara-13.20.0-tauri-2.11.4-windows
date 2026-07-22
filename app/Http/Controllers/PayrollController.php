<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PayrollComponent;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Services\PayrollService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function __construct(private readonly PayrollService $payroll) {}

    // ── Payroll Components ─────────────────────────────────────────────────

    public function components(Company $company): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $components = $company->payrollComponents()->orderBy('sort_order')->orderBy('type')->get();

        return view('companies.payroll.components', compact('company', 'components'));
    }

    public function storeComponent(Company $company, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:100'],
            'type'            => ['required', 'in:earning,deduction,employer_contribution'],
            'category'        => ['required', 'in:basic,overtime,commission,bonus,travel_allowance,housing_allowance,car_allowance,meal_allowance,medical_aid,pension_fund,retirement_annuity,loan_repayment,other'],
            'is_taxable'      => ['sometimes', 'boolean'],
            'is_pensionable'  => ['sometimes', 'boolean'],
        ]);

        $company->payrollComponents()->create([
            'name'           => $validated['name'],
            'type'           => $validated['type'],
            'category'       => $validated['category'],
            'is_taxable'     => $request->boolean('is_taxable'),
            'is_pensionable' => $request->boolean('is_pensionable'),
            'is_active'      => true,
            'sort_order'     => $company->payrollComponents()->count(),
        ]);

        return back()->with('success', 'Payroll component created.');
    }

    public function updateComponent(Company $company, PayrollComponent $component, Request $request): RedirectResponse
    {
        abort_unless($company->id === $component->company_id && $company->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:100'],
            'type'            => ['required', 'in:earning,deduction,employer_contribution'],
            'category'        => ['required', 'in:basic,overtime,commission,bonus,travel_allowance,housing_allowance,car_allowance,meal_allowance,medical_aid,pension_fund,retirement_annuity,loan_repayment,other'],
            'is_taxable'      => ['sometimes', 'boolean'],
            'is_pensionable'  => ['sometimes', 'boolean'],
            'is_active'       => ['sometimes', 'boolean'],
        ]);

        $component->update($validated);

        return back()->with('success', 'Component updated.');
    }

    public function destroyComponent(Company $company, PayrollComponent $component): RedirectResponse
    {
        abort_unless($company->id === $component->company_id && $company->user_id === auth()->id(), 403);

        $component->delete();

        return back()->with('success', 'Component deleted.');
    }

    // ── Payroll Runs ──────────────────────────────────────────────────────

    public function runs(Company $company): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $runs = $company->payrollRuns()->latest('period_end')->paginate(20);

        return view('companies.payroll.runs.index', compact('company', 'runs'));
    }

    public function createRun(Company $company): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $active = $company->employees()->where('is_active', true);

        $salaryCount = (clone $active)->where('pay_type', 'salary')->count();
        $hourlyCount = (clone $active)->where('pay_type', 'hourly')->count();

        $frequencyCounts = (clone $active)
            ->selectRaw("pay_frequency, pay_type, count(*) as total")
            ->groupBy('pay_frequency', 'pay_type')
            ->get()
            ->groupBy('pay_type')
            ->map(fn ($group) => $group->pluck('total', 'pay_frequency'));

        return view('companies.payroll.runs.create', compact('company', 'salaryCount', 'hourlyCount', 'frequencyCounts'));
    }

    public function storeRun(Company $company, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'period_start'  => ['required', 'date'],
            'period_end'    => ['required', 'date', 'after_or_equal:period_start'],
            'employee_type' => ['required', 'in:salary,hourly'],
            'pay_frequency' => ['required', 'in:monthly,fortnightly,weekly'],
            'notes'         => ['nullable', 'string', 'max:500'],
        ]);

        $run = $company->payrollRuns()->create($validated);

        $this->payroll->calculate($run);

        return redirect()
            ->route('companies.payroll.runs.show', [$company, $run])
            ->with('success', 'Payroll run created and calculated.');
    }

    public function showRun(Company $company, PayrollRun $run): View
    {
        abort_unless($company->id === $run->company_id && $company->user_id === auth()->id(), 403);

        $payslips     = $run->payslips()->with(['employee', 'lines'])->get();
        $accounts     = $company->chartOfAccounts()->orderBy('account_code')->get();
        $journalLines = $this->payroll->getJournalLines($run);

        return view('companies.payroll.runs.show', compact('company', 'run', 'payslips', 'accounts', 'journalLines'));
    }

    public function recalculateRun(Company $company, PayrollRun $run): RedirectResponse
    {
        abort_unless($company->id === $run->company_id && $company->user_id === auth()->id(), 403);
        abort_if($run->isPosted(), 422, 'Cannot recalculate a posted run.');

        $this->payroll->calculate($run);

        return back()->with('success', 'Payroll recalculated.');
    }

    public function postRun(Company $company, PayrollRun $run): RedirectResponse
    {
        abort_unless($company->id === $run->company_id && $company->user_id === auth()->id(), 403);
        abort_if($run->isPosted(), 422, 'This run has already been finalised.');
        abort_if($run->payslips()->count() === 0, 422, 'No payslips to finalise.');

        $run->update(['status' => 'posted']);

        return back()->with('success', 'Payroll run finalised. Post the journal entries shown below to your accounting system.');
    }

    public function destroyRun(Company $company, PayrollRun $run): RedirectResponse
    {
        abort_unless($company->id === $run->company_id && $company->user_id === auth()->id(), 403);
        abort_if($run->isPosted(), 422, 'Cannot delete a posted payroll run.');

        $run->delete();

        return redirect()
            ->route('companies.payroll.runs.index', $company)
            ->with('success', 'Payroll run deleted.');
    }

    // ── Payslip PDF ───────────────────────────────────────────────────────

    public function payslipPdf(Company $company, PayrollRun $run, Payslip $payslip): \Illuminate\Http\Response
    {
        abort_unless(
            $company->id === $run->company_id
            && $run->id === $payslip->payroll_run_id
            && $company->user_id === auth()->id(),
            403,
        );

        $payslip->load(['employee', 'lines', 'payrollRun']);

        $pdf = Pdf::loadView('pdf.payslip', compact('company', 'payslip'))
            ->setPaper('a4', 'portrait');

        $filename = 'payslip-' . str($payslip->employee->full_name)->slug() . '-' . $run->period_end->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    // ── EMP201 Report ─────────────────────────────────────────────────────

    public function emp201(Company $company, PayrollRun $run): View
    {
        abort_unless($company->id === $run->company_id && $company->user_id === auth()->id(), 403);

        $payslips = $run->payslips()->with('employee')->get();

        return view('companies.payroll.emp201', compact('company', 'run', 'payslips'));
    }

    /**
     * Bulk-update hours worked for all hourly payslips in a run, then recalculate.
     */
    public function updateHoursWorksheet(Company $company, PayrollRun $run, Request $request): RedirectResponse
    {
        abort_unless($company->id === $run->company_id && $company->user_id === auth()->id(), 403);
        abort_if($run->isPosted(), 422, 'Cannot edit a posted payroll run.');
        abort_unless($run->employee_type === 'hourly', 422, 'Hours worksheet is only for hourly runs.');

        $validated = $request->validate([
            'hours'          => ['required', 'array'],
            'hours.*.id'     => ['required', 'integer'],
            'hours.*.worked' => ['required', 'numeric', 'min:0', 'max:744'],
        ]);

        foreach ($validated['hours'] as $row) {
            $payslip = $run->payslips()->find($row['id']);
            if (! $payslip) {
                continue;
            }
            $this->payroll->adjustPayslip($payslip, (float) $row['worked'], null);
        }

        return back()->with('success', 'Hours worksheet saved and payslips recalculated.');
    }

    public function adjustPayslip(Company $company, PayrollRun $run, Payslip $payslip, Request $request): RedirectResponse
    {
        abort_unless($company->id === $run->company_id && $company->user_id === auth()->id(), 403);
        abort_unless($payslip->payroll_run_id === $run->id, 404);

        $validated = $request->validate([
            'hours_worked'            => ['nullable', 'numeric', 'min:0'],
            'override_gross_earnings' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->payroll->adjustPayslip(
            payslip: $payslip,
            hoursWorked: isset($validated['hours_worked']) ? (float) $validated['hours_worked'] : null,
            overrideGross: isset($validated['override_gross_earnings']) ? (float) $validated['override_gross_earnings'] : null,
        );

        return redirect()
            ->route('companies.payroll.runs.show', [$company, $run])
            ->with('success', 'Payslip adjusted successfully.');
    }
}
