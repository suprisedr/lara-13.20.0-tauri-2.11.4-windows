<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SupplierController extends Controller
{
    public function index(Company $company): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $suppliers = $company->suppliers()
            ->orderBy('name')
            ->get();

        return view('companies.suppliers.index', compact('company', 'suppliers'));
    }

    public function create(Company $company): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        return view('companies.suppliers.create', compact('company'));
    }

    public function show(Company $company, Supplier $supplier): View
    {
        abort_unless($company->id === $supplier->company_id && $company->user_id === auth()->id(), 403);

        $journalLines = $supplier->journalLines()
            ->with('transaction', 'account')
            ->whereHas('transaction', fn ($query) => $query->whereNotIn('status', ['draft', 'reversed']))
            ->get()
            ->sortBy(fn ($line) => $line->transaction->transaction_date)
            ->values();

        return view('companies.suppliers.show', compact('company', 'supplier', 'journalLines'));
    }

    public function statement(Company $company, Supplier $supplier, Request $request): View
    {
        abort_unless($company->id === $supplier->company_id && $company->user_id === auth()->id(), 403);

        $data = $this->buildStatement($company, $supplier, $request);

        return view('companies.suppliers.statement', $data);
    }

    public function statementPdf(Company $company, Supplier $supplier, Request $request): Response
    {
        abort_unless($company->id === $supplier->company_id && $company->user_id === auth()->id(), 403);

        $data = $this->buildStatement($company, $supplier, $request);

        $pdf = Pdf::loadView('companies.suppliers.statement-pdf', $data)->setPaper('a4', 'portrait');

        $filename = $company->slug . '-' . str($supplier->name)->slug() . '-statement.pdf';

        return $pdf->download($filename);
    }

    /**
     * Build the supplier statement of account: opening balance as at the start
     * of the period, a chronological list of purchases/bills (credits, which
     * increase the amount owed) and payments (debits, which reduce it) within
     * the period, a running balance, and the closing balance.
     */
    private function buildStatement(Company $company, Supplier $supplier, Request $request): array
    {
        $startDate = $request->input('start_date', now()->subMonths(3)->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $journalLines = $supplier->journalLines()
            ->with('transaction')
            ->whereHas('transaction', fn ($query) => $query->whereNotIn('status', ['draft', 'reversed']))
            ->get()
            ->sortBy(fn ($line) => $line->transaction->transaction_date)
            ->values();

        $openingBalance = 0.0;
        $lines = collect();

        foreach ($journalLines as $line) {
            $date = $line->transaction->transaction_date->copy()->startOfDay();
            $amount = (float) $line->amount;
            $isCredit = $line->type === 'credit';

            if ($date->lt($start)) {
                $openingBalance += $isCredit ? $amount : -$amount;
            } elseif ($date->lte($end)) {
                $lines->push([
                    'date' => $date,
                    'type' => $isCredit ? 'Purchase' : 'Payment',
                    'reference' => $line->transaction->reference,
                    'description' => $line->description ?? $line->transaction->description,
                    'debit' => $isCredit ? 0.0 : $amount,
                    'credit' => $isCredit ? $amount : 0.0,
                ]);
            }
        }

        $runningBalance = $openingBalance;
        $lines = $lines->map(function (array $line) use (&$runningBalance) {
            $runningBalance += $line['credit'] - $line['debit'];
            $line['balance'] = $runningBalance;

            return $line;
        });

        return [
            'company' => $company,
            'supplier' => $supplier,
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
            'email' => ['required', 'email', 'max:255', \Illuminate\Validation\Rule::unique('suppliers')->where('company_id', $company->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $company->suppliers()->create([
            'name' => $validated['name'],
            'contact_name' => $validated['contact_name'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()
            ->route('companies.suppliers.index', $company)
            ->with('success', 'Supplier created successfully.');
    }

    public function edit(Company $company, Supplier $supplier): View
    {
        abort_unless($company->id === $supplier->company_id && $company->user_id === auth()->id(), 403);

        return view('companies.suppliers.edit', compact('company', 'supplier'));
    }

    public function update(Company $company, Supplier $supplier, Request $request): RedirectResponse
    {
        abort_unless($company->id === $supplier->company_id && $company->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', \Illuminate\Validation\Rule::unique('suppliers')->where('company_id', $company->id)->ignore($supplier->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $supplier->update([
            'name' => $validated['name'],
            'contact_name' => $validated['contact_name'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()
            ->route('companies.suppliers.index', $company)
            ->with('success', 'Supplier updated successfully.');
    }
}
