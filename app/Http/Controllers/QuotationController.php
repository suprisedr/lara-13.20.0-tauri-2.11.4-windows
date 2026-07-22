<?php

namespace App\Http\Controllers;

use App\Enums\QuotationStatus;
use App\Models\Company;
use App\Models\InventoryItem;
use App\Models\Quotation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class QuotationController extends Controller
{
    public function index(Company $company): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $quotations = $company->quotations()
            ->with(['items', 'customer'])
            ->latest('quotation_date')
            ->get();

        return view('companies.quotations.index', compact('company', 'quotations'));
    }

    public function create(Company $company): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $inventoryItems = $company->inventoryItems()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $customers = $company->customers()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $inventoryItemsForJs = $inventoryItems->map(fn ($i) => [
            'id' => $i->id,
            'name' => $i->name,
            'unit_price' => (float) $i->unit_price,
            'tax_rate' => $i->tax_rate !== null ? (float) $i->tax_rate : null,
            'description' => $i->description ?? $i->name,
        ]);

        $nextNumber = $this->nextQuotationNumber($company);

        return view('companies.quotations.create', compact('company', 'inventoryItems', 'inventoryItemsForJs', 'customers', 'nextNumber'));
    }

    public function store(Company $company, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'quotation_number'          => ['required', 'string', 'max:50'],
            'customer_id'               => ['nullable', 'integer', 'exists:customers,id'],
            'customer_name'             => ['required', 'string', 'max:255'],
            'customer_email'            => ['required', 'email', 'max:255'],
            'customer_address'          => ['nullable', 'string', 'max:500'],
            'quotation_date'            => ['required', 'date'],
            'expiry_date'               => ['nullable', 'date', 'after_or_equal:quotation_date'],
            'status'                    => ['required', 'in:draft,sent,accepted,declined,expired'],
            'notes'                     => ['nullable', 'string', 'max:1000'],
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => [
                'required',
                'integer',
                'exists:inventory_items,id',
            ],
            'items.*.quantity'          => ['required', 'numeric', 'min:0.01'],
        ]);

        $customer = $this->resolveCustomer($company, $validated);

        // Verify every inventory item belongs to this company
        $inventoryItemIds = collect($validated['items'])->pluck('inventory_item_id');
        $inventoryItems = InventoryItem::whereIn('id', $inventoryItemIds)
            ->where('company_id', $company->id)
            ->get()
            ->keyBy('id');

        abort_if($inventoryItems->count() !== $inventoryItemIds->unique()->count(), 403);

        $quotation = $company->quotations()->create([
            'customer_id'      => $customer->id,
            'quotation_number' => $validated['quotation_number'],
            'customer_name'    => $customer->name,
            'customer_email'   => $customer->email,
            'customer_address' => $customer->address,
            'quotation_date'   => $validated['quotation_date'],
            'expiry_date'      => $validated['expiry_date'] ?? null,
            'status'           => $validated['status'],
            'notes'            => $validated['notes'] ?? null,
        ]);

        foreach ($validated['items'] as $line) {
            $item = $inventoryItems->get($line['inventory_item_id']);
            $quotation->items()->create([
                'inventory_item_id' => $item->id,
                'description'       => $item->description ?? $item->name,
                'quantity'          => $line['quantity'],
                'unit_price'        => $item->unit_price,
                'tax_rate'          => $item->tax_rate,
            ]);
        }

        return redirect()
            ->route('companies.quotations.show', [$company, $quotation])
            ->with('success', 'Quotation ' . $quotation->quotation_number . ' created.');
    }

    public function edit(Company $company, Quotation $quotation): View
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($quotation->company_id === $company->id, 403);
        abort_unless($quotation->status === QuotationStatus::Draft->value, 403, 'Only draft quotations can be edited.');

        $quotation->load('items');

        $inventoryItems = $company->inventoryItems()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $customers = $company->customers()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $inventoryItemsForJs = $inventoryItems->map(fn ($i) => [
            'id' => $i->id,
            'name' => $i->name,
            'unit_price' => (float) $i->unit_price,
            'tax_rate' => $i->tax_rate !== null ? (float) $i->tax_rate : null,
            'description' => $i->description ?? $i->name,
        ]);

        return view('companies.quotations.edit', compact('company', 'quotation', 'inventoryItems', 'inventoryItemsForJs', 'customers'));
    }

    public function update(Company $company, Quotation $quotation, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($quotation->company_id === $company->id, 403);
        abort_unless($quotation->status === QuotationStatus::Draft->value, 403, 'Only draft quotations can be edited.');

        $validated = $request->validate([
            'quotation_number'          => ['required', 'string', 'max:50'],
            'customer_id'               => ['nullable', 'integer', 'exists:customers,id'],
            'customer_name'             => ['required', 'string', 'max:255'],
            'customer_email'            => ['required', 'email', 'max:255'],
            'customer_address'          => ['nullable', 'string', 'max:500'],
            'quotation_date'            => ['required', 'date'],
            'expiry_date'               => ['nullable', 'date', 'after_or_equal:quotation_date'],
            'status'                    => ['required', 'in:draft,sent,accepted,declined,expired'],
            'notes'                     => ['nullable', 'string', 'max:1000'],
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => [
                'required',
                'integer',
                'exists:inventory_items,id',
            ],
            'items.*.quantity'          => ['required', 'numeric', 'min:0.01'],
        ]);

        $customer = $this->resolveCustomer($company, $validated);

        // Verify every inventory item belongs to this company
        $inventoryItemIds = collect($validated['items'])->pluck('inventory_item_id');
        $inventoryItems = InventoryItem::whereIn('id', $inventoryItemIds)
            ->where('company_id', $company->id)
            ->get()
            ->keyBy('id');

        abort_if($inventoryItems->count() !== $inventoryItemIds->unique()->count(), 403);

        $quotation->update([
            'customer_id'      => $customer->id,
            'quotation_number' => $validated['quotation_number'],
            'customer_name'    => $customer->name,
            'customer_email'   => $customer->email,
            'customer_address' => $customer->address,
            'quotation_date'   => $validated['quotation_date'],
            'expiry_date'      => $validated['expiry_date'] ?? null,
            'status'           => $validated['status'],
            'notes'            => $validated['notes'] ?? null,
        ]);

        $quotation->items()->delete();

        foreach ($validated['items'] as $line) {
            $item = $inventoryItems->get($line['inventory_item_id']);
            $quotation->items()->create([
                'inventory_item_id' => $item->id,
                'description'       => $item->description ?? $item->name,
                'quantity'          => $line['quantity'],
                'unit_price'        => $item->unit_price,
                'tax_rate'          => $item->tax_rate,
            ]);
        }

        return redirect()
            ->route('companies.quotations.show', [$company, $quotation])
            ->with('success', 'Quotation ' . $quotation->quotation_number . ' updated.');
    }

    public function show(Company $company, Quotation $quotation): View
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($quotation->company_id === $company->id, 403);

        $quotation->load('items.inventoryItem', 'convertedInvoice');

        return view('companies.quotations.show', compact('company', 'quotation'));
    }

    public function pdf(Company $company, Quotation $quotation): Response
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($quotation->company_id === $company->id, 403);

        $quotation->load('items.inventoryItem');

        $pdf = Pdf::loadView('companies.quotations.pdf', compact('company', 'quotation'))
            ->setPaper('a4', 'portrait');

        $filename = $company->slug . '-' . str($quotation->quotation_number)->slug() . '.pdf';

        return $pdf->download($filename);
    }

    public function updateStatus(Company $company, Quotation $quotation, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($quotation->company_id === $company->id, 403);

        $validated = $request->validate([
            'status' => ['required', 'in:draft,sent,accepted,declined,expired'],
        ]);

        $oldStatus = QuotationStatus::from($quotation->status);
        $targetStatus = QuotationStatus::from($validated['status']);

        if ($oldStatus !== $targetStatus && ! $oldStatus->canTransitionTo($targetStatus)) {
            return redirect()
                ->route('companies.quotations.show', [$company, $quotation])
                ->with('error', "Cannot change quotation status from \"{$oldStatus->label()}\" to \"{$targetStatus->label()}\".");
        }

        $quotation->update(['status' => $targetStatus->value]);

        return redirect()
            ->route('companies.quotations.show', [$company, $quotation])
            ->with('success', 'Quotation status updated to "' . $targetStatus->label() . '".');
    }

    public function convertToInvoice(Company $company, Quotation $quotation): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($quotation->company_id === $company->id, 403);
        abort_if($quotation->converted_invoice_id !== null, 422, 'This quotation has already been converted to an invoice.');

        $quotation->load('items');

        $invoice = $company->invoices()->create([
            'customer_id'      => $quotation->customer_id,
            'invoice_number'   => $this->nextInvoiceNumber($company),
            'customer_name'    => $quotation->customer_name,
            'customer_email'   => $quotation->customer_email,
            'customer_address' => $quotation->customer_address,
            'invoice_date'     => now()->format('Y-m-d'),
            'due_date'         => null,
            'status'           => 'draft',
            'notes'            => $quotation->notes,
        ]);

        foreach ($quotation->items as $line) {
            $invoice->items()->create([
                'inventory_item_id' => $line->inventory_item_id,
                'description'       => $line->description,
                'quantity'          => $line->quantity,
                'unit_price'        => $line->unit_price,
                'tax_rate'          => $line->tax_rate,
            ]);
        }

        $quotation->update([
            'converted_invoice_id' => $invoice->id,
            'status'               => 'accepted',
        ]);

        return redirect()
            ->route('companies.invoices.show', [$company, $invoice])
            ->with('success', 'Quotation ' . $quotation->quotation_number . ' converted to invoice ' . $invoice->invoice_number . '.');
    }

    private function resolveCustomer(Company $company, array $validated): \App\Models\Customer
    {
        if (! empty($validated['customer_id'])) {
            return $company->customers()->findOrFail($validated['customer_id']);
        }

        return $company->customers()->firstOrCreate(
            ['email' => $validated['customer_email']],
            [
                'name'      => $validated['customer_name'],
                'address'   => $validated['customer_address'] ?? null,
                'is_active' => true,
            ]
        );
    }

    private function nextQuotationNumber(Company $company): string
    {
        $today = now()->format('ymd');
        $prefix = 'QOU-' . $today;

        $last = $company->quotations()
            ->where('quotation_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('quotation_number');

        if (! $last) {
            return $prefix . '001';
        }

        $seq = (int) substr($last, strlen($prefix));

        return $prefix . str_pad((string) ($seq + 1), 3, '0', STR_PAD_LEFT);
    }

    private function nextInvoiceNumber(Company $company): string
    {
        $today = now()->format('ymd');
        $prefix = 'INV-' . $today;

        $last = $company->invoices()
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
