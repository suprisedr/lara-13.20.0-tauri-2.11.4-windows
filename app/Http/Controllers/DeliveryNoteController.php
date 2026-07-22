<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\DeliveryNote;
use App\Models\Invoice;
use App\Models\InventoryItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class DeliveryNoteController extends Controller
{
    public function index(Company $company): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $notes = $company->deliveryNotes()
            ->with(['customer', 'invoice'])
            ->latest('delivery_date')
            ->get();

        return view('companies.delivery-notes.index', compact('company', 'notes'));
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

        $linkedInvoiceIds = $company->deliveryNotes()
            ->whereNotNull('invoice_id')
            ->pluck('invoice_id');

        $invoices = $company->invoices()
            ->with('customer', 'items.inventoryItem')
            ->whereNotIn('status', ['draft', 'voided'])
            ->whereNotIn('id', $linkedInvoiceIds)
            ->latest('invoice_date')
            ->get();

        $customers = $company->customers()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $inventoryItems = $company->inventoryItems()
            ->where('is_active', true)
            ->where('is_service', false)
            ->orderBy('name')
            ->get();

        $nextNumber = $this->nextDeliveryNoteNumber($company);

        return view('companies.delivery-notes.create', compact(
            'company', 'invoice', 'invoices', 'customers', 'inventoryItems', 'nextNumber'
        ));
    }

    public function store(Company $company, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'delivery_note_number'   => ['required', 'string', 'max:50'],
            'invoice_id'             => ['nullable', 'integer', 'exists:invoices,id'],
            'customer_id'            => ['nullable', 'integer', 'exists:customers,id'],
            'customer_name'          => ['required', 'string', 'max:255'],
            'customer_email'         => ['nullable', 'email', 'max:255'],
            'delivery_address'       => ['nullable', 'string', 'max:500'],
            'delivery_date'          => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date'],
            'status'                 => ['required', 'in:draft,dispatched,delivered,cancelled'],
            'dispatched_by'          => ['nullable', 'string', 'max:100'],
            'vehicle_registration'   => ['nullable', 'string', 'max:50'],
            'tracking_reference'     => ['nullable', 'string', 'max:100'],
            'notes'                  => ['nullable', 'string', 'max:1000'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.description'    => ['required', 'string', 'max:255'],
            'items.*.quantity'       => ['required', 'numeric', 'min:0.01'],
            'items.*.unit'           => ['nullable', 'string', 'max:30'],
            'items.*.inventory_item_id' => ['nullable', 'integer', 'exists:inventory_items,id'],
        ]);

        $note = $company->deliveryNotes()->create([
            'invoice_id'             => $validated['invoice_id'] ?? null,
            'customer_id'            => $validated['customer_id'] ?? null,
            'delivery_note_number'   => $validated['delivery_note_number'],
            'customer_name'          => $validated['customer_name'],
            'customer_email'         => $validated['customer_email'] ?? null,
            'delivery_address'       => $validated['delivery_address'] ?? null,
            'delivery_date'          => $validated['delivery_date'],
            'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
            'status'                 => $validated['status'],
            'dispatched_by'          => $validated['dispatched_by'] ?? null,
            'vehicle_registration'   => $validated['vehicle_registration'] ?? null,
            'tracking_reference'     => $validated['tracking_reference'] ?? null,
            'notes'                  => $validated['notes'] ?? null,
        ]);

        foreach ($validated['items'] as $line) {
            $note->items()->create([
                'inventory_item_id' => $line['inventory_item_id'] ?? null,
                'description'       => $line['description'],
                'quantity'          => $line['quantity'],
                'unit'              => $line['unit'] ?? null,
            ]);
        }

        return redirect()
            ->route('companies.delivery-notes.show', [$company, $note])
            ->with('success', 'Delivery note ' . $note->delivery_note_number . ' created.');
    }

    public function show(Company $company, DeliveryNote $deliveryNote): View
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($deliveryNote->company_id === $company->id, 404);

        $deliveryNote->load(['items.inventoryItem', 'customer', 'invoice']);

        return view('companies.delivery-notes.show', compact('company', 'deliveryNote'));
    }

    public function updateStatus(Company $company, DeliveryNote $deliveryNote, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($deliveryNote->company_id === $company->id, 404);

        $validated = $request->validate([
            'status' => ['required', 'in:draft,dispatched,delivered,cancelled'],
        ]);

        $deliveryNote->update(['status' => $validated['status']]);

        return back()->with('success', 'Status updated to ' . $deliveryNote->fresh()->statusLabel() . '.');
    }

    public function pdf(Company $company, DeliveryNote $deliveryNote): Response
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($deliveryNote->company_id === $company->id, 404);

        $deliveryNote->load(['items.inventoryItem', 'customer', 'invoice']);

        $pdf = Pdf::loadView('companies.delivery-notes.pdf', compact('company', 'deliveryNote'))
            ->setPaper('a4', 'portrait');

        $filename = $company->slug . '-dn-' . str($deliveryNote->delivery_note_number)->slug() . '.pdf';

        return $pdf->download($filename);
    }

    private function nextDeliveryNoteNumber(Company $company): string
    {
        $today  = now()->format('ymd');
        $prefix = 'DN-' . $today;

        $last = $company->deliveryNotes()
            ->where('delivery_note_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('delivery_note_number');

        if (! $last) {
            return $prefix . '001';
        }

        $seq = (int) substr($last, strlen($prefix));

        return $prefix . str_pad((string) ($seq + 1), 3, '0', STR_PAD_LEFT);
    }
}
