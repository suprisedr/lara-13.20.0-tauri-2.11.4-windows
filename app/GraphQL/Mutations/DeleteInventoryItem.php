<?php

namespace App\GraphQL\Mutations;

use App\Models\InventoryItem;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class DeleteInventoryItem
{
    /**
     * @param  array{id: string}  $args
     * @return array{success: bool, message: string}
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): array
    {
        /** @var InventoryItem $inventoryItem */
        $inventoryItem = InventoryItem::with('company')->findOrFail($args['id']);

        if ($inventoryItem->company->user_id !== $context->user()->id) {
            abort(403, 'You do not own this inventory item.');
        }

        $name = $inventoryItem->name;
        $inventoryItem->delete();

        return [
            'success' => true,
            'message' => "Item \"{$name}\" deleted.",
        ];
    }
}
