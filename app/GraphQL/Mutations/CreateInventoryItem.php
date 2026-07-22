<?php

namespace App\GraphQL\Mutations;

use App\Models\Company;
use App\Models\InventoryItem;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class CreateInventoryItem
{
    /**
     * @param  array{
     *     company_id: string,
     *     name: string,
     *     description?: string|null,
     *     sku?: string|null,
     *     unit_price: float,
     *     purchase_cost?: float|null,
     *     freight_in?: float|null,
     *     import_duties?: float|null,
     *     handling_costs?: float|null,
     *     trade_discount?: float|null,
     *     tax_rate?: float|null,
     *     inventory_type?: string|null,
     *     quantity_on_hand?: float|null,
     *     quantity_reserved?: float|null,
     *     inventory_account_id?: string|null,
     *     cogs_account_id?: string|null,
     *     write_down_account_id?: string|null
     * }  $args
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): InventoryItem
    {
        /** @var Company $company */
        $company = Company::findOrFail($args['company_id']);

        if ($company->user_id !== $context->user()->id) {
            abort(403, 'You do not own this company.');
        }

        return $company->inventoryItems()->create([
            'name' => $args['name'],
            'description' => $args['description'] ?? null,
            'sku' => $args['sku'] ?? null,
            'unit_price' => $args['unit_price'],
            'purchase_cost' => $args['purchase_cost'] ?? 0,
            'freight_in' => $args['freight_in'] ?? 0,
            'import_duties' => $args['import_duties'] ?? 0,
            'handling_costs' => $args['handling_costs'] ?? 0,
            'trade_discount' => $args['trade_discount'] ?? 0,
            'tax_rate' => $args['tax_rate'] ?? null,
            'is_active' => true,
            'inventory_type' => $args['inventory_type'] ?? 'merchandise',
            'quantity_on_hand' => $args['quantity_on_hand'] ?? 0,
            'quantity_reserved' => $args['quantity_reserved'] ?? 0,
            'inventory_account_id' => $args['inventory_account_id'] ?? null,
            'cogs_account_id' => $args['cogs_account_id'] ?? null,
            'write_down_account_id' => $args['write_down_account_id'] ?? null,
        ]);
    }
}
