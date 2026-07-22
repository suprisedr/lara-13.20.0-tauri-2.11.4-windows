<?php

namespace App\GraphQL\Queries;

use App\Models\ChartOfAccount;
use App\Models\Company;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Collection;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class ChartOfAccounts
{
    /**
     * @param  array{company_id: string, only_parents?: bool}  $args
     * @return Collection<int, ChartOfAccount>
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Collection
    {
        /** @var Company $company */
        $company = Company::findOrFail($args['company_id']);

        if ($company->user_id !== $context->user()->id) {
            abort(403, 'You do not own this company.');
        }

        $onlyParents = (bool) ($args['only_parents'] ?? false);

        $query = $company->chartOfAccounts()
            ->with(['items' => fn ($q) => $q->orderBy('account_code')])
            ->orderBy('account_code');

        if ($onlyParents) {
            $query->whereNull('parent_id');
        }

        return $query->get();
    }
}
