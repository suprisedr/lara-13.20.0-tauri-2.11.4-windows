<?php

namespace App\Services;

use App\Enums\InventoryType;
use App\Enums\StockMovementAction;
use App\Models\Company;
use Carbon\Carbon;

/**
 * Builds the IAS 2 inventory movement schedule for the notes to the AFS,
 * grouped by inventory type (raw materials, WIP, finished goods, etc.).
 *
 * Source of truth: the INVENTORY REGISTER (inventory_items + inventory_movements).
 */
class InventoryMovementService
{
    public function build(Company $company, string $startDate, string $endDate): array
    {
        $dayBefore = Carbon::parse($startDate)->subDay()->format('Y-m-d');

        $items = $company->inventoryItems()
            ->where('is_service', false)
            ->with(['movements' => fn($q) => $q->orderBy('moved_at')])
            ->get();

        if ($items->isEmpty()) {
            return [];
        }

        $typeLabels = [
            'raw_material'   => 'Raw Materials',
            'wip'            => 'Work in Progress',
            'finished_goods' => 'Finished Goods',
            'merchandise'    => 'Merchandise',
            'consumable'     => 'Consumables',
            'unclassified'   => 'Unclassified',
        ];

        $grouped = $items->groupBy(fn($i) => $i->inventory_type?->value ?? 'unclassified');

        $rows = [];

        foreach ($grouped as $typeKey => $typeItems) {
            $openingQty = 0.0;
            $openingVal = 0.0;
            $qtyReceived = 0.0;
            $costReceived = 0.0;
            $qtyIssued = 0.0;
            $costIssued = 0.0;
            $qtyAdjusted = 0.0;
            $costAdjusted = 0.0;
            $writeDowns = 0.0;
            $reversals = 0.0;

            foreach ($typeItems as $item) {
                $unitCost = (float) ($item->purchase_cost ?: $item->unit_price);
                $itemOpeningQty = (float) $item->initial_quantity;
                $itemOpeningVal = $itemOpeningQty * $unitCost;

                // Movements before the period adjust the opening position
                $prePeriod = $item->movements->filter(
                    fn($m) => $m->moved_at->toDateString() < $startDate
                );
                foreach ($prePeriod as $m) {
                    $mAction = $m->action;
                    $mQty = (float) $m->quantity;
                    $mCost = (float) ($m->unit_cost ?? $unitCost);

                    if ($mAction === StockMovementAction::Receive) {
                        $itemOpeningQty += $mQty;
                        $itemOpeningVal += abs($mQty) * $mCost;
                    } elseif ($mAction === StockMovementAction::Issue) {
                        $itemOpeningQty += $mQty; // negative
                        $itemOpeningVal -= abs($mQty) * $mCost;
                    } elseif ($mAction === StockMovementAction::Adjust) {
                        $itemOpeningQty += $mQty;
                        $itemOpeningVal += $mQty * $mCost;
                    } elseif ($mAction === StockMovementAction::WriteDown) {
                        $itemOpeningVal -= (float) $m->unit_cost;
                    } elseif ($mAction === StockMovementAction::ReverseWriteDown) {
                        $itemOpeningVal += (float) $m->unit_cost;
                    }
                }

                $openingQty += $itemOpeningQty;
                $openingVal += $itemOpeningVal;

                // Movements in the period
                $inPeriod = $item->movements->filter(
                    fn($m) => $m->moved_at->toDateString() >= $startDate
                        && $m->moved_at->toDateString() <= $endDate
                );

                foreach ($inPeriod as $m) {
                    $mAction = $m->action;
                    $mQty = (float) $m->quantity;
                    $mCost = (float) ($m->unit_cost ?? $unitCost);

                    match ($mAction) {
                        StockMovementAction::Receive => (function () use (&$qtyReceived, &$costReceived, $mQty, $mCost) {
                            $qtyReceived += $mQty;
                            $costReceived += abs($mQty) * $mCost;
                        })(),
                        StockMovementAction::Issue => (function () use (&$qtyIssued, &$costIssued, $mQty, $mCost) {
                            $qtyIssued += abs($mQty);
                            $costIssued += abs($mQty) * $mCost;
                        })(),
                        StockMovementAction::Adjust => (function () use (&$qtyAdjusted, &$costAdjusted, $mQty, $mCost) {
                            $qtyAdjusted += $mQty;
                            $costAdjusted += $mQty * $mCost;
                        })(),
                        StockMovementAction::WriteDown => (function () use (&$writeDowns, $m) {
                            $writeDowns += (float) $m->unit_cost;
                        })(),
                        StockMovementAction::ReverseWriteDown => (function () use (&$reversals, $m) {
                            $reversals += (float) $m->unit_cost;
                        })(),
                        default => null,
                    };
                }
            }

            $closingQty = $openingQty + $qtyReceived - $qtyIssued + $qtyAdjusted;
            $closingVal = $openingVal + $costReceived - $costIssued + $costAdjusted;
            $carryingVal = $closingVal - $writeDowns + $reversals;

            $rows[] = [
                'type_key'       => $typeKey,
                'type_label'     => $typeLabels[$typeKey] ?? ucfirst($typeKey),
                'item_count'     => $typeItems->count(),

                'opening_qty'    => round($openingQty, 2),
                'opening_val'    => round($openingVal, 2),

                'qty_received'   => round($qtyReceived, 2),
                'cost_received'  => round($costReceived, 2),
                'qty_issued'     => round($qtyIssued, 2),
                'cost_issued'    => round($costIssued, 2),
                'qty_adjusted'   => round($qtyAdjusted, 2),
                'cost_adjusted'  => round($costAdjusted, 2),

                'write_downs'    => round($writeDowns, 2),
                'reversals'      => round($reversals, 2),

                'closing_qty'    => round($closingQty, 2),
                'closing_val'    => round($closingVal, 2),
                'carrying_val'   => round($carryingVal, 2),
            ];
        }

        return collect($rows)
            ->sortBy('type_key')
            ->values()
            ->all();
    }
}
