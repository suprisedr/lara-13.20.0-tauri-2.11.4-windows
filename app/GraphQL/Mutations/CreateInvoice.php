<?php

namespace App\GraphQL\Mutations;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\InventoryItem;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class CreateInvoice
{
    /**
     * @param  array{
     *     company_id: string,
     *     invoice_number?: string|null,
     *     customer_name: string,
     *     customer_email?: string|null,
     *     customer_address?: string|null,
     *     invoice_date: string,
     *     due_date?: string|null,
     *     status?: string|null,
     *     notes?: string|null,
     *     items: array<array{inventory_item_id: string, quantity: float}>
     * }  $args
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Invoice
    {
        /** @var Company $company */
        $company = Company::findOrFail($args['company_id']);

        if ($company->user_id !== $context->user()->id) {
            abort(403, 'You do not own this company.');
        }

        // Validate and load all inventory items belong to this company
        $inventoryItemIds = collect($args['items'])->pluck('inventory_item_id');
        $inventoryItems = InventoryItem::whereIn('id', $inventoryItemIds)
            ->where('company_id', $company->id)
            ->get()
            ->keyBy('id');

        if ($inventoryItems->count() !== $inventoryItemIds->unique()->count()) {
            abort(403, 'One or more inventory items do not belong to this company.');
        }

        $invoiceNumber = $args['invoice_number'] ?? $this->nextInvoiceNumber($company);

        $customerId = $args['customer_id'] ?? null;
        $customer = null;

        if ($customerId !== null) {
            $customer = $company->customers()->findOrFail($customerId);
        }

        $invoice = $company->invoices()->create([
            'customer_id'      => $customer?->id,
            'invoice_number'   => $invoiceNumber,
            'customer_name'    => $customer?->name ?? $args['customer_name'],
            'customer_email'   => $customer?->email ?? $args['customer_email'] ?? null,
            'customer_address' => $customer?->address ?? $args['customer_address'] ?? null,
            'invoice_date'     => $args['invoice_date'],
            'due_date'         => $args['due_date'] ?? null,
            'status'           => isset($args['status']) ? strtolower($args['status']) : 'draft',
            'notes'            => $args['notes'] ?? null,
        ]);

        foreach ($args['items'] as $line) {
            /** @var InventoryItem $item */
            $item = $inventoryItems->get($line['inventory_item_id']);
            $invoice->items()->create([
                'inventory_item_id' => $item->id,
                'description'       => $item->description ?? $item->name,
                'quantity'          => $line['quantity'],
                'unit_price'        => $item->unit_price,
                'tax_rate'          => $item->tax_rate,
            ]);
        }

        return $invoice->load('items.inventoryItem');
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
