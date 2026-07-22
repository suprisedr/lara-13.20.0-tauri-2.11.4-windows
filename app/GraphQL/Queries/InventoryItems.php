<?php

namespace App\GraphQL\Queries;

use App\Models\Company;
use App\Models\InventoryItem;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Database\Eloquent\Collection;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class InventoryItems
{
    /**
     * @param  array{company_id: string, only_active?: bool}  $args
     * @return Collection<int, InventoryItem>
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Collection
    {
        /** @var Company $company */
        $company = Company::findOrFail($args['company_id']);

        if ($company->user_id !== $context->user()->id) {
            abort(403, 'You do not own this company.');
        }

        $query = $company->inventoryItems()->orderBy('name');

        if ((bool) ($args['only_active'] ?? false)) {
            $query->where('is_active', true);
        }

        return $query->get();
    }
}
