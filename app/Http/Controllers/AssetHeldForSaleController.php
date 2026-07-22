<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetEvent;
use App\Models\AssetHeldForSale;
use App\Models\Company;
use App\Services\RoadRunnerAssetPostingDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetHeldForSaleController extends Controller
{
    public function index(Company $company): View
    {
        $this->authorizeCompany($company);

        $items = $company->assetsHeldForSale()
            ->with('asset.ppeClass')
            ->orderByDesc('reclassification_date')
            ->get();

        return view('companies.held-for-sale.index', compact('company', 'items'));
    }

    public function show(Company $company, AssetHeldForSale $heldForSale): View
    {
        $this->authorizeCompany($company);
        abort_unless($heldForSale->company_id === $company->id, 404);

        $heldForSale->load('asset.ppeClass');

        return view('companies.held-for-sale.show', compact('company', 'heldForSale'));
    }

    /**
     * Reclassify a PPE asset as held for sale (IFRS 5).
     * Called from the asset show page.
     */
    public function reclassify(Company $company, Asset $asset, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($asset->company_id === $company->id, 404);
        abort_if($asset->status !== Asset::STATUS_ACTIVE, 422, 'Only active assets can be reclassified.');

        $data = $request->validate([
            'reclassification_date'         => ['required', 'date'],
            'fair_value_less_costs_to_sell'  => ['nullable', 'numeric', 'min:0'],
            'expected_sale_date'             => ['nullable', 'date', 'after_or_equal:reclassification_date'],
            'buyer_details'                 => ['nullable', 'string', 'max:500'],
            'notes'                         => ['nullable', 'string', 'max:1000'],
        ]);

        $carryingAmount = $asset->netBookValue($data['reclassification_date']);
        $fvlcts = isset($data['fair_value_less_costs_to_sell']) ? (float) $data['fair_value_less_costs_to_sell'] : null;
        $impairment = 0.0;

        if ($fvlcts !== null && $fvlcts < $carryingAmount) {
            $impairment = round($carryingAmount - $fvlcts, 2);
        }

        $hfs = AssetHeldForSale::create([
            'company_id'                          => $company->id,
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

        return redirect()->route('companies.held-for-sale.show', [$company, $hfs])
            ->with('success', 'Asset reclassified as held for sale (IFRS 5). AI is posting the journal.');
    }

    /**
     * Reverse the held-for-sale classification back to PPE.
     */
    public function reverse(Company $company, AssetHeldForSale $heldForSale, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($heldForSale->company_id === $company->id, 404);
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

        return redirect()->route('companies.assets.show', [$company, $asset])
            ->with('success', 'Asset returned to PPE register. AI is posting the reversal journal.');
    }

    /**
     * Dispose the held-for-sale asset (actual sale).
     */
    public function dispose(Company $company, AssetHeldForSale $heldForSale, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($heldForSale->company_id === $company->id, 404);
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

        app(RoadRunnerAssetPostingDispatcher::class)->dispatch(
            $event->id, $asset->id, auth()->id(), 'dispose',
            $data
        );

        return redirect()->route('companies.held-for-sale.show', [$company, $heldForSale])
            ->with('success', 'Asset sold. AI is posting the disposal journal.');
    }

    public function update(Company $company, AssetHeldForSale $heldForSale, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($heldForSale->company_id === $company->id, 404);

        $validated = $request->validate([
            'fair_value_less_costs_to_sell' => ['nullable', 'numeric', 'min:0'],
            'expected_sale_date'           => ['nullable', 'date'],
            'buyer_details'                => ['nullable', 'string', 'max:500'],
            'notes'                        => ['nullable', 'string', 'max:1000'],
        ]);

        $heldForSale->update($validated);

        return redirect()->route('companies.held-for-sale.show', [$company, $heldForSale])
            ->with('success', 'Held-for-sale details updated.');
    }

    private function authorizeCompany(Company $company): void
    {
        abort_unless($company->user_id === auth()->id(), 403);
    }
}
