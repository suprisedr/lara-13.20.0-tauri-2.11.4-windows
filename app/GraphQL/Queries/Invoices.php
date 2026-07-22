<?php

namespace App\GraphQL\Queries;

use App\Models\Company;
use App\Models\Invoice;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Collection;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class Invoices
{
    /**
     * @param  array{company_id: string}  $args
     * @return Collection<int, Invoice>
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Collection
    {
        /** @var Company $company */
        $company = Company::findOrFail($args['company_id']);

        if ($company->user_id !== $context->user()->id) {
            abort(403, 'You do not own this company.');
        }

        return $company->invoices()
            ->with(['items', 'customer'])
            ->latest('invoice_date')
            ->get();
    }
}
