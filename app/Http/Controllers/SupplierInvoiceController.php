<?php

namespace App\Http\Controllers;

use App\Enums\SupplierInvoiceStatus;
use App\Models\Company;
use App\Models\InventoryItem;
use App\Models\SupplierInvoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SupplierInvoiceController extends Controller
{
    public function index(Company $company, Request $request): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $query = $company->supplierInvoices()->with(['items', 'supplier']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $supplierInvoices = $query->latest('invoice_date')->paginate(25);

        return view('companies.supplier-invoices.index', compact('company', 'supplierInvoices'));
    }

    public function create(Company $company): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $inventoryItems = $company->inventoryItems()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $suppliers = $company->suppliers()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $inventoryItemsForJs = $inventoryItems->map(fn($i) => [
            'id' => $i->id,
            'name' => $i->name,
            'unit_price' => (float) $i->unit_price,
            'tax_rate' => $i->tax_rate !== null ? (float) $i->tax_rate : null,
            'description' => $i->description ?? $i->name,
            'quantity_on_hand' => (float) $i->quantity_on_hand,
            'is_service' => (bool) $i->is_service,
        ]);

        $nextNumber = $this->nextInvoiceNumber($company);

        return view('companies.supplier-invoices.create', compact('company', 'inventoryItems', 'inventoryItemsForJs', 'suppliers', 'nextNumber'));
    }

    public function store(Company $company, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'supplier_id'               => ['required', 'integer', 'exists:suppliers,id'],
            'invoice_number'            => ['required', 'string', 'max:50'],
            'invoice_date'              => ['required', 'date'],
            'due_date'                  => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'currency'                  => ['nullable', 'string', 'max:3'],
            'status'                    => ['required', 'in:draft,pending,approved'],
            'notes'                     => ['nullable', 'string', 'max:1000'],
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['nullable', 'integer', 'exists:inventory_items,id'],
            'items.*.description'       => ['required', 'string', 'max:255'],
            'items.*.quantity'          => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price'        => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate'          => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        // Verify supplier belongs to this company
        $supplier = $company->suppliers()->findOrFail($validated['supplier_id']);

        // Verify inventory items belong to this company
        $inventoryItemIds = collect($validated['items'])->pluck('inventory_item_id')->filter();
        if ($inventoryItemIds->isNotEmpty()) {
            $inventoryItems = InventoryItem::whereIn('id', $inventoryItemIds)
                ->where('company_id', $company->id)
                ->get();
            abort_if($inventoryItems->count() !== $inventoryItemIds->unique()->count(), 403);
        }

        // Calculate totals
        $subtotal = 0;
        $taxTotal = 0;
        foreach ($validated['items'] as $line) {
            $base = (float) $line['quantity'] * (float) $line['unit_price'];
            $lineTax = isset($line['tax_rate']) ? $base * ((float) $line['tax_rate'] / 100) : 0;
            $subtotal += $base;
            $taxTotal += $lineTax;
        }

        $supplierInvoice = $company->supplierInvoices()->create([
            'supplier_id'    => $supplier->id,
            'invoice_number' => $validated['invoice_number'],
            'invoice_date'   => $validated['invoice_date'],
            'due_date'       => $validated['due_date'] ?? null,
            'currency'       => $validated['currency'] ?? 'ZAR',
            'subtotal'       => round($subtotal, 2),
            'tax_total'      => round($taxTotal, 2),
            'total'          => round($subtotal + $taxTotal, 2),
            'status'         => $validated['status'],
            'notes'          => $validated['notes'] ?? null,
        ]);

        foreach ($validated['items'] as $line) {
            $supplierInvoice->items()->create([
                'inventory_item_id' => $line['inventory_item_id'] ?? null,
                'description'       => $line['description'],
                'quantity'          => $line['quantity'],
                'unit_price'        => $line['unit_price'],
                'tax_rate'          => $line['tax_rate'] ?? null,
            ]);
        }

        return redirect()
            ->route('companies.supplier-invoices.show', [$company, $supplierInvoice])
            ->with('success', 'Supplier invoice ' . $supplierInvoice->invoice_number . ' created.');
    }

    public function show(Company $company, SupplierInvoice $supplierInvoice): View
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($supplierInvoice->company_id === $company->id, 403);

        $supplierInvoice->load('items.inventoryItem', 'supplier');

        return view('companies.supplier-invoices.show', compact('company', 'supplierInvoice'));
    }

    public function edit(Company $company, SupplierInvoice $supplierInvoice): View
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($supplierInvoice->company_id === $company->id, 403);
        abort_unless(
            in_array($supplierInvoice->status, [SupplierInvoiceStatus::Draft, SupplierInvoiceStatus::Pending]),
            403,
            'Only draft or pending invoices can be edited.'
        );

        $supplierInvoice->load('items');

        $inventoryItems = $company->inventoryItems()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $suppliers = $company->suppliers()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $inventoryItemsForJs = $inventoryItems->map(fn($i) => [
            'id' => $i->id,
            'name' => $i->name,
            'unit_price' => (float) $i->unit_price,
            'tax_rate' => $i->tax_rate !== null ? (float) $i->tax_rate : null,
            'description' => $i->description ?? $i->name,
            'quantity_on_hand' => (float) $i->quantity_on_hand,
            'is_service' => (bool) $i->is_service,
        ]);

        return view('companies.supplier-invoices.edit', compact('company', 'supplierInvoice', 'inventoryItems', 'inventoryItemsForJs', 'suppliers'));
    }

    public function update(Company $company, SupplierInvoice $supplierInvoice, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($supplierInvoice->company_id === $company->id, 403);
        abort_unless(
            in_array($supplierInvoice->status, [SupplierInvoiceStatus::Draft, SupplierInvoiceStatus::Pending]),
            403,
            'Only draft or pending invoices can be edited.'
        );

        $validated = $request->validate([
            'supplier_id'               => ['required', 'integer', 'exists:suppliers,id'],
            'invoice_number'            => ['required', 'string', 'max:50'],
            'invoice_date'              => ['required', 'date'],
            'due_date'                  => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'currency'                  => ['nullable', 'string', 'max:3'],
            'status'                    => ['required', 'in:draft,pending,approved'],
            'notes'                     => ['nullable', 'string', 'max:1000'],
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['nullable', 'integer', 'exists:inventory_items,id'],
            'items.*.description'       => ['required', 'string', 'max:255'],
            'items.*.quantity'          => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price'        => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate'          => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $supplier = $company->suppliers()->findOrFail($validated['supplier_id']);

        // Verify inventory items
        $inventoryItemIds = collect($validated['items'])->pluck('inventory_item_id')->filter();
        if ($inventoryItemIds->isNotEmpty()) {
            $inventoryItems = InventoryItem::whereIn('id', $inventoryItemIds)
                ->where('company_id', $company->id)
                ->get();
            abort_if($inventoryItems->count() !== $inventoryItemIds->unique()->count(), 403);
        }

        // Calculate totals
        $subtotal = 0;
        $taxTotal = 0;
        foreach ($validated['items'] as $line) {
            $base = (float) $line['quantity'] * (float) $line['unit_price'];
            $lineTax = isset($line['tax_rate']) ? $base * ((float) $line['tax_rate'] / 100) : 0;
            $subtotal += $base;
            $taxTotal += $lineTax;
        }

        $supplierInvoice->update([
            'supplier_id'    => $supplier->id,
            'invoice_number' => $validated['invoice_number'],
            'invoice_date'   => $validated['invoice_date'],
            'due_date'       => $validated['due_date'] ?? null,
            'currency'       => $validated['currency'] ?? 'ZAR',
            'subtotal'       => round($subtotal, 2),
            'tax_total'      => round($taxTotal, 2),
            'total'          => round($subtotal + $taxTotal, 2),
            'status'         => $validated['status'],
            'notes'          => $validated['notes'] ?? null,
        ]);

        $supplierInvoice->items()->delete();

        foreach ($validated['items'] as $line) {
            $supplierInvoice->items()->create([
                'inventory_item_id' => $line['inventory_item_id'] ?? null,
                'description'       => $line['description'],
                'quantity'          => $line['quantity'],
                'unit_price'        => $line['unit_price'],
                'tax_rate'          => $line['tax_rate'] ?? null,
            ]);
        }

        return redirect()
            ->route('companies.supplier-invoices.show', [$company, $supplierInvoice])
            ->with('success', 'Supplier invoice ' . $supplierInvoice->invoice_number . ' updated.');
    }

    public function updateStatus(Company $company, SupplierInvoice $supplierInvoice, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($supplierInvoice->company_id === $company->id, 403);

        $validated = $request->validate([
            'status' => ['required', 'in:draft,pending,approved,partially_paid,paid,disputed,voided'],
        ]);

        $supplierInvoice->update(['status' => $validated['status']]);

        $newLabel = SupplierInvoiceStatus::from($validated['status'])->label();

        return redirect()
            ->route('companies.supplier-invoices.show', [$company, $supplierInvoice])
            ->with('success', 'Status updated to "' . $newLabel . '".');
    }

    public function pdf(Company $company, SupplierInvoice $supplierInvoice): Response
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($supplierInvoice->company_id === $company->id, 403);

        $supplierInvoice->load('items.inventoryItem', 'supplier');

        $pdf = Pdf::loadView('companies.supplier-invoices.pdf', compact('company', 'supplierInvoice'))
            ->setPaper('a4', 'portrait');

        $filename = $company->slug . '-supplier-inv-' . str($supplierInvoice->invoice_number)->slug() . '.pdf';

        return $pdf->download($filename);
    }

    public function destroy(Company $company, SupplierInvoice $supplierInvoice): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($supplierInvoice->company_id === $company->id, 403);
        abort_unless($supplierInvoice->status === SupplierInvoiceStatus::Draft, 403, 'Only draft invoices can be deleted.');

        $supplierInvoice->items()->delete();
        $supplierInvoice->delete();

        return redirect()
            ->route('companies.supplier-invoices.index', $company)
            ->with('success', 'Supplier invoice deleted.');
    }

    private function nextInvoiceNumber(Company $company): string
    {
        $today = now()->format('ymd');
        $prefix = 'SINV-' . $today;

        $last = $company->supplierInvoices()
            ->where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('invoice_number');

        if (! $last) {
            return $prefix . '001';
        }

        $seq = (int) substr($last, strlen($prefix));

        return $prefix . str_pad((string) ($seq + 1), 3, '0', STR_PAD_LEFT);
    }
}
