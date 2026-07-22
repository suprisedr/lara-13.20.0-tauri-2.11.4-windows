<?php

namespace App\Http\Controllers\Api;

use App\Enums\InventoryType;
use App\Enums\StockMovementAction;
use App\Http\Controllers\Controller;
use App\Services\RoadRunnerInventoryPostingDispatcher;
use App\Models\Company;
use App\Models\InventoryItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class InventoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => ['required', 'integer'],
            'only_active' => ['nullable', 'boolean'],
            'include_services' => ['nullable', 'boolean'],
        ]);

        $company = Company::where('id', $request->company_id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $query = $company->inventoryItems()->orderBy('name');

        if ($request->boolean('only_active', false)) {
            $query->where('is_active', true);
        }

        if (! $request->boolean('include_services', true)) {
            $query->where('is_service', false);
        }

        $items = $query->get()->map(fn ($item) => $this->formatItem($item));

        return response()->json(['items' => $items]);
    }

    public function show(InventoryItem $inventoryItem): JsonResponse
    {
        $this->authorizeItem($inventoryItem);

        $inventoryItem->load(['movements' => fn ($q) => $q->orderByDesc('moved_at')->limit(50)]);

        return response()->json([
            'item' => $this->formatItem($inventoryItem),
            'movements' => $inventoryItem->movements->map(fn ($m) => [
                'id' => $m->id,
                'action' => $m->action->value,
                'quantity' => (float) $m->quantity,
                'unit_cost' => $m->unit_cost !== null ? (float) $m->unit_cost : null,
                'reference' => $m->reference,
                'notes' => $m->notes,
                'moved_at' => $m->moved_at->toDateString(),
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => ['required', 'integer'],
        ]);

        $company = Company::where('id', $request->company_id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $isService = $request->boolean('is_service', false);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_service' => ['boolean'],
        ];

        if (! $isService) {
            $rules += [
                'sku' => ['nullable', 'string', 'max:100'],
                'inventory_type' => ['nullable', new Enum(InventoryType::class)],
                'purchase_cost' => ['nullable', 'numeric', 'min:0'],
                'freight_in' => ['nullable', 'numeric', 'min:0'],
                'import_duties' => ['nullable', 'numeric', 'min:0'],
                'handling_costs' => ['nullable', 'numeric', 'min:0'],
                'trade_discount' => ['nullable', 'numeric', 'min:0'],
                'initial_quantity' => ['nullable', 'numeric', 'min:0'],
            ];
        }

        $validated = $request->validate($rules);

        $data = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'unit_price' => $validated['unit_price'],
            'tax_rate' => $validated['tax_rate'] ?? null,
            'is_service' => $isService,
            'is_active' => true,
        ];

        if (! $isService) {
            $data += [
                'sku' => $validated['sku'] ?? null,
                'inventory_type' => $validated['inventory_type'] ?? null,
                'purchase_cost' => $validated['purchase_cost'] ?? null,
                'freight_in' => $validated['freight_in'] ?? null,
                'import_duties' => $validated['import_duties'] ?? null,
                'handling_costs' => $validated['handling_costs'] ?? null,
                'trade_discount' => $validated['trade_discount'] ?? null,
                'initial_quantity' => $validated['initial_quantity'] ?? 0,
                'quantity_on_hand' => $validated['initial_quantity'] ?? 0,
            ];
        }

        $item = $company->inventoryItems()->create($data);

        return response()->json(['success' => true, 'item' => $this->formatItem($item)], 201);
    }

    public function update(InventoryItem $inventoryItem, Request $request): JsonResponse
    {
        $this->authorizeItem($inventoryItem);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'sku' => ['nullable', 'string', 'max:100'],
            'inventory_type' => ['nullable', new Enum(InventoryType::class)],
            'unit_price' => ['sometimes', 'numeric', 'min:0'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $inventoryItem->update($validated);

        return response()->json(['success' => true, 'item' => $this->formatItem($inventoryItem->fresh())]);
    }

    public function recordMovement(InventoryItem $inventoryItem, Request $request): JsonResponse
    {
        $this->authorizeItem($inventoryItem);

        if ($inventoryItem->is_service) {
            return response()->json(['message' => 'Cannot record stock movements for service items.'], 422);
        }

        $validated = $request->validate([
            'action' => ['required', new Enum(StockMovementAction::class)],
            'quantity' => ['required', 'numeric'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'moved_at' => ['required', 'date'],
        ]);

        $action = StockMovementAction::from($validated['action']);
        $qty = abs((float) $validated['quantity']);

        if (! in_array($action, [StockMovementAction::Receive, StockMovementAction::Issue, StockMovementAction::Adjust])) {
            return response()->json(['message' => 'Use the dedicated write-down/reversal endpoints for NRV adjustments.'], 422);
        }

        $signedQty = match ($action) {
            StockMovementAction::Receive => $qty,
            StockMovementAction::Issue => -$qty,
            StockMovementAction::Adjust => (float) $validated['quantity'],
            default => 0,
        };

        $movement = $inventoryItem->movements()->create([
            'company_id' => $inventoryItem->company_id,
            'action' => $action,
            'quantity' => $signedQty,
            'unit_cost' => $validated['unit_cost'] ?? null,
            'reference' => $validated['reference'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'moved_at' => $validated['moved_at'],
            'created_by' => auth()->id(),
        ]);

        $inventoryItem->increment('quantity_on_hand', $signedQty);

        app(RoadRunnerInventoryPostingDispatcher::class)->dispatch($movement->id, auth()->id());

        return response()->json([
            'success' => true,
            'movement_id' => $movement->id,
            'quantity_on_hand' => (float) $inventoryItem->fresh()->quantity_on_hand,
        ]);
    }

    public function writeDown(InventoryItem $inventoryItem, Request $request): JsonResponse
    {
        $this->authorizeItem($inventoryItem);

        if ($inventoryItem->is_service) {
            return response()->json(['message' => 'Cannot write down a service item.'], 422);
        }

        $validated = $request->validate([
            'nrv_per_unit' => ['required', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $costPerUnit = (float) $inventoryItem->landedCost() ?: ((float) $inventoryItem->purchase_cost ?: (float) $inventoryItem->unit_price);
        $nrv = (float) $validated['nrv_per_unit'];

        if ($nrv >= $costPerUnit) {
            return response()->json([
                'message' => "NRV (R" . number_format($nrv, 2) . ") is not below cost (R" . number_format($costPerUnit, 2) . "). No write-down required.",
            ], 422);
        }

        $qty = (float) $inventoryItem->quantity_on_hand;
        $writeDownAmount = round(($costPerUnit - $nrv) * $qty, 2);
        $previousWriteDown = (float) $inventoryItem->accumulated_write_down;
        $incrementalWriteDown = $writeDownAmount - $previousWriteDown;

        if ($incrementalWriteDown <= 0) {
            return response()->json([
                'message' => 'The calculated write-down is not greater than the existing write-down. Use reverse-write-down if NRV has recovered.',
            ], 422);
        }

        $movement = $inventoryItem->movements()->create([
            'company_id' => $inventoryItem->company_id,
            'action' => StockMovementAction::WriteDown,
            'quantity' => 0,
            'unit_cost' => $incrementalWriteDown,
            'reference' => 'NRV write-down to R' . number_format($nrv, 2) . '/unit',
            'notes' => $validated['notes'] ?? null,
            'moved_at' => $validated['date'],
            'created_by' => auth()->id(),
        ]);

        $inventoryItem->update([
            'nrv_per_unit' => $nrv,
            'accumulated_write_down' => $writeDownAmount,
        ]);

        app(RoadRunnerInventoryPostingDispatcher::class)->dispatch($movement->id, auth()->id());

        return response()->json([
            'success' => true,
            'write_down_amount' => $incrementalWriteDown,
            'accumulated_write_down' => $writeDownAmount,
            'nrv_per_unit' => $nrv,
            'carrying_amount' => $inventoryItem->fresh()->carryingAmount(),
        ]);
    }

    public function reverseWriteDown(InventoryItem $inventoryItem, Request $request): JsonResponse
    {
        $this->authorizeItem($inventoryItem);

        if ($inventoryItem->is_service) {
            return response()->json(['message' => 'Cannot reverse write-down on a service item.'], 422);
        }

        $previousWriteDown = (float) $inventoryItem->accumulated_write_down;

        if ($previousWriteDown <= 0) {
            return response()->json(['message' => 'No write-down exists to reverse.'], 422);
        }

        $validated = $request->validate([
            'new_nrv_per_unit' => ['required', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $costPerUnit = (float) $inventoryItem->landedCost() ?: ((float) $inventoryItem->purchase_cost ?: (float) $inventoryItem->unit_price);
        $newNrv = (float) $validated['new_nrv_per_unit'];
        $qty = (float) $inventoryItem->quantity_on_hand;

        $newWriteDown = $newNrv >= $costPerUnit ? 0.0 : round(($costPerUnit - $newNrv) * $qty, 2);
        $reversalAmount = round($previousWriteDown - $newWriteDown, 2);

        if ($reversalAmount <= 0) {
            return response()->json([
                'message' => 'New NRV does not result in a reversal.',
            ], 422);
        }

        $movement = $inventoryItem->movements()->create([
            'company_id' => $inventoryItem->company_id,
            'action' => StockMovementAction::ReverseWriteDown,
            'quantity' => 0,
            'unit_cost' => $reversalAmount,
            'reference' => 'NRV reversal to R' . number_format($newNrv, 2) . '/unit',
            'notes' => $validated['notes'] ?? null,
            'moved_at' => $validated['date'],
            'created_by' => auth()->id(),
        ]);

        $inventoryItem->update([
            'nrv_per_unit' => $newNrv >= $costPerUnit ? null : $newNrv,
            'accumulated_write_down' => $newWriteDown,
        ]);

        app(RoadRunnerInventoryPostingDispatcher::class)->dispatch($movement->id, auth()->id());

        return response()->json([
            'success' => true,
            'reversal_amount' => $reversalAmount,
            'accumulated_write_down' => $newWriteDown,
            'carrying_amount' => $inventoryItem->fresh()->carryingAmount(),
        ]);
    }

    private function formatItem(InventoryItem $item): array
    {
        $data = [
            'id' => $item->id,
            'name' => $item->name,
            'description' => $item->description,
            'sku' => $item->sku,
            'unit_price' => (float) $item->unit_price,
            'tax_rate' => $item->tax_rate !== null ? (float) $item->tax_rate : null,
            'is_active' => (bool) $item->is_active,
            'is_service' => (bool) $item->is_service,
        ];

        if (! $item->is_service) {
            $data += [
                'inventory_type' => $item->inventory_type?->value,
                'purchase_cost' => $item->purchase_cost !== null ? (float) $item->purchase_cost : null,
                'landed_cost' => (float) $item->landedCost(),
                'quantity_on_hand' => (float) $item->quantity_on_hand,
                'stock_value' => (float) $item->stockValue(),
                'carrying_amount' => (float) $item->carryingAmount(),
                'nrv_per_unit' => $item->nrv_per_unit !== null ? (float) $item->nrv_per_unit : null,
                'accumulated_write_down' => (float) ($item->accumulated_write_down ?? 0),
            ];
        }

        return $data;
    }

    private function authorizeItem(InventoryItem $inventoryItem): void
    {
        $company = $inventoryItem->company;
        abort_unless($company && $company->user_id === auth()->id(), 403);
    }
}
