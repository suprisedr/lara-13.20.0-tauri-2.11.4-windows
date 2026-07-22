<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BiologicalAsset;
use App\Models\BiologicalAssetClass;
use App\Models\BiologicalAssetEvent;
use App\Models\Company;
use App\Services\RoadRunnerBiologicalAssetPostingDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BiologicalAssetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $company = $this->company($request);

        $assets = $company->biologicalAssets()
            ->with('biologicalAssetClass')
            ->orderByDesc('acquisition_date')
            ->get()
            ->map(fn (BiologicalAsset $a) => $this->assetSummary($a));

        return response()->json($assets);
    }

    public function store(Request $request): JsonResponse
    {
        $company = $this->company($request);

        $data = $request->validate([
            'biological_asset_class_id' => ['nullable', 'integer', Rule::exists('biological_asset_classes', 'id')->where('company_id', $company->id)],
            'name'             => ['required', 'string', 'max:150'],
            'reference'        => ['nullable', 'string', 'max:60'],
            'location'         => ['nullable', 'string', 'max:200'],
            'acquisition_date' => ['required', 'date'],
            'quantity'         => ['required', 'numeric', 'min:0'],
            'unit'             => ['required', 'string', 'max:30'],
            'cost'             => ['required', 'numeric', 'min:0'],
            'fair_value'       => ['nullable', 'numeric', 'min:0'],
            'fair_value_date'  => ['nullable', 'date'],
            'notes'            => ['nullable', 'string'],
        ]);

        $asset = $company->biologicalAssets()->create($data);

        $event = BiologicalAssetEvent::create([
            'biological_asset_id' => $asset->id,
            'event_type'          => BiologicalAssetEvent::TYPE_ACQUISITION,
            'event_date'          => $data['acquisition_date'],
            'amount'              => $data['cost'],
            'quantity_change'     => $data['quantity'],
            'description'         => "Acquired: {$asset->name} — {$data['quantity']} {$data['unit']} for R " . number_format((float) $data['cost'], 2),
            'journal_status'      => BiologicalAssetEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerBiologicalAssetPostingDispatcher::class)->dispatch($event->id, $asset->id, auth()->id(), 'acquire', $data);

        return response()->json([
            'success' => true,
            'asset'   => $this->assetSummary($asset->fresh('biologicalAssetClass')),
            'note'    => 'Biological asset created. AI will post the acquisition journal in the background.',
        ], 201);
    }

    public function show(BiologicalAsset $biologicalAsset): JsonResponse
    {
        $this->authorise($biologicalAsset);
        $biologicalAsset->load('biologicalAssetClass');

        return response()->json($this->assetSummary($biologicalAsset));
    }

    public function update(BiologicalAsset $biologicalAsset, Request $request): JsonResponse
    {
        $this->authorise($biologicalAsset);

        $data = $request->validate([
            'name'      => ['sometimes', 'string', 'max:150'],
            'reference' => ['sometimes', 'nullable', 'string', 'max:60'],
            'location'  => ['sometimes', 'nullable', 'string', 'max:200'],
            'quantity'  => ['sometimes', 'numeric', 'min:0'],
            'unit'      => ['sometimes', 'string', 'max:30'],
            'notes'     => ['sometimes', 'nullable', 'string'],
        ]);

        $biologicalAsset->update($data);

        return response()->json(['success' => true, 'asset' => $this->assetSummary($biologicalAsset->fresh('biologicalAssetClass'))]);
    }

    public function fairValueAdjust(BiologicalAsset $biologicalAsset, Request $request): JsonResponse
    {
        $this->authorise($biologicalAsset);

        $data = $request->validate([
            'new_fair_value' => ['required', 'numeric', 'gt:0'],
            'date'           => ['required', 'date'],
        ]);

        $oldValue = (float) ($biologicalAsset->fair_value ?? $biologicalAsset->cost);
        $change   = (float) $data['new_fair_value'] - $oldValue;
        $label    = $change >= 0 ? 'gain' : 'loss';

        $biologicalAsset->update([
            'fair_value'          => $data['new_fair_value'],
            'fair_value_date'     => $data['date'],
            'fair_value_gain_loss' => (float) $biologicalAsset->fair_value_gain_loss + $change,
        ]);

        $event = BiologicalAssetEvent::create([
            'biological_asset_id' => $biologicalAsset->id,
            'event_type'          => BiologicalAssetEvent::TYPE_FAIR_VALUE,
            'event_date'          => $data['date'],
            'amount'              => $data['new_fair_value'],
            'description'         => "Fair value adjusted to R " . number_format((float) $data['new_fair_value'], 2) . " ({$label} of R " . number_format(abs($change), 2) . " to P&L)",
            'journal_status'      => BiologicalAssetEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerBiologicalAssetPostingDispatcher::class)->dispatch($event->id, $biologicalAsset->id, auth()->id(), 'fair_value_adjust', $data);

        return response()->json([
            'success' => true,
            'asset'   => $this->assetSummary($biologicalAsset->fresh('biologicalAssetClass')),
            'event'   => $this->eventSummary($event),
            'note'    => "Fair value adjustment recorded ({$label}). AI will post the journal in the background.",
        ]);
    }

    public function harvest(BiologicalAsset $biologicalAsset, Request $request): JsonResponse
    {
        $this->authorise($biologicalAsset);

        $data = $request->validate([
            'fair_value_at_harvest' => ['required', 'numeric', 'gt:0'],
            'quantity'              => ['required', 'numeric', 'gt:0'],
            'date'                  => ['required', 'date'],
            'description'           => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $biologicalAsset->decrement('quantity', (float) $data['quantity']);

        $event = BiologicalAssetEvent::create([
            'biological_asset_id' => $biologicalAsset->id,
            'event_type'          => BiologicalAssetEvent::TYPE_HARVEST,
            'event_date'          => $data['date'],
            'amount'              => $data['fair_value_at_harvest'],
            'quantity_change'     => -(float) $data['quantity'],
            'description'         => "Harvested {$data['quantity']} {$biologicalAsset->unit} at FVLCTS R " . number_format((float) $data['fair_value_at_harvest'], 2),
            'journal_status'      => BiologicalAssetEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerBiologicalAssetPostingDispatcher::class)->dispatch($event->id, $biologicalAsset->id, auth()->id(), 'harvest', $data);

        return response()->json([
            'success' => true,
            'asset'   => $this->assetSummary($biologicalAsset->fresh('biologicalAssetClass')),
            'event'   => $this->eventSummary($event),
            'note'    => 'Harvest recorded. AI will post the journal in the background.',
        ]);
    }

    public function dispose(BiologicalAsset $biologicalAsset, Request $request): JsonResponse
    {
        $this->authorise($biologicalAsset);

        $data = $request->validate([
            'disposal_date'     => ['required', 'date'],
            'disposal_proceeds' => ['nullable', 'numeric', 'min:0'],
        ]);

        $biologicalAsset->update([
            'disposal_date'     => $data['disposal_date'],
            'disposal_proceeds' => $data['disposal_proceeds'] ?? 0,
            'status'            => BiologicalAsset::STATUS_DISPOSED,
        ]);

        $event = BiologicalAssetEvent::create([
            'biological_asset_id' => $biologicalAsset->id,
            'event_type'          => BiologicalAssetEvent::TYPE_DISPOSAL,
            'event_date'          => $data['disposal_date'],
            'amount'              => $data['disposal_proceeds'] ?? 0,
            'description'         => 'Disposed/sold. Proceeds: R ' . number_format((float) ($data['disposal_proceeds'] ?? 0), 2),
            'journal_status'      => BiologicalAssetEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerBiologicalAssetPostingDispatcher::class)->dispatch($event->id, $biologicalAsset->id, auth()->id(), 'dispose', $data);

        return response()->json([
            'success' => true,
            'asset'   => $this->assetSummary($biologicalAsset->fresh('biologicalAssetClass')),
            'event'   => $this->eventSummary($event),
            'note'    => 'Disposal recorded. AI will post the journal in the background.',
        ]);
    }

    private function company(Request $request): Company
    {
        $company = Company::findOrFail($request->input('company_id'));
        abort_unless($company->user_id === auth()->id(), 403);
        return $company;
    }

    private function authorise(BiologicalAsset $a): void
    {
        abort_unless($a->company->user_id === auth()->id(), 403);
    }

    private function assetSummary(BiologicalAsset $a): array
    {
        return [
            'id'                    => $a->id,
            'name'                  => $a->name,
            'reference'             => $a->reference,
            'location'              => $a->location,
            'acquisition_date'      => $a->acquisition_date?->format('Y-m-d'),
            'quantity'              => (float) $a->quantity,
            'unit'                  => $a->unit,
            'cost'                  => (float) $a->cost,
            'fair_value'            => $a->fair_value !== null ? (float) $a->fair_value : null,
            'fair_value_date'       => $a->fair_value_date?->format('Y-m-d'),
            'fair_value_gain_loss'  => (float) ($a->fair_value_gain_loss ?? 0),
            'accumulated_impairment' => (float) ($a->accumulated_impairment ?? 0),
            'carrying_amount'       => $a->carryingAmount(),
            'status'                => $a->status,
            'class'                 => $a->biologicalAssetClass ? [
                'id'       => $a->biologicalAssetClass->id,
                'name'     => $a->biologicalAssetClass->name,
                'category' => $a->biologicalAssetClass->category,
            ] : null,
        ];
    }

    private function eventSummary(BiologicalAssetEvent $e): array
    {
        return [
            'id'              => $e->id,
            'event_type'      => $e->event_type,
            'event_date'      => $e->event_date->format('Y-m-d'),
            'amount'          => (float) $e->amount,
            'quantity_change'  => (float) $e->quantity_change,
            'journal_status'  => $e->journal_status,
        ];
    }
}
