<?php

namespace App\GraphQL\Queries;

use App\Models\Company;
use App\Models\VatRegistration;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class VatRegistrationStatus
{
    /**
     * @param  array{company_id: string}  $args
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): ?VatRegistration
    {
        /** @var Company $company */
        $company = Company::findOrFail($args['company_id']);

        if ($company->user_id !== $context->user()->id) {
            abort(403, 'You do not own this company.');
        }

        return $company->activeVatRegistration;
    }
}
