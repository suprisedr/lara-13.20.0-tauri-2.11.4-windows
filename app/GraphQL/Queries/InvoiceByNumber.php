<?php

namespace App\GraphQL\Queries;

use App\Models\Company;
use App\Models\Invoice;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class InvoiceByNumber
{
    /**
     * @param  array{company_id: string, invoice_number: string}  $args
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): ?Invoice
    {
        /** @var Company $company */
        $company = Company::findOrFail($args['company_id']);

        if ($company->user_id !== $context->user()->id) {
            abort(403, 'You do not own this company.');
        }

        return $company->invoices()
            ->with(['items', 'customer'])
            ->where('invoice_number', $args['invoice_number'])
            ->first();
    }
}
