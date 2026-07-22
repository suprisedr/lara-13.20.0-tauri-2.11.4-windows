<?php

namespace App\GraphQL\Mutations;

use App\Models\InventoryItem;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class UpdateInventoryItem
{
    /**
     * @param  array{
     *     id: string,
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
     *     is_active?: bool|null,
     *     inventory_type?: string|null,
     *     inventory_account_id?: string|null,
     *     cogs_account_id?: string|null,
     *     write_down_account_id?: string|null
     * }  $args
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): InventoryItem
    {
        /** @var InventoryItem $inventoryItem */
        $inventoryItem = InventoryItem::with('company')->findOrFail($args['id']);

        if ($inventoryItem->company->user_id !== $context->user()->id) {
            abort(403, 'You do not own this inventory item.');
        }

        $inventoryItem->update([
            'name' => $args['name'],
            'description' => $args['description'] ?? null,
            'sku' => $args['sku'] ?? null,
            'unit_price' => $args['unit_price'],
            'purchase_cost' => $args['purchase_cost'] ?? $inventoryItem->purchase_cost,
            'freight_in' => $args['freight_in'] ?? $inventoryItem->freight_in,
            'import_duties' => $args['import_duties'] ?? $inventoryItem->import_duties,
            'handling_costs' => $args['handling_costs'] ?? $inventoryItem->handling_costs,
            'trade_discount' => $args['trade_discount'] ?? $inventoryItem->trade_discount,
            'tax_rate' => $args['tax_rate'] ?? null,
            'is_active' => isset($args['is_active']) ? (bool) $args['is_active'] : $inventoryItem->is_active,
            'inventory_type' => $args['inventory_type'] ?? $inventoryItem->inventory_type,
            'inventory_account_id' => array_key_exists('inventory_account_id', $args) ? $args['inventory_account_id'] : $inventoryItem->inventory_account_id,
            'cogs_account_id' => array_key_exists('cogs_account_id', $args) ? $args['cogs_account_id'] : $inventoryItem->cogs_account_id,
            'write_down_account_id' => array_key_exists('write_down_account_id', $args) ? $args['write_down_account_id'] : $inventoryItem->write_down_account_id,
        ]);

        return $inventoryItem->fresh();
    }
}
