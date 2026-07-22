<?php

namespace App\GraphQL\Mutations;

use App\Models\ChartOfAccount;
use App\Models\Company;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\DB;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class CreateChartOfAccount
{
    /**
     * @param  array{
     *     company_id: string,
     *     account_name: string,
     *     account_type: string,
     *     expense_class?: string|null,
     *     category?: string|null,
     *     cash_flow_category?: string|null,
     *     is_contra?: bool|null,
     *     description?: string|null,
     *     parent_id?: string|null,
     *     is_active?: bool|null,
     *     opening_balance?: float|null
     * }  $args
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): ChartOfAccount
    {
        /** @var Company $company */
        $company = Company::findOrFail($args['company_id']);

        if ($company->user_id !== $context->user()->id) {
            abort(403, 'You do not own this company.');
        }

        $parentId   = $args['parent_id'] ?? null;
        $parentCode = null;

        if ($parentId !== null) {
            /** @var ChartOfAccount $parent */
            $parent     = $company->chartOfAccounts()->findOrFail($parentId);
            $code       = $this->nextChildAccountCode($company, $parent);
            $parentCode = $parent->account_code;
            $accountType = $parent->account_type;
        } else {
            $expenseClass = $args['expense_class'] ?? 'operating';
            $code         = $this->nextParentAccountCode($company, $args['account_type'], $expenseClass);
            $accountType  = $args['account_type'];
        }

        return $company->chartOfAccounts()->create([
            'account_code'       => $code,
            'account_name'       => $args['account_name'],
            'account_type'       => $accountType,
            'category'           => $args['category'] ?? null,
            'cash_flow_category' => $args['cash_flow_category'] ?? null,
            'is_contra'          => $args['is_contra'] ?? false,
            'description'        => $args['description'] ?? null,
            'parent_id'          => $parentId,
            'parent_code'        => $parentCode,
            'is_active'          => $args['is_active'] ?? true,
            'opening_balance'    => $args['opening_balance'] ?? 0,
        ]);
    }

    /**
     * Next available "xx00" parent code within the type's range.
     * Mirrors CompanyController::nextParentAccountCode().
     */
    private function nextParentAccountCode(Company $company, string $type, ?string $expenseClass = null): string
    {
        $ranges = [
            'assets'      => [1000000, 1999000],
            'liabilities' => [2000000, 2999000],
            'equity'      => [3000000, 3999000],
            'income'      => [4000000, 4999000],
            'expenses'    => [5000000, 8999000],
        ];

        $expenseRanges = [
            'cost_of_sales' => [5000000, 5999000],
            'operating'     => [6000000, 6999000],
            'finance'       => [7000000, 7999000],
            'tax'           => [8000000, 8999000],
        ];

        [$min, $max] = ($type === 'expenses' && isset($expenseRanges[$expenseClass]))
            ? $expenseRanges[$expenseClass]
            : $ranges[$type];

        $existingCodes = $company->chartOfAccounts()
            ->whereNull('parent_id')
            ->whereBetween(DB::raw('CAST(account_code AS UNSIGNED)'), [$min, $max])
            ->pluck('account_code')
            ->map(fn ($c) => (int) $c)
            ->all();

        // Parents start at min+1000 (e.g. 1001000) and step by 1000.
        $candidate = $min + 1000;
        while (in_array($candidate, $existingCodes, true) && $candidate <= $max) {
            $candidate += 1000;
        }

        return (string) $candidate;
    }

    /**
     * Next available child code within the parent's sub-range (parent+1 … parent+999).
     * Mirrors CompanyController::nextChildAccountCode().
     */
    private function nextChildAccountCode(Company $company, ChartOfAccount $parent): string
    {
        $parentCode = (int) $parent->account_code;
        $rangeMin   = $parentCode + 1;
        $rangeMax   = $parentCode + 999;

        $maxExisting = $company->chartOfAccounts()
            ->where('parent_id', $parent->id)
            ->whereBetween(DB::raw('CAST(account_code AS UNSIGNED)'), [$rangeMin, $rangeMax])
            ->max(DB::raw('CAST(account_code AS UNSIGNED)'));

        return (string) ($maxExisting ? ((int) $maxExisting + 1) : $rangeMin);
    }
}
