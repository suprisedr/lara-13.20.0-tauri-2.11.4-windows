<?php

namespace App\GraphQL\Queries;

use App\Models\Company;
use App\Models\Quotation;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class QuotationByNumber
{
    /**
     * @param  array{company_id: string, quotation_number: string}  $args
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): ?Quotation
    {
        /** @var Company $company */
        $company = Company::findOrFail($args['company_id']);

        if ($company->user_id !== $context->user()->id) {
            abort(403, 'You do not own this company.');
        }

        return $company->quotations()
            ->with(['items', 'customer'])
            ->where('quotation_number', $args['quotation_number'])
            ->first();
    }
}
