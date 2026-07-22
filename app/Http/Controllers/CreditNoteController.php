<?php

namespace App\Http\Controllers;

use App\Jobs\PostCreditNoteJob;
use App\Models\Company;
use App\Services\RoadRunnerCreditNotePostingDispatcher;
use App\Models\CreditNote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CreditNoteController extends Controller
{
    public function index(Company $company): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $creditNotes = $company->creditNotes()
            ->with(['customer', 'invoice'])
            ->latest('credit_note_date')
            ->get();

        return view('companies.credit-notes.index', compact('company', 'creditNotes'));
    }

    public function create(Company $company, Request $request): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $invoice = null;
        if ($request->filled('invoice_id')) {
            $invoice = $company->invoices()
                ->with('items.inventoryItem', 'customer')
                ->find($request->invoice_id);
        }

        $invoices = $company->invoices()
            ->with('customer', 'items.inventoryItem')
            ->whereNotIn('status', ['draft', 'voided'])
            ->latest('invoice_date')
            ->get();

        $customers = $company->customers()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $inventoryItems = $company->inventoryItems()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $nextNumber = $this->nextCreditNoteNumber($company);

        return view('companies.credit-notes.create', compact(
            'company', 'invoice', 'invoices', 'customers', 'inventoryItems', 'nextNumber'
        ));
    }

    public function store(Company $company, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'credit_note_number'      => ['required', 'string', 'max:50'],
            'invoice_id'              => ['nullable', 'integer', 'exists:invoices,id'],
            'customer_id'             => ['nullable', 'integer', 'exists:customers,id'],
            'customer_name'           => ['required', 'string', 'max:255'],
            'customer_email'          => ['nullable', 'email', 'max:255'],
            'customer_address'        => ['nullable', 'string', 'max:500'],
            'credit_note_date'        => ['required', 'date'],
            'status'                  => ['required', 'in:draft,issued,applied,voided'],
            'reason'                  => ['nullable', 'string', 'max:255'],
            'notes'                   => ['nullable', 'string', 'max:1000'],
            'items'                   => ['required', 'array', 'min:1'],
            'items.*.description'     => ['required', 'string', 'max:255'],
            'items.*.quantity'        => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price'      => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate'        => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.inventory_item_id' => ['nullable', 'integer', 'exists:inventory_items,id'],
            'items.*.return_to_stock' => ['nullable', 'boolean'],
        ]);

        $creditNote = $company->creditNotes()->create([
            'invoice_id'        => $validated['invoice_id']  ?? null,
            'customer_id'       => $validated['customer_id'] ?? null,
            'credit_note_number' => $validated['credit_note_number'],
            'customer_name'     => $validated['customer_name'],
            'customer_email'    => $validated['customer_email']   ?? null,
            'customer_address'  => $validated['customer_address'] ?? null,
            'credit_note_date'  => $validated['credit_note_date'],
            'status'            => $validated['status'],
            'reason'            => $validated['reason'] ?? null,
            'notes'             => $validated['notes']   ?? null,
        ]);

        foreach ($validated['items'] as $line) {
            $creditNote->items()->create([
                'inventory_item_id' => $line['inventory_item_id'] ?? null,
                'description'       => $line['description'],
                'quantity'          => $line['quantity'],
                'unit_price'        => $line['unit_price'],
                'tax_rate'          => $line['tax_rate'] ?? null,
                'return_to_stock'   => !empty($line['return_to_stock']),
            ]);
        }

        if ($validated['status'] === 'issued') {
            app(RoadRunnerCreditNotePostingDispatcher::class)->dispatch($company->id, auth()->id(), $creditNote->id);
        }

        return redirect()
            ->route('companies.credit-notes.show', [$company, $creditNote])
            ->with('success', 'Credit note ' . $creditNote->credit_note_number . ' created.');
    }

    public function show(Company $company, CreditNote $creditNote): View
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($creditNote->company_id === $company->id, 404);

        $creditNote->load(['items.inventoryItem', 'customer', 'invoice', 'postingTransaction']);

        return view('companies.credit-notes.show', compact('company', 'creditNote'));
    }

    public function updateStatus(Company $company, CreditNote $creditNote, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($creditNote->company_id === $company->id, 404);

        $validated = $request->validate([
            'status' => ['required', 'in:draft,issued,applied,voided'],
        ]);

        $wasIssued = $creditNote->status !== 'issued' && $validated['status'] === 'issued';

        $creditNote->update(['status' => $validated['status']]);

        if ($wasIssued && !$creditNote->posting_transaction_id) {
            app(RoadRunnerCreditNotePostingDispatcher::class)->dispatch($company->id, auth()->id(), $creditNote->id);
            return back()->with('success', 'Status updated. AI is posting the journal entry in the background.');
        }

        return back()->with('success', 'Status updated to ' . $creditNote->fresh()->statusLabel() . '.');
    }

    public function pdf(Company $company, CreditNote $creditNote): Response
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($creditNote->company_id === $company->id, 404);

        $creditNote->load(['items.inventoryItem', 'customer', 'invoice']);

        $pdf = Pdf::loadView('companies.credit-notes.pdf', compact('company', 'creditNote'))
            ->setPaper('a4', 'portrait');

        $filename = $company->slug . '-cn-' . str($creditNote->credit_note_number)->slug() . '.pdf';

        return $pdf->download($filename);
    }

    private function nextCreditNoteNumber(Company $company): string
    {
        $today  = now()->format('ymd');
        $prefix = 'CN-' . $today;

        $last = $company->creditNotes()
            ->where('credit_note_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('credit_note_number');

        if (! $last) {
            return $prefix . '001';
        }

        $seq = (int) substr($last, strlen($prefix));

        return $prefix . str_pad((string) ($seq + 1), 3, '0', STR_PAD_LEFT);
    }
}
