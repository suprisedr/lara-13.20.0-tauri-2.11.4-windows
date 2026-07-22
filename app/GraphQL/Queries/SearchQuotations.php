<?php

namespace App\GraphQL\Queries;

use App\Models\Company;
use App\Models\Quotation;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Collection;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class SearchQuotations
{
    /**
     * @param  array{
     *   company_id: string,
     *   quotation_number?: string,
     *   customer_name?: string,
     *   date?: string,
     *   status?: string,
     *   total_min?: float,
     *   total_max?: float,
     * }  $args
     * @return Collection<int, Quotation>
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Collection
    {
        /** @var Company $company */
        $company = Company::findOrFail($args['company_id']);

        if ($company->user_id !== $context->user()->id) {
            abort(403, 'You do not own this company.');
        }

        $query = $company->quotations()->with(['items', 'customer']);

        if (!empty($args['quotation_number'])) {
            $query->where('quotation_number', 'like', $args['quotation_number'] . '%');
        }

        if (!empty($args['customer_name'])) {
            $query->where('customer_name', 'like', '%' . $args['customer_name'] . '%');
        }

        if (!empty($args['date'])) {
            $query->whereDate('quotation_date', $args['date']);
        }

        if (!empty($args['status'])) {
            $query->where('status', strtolower($args['status']));
        }

        $results = $query->latest('quotation_date')->get();

        if (isset($args['total_min']) || isset($args['total_max'])) {
            $results = $results->filter(function ($quotation) use ($args) {
                $total = $quotation->total();
                if (isset($args['total_min']) && $total < $args['total_min']) {
                    return false;
                }
                if (isset($args['total_max']) && $total > $args['total_max']) {
                    return false;
                }
                return true;
            })->values();
        }

        return $results;
    }
}
