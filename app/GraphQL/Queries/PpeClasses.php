<?php

namespace App\GraphQL\Queries;

use App\Models\Company;
use App\Models\PpeClass;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Collection;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class PpeClasses
{
    /**
     * @param  array{company_id: string}  $args
     * @return Collection<int, PpeClass>
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Collection
    {
        /** @var Company $company */
        $company = Company::findOrFail($args['company_id']);

        if ($company->user_id !== $context->user()->id) {
            abort(403, 'You do not own this company.');
        }

        return $company->ppeClasses()->orderBy('sort_order')->orderBy('name')->get();
    }
}
