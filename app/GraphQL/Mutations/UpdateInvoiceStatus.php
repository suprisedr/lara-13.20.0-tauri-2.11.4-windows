<?php

namespace App\GraphQL\Mutations;

use App\Models\Invoice;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class UpdateInvoiceStatus
{
    /**
     * @param  array{id: string, status: string}  $args
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Invoice
    {
        /** @var Invoice $invoice */
        $invoice = Invoice::with('company')->findOrFail($args['id']);

        if ($invoice->company->user_id !== $context->user()->id) {
            abort(403, 'You do not own this invoice.');
        }

        $invoice->update(['status' => strtolower($args['status'])]);

        return $invoice->load('items.inventoryItem');
    }
}
