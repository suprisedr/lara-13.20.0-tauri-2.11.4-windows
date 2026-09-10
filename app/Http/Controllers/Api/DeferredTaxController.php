<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\DeferredTaxItem;
use App\Models\DeferredTaxEvent;
use App\Services\RoadRunnerDeferredTaxPostingDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeferredTaxController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $company = $this->company($request);

        $items = $company->deferredTaxItems()
            ->orderByDesc('measurement_date')
            ->get()
            ->map(fn (DeferredTaxItem $i) => $this->itemSummary($i));

        return response()->json($items);
    }

    public function store(Request $request): JsonResponse
    {
        $company = $this->company($request);

        $data = $request->validate([
            'name'            => ['required', 'string', 'max:200'],
            'source_type'     => ['required', Rule::in([
                DeferredTaxItem::SOURCE_PPE,
                DeferredTaxItem::SOURCE_INTANGIBLE,
                DeferredTaxItem::SOURCE_LEASE,
                DeferredTaxItem::SOURCE_PROVISION,
                DeferredTaxItem::SOURCE_REVENUE_CONTRACT,
                DeferredTaxItem::SOURCE_INVENTORY,
                DeferredTaxItem::SOURCE_OTHER,
            ])],
            'source_id'       => ['nullable', 'integer'],
            'tax_base'        => ['required', 'numeric', 'min:0'],
            'carrying_amount' => ['required', 'numeric', 'min:0'],
            'tax_rate'        => ['required', 'numeric', 'min:0', 'max:100'],
            'is_taxable'      => ['boolean'],
            'measurement_date'=> ['required', 'date'],
            'notes'           => ['nullable', 'string'],
        ]);

        $temporaryDifference = (float) $data['carrying_amount'] - (float) $data['tax_base'];
        $deferredTax = abs($temporaryDifference) * (float) $data['tax_rate'] / 100;

        $data['temporary_difference']    = $temporaryDifference;
        $data['deferred_tax_asset']      = $temporaryDifference < 0 ? $deferredTax : 0;
        $data['deferred_tax_liability']  = $temporaryDifference > 0 ? $deferredTax : 0;
        $data['is_taxable']              = $data['is_taxable'] ?? true;
        $data['status']                  = DeferredTaxItem::STATUS_ACTIVE;

        $item = $company->deferredTaxItems()->create($data);

        DeferredTaxEvent::create([
            'deferred_tax_item_id' => $item->id,
            'event_type'           => DeferredTaxEvent::TYPE_INITIAL_RECOGNITION,
            'event_date'           => $data['measurement_date'],
            'amount'               => $deferredTax,
            'previous_balance'     => 0,
            'new_balance'          => $deferredTax,
            'description'          => "Initial recognition: {$item->name} — R " . number_format($deferredTax, 2),
            'journal_status'       => DeferredTaxEvent::STATUS_PENDING,
        ]);

        return response()->json([
            'success' => true,
            'item'    => $this->itemSummary($item),
            'note'    => 'Deferred tax item registered.',
        ], 201);
    }

    public function show(DeferredTaxItem $deferredTaxItem): JsonResponse
    {
        $this->authorise($deferredTaxItem);

        return response()->json($this->itemSummary($deferredTaxItem));
    }

    public function update(DeferredTaxItem $deferredTaxItem, Request $request): JsonResponse
    {
        $this->authorise($deferredTaxItem);

        $data = $request->validate([
            'name'     => ['sometimes', 'string', 'max:200'],
            'tax_rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'notes'    => ['sometimes', 'nullable', 'string'],
        ]);

        $deferredTaxItem->update($data);

        return response()->json(['success' => true, 'item' => $this->itemSummary($deferredTaxItem->fresh())]);
    }

    public function remeasure(DeferredTaxItem $deferredTaxItem, Request $request): JsonResponse
    {
        $this->authorise($deferredTaxItem);

        $data = $request->validate([
            'new_carrying_amount' => ['required', 'numeric'],
            'new_tax_base'        => ['required', 'numeric'],
            'date'                => ['required', 'date'],
        ]);

        $previousBalance = $deferredTaxItem->netPosition();

        $temporaryDifference = (float) $data['new_carrying_amount'] - (float) $data['new_tax_base'];
        $deferredTax = abs($temporaryDifference) * (float) $deferredTaxItem->tax_rate / 100;

        $deferredTaxItem->update([
            'carrying_amount'       => $data['new_carrying_amount'],
            'tax_base'              => $data['new_tax_base'],
            'temporary_difference'  => $temporaryDifference,
            'deferred_tax_asset'    => $temporaryDifference < 0 ? $deferredTax : 0,
            'deferred_tax_liability'=> $temporaryDifference > 0 ? $deferredTax : 0,
            'measurement_date'      => $data['date'],
        ]);

        $newBalance = $deferredTaxItem->fresh()->netPosition();

        $event = DeferredTaxEvent::create([
            'deferred_tax_item_id' => $deferredTaxItem->id,
            'event_type'           => DeferredTaxEvent::TYPE_REMEASUREMENT,
            'event_date'           => $data['date'],
            'amount'               => abs($newBalance - $previousBalance),
            'previous_balance'     => $previousBalance,
            'new_balance'          => $newBalance,
            'description'          => "Remeasured: {$deferredTaxItem->name} — R " . number_format($previousBalance, 2) . " → R " . number_format($newBalance, 2),
            'journal_status'       => DeferredTaxEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerDeferredTaxPostingDispatcher::class)->dispatch($event->id, $deferredTaxItem->id, auth()->id(), 'remeasure', $data);

        return response()->json([
            'success' => true,
            'item'    => $this->itemSummary($deferredTaxItem->fresh()),
            'note'    => 'Remeasurement recorded. AI will post the journal in the background.',
        ]);
    }

    public function reverse(DeferredTaxItem $deferredTaxItem, Request $request): JsonResponse
    {
        $this->authorise($deferredTaxItem);

        $data = $request->validate([
            'date' => ['required', 'date'],
        ]);

        $previousBalance = $deferredTaxItem->netPosition();
        $amount = abs($previousBalance);

        $deferredTaxItem->update([
            'status'                => DeferredTaxItem::STATUS_REVERSED,
            'deferred_tax_asset'    => 0,
            'deferred_tax_liability'=> 0,
            'temporary_difference'  => 0,
        ]);

        $event = DeferredTaxEvent::create([
            'deferred_tax_item_id' => $deferredTaxItem->id,
            'event_type'           => DeferredTaxEvent::TYPE_REVERSAL,
            'event_date'           => $data['date'],
            'amount'               => $amount,
            'previous_balance'     => $previousBalance,
            'new_balance'          => 0,
            'description'          => "Reversed: {$deferredTaxItem->name} — R " . number_format($amount, 2),
            'journal_status'       => DeferredTaxEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerDeferredTaxPostingDispatcher::class)->dispatch($event->id, $deferredTaxItem->id, auth()->id(), 'reverse', $data);

        return response()->json([
            'success' => true,
            'item'    => $this->itemSummary($deferredTaxItem->fresh()),
            'note'    => 'Reversal recorded. AI will post the journal in the background.',
        ]);
    }

    private function company(Request $request): Company
    {
        $company = Company::findOrFail($request->input('company_id'));
        abort_unless($company->user_id === auth()->id(), 403);
        return $company;
    }

    private function authorise(DeferredTaxItem $i): void
    {
        abort_unless($i->company->user_id === auth()->id(), 403);
    }

    private function itemSummary(DeferredTaxItem $i): array
    {
        return [
            'id'                     => $i->id,
            'name'                   => $i->name,
            'source_type'            => $i->source_type,
            'source_id'              => $i->source_id,
            'tax_base'               => (float) $i->tax_base,
            'carrying_amount'        => (float) $i->carrying_amount,
            'temporary_difference'   => (float) $i->temporary_difference,
            'deferred_tax_asset'     => (float) $i->deferred_tax_asset,
            'deferred_tax_liability' => (float) $i->deferred_tax_liability,
            'tax_rate'               => (float) $i->tax_rate,
            'is_taxable'             => (bool) $i->is_taxable,
            'measurement_date'       => $i->measurement_date?->format('Y-m-d'),
            'status'                 => $i->status,
            'net_position'           => $i->netPosition(),
            'notes'                  => $i->notes,
        ];
    }
}
