<?php

namespace App\GraphQL\Mutations;

use App\Models\Company;
use App\Models\Customer;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class CreateCustomer
{
    /**
     * @param  array{company_id: string, name: string, email?: string|null, address?: string|null, is_active?: bool}  $args
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): Customer
    {
        /** @var Company $company */
        $company = Company::findOrFail($args['company_id']);

        if ($company->user_id !== $context->user()->id) {
            abort(403, 'You do not own this company.');
        }

        return $company->customers()->create([
            'name' => $args['name'],
            'email' => $args['email'] ?? null,
            'address' => $args['address'] ?? null,
            'is_active' => $args['is_active'] ?? true,
        ]);
    }
}
