<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InventoryItem;
use App\Services\InvoicePostingService;
use App\Services\RoadRunnerInvoicePostingDispatcher;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    public function index(Company $company): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $invoices = $company->invoices()
            ->with(['items', 'customer'])
            ->latest('invoice_date')
            ->get();

        return view('companies.invoices.index', compact('company', 'invoices'));
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

        return view('companies.invoices.create', compact('company', 'inventoryItems', 'inventoryItemsForJs', 'customers', 'nextNumber'));
    }

    public function store(Company $company, Request $request, InvoicePostingService $postingService): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $validated = $request->validate([
            'invoice_number'            => ['required', 'string', 'max:50'],
            'customer_id'               => ['nullable', 'integer', 'exists:customers,id'],
            'customer_name'             => ['required', 'string', 'max:255'],
            'customer_email'            => ['required', 'email', 'max:255'],
            'customer_address'          => ['nullable', 'string', 'max:500'],
            'invoice_date'              => ['required', 'date'],
            'due_date'                  => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'status'                    => ['required', 'in:draft,pending,paid'],
            'notes'                     => ['nullable', 'string', 'max:1000'],
            // 'partially_paid', 'overdue', 'voided' and 'write_off' are not selectable on create —
            // an invoice always starts as draft, pending, or (immediately) paid.
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

        $stockError = $this->checkStockForInvoice(
            $validated['items'], $inventoryItems, $company,
            'companies.invoices.create', [$company]
        );
        if ($stockError) {
            return $stockError;
        }

        $invoice = $company->invoices()->create([
            'customer_id'      => $customer->id,
            'invoice_number'   => $validated['invoice_number'],
            'customer_name'    => $customer->name,
            'customer_email'   => $customer->email,
            'customer_address' => $customer->address,
            'invoice_date'     => $validated['invoice_date'],
            'due_date'         => $validated['due_date'] ?? null,
            'status'           => 'draft',
            'notes'            => $validated['notes'] ?? null,
        ]);

        foreach ($validated['items'] as $line) {
            $item = $inventoryItems->get($line['inventory_item_id']);
            $invoice->items()->create([
                'inventory_item_id' => $item->id,
                'description'       => $item->description ?? $item->name,
                'quantity'          => $line['quantity'],
                'unit_price'        => $item->unit_price,
                'tax_rate'          => $item->tax_rate,
            ]);
        }

        if ($validated['status'] !== 'draft') {
            $invoice->load('items');

            try {
                $postingService->updateStatus($company, $invoice, auth()->user(), $validated['status']);
            } catch (InvalidArgumentException $e) {
                return redirect()
                    ->route('companies.invoices.show', [$company, $invoice])
                    ->with('error', 'Invoice created as draft. ' . $e->getMessage());
            }
        }

        return redirect()
            ->route('companies.invoices.show', [$company, $invoice])
            ->with('success', 'Invoice ' . $invoice->invoice_number . ' created.');
    }

    public function edit(Company $company, Invoice $invoice): View
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($invoice->company_id === $company->id, 403);
        abort_unless($invoice->status === InvoiceStatus::Draft->value, 403, 'Only draft invoices can be edited.');

        $invoice->load('items');

        $inventoryItems = $company->inventoryItems()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $customers = $company->customers()
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

        return view('companies.invoices.edit', compact('company', 'invoice', 'inventoryItems', 'inventoryItemsForJs', 'customers'));
    }

    public function update(Company $company, Invoice $invoice, Request $request, InvoicePostingService $postingService): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($invoice->company_id === $company->id, 403);
        abort_unless($invoice->status === InvoiceStatus::Draft->value, 403, 'Only draft invoices can be edited.');

        $validated = $request->validate([
            'invoice_number'            => ['required', 'string', 'max:50'],
            'customer_id'               => ['nullable', 'integer', 'exists:customers,id'],
            'customer_name'             => ['required', 'string', 'max:255'],
            'customer_email'            => ['required', 'email', 'max:255'],
            'customer_address'          => ['nullable', 'string', 'max:500'],
            'invoice_date'              => ['required', 'date'],
            'due_date'                  => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'status'                    => ['required', 'in:draft,pending,paid'],
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

        $stockError = $this->checkStockForInvoice(
            $validated['items'], $inventoryItems, $company,
            'companies.invoices.edit', [$company, $invoice]
        );
        if ($stockError) {
            return $stockError;
        }

        $invoice->update([
            'customer_id'      => $customer->id,
            'invoice_number'   => $validated['invoice_number'],
            'customer_name'    => $customer->name,
            'customer_email'   => $customer->email,
            'customer_address' => $customer->address,
            'invoice_date'     => $validated['invoice_date'],
            'due_date'         => $validated['due_date'] ?? null,
            'notes'            => $validated['notes'] ?? null,
        ]);

        $invoice->items()->delete();

        foreach ($validated['items'] as $line) {
            $item = $inventoryItems->get($line['inventory_item_id']);
            $invoice->items()->create([
                'inventory_item_id' => $item->id,
                'description'       => $item->description ?? $item->name,
                'quantity'          => $line['quantity'],
                'unit_price'        => $item->unit_price,
                'tax_rate'          => $item->tax_rate,
            ]);
        }

        if ($validated['status'] !== 'draft') {
            $invoice->load('items');

            try {
                $postingService->updateStatus($company, $invoice, auth()->user(), $validated['status']);
            } catch (InvalidArgumentException $e) {
                return redirect()
                    ->route('companies.invoices.show', [$company, $invoice])
                    ->with('error', 'Invoice updated as draft. ' . $e->getMessage());
            }
        }

        return redirect()
            ->route('companies.invoices.show', [$company, $invoice])
            ->with('success', 'Invoice ' . $invoice->invoice_number . ' updated.');
    }

    public function show(Company $company, Invoice $invoice): View
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($invoice->company_id === $company->id, 403);

        $invoice->load('items.inventoryItem', 'payments.user', 'paymentTransaction');

        return view('companies.invoices.show', compact('company', 'invoice'));
    }

    public function pdf(Company $company, Invoice $invoice): Response
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($invoice->company_id === $company->id, 403);

        $invoice->load('items.inventoryItem');

        $pdf = Pdf::loadView('companies.invoices.pdf', compact('company', 'invoice'))
            ->setPaper('a4', 'portrait');

        $filename = $company->slug . '-' . str($invoice->invoice_number)->slug() . '.pdf';

        return $pdf->download($filename);
    }

    public function updateStatus(Company $company, Invoice $invoice, Request $request, InvoicePostingService $postingService): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($invoice->company_id === $company->id, 403);

        $validated = $request->validate([
            'status' => ['required', 'in:draft,pending,partially_paid,paid,overdue,voided,write_off'],
        ]);

        $invoice->load('items.inventoryItem');

        $oldStatus = InvoiceStatus::from($invoice->status);
        $newStatus = InvoiceStatus::from($validated['status']);
        $isIssuing = $oldStatus === InvoiceStatus::Draft && $newStatus->triggersAiPosting();

        if ($isIssuing) {
            $lineItems = $invoice->items->map(fn($li) => [
                'inventory_item_id' => $li->inventory_item_id,
                'quantity' => (float) $li->quantity,
            ])->toArray();

            $itemsKeyed = InventoryItem::whereIn('id', $invoice->items->pluck('inventory_item_id'))
                ->where('company_id', $company->id)
                ->get()
                ->keyBy('id');

            $stockError = $this->checkStockForInvoice(
                $lineItems, $itemsKeyed, $company,
                'companies.invoices.show', [$company, $invoice]
            );
            if ($stockError) {
                return $stockError;
            }
        }

        try {
            $updated = $postingService->updateStatus($company, $invoice, auth()->user(), $validated['status']);
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('companies.invoices.show', [$company, $invoice])
                ->with('error', $e->getMessage());
        }

        $newStatus = InvoiceStatus::from($validated['status']);
        $message = $newStatus === InvoiceStatus::Paid && $updated->status !== InvoiceStatus::Paid->value
            ? 'Payment journal is being posted by AI. Status will update once the journal is confirmed.'
            : 'Invoice status updated to "' . InvoiceStatus::from($updated->status)->label() . '".';

        $redirect = redirect()
            ->route('companies.invoices.show', [$company, $invoice])
            ->with('success', $message);

        if ($newStatus === InvoiceStatus::Paid && $updated->status !== InvoiceStatus::Paid->value) {
            $redirect->with('payment_posting', true);
        }

        return $redirect;
    }

    public function recordPayment(Company $company, Invoice $invoice, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($invoice->company_id === $company->id, 403);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $amount = round((float) $validated['amount'], 2);
        $balanceDue = $invoice->balanceDue();

        if ($amount > $balanceDue) {
            return redirect()
                ->route('companies.invoices.show', [$company, $invoice])
                ->with('error', 'Payment amount cannot exceed the outstanding balance of R' . number_format($balanceDue, 2) . '.');
        }

        if (! $invoice->posting_transaction_id) {
            return redirect()
                ->route('companies.invoices.show', [$company, $invoice])
                ->with('error', 'This invoice has not been posted to the ledger yet.');
        }

        app(RoadRunnerInvoicePostingDispatcher::class)
            ->dispatchPayment($invoice->fresh('items', 'payments'), auth()->user(), $amount);

        return redirect()
            ->route('companies.invoices.show', [$company, $invoice])
            ->with('success', 'AI is posting payment of R' . number_format($amount, 2) . ' for invoice ' . $invoice->invoice_number . '.')
            ->with('payment_posting', true);
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

    /**
     * Check that every inventory (non-service) line item has enough stock.
     * Returns null if OK, or a redirect response with the error.
     */
    private function checkStockForInvoice(array $lineItems, \Illuminate\Support\Collection $inventoryItems, Company $company, string $redirectRoute, array $redirectParams): ?RedirectResponse
    {
        $shortages = [];

        // Aggregate quantities per item in case the same item appears on multiple lines
        $qtyByItem = [];
        foreach ($lineItems as $line) {
            $id = $line['inventory_item_id'];
            $qtyByItem[$id] = ($qtyByItem[$id] ?? 0) + (float) $line['quantity'];
        }

        foreach ($qtyByItem as $itemId => $requiredQty) {
            $item = $inventoryItems->get($itemId);
            if (! $item || $item->is_service) {
                continue;
            }

            $available = (float) $item->quantity_on_hand;
            if ($available < $requiredQty) {
                $shortages[] = $item->name . ': need ' . number_format($requiredQty, 2) . ', available ' . number_format($available, 2);
            }
        }

        if (empty($shortages)) {
            return null;
        }

        return redirect()
            ->route($redirectRoute, $redirectParams)
            ->withInput()
            ->with('error', 'Insufficient stock to issue this invoice. ' . implode('; ', $shortages) . '.');
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
