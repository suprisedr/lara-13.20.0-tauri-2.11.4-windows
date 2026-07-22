<?php

namespace App\GraphQL\Queries;

use App\Models\Company;
use App\Models\Invoice;
use Carbon\Carbon;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Collection;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class InvoicesByDate
{
    /**
     * @param  array{company_id: string, date: string}  $args
     * @return Collection<int, Invoice>
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Collection
    {
        /** @var Company $company */
        $company = Company::findOrFail($args['company_id']);

        if ($company->user_id !== $context->user()->id) {
            abort(403, 'You do not own this company.');
        }

        // Invoice numbers are formatted as INV-YYMMDD### — build the prefix from the date.
        $prefix = 'INV-' . Carbon::parse($args['date'])->format('ymd');

        return $company->invoices()
            ->with(['items', 'customer'])
            ->where('invoice_number', 'like', $prefix . '%')
            ->orderBy('invoice_number')
            ->get();
    }
}
