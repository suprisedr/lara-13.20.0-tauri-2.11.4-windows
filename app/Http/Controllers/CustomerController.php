<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Customer;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CustomerController extends Controller
{
    public function index(Company $company): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $customers = $company->customers()
            ->orderBy('name')
            ->get();

        return view('companies.customers.index', compact('company', 'customers'));
    }

    public function create(Company $company): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        return view('companies.customers.create', compact('company'));
    }

    public function show(Company $company, Customer $customer): View
    {
        abort_unless($company->id === $customer->company_id && $company->user_id === auth()->id(), 403);

        $invoices = $customer->invoices()->with(['items', 'payments'])->latest('invoice_date')->get();
        $quotations = $customer->quotations()->with('items')->latest('quotation_date')->get();

        return view('companies.customers.show', compact('company', 'customer', 'invoices', 'quotations'));
    }

    public function statement(Company $company, Customer $customer, Request $request): View
    {
        abort_unless($company->id === $customer->company_id && $company->user_id === auth()->id(), 403);

        $data = $this->buildStatement($company, $customer, $request);

        return view('companies.customers.statement', $data);
    }

    public function statementPdf(Company $company, Customer $customer, Request $request): Response
    {
        abort_unless($company->id === $customer->company_id && $company->user_id === auth()->id(), 403);

        $data = $this->buildStatement($company, $customer, $request);

        $pdf = Pdf::loadView('companies.customers.statement-pdf', $data)->setPaper('a4', 'portrait');

        $filename = $company->slug . '-' . str($customer->name)->slug() . '-statement.pdf';

        return $pdf->download($filename);
    }

    /**
     * Build the customer statement of account: opening balance as at the start
     * of the period, a chronological list of invoices (debits) and payments
     * (credits) within the period, a running balance, and the closing balance.
     */
    private function buildStatement(Company $company, Customer $customer, Request $request): array
    {
        $startDate = $request->input('start_date', now()->subMonths(3)->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $invoices = $customer->invoices()
            ->with('items', 'payments')
            ->whereNotIn('status', ['draft', 'voided'])
            ->get();

        $openingBalance = 0.0;
        $lines = collect();

        foreach ($invoices as $invoice) {
            $invoiceDate = $invoice->invoice_date->copy()->startOfDay();
            $total = $invoice->total();

            if ($invoiceDate->lt($start)) {
                $openingBalance += $total;
            } elseif ($invoiceDate->lte($end)) {
                $lines->push([
                    'date' => $invoiceDate,
                    'type' => 'Invoice',
                    'reference' => $invoice->invoice_number,
                    'description' => 'Invoice ' . $invoice->invoice_number,
                    'debit' => $total,
                    'credit' => 0.0,
                    'link' => route('companies.invoices.show', [$company, $invoice]),
                ]);
            }

            foreach ($invoice->payments as $payment) {
                $paymentDate = $payment->payment_date->copy()->startOfDay();
                $amount = (float) $payment->amount;

                if ($paymentDate->lt($start)) {
                    $openingBalance -= $amount;
                } elseif ($paymentDate->lte($end)) {
                    $lines->push([
                        'date' => $paymentDate,
                        'type' => 'Payment',
                        'reference' => $invoice->invoice_number,
                        'description' => 'Payment received — ' . $invoice->invoice_number . ($payment->method ? ' (' . ucfirst($payment->method) . ')' : ''),
                        'debit' => 0.0,
                        'credit' => $amount,
                        'link' => route('companies.invoices.show', [$company, $invoice]),
                    ]);
                }
            }
        }

        $lines = $lines->sortBy('date')->values();

        $runningBalance = $openingBalance;
        $lines = $lines->map(function (array $line) use (&$runningBalance) {
            $runningBalance += $line['debit'] - $line['credit'];
            $line['balance'] = $runningBalance;

            return $line;
        });

        return [
            'company' => $company,
            'customer' => $customer,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'openingBalance' => round($openingBalance, 2),
            'closingBalance' => round($runningBalance, 2),
            'lines' => $lines,
        ];
    }

    public function store(Company $company, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', \Illuminate\Validation\Rule::unique('customers')->where('company_id', $company->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $company->customers()->create([
            'name' => $validated['name'],
            'contact_name' => $validated['contact_name'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()
            ->route('companies.customers.index', $company)
            ->with('success', 'Customer created successfully.');
    }

    public function edit(Company $company, Customer $customer): View
    {
        abort_unless($company->id === $customer->company_id && $company->user_id === auth()->id(), 403);

        return view('companies.customers.edit', compact('company', 'customer'));
    }

    public function update(Company $company, Customer $customer, Request $request): RedirectResponse
    {
        abort_unless($company->id === $customer->company_id && $company->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', \Illuminate\Validation\Rule::unique('customers')->where('company_id', $company->id)->ignore($customer->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $customer->update([
            'name' => $validated['name'],
            'contact_name' => $validated['contact_name'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()
            ->route('companies.customers.index', $company)
            ->with('success', 'Customer updated successfully.');
    }
}
