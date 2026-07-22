<?php

namespace App\Http\Controllers;

use App\Enums\InventoryType;
use App\Enums\StockMovementAction;
use App\Services\RoadRunnerInventoryPostingDispatcher;
use App\Models\Company;
use App\Models\InventoryItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

class InventoryItemController extends Controller
{
    public function index(Company $company): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $inventoryItems = $company->inventoryItems()
            ->orderBy('inventory_type')
            ->orderBy('name')
            ->get();

        return view('companies.inventory.index', compact('company', 'inventoryItems'));
    }

    public function store(Company $company, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $isService = (bool) $request->input('is_service', false);

        $rules = [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'unit_price'  => ['required', 'numeric', 'min:0'],
            'tax_rate'    => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_service'  => ['boolean'],
        ];

        if (! $isService) {
            $rules += [
                'sku'              => ['nullable', 'string', 'max:100'],
                'inventory_type'   => ['nullable', new Enum(InventoryType::class)],
                'purchase_cost'    => ['nullable', 'numeric', 'min:0'],
                'freight_in'       => ['nullable', 'numeric', 'min:0'],
                'import_duties'    => ['nullable', 'numeric', 'min:0'],
                'handling_costs'   => ['nullable', 'numeric', 'min:0'],
                'trade_discount'   => ['nullable', 'numeric', 'min:0'],
                'initial_quantity' => ['nullable', 'numeric', 'min:0'],
            ];
        }

        $validated = $request->validate($rules);

        $data = [
            'name'           => $validated['name'],
            'description'    => $validated['description'] ?? null,
            'unit_price'     => $validated['unit_price'],
            'tax_rate'       => $validated['tax_rate'] ?? null,
            'is_service'     => $isService,
            'is_active'      => true,
            'inventory_type' => $isService ? null : ($validated['inventory_type'] ?? 'merchandise'),
        ];

        if (! $isService) {
            $data += [
                'sku'              => $validated['sku'] ?? null,
                'purchase_cost'    => $validated['purchase_cost'] ?? null,
                'freight_in'       => $validated['freight_in'] ?? null,
                'import_duties'    => $validated['import_duties'] ?? null,
                'handling_costs'   => $validated['handling_costs'] ?? null,
                'trade_discount'   => $validated['trade_discount'] ?? null,
                'initial_quantity' => $validated['initial_quantity'] ?? 0,
                'quantity_on_hand' => $validated['initial_quantity'] ?? 0,
            ];
        }

        $company->inventoryItems()->create($data);

        $label = $isService ? 'Service' : 'Item';

        return redirect()
            ->route('companies.inventory.index', $company)
            ->with('success', "{$label} \"{$validated['name']}\" added.");
    }

    public function update(Company $company, InventoryItem $inventoryItem, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($inventoryItem->company_id === $company->id, 403);

        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string', 'max:500'],
            'sku'              => ['nullable', 'string', 'max:100'],
            'inventory_type'   => ['nullable', new Enum(InventoryType::class)],
            'unit_price'       => ['required', 'numeric', 'min:0'],
            'purchase_cost'    => ['nullable', 'numeric', 'min:0'],
            'freight_in'       => ['nullable', 'numeric', 'min:0'],
            'import_duties'    => ['nullable', 'numeric', 'min:0'],
            'handling_costs'   => ['nullable', 'numeric', 'min:0'],
            'trade_discount'   => ['nullable', 'numeric', 'min:0'],
            'tax_rate'         => ['nullable', 'numeric', 'min:0', 'max:100'],
            'initial_quantity' => ['nullable', 'numeric', 'min:0'],
            'is_active'        => ['boolean'],
        ]);

        // Service items don't have an inventory_type; physical items fall back to existing or merchandise
        if (! $inventoryItem->is_service) {
            $validated['inventory_type'] = $validated['inventory_type']
                ?? $inventoryItem->inventory_type?->value
                ?? 'merchandise';
        } else {
            $validated['inventory_type'] = null;
        }

        $oldInitial = (float) $inventoryItem->initial_quantity;
        $newInitial = (float) ($validated['initial_quantity'] ?? 0);

        $inventoryItem->update($validated);

        if ($newInitial !== $oldInitial) {
            $inventoryItem->increment('quantity_on_hand', $newInitial - $oldInitial);
        }

        return redirect()
            ->route('companies.inventory.index', $company)
            ->with('success', 'Item "' . $validated['name'] . '" updated.');
    }

    public function show(Company $company, InventoryItem $inventoryItem): View
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($inventoryItem->company_id === $company->id, 403);

        $movements = $inventoryItem->movements()
            ->with('invoice')
            ->orderByDesc('moved_at')
            ->orderByDesc('id')
            ->get();

        return view('companies.inventory.show', compact('company', 'inventoryItem', 'movements'));
    }

    public function recordMovement(Company $company, InventoryItem $inventoryItem, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($inventoryItem->company_id === $company->id, 403);

        $validated = $request->validate([
            'action'    => ['required', new Enum(StockMovementAction::class)],
            'quantity'  => ['required', 'numeric'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes'     => ['nullable', 'string', 'max:500'],
            'moved_at'  => ['required', 'date'],
        ]);

        $action = StockMovementAction::from($validated['action']);
        $qty = abs((float) $validated['quantity']);

        $signedQty = match ($action) {
            StockMovementAction::Receive  => $qty,
            StockMovementAction::Issue    => -$qty,
            StockMovementAction::Adjust   => (float) $validated['quantity'],
            StockMovementAction::Transfer => 0,
        };

        $movement = $inventoryItem->movements()->create([
            'company_id' => $company->id,
            'action'     => $action,
            'quantity'   => $signedQty,
            'unit_cost'  => $validated['unit_cost'] ?? null,
            'reference'  => $validated['reference'] ?? null,
            'notes'      => $validated['notes'] ?? null,
            'moved_at'   => $validated['moved_at'],
            'created_by' => auth()->id(),
        ]);

        $inventoryItem->increment('quantity_on_hand', $signedQty);

        app(RoadRunnerInventoryPostingDispatcher::class)->dispatch($movement->id, auth()->id());

        $actionLabel = ucfirst($action->value);

        return redirect()
            ->route('companies.inventory.show', [$company, $inventoryItem])
            ->with('success', "{$actionLabel} recorded — {$inventoryItem->name}.");
    }

    public function writeDown(Company $company, InventoryItem $inventoryItem, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($inventoryItem->company_id === $company->id, 403);

        $validated = $request->validate([
            'nrv_per_unit' => ['required', 'numeric', 'min:0'],
            'date'         => ['required', 'date'],
            'notes'        => ['nullable', 'string', 'max:500'],
        ]);

        $costPerUnit = (float) $inventoryItem->landedCost() ?: ((float) $inventoryItem->purchase_cost ?: (float) $inventoryItem->unit_price);
        $nrv = (float) $validated['nrv_per_unit'];

        if ($nrv >= $costPerUnit) {
            return redirect()
                ->route('companies.inventory.show', [$company, $inventoryItem])
                ->with('error', 'NRV (R' . number_format($nrv, 2) . ') is not below cost (R' . number_format($costPerUnit, 2) . '). No write-down required.');
        }

        $qty = (float) $inventoryItem->quantity_on_hand;
        $writeDownAmount = round(($costPerUnit - $nrv) * $qty, 2);
        $previousWriteDown = (float) $inventoryItem->accumulated_write_down;
        $incrementalWriteDown = $writeDownAmount - $previousWriteDown;

        if ($incrementalWriteDown <= 0) {
            return redirect()
                ->route('companies.inventory.show', [$company, $inventoryItem])
                ->with('error', 'The calculated write-down (R' . number_format($writeDownAmount, 2) . ') is not greater than the existing write-down (R' . number_format($previousWriteDown, 2) . '). Use "Reverse Write-Down" if NRV has recovered.');
        }

        $movement = $inventoryItem->movements()->create([
            'company_id' => $company->id,
            'action'     => StockMovementAction::WriteDown,
            'quantity'   => 0,
            'unit_cost'  => $incrementalWriteDown,
            'reference'  => 'NRV write-down to R' . number_format($nrv, 2) . '/unit',
            'notes'      => $validated['notes'] ?? null,
            'moved_at'   => $validated['date'],
            'created_by' => auth()->id(),
        ]);

        $inventoryItem->update([
            'nrv_per_unit'          => $nrv,
            'accumulated_write_down' => $writeDownAmount,
        ]);

        app(RoadRunnerInventoryPostingDispatcher::class)->dispatch($movement->id, auth()->id());

        return redirect()
            ->route('companies.inventory.show', [$company, $inventoryItem])
            ->with('success', 'IAS 2 write-down of R' . number_format($incrementalWriteDown, 2) . ' recorded — NRV set to R' . number_format($nrv, 2) . '/unit.');
    }

    public function reverseWriteDown(Company $company, InventoryItem $inventoryItem, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($inventoryItem->company_id === $company->id, 403);

        $previousWriteDown = (float) $inventoryItem->accumulated_write_down;

        if ($previousWriteDown <= 0) {
            return redirect()
                ->route('companies.inventory.show', [$company, $inventoryItem])
                ->with('error', 'No write-down exists to reverse.');
        }

        $validated = $request->validate([
            'new_nrv_per_unit' => ['required', 'numeric', 'min:0'],
            'date'             => ['required', 'date'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ]);

        $costPerUnit = (float) $inventoryItem->landedCost() ?: ((float) $inventoryItem->purchase_cost ?: (float) $inventoryItem->unit_price);
        $newNrv = (float) $validated['new_nrv_per_unit'];
        $qty = (float) $inventoryItem->quantity_on_hand;

        $newWriteDown = $newNrv >= $costPerUnit
            ? 0.0
            : round(($costPerUnit - $newNrv) * $qty, 2);

        $reversalAmount = round($previousWriteDown - $newWriteDown, 2);

        if ($reversalAmount <= 0) {
            return redirect()
                ->route('companies.inventory.show', [$company, $inventoryItem])
                ->with('error', 'New NRV does not result in a reversal. The write-down would increase, not decrease.');
        }

        $movement = $inventoryItem->movements()->create([
            'company_id' => $company->id,
            'action'     => StockMovementAction::ReverseWriteDown,
            'quantity'   => 0,
            'unit_cost'  => $reversalAmount,
            'reference'  => 'NRV reversal to R' . number_format($newNrv, 2) . '/unit',
            'notes'      => $validated['notes'] ?? null,
            'moved_at'   => $validated['date'],
            'created_by' => auth()->id(),
        ]);

        $inventoryItem->update([
            'nrv_per_unit'          => $newNrv >= $costPerUnit ? null : $newNrv,
            'accumulated_write_down' => $newWriteDown,
        ]);

        app(RoadRunnerInventoryPostingDispatcher::class)->dispatch($movement->id, auth()->id());

        return redirect()
            ->route('companies.inventory.show', [$company, $inventoryItem])
            ->with('success', 'Write-down reversal of R' . number_format($reversalAmount, 2) . ' recorded (IAS 2.33).');
    }

    public function destroy(Company $company, InventoryItem $inventoryItem): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($inventoryItem->company_id === $company->id, 403);

        $name = $inventoryItem->name;
        $inventoryItem->delete();

        return redirect()
            ->route('companies.inventory.index', $company)
            ->with('success', 'Item "' . $name . '" removed.');
    }
}
