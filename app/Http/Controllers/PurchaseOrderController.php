<?php

namespace App\Http\Controllers;

use App\Enums\PurchaseOrderStatus;
use App\Models\Company;
use App\Models\InventoryItem;
use App\Models\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PurchaseOrderController extends Controller
{
    public function index(Company $company, Request $request): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $query = $company->purchaseOrders()->with(['items', 'supplier']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $purchaseOrders = $query->latest('order_date')->paginate(25);

        return view('companies.purchase-orders.index', compact('company', 'purchaseOrders'));
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

        $nextNumber = $this->nextPoNumber($company);

        return view('companies.purchase-orders.create', compact('company', 'inventoryItems', 'inventoryItemsForJs', 'suppliers', 'nextNumber'));
    }

    public function store(Company $company, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'supplier_id'               => ['required', 'integer', 'exists:suppliers,id'],
            'po_number'                 => ['required', 'string', 'max:50'],
            'order_date'                => ['required', 'date'],
            'expected_delivery_date'    => ['nullable', 'date', 'after_or_equal:order_date'],
            'currency'                  => ['nullable', 'string', 'max:3'],
            'status'                    => ['required', 'in:draft,sent'],
            'notes'                     => ['nullable', 'string', 'max:1000'],
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['nullable', 'integer', 'exists:inventory_items,id'],
            'items.*.description'       => ['required', 'string', 'max:255'],
            'items.*.quantity'          => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price'        => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate'          => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

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

        $purchaseOrder = $company->purchaseOrders()->create([
            'supplier_id'            => $supplier->id,
            'po_number'              => $validated['po_number'],
            'order_date'             => $validated['order_date'],
            'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
            'currency'               => $validated['currency'] ?? 'ZAR',
            'subtotal'               => round($subtotal, 2),
            'tax_total'              => round($taxTotal, 2),
            'total'                  => round($subtotal + $taxTotal, 2),
            'status'                 => $validated['status'],
            'notes'                  => $validated['notes'] ?? null,
        ]);

        foreach ($validated['items'] as $line) {
            $purchaseOrder->items()->create([
                'inventory_item_id' => $line['inventory_item_id'] ?? null,
                'description'       => $line['description'],
                'quantity'          => $line['quantity'],
                'unit_price'        => $line['unit_price'],
                'tax_rate'          => $line['tax_rate'] ?? null,
            ]);
        }

        return redirect()
            ->route('companies.purchase-orders.show', [$company, $purchaseOrder])
            ->with('success', 'Purchase order ' . $purchaseOrder->po_number . ' created.');
    }

    public function show(Company $company, PurchaseOrder $purchaseOrder): View
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($purchaseOrder->company_id === $company->id, 403);

        $purchaseOrder->load('items.inventoryItem', 'supplier');

        return view('companies.purchase-orders.show', compact('company', 'purchaseOrder'));
    }

    public function edit(Company $company, PurchaseOrder $purchaseOrder): View
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($purchaseOrder->company_id === $company->id, 403);
        abort_unless($purchaseOrder->status === PurchaseOrderStatus::Draft, 403, 'Only draft purchase orders can be edited.');

        $purchaseOrder->load('items');

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

        return view('companies.purchase-orders.edit', compact('company', 'purchaseOrder', 'inventoryItems', 'inventoryItemsForJs', 'suppliers'));
    }

    public function update(Company $company, PurchaseOrder $purchaseOrder, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($purchaseOrder->company_id === $company->id, 403);
        abort_unless($purchaseOrder->status === PurchaseOrderStatus::Draft, 403, 'Only draft purchase orders can be edited.');

        $validated = $request->validate([
            'supplier_id'               => ['required', 'integer', 'exists:suppliers,id'],
            'po_number'                 => ['required', 'string', 'max:50'],
            'order_date'                => ['required', 'date'],
            'expected_delivery_date'    => ['nullable', 'date', 'after_or_equal:order_date'],
            'currency'                  => ['nullable', 'string', 'max:3'],
            'status'                    => ['required', 'in:draft,sent'],
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

        $purchaseOrder->update([
            'supplier_id'            => $supplier->id,
            'po_number'              => $validated['po_number'],
            'order_date'             => $validated['order_date'],
            'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
            'currency'               => $validated['currency'] ?? 'ZAR',
            'subtotal'               => round($subtotal, 2),
            'tax_total'              => round($taxTotal, 2),
            'total'                  => round($subtotal + $taxTotal, 2),
            'status'                 => $validated['status'],
            'notes'                  => $validated['notes'] ?? null,
        ]);

        $purchaseOrder->items()->delete();

        foreach ($validated['items'] as $line) {
            $purchaseOrder->items()->create([
                'inventory_item_id' => $line['inventory_item_id'] ?? null,
                'description'       => $line['description'],
                'quantity'          => $line['quantity'],
                'unit_price'        => $line['unit_price'],
                'tax_rate'          => $line['tax_rate'] ?? null,
            ]);
        }

        return redirect()
            ->route('companies.purchase-orders.show', [$company, $purchaseOrder])
            ->with('success', 'Purchase order ' . $purchaseOrder->po_number . ' updated.');
    }

    public function updateStatus(Company $company, PurchaseOrder $purchaseOrder, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($purchaseOrder->company_id === $company->id, 403);

        $validated = $request->validate([
            'status' => ['required', 'in:draft,sent,acknowledged,partially_received,received,cancelled'],
        ]);

        $purchaseOrder->update(['status' => $validated['status']]);

        $newLabel = PurchaseOrderStatus::from($validated['status'])->label();

        return redirect()
            ->route('companies.purchase-orders.show', [$company, $purchaseOrder])
            ->with('success', 'Status updated to "' . $newLabel . '".');
    }

    public function convertToInvoice(Company $company, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($purchaseOrder->company_id === $company->id, 403);

        $purchaseOrder->load('items');

        $supplierInvoice = $company->supplierInvoices()->create([
            'supplier_id'    => $purchaseOrder->supplier_id,
            'invoice_number' => 'SINV-' . now()->format('ymd') . '001',
            'invoice_date'   => now()->format('Y-m-d'),
            'due_date'       => now()->addDays(30)->format('Y-m-d'),
            'currency'       => $purchaseOrder->currency,
            'subtotal'       => $purchaseOrder->subtotal,
            'tax_total'      => $purchaseOrder->tax_total,
            'total'          => $purchaseOrder->total,
            'status'         => 'draft',
            'notes'          => 'Created from PO ' . $purchaseOrder->po_number,
        ]);

        foreach ($purchaseOrder->items as $poItem) {
            $supplierInvoice->items()->create([
                'inventory_item_id' => $poItem->inventory_item_id,
                'description'       => $poItem->description,
                'quantity'          => $poItem->quantity,
                'unit_price'        => $poItem->unit_price,
                'tax_rate'          => $poItem->tax_rate,
            ]);
        }

        return redirect()
            ->route('companies.supplier-invoices.edit', [$company, $supplierInvoice])
            ->with('success', 'Supplier invoice created from PO ' . $purchaseOrder->po_number . '. Please review and save.');
    }

    public function pdf(Company $company, PurchaseOrder $purchaseOrder): Response
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($purchaseOrder->company_id === $company->id, 403);

        $purchaseOrder->load('items.inventoryItem', 'supplier');

        $pdf = Pdf::loadView('companies.purchase-orders.pdf', compact('company', 'purchaseOrder'))
            ->setPaper('a4', 'portrait');

        $filename = $company->slug . '-po-' . str($purchaseOrder->po_number)->slug() . '.pdf';

        return $pdf->download($filename);
    }

    public function destroy(Company $company, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($purchaseOrder->company_id === $company->id, 403);
        abort_unless($purchaseOrder->status === PurchaseOrderStatus::Draft, 403, 'Only draft purchase orders can be deleted.');

        $purchaseOrder->items()->delete();
        $purchaseOrder->delete();

        return redirect()
            ->route('companies.purchase-orders.index', $company)
            ->with('success', 'Purchase order deleted.');
    }

    private function nextPoNumber(Company $company): string
    {
        $today = now()->format('ymd');
        $prefix = 'PO-' . $today;

        $last = $company->purchaseOrders()
            ->where('po_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('po_number');

        if (! $last) {
            return $prefix . '001';
        }

        $seq = (int) substr($last, strlen($prefix));

        return $prefix . str_pad((string) ($seq + 1), 3, '0', STR_PAD_LEFT);
    }
}
