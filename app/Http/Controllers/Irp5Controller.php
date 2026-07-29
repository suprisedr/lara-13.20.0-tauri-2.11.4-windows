<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;
use App\Services\Emp501Service;
use App\Services\Irp5Service;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;

class Irp5Controller extends Controller
{
    public function __construct(
        private readonly Irp5Service $irp5Service,
        private readonly Emp501Service $emp501Service,
    ) {}

    // ── IRP5/IT3(a) ──────────────────────────────────────────────────────

    /**
     * List employees with IRP5 generation status for a selected tax year.
     */
    public function index(Company $company, Request $request): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $taxYears = $this->irp5Service->availableTaxYears($company);
        $selectedYear = (int) $request->get('tax_year', Irp5Service::currentTaxYear());

        $employees = $company->employees()->orderBy('last_name')->orderBy('first_name')->get();

        // Check which employees have data
        $employeeData = $employees->map(function ($employee) use ($company, $selectedYear) {
            return [
                'employee' => $employee,
                'has_data' => $this->irp5Service->hasDataForTaxYear($company, $employee, $selectedYear),
            ];
        });

        return view('companies.payroll.irp5.index', compact(
            'company', 'taxYears', 'selectedYear', 'employeeData'
        ));
    }

    /**
     * Show IRP5 certificate preview for a specific employee and tax year.
     */
    public function show(Company $company, Employee $employee, Request $request): View
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($employee->company_id === $company->id, 404);

        $taxYear = (int) $request->get('tax_year', Irp5Service::currentTaxYear());
        $certificate = $this->irp5Service->buildCertificate($company, $employee, $taxYear);

        return view('companies.payroll.irp5.show', compact('company', 'certificate'));
    }

    /**
     * Generate IRP5 PDF for a single employee.
     */
    public function pdf(Company $company, Employee $employee, Request $request): \Illuminate\Http\Response
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($employee->company_id === $company->id, 404);

        $taxYear = (int) $request->get('tax_year', Irp5Service::currentTaxYear());
        $certificate = $this->irp5Service->buildCertificate($company, $employee, $taxYear);

        $pdf = Pdf::loadView('companies.payroll.irp5.pdf', compact('company', 'certificate'))
            ->setPaper('a4', 'landscape');

        $filename = strtolower($certificate['certificate_type'])
            . '-' . str($employee->full_name)->slug()
            . '-' . $certificate['tax_year_label']
            . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Generate all IRP5 certificates as a combined PDF.
     */
    public function bulkPdf(Company $company, Request $request): \Illuminate\Http\Response
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $taxYear = (int) $request->get('tax_year', Irp5Service::currentTaxYear());
        $employees = $company->employees()->orderBy('last_name')->orderBy('first_name')->get();

        $certificates = [];
        foreach ($employees as $employee) {
            if ($this->irp5Service->hasDataForTaxYear($company, $employee, $taxYear)) {
                $certificates[] = $this->irp5Service->buildCertificate($company, $employee, $taxYear);
            }
        }

        abort_if(empty($certificates), 404, 'No IRP5 certificates to generate for this tax year.');

        $pdf = Pdf::loadView('companies.payroll.irp5.pdf-bulk', compact('company', 'certificates'))
            ->setPaper('a4', 'landscape');

        $filename = 'irp5-certificates-' . ($taxYear - 1) . '-' . $taxYear . '.pdf';

        return $pdf->download($filename);
    }

    // ── EMP501 ────────────────────────────────────────────────────────────

    /**
     * Show EMP501 reconciliation report.
     */
    public function emp501(Company $company, Request $request): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $taxYears = $this->irp5Service->availableTaxYears($company);
        $selectedYear = (int) $request->get('tax_year', Irp5Service::currentTaxYear());

        $report = $this->emp501Service->buildReconciliation($company, $selectedYear);

        return view('companies.payroll.emp501.index', compact('company', 'taxYears', 'selectedYear', 'report'));
    }

    /**
     * Generate EMP501 reconciliation PDF.
     */
    public function emp501Pdf(Company $company, Request $request): \Illuminate\Http\Response
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $taxYear = (int) $request->get('tax_year', Irp5Service::currentTaxYear());
        $report = $this->emp501Service->buildReconciliation($company, $taxYear);

        $pdf = Pdf::loadView('companies.payroll.emp501.pdf', compact('company', 'report'))
            ->setPaper('a4', 'landscape');

        $filename = 'emp501-reconciliation-' . ($taxYear - 1) . '-' . $taxYear . '.pdf';

        return $pdf->download($filename);
    }
}
