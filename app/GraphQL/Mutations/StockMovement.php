<?php

namespace App\GraphQL\Mutations;

use App\Enums\StockMovementAction;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\DB;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class StockMovement
{
    /**
     * @param  array{
     *     inventory_item_id: string,
     *     action: string,
     *     quantity: float,
     *     unit_cost?: float|null,
     *     reference?: string|null,
     *     notes?: string|null,
     *     moved_at?: string|null
     * }  $args
     */
    public function __invoke(mixed $root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo): InventoryMovement
    {
        /** @var InventoryItem $item */
        $item = InventoryItem::with('company')->findOrFail($args['inventory_item_id']);

        if ($item->company->user_id !== $context->user()->id) {
            abort(403, 'You do not own this inventory item.');
        }

        $action = StockMovementAction::from($args['action']);
        $quantity = (float) $args['quantity'];

        return DB::transaction(function () use ($item, $action, $quantity, $args, $context) {
            $movement = InventoryMovement::create([
                'company_id' => $item->company_id,
                'inventory_item_id' => $item->id,
                'action' => $action,
                'quantity' => $quantity,
                'unit_cost' => $args['unit_cost'] ?? null,
                'reference' => $args['reference'] ?? null,
                'notes' => $args['notes'] ?? null,
                'moved_at' => $args['moved_at'] ?? now(),
                'created_by' => $context->user()->id,
            ]);

            match ($action) {
                StockMovementAction::Receive => $item->increment('quantity_on_hand', $quantity),
                StockMovementAction::Issue => $item->decrement('quantity_on_hand', $quantity),
                StockMovementAction::Adjust => $item->increment('quantity_on_hand', $quantity),
                StockMovementAction::Transfer => null, // location-level transfer; no net on-hand change
            };

            return $movement;
        });
    }
}
