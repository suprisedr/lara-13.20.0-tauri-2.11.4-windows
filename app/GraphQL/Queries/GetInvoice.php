<?php

namespace App\GraphQL\Queries;

use App\Models\Invoice;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class GetInvoice
{
    /**
     * @param  array{id: string}  $args
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Invoice
    {
        /** @var Invoice $invoice */
        $invoice = Invoice::with(['items.inventoryItem', 'company', 'customer'])->findOrFail($args['id']);

        if ($invoice->company->user_id !== $context->user()->id) {
            abort(403, 'You do not own this invoice.');
        }

        return $invoice;
    }
}
