<?php

namespace App\GraphQL\Queries;

use App\Models\Company;
use App\Models\Quotation;
use Carbon\Carbon;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Collection;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class QuotationsByDate
{
    /**
     * @param  array{company_id: string, date: string}  $args
     * @return Collection<int, Quotation>
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Collection
    {
        /** @var Company $company */
        $company = Company::findOrFail($args['company_id']);

        if ($company->user_id !== $context->user()->id) {
            abort(403, 'You do not own this company.');
        }

        // Quotation numbers are formatted as QOU-YYMMDD### — build the prefix from the date.
        $prefix = 'QOU-' . Carbon::parse($args['date'])->format('ymd');

        return $company->quotations()
            ->with(['items', 'customer'])
            ->where('quotation_number', 'like', $prefix . '%')
            ->orderBy('quotation_number')
            ->get();
    }
}
