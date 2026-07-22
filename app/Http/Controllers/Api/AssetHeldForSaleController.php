<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetEvent;
use App\Models\AssetHeldForSale;
use App\Models\Company;
use App\Services\RoadRunnerAssetPostingDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetHeldForSaleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $company = $this->company($request);

        $items = $company->assetsHeldForSale()
            ->with('asset.ppeClass')
            ->orderByDesc('reclassification_date')
            ->get()
            ->map(fn (AssetHeldForSale $h) => $this->hfsSummary($h));

        return response()->json($items);
    }

    public function show(AssetHeldForSale $heldForSale): JsonResponse
    {
        $this->authorise($heldForSale);
        $heldForSale->load('asset.ppeClass');

        return response()->json($this->hfsSummary($heldForSale));
    }

    public function reclassify(Asset $asset, Request $request): JsonResponse
    {
        abort_unless($asset->company->user_id === auth()->id(), 403);
        abort_if($asset->status !== Asset::STATUS_ACTIVE, 422, 'Only active assets can be reclassified.');

        $data = $request->validate([
            'reclassification_date'        => ['required', 'date'],
            'fair_value_less_costs_to_sell' => ['nullable', 'numeric', 'min:0'],
            'expected_sale_date'           => ['nullable', 'date', 'after_or_equal:reclassification_date'],
            'buyer_details'                => ['nullable', 'string', 'max:500'],
            'notes'                        => ['nullable', 'string', 'max:1000'],
        ]);

        $carryingAmount = $asset->netBookValue($data['reclassification_date']);
        $fvlcts     = isset($data['fair_value_less_costs_to_sell']) ? (float) $data['fair_value_less_costs_to_sell'] : null;
        $impairment = 0.0;

        if ($fvlcts !== null && $fvlcts < $carryingAmount) {
            $impairment = round($carryingAmount - $fvlcts, 2);
        }

        $hfs = AssetHeldForSale::create([
            'company_id'                          => $asset->company_id,
            'asset_id'                            => $asset->id,
            'reclassification_date'               => $data['reclassification_date'],
            'carrying_amount_at_reclassification' => $carryingAmount,
            'fair_value_less_costs_to_sell'        => $fvlcts,
            'impairment_on_reclassification'      => $impairment,
            'expected_sale_date'                  => $data['expected_sale_date'] ?? null,
            'buyer_details'                       => $data['buyer_details'] ?? null,
            'notes'                               => $data['notes'] ?? null,
            'status'                              => AssetHeldForSale::STATUS_HELD_FOR_SALE,
        ]);

        $asset->update(['status' => Asset::STATUS_HELD_FOR_SALE]);

        $event = AssetEvent::create([
            'asset_id'       => $asset->id,
            'event_type'     => AssetEvent::TYPE_HELD_FOR_SALE,
            'event_date'     => $data['reclassification_date'],
            'amount'         => $carryingAmount,
            'description'    => 'Reclassified as held for sale (IFRS 5). Carrying amount: R ' . number_format($carryingAmount, 2)
                . ($impairment > 0 ? '. Write-down to FVLCTS: R ' . number_format($impairment, 2) : ''),
            'journal_status' => AssetEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerAssetPostingDispatcher::class)->dispatch(
            $event->id, $asset->id, auth()->id(), 'held_for_sale',
            array_merge($data, [
                'carrying_amount' => $carryingAmount,
                'impairment'      => $impairment,
                'hfs_id'          => $hfs->id,
            ])
        );

        return response()->json([
            'success'       => true,
            'held_for_sale' => $this->hfsSummary($hfs->load('asset.ppeClass')),
            'note'          => 'Asset reclassified as held for sale (IFRS 5). AI will post the journal in the background.',
        ], 201);
    }

    public function reverse(AssetHeldForSale $heldForSale, Request $request): JsonResponse
    {
        $this->authorise($heldForSale);
        abort_unless($heldForSale->status === AssetHeldForSale::STATUS_HELD_FOR_SALE, 422);

        $data = $request->validate([
            'reversal_date' => ['required', 'date'],
        ]);

        $asset = $heldForSale->asset;
        $heldForSale->update(['status' => AssetHeldForSale::STATUS_REVERSED]);
        $asset->update(['status' => Asset::STATUS_ACTIVE]);

        $event = AssetEvent::create([
            'asset_id'       => $asset->id,
            'event_type'     => AssetEvent::TYPE_HELD_FOR_SALE_REVERSAL,
            'event_date'     => $data['reversal_date'],
            'amount'         => (float) $heldForSale->carrying_amount_at_reclassification,
            'description'    => 'Held-for-sale classification reversed (IFRS 5.26). Asset returned to PPE register.',
            'journal_status' => AssetEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerAssetPostingDispatcher::class)->dispatch(
            $event->id, $asset->id, auth()->id(), 'held_for_sale_reversal',
            array_merge($data, [
                'hfs_id'                              => $heldForSale->id,
                'carrying_amount_at_reclassification' => (float) $heldForSale->carrying_amount_at_reclassification,
                'impairment_on_reclassification'      => (float) $heldForSale->impairment_on_reclassification,
            ])
        );

        return response()->json([
            'success' => true,
            'note'    => 'Held-for-sale classification reversed. Asset returned to PPE register. AI will post the journal in the background.',
        ]);
    }

    public function dispose(AssetHeldForSale $heldForSale, Request $request): JsonResponse
    {
        $this->authorise($heldForSale);
        abort_unless($heldForSale->status === AssetHeldForSale::STATUS_HELD_FOR_SALE, 422);

        $data = $request->validate([
            'disposal_date'     => ['required', 'date'],
            'disposal_proceeds' => ['nullable', 'numeric', 'min:0'],
        ]);

        $asset = $heldForSale->asset;

        $heldForSale->update([
            'status'            => AssetHeldForSale::STATUS_SOLD,
            'disposal_date'     => $data['disposal_date'],
            'disposal_proceeds' => $data['disposal_proceeds'] ?? 0,
        ]);

        $asset->update([
            'status'            => Asset::STATUS_DISPOSED,
            'disposal_date'     => $data['disposal_date'],
            'disposal_proceeds' => $data['disposal_proceeds'] ?? 0,
        ]);

        $event = AssetEvent::create([
            'asset_id'       => $asset->id,
            'event_type'     => AssetEvent::TYPE_DISPOSAL,
            'event_date'     => $data['disposal_date'],
            'amount'         => $data['disposal_proceeds'] ?? 0,
            'description'    => 'Held-for-sale asset sold. Proceeds: R ' . number_format((float) ($data['disposal_proceeds'] ?? 0), 2),
            'journal_status' => AssetEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerAssetPostingDispatcher::class)->dispatch($event->id, $asset->id, auth()->id(), 'dispose', $data);

        return response()->json([
            'success' => true,
            'note'    => 'Asset sold. AI will post the disposal journal in the background.',
        ]);
    }

    private function company(Request $request): Company
    {
        $company = Company::findOrFail($request->input('company_id'));
        abort_unless($company->user_id === auth()->id(), 403);
        return $company;
    }

    private function authorise(AssetHeldForSale $h): void
    {
        abort_unless($h->company->user_id === auth()->id(), 403);
    }

    private function hfsSummary(AssetHeldForSale $h): array
    {
        return [
            'id'                                  => $h->id,
            'asset_id'                            => $h->asset_id,
            'asset_name'                          => $h->asset->name,
            'asset_tag'                           => $h->asset->asset_tag,
            'ppe_class'                           => $h->asset->ppeClass?->name,
            'reclassification_date'               => $h->reclassification_date->format('Y-m-d'),
            'carrying_amount_at_reclassification' => (float) $h->carrying_amount_at_reclassification,
            'fair_value_less_costs_to_sell'        => $h->fair_value_less_costs_to_sell !== null ? (float) $h->fair_value_less_costs_to_sell : null,
            'impairment_on_reclassification'      => (float) $h->impairment_on_reclassification,
            'ifrs5_carrying_amount'               => $h->carryingAmount(),
            'expected_sale_date'                  => $h->expected_sale_date?->format('Y-m-d'),
            'buyer_details'                       => $h->buyer_details,
            'status'                              => $h->status,
            'disposal_date'                       => $h->disposal_date?->format('Y-m-d'),
            'disposal_proceeds'                   => $h->disposal_proceeds !== null ? (float) $h->disposal_proceeds : null,
        ];
    }
}
