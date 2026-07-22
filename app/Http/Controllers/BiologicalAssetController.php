<?php

namespace App\Http\Controllers;

use App\Models\BiologicalAsset;
use App\Models\BiologicalAssetClass;
use App\Models\BiologicalAssetEvent;
use App\Models\Company;
use App\Services\RoadRunnerBiologicalAssetPostingDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BiologicalAssetController extends Controller
{
    public function index(Company $company): View
    {
        $this->authorizeCompany($company);

        $assets = $company->biologicalAssets()
            ->with('biologicalAssetClass')
            ->orderByDesc('acquisition_date')
            ->orderBy('name')
            ->get();

        $classes = $company->biologicalAssetClasses()->withCount('biologicalAssets')->orderBy('sort_order')->get();

        return view('companies.biological-assets.index', compact('company', 'assets', 'classes'));
    }

    public function show(Company $company, BiologicalAsset $biologicalAsset): View
    {
        $this->authorizeCompany($company);
        abort_unless($biologicalAsset->company_id === $company->id, 404);

        $biologicalAsset->load('biologicalAssetClass');

        return view('companies.biological-assets.show', compact('company', 'biologicalAsset'));
    }

    public function store(Company $company, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);

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

        return redirect()->route('companies.biological-assets.index', $company)
            ->with('success', 'Biological asset added. AI is posting the acquisition journal.');
    }

    public function update(Company $company, BiologicalAsset $biologicalAsset, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($biologicalAsset->company_id === $company->id, 404);

        $data = $request->validate([
            'biological_asset_class_id' => ['nullable', 'integer', Rule::exists('biological_asset_classes', 'id')->where('company_id', $company->id)],
            'name'             => ['required', 'string', 'max:150'],
            'reference'        => ['nullable', 'string', 'max:60'],
            'location'         => ['nullable', 'string', 'max:200'],
            'quantity'         => ['required', 'numeric', 'min:0'],
            'unit'             => ['required', 'string', 'max:30'],
            'notes'            => ['nullable', 'string'],
        ]);

        $biologicalAsset->update($data);

        return redirect()->route('companies.biological-assets.show', [$company, $biologicalAsset])
            ->with('success', 'Biological asset updated.');
    }

    public function destroy(Company $company, BiologicalAsset $biologicalAsset): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($biologicalAsset->company_id === $company->id, 404);

        $biologicalAsset->delete();

        return redirect()->route('companies.biological-assets.index', $company)
            ->with('success', 'Biological asset removed.');
    }

    // ─── Classes ─────────────────────────────────────────────────────────

    public function storeClass(Company $company, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);

        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:120'],
            'category' => ['nullable', Rule::in(array_keys(BiologicalAssetClass::CATEGORIES))],
        ]);

        $maxSort = (int) $company->biologicalAssetClasses()->max('sort_order');
        $company->biologicalAssetClasses()->create($validated + ['sort_order' => $maxSort + 1]);

        return redirect()->route('companies.biological-assets.index', $company)
            ->with('success', 'Biological asset class added.');
    }

    public function destroyClass(Company $company, BiologicalAssetClass $biologicalAssetClass): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($biologicalAssetClass->company_id === $company->id, 404);

        $biologicalAssetClass->delete();

        return redirect()->route('companies.biological-assets.index', $company)
            ->with('success', 'Biological asset class removed.');
    }

    // ─── IAS 41 Actions ──────────────────────────────────────────────────

    public function fairValueAdjust(Company $company, BiologicalAsset $biologicalAsset, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($biologicalAsset->company_id === $company->id, 404);

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

        return redirect()->route('companies.biological-assets.show', [$company, $biologicalAsset])
            ->with('success', 'Fair value adjustment recorded.');
    }

    public function harvest(Company $company, BiologicalAsset $biologicalAsset, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($biologicalAsset->company_id === $company->id, 404);

        $data = $request->validate([
            'fair_value_at_harvest' => ['required', 'numeric', 'gt:0'],
            'quantity'              => ['required', 'numeric', 'gt:0'],
            'date'                  => ['required', 'date'],
            'description'           => ['nullable', 'string', 'max:255'],
        ]);

        $biologicalAsset->decrement('quantity', (float) $data['quantity']);

        $event = BiologicalAssetEvent::create([
            'biological_asset_id' => $biologicalAsset->id,
            'event_type'          => BiologicalAssetEvent::TYPE_HARVEST,
            'event_date'          => $data['date'],
            'amount'              => $data['fair_value_at_harvest'],
            'quantity_change'     => -(float) $data['quantity'],
            'description'         => "Harvested {$data['quantity']} {$biologicalAsset->unit} at FVLCTS R " . number_format((float) $data['fair_value_at_harvest'], 2) . ($data['description'] ? ' — ' . $data['description'] : ''),
            'journal_status'      => BiologicalAssetEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerBiologicalAssetPostingDispatcher::class)->dispatch($event->id, $biologicalAsset->id, auth()->id(), 'harvest', $data);

        return redirect()->route('companies.biological-assets.show', [$company, $biologicalAsset])
            ->with('success', 'Harvest recorded.');
    }

    public function naturalIncrease(Company $company, BiologicalAsset $biologicalAsset, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($biologicalAsset->company_id === $company->id, 404);

        $data = $request->validate([
            'quantity' => ['required', 'numeric', 'gt:0'],
            'date'     => ['required', 'date'],
            'notes'    => ['nullable', 'string', 'max:500'],
        ]);

        $biologicalAsset->increment('quantity', (float) $data['quantity']);

        BiologicalAssetEvent::create([
            'biological_asset_id' => $biologicalAsset->id,
            'event_type'          => BiologicalAssetEvent::TYPE_NATURAL_INCREASE,
            'event_date'          => $data['date'],
            'amount'              => 0,
            'quantity_change'     => (float) $data['quantity'],
            'description'         => "Natural increase: +{$data['quantity']} {$biologicalAsset->unit}" . ($data['notes'] ? ' — ' . $data['notes'] : ''),
            'journal_status'      => BiologicalAssetEvent::STATUS_POSTED,
        ]);

        return redirect()->route('companies.biological-assets.show', [$company, $biologicalAsset])
            ->with('success', 'Natural increase recorded.');
    }

    public function mortality(Company $company, BiologicalAsset $biologicalAsset, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($biologicalAsset->company_id === $company->id, 404);

        $data = $request->validate([
            'quantity' => ['required', 'numeric', 'gt:0'],
            'date'     => ['required', 'date'],
            'notes'    => ['nullable', 'string', 'max:500'],
        ]);

        $biologicalAsset->decrement('quantity', (float) $data['quantity']);

        BiologicalAssetEvent::create([
            'biological_asset_id' => $biologicalAsset->id,
            'event_type'          => BiologicalAssetEvent::TYPE_MORTALITY,
            'event_date'          => $data['date'],
            'amount'              => 0,
            'quantity_change'     => -(float) $data['quantity'],
            'description'         => "Mortality/loss: -{$data['quantity']} {$biologicalAsset->unit}" . ($data['notes'] ? ' — ' . $data['notes'] : ''),
            'journal_status'      => BiologicalAssetEvent::STATUS_POSTED,
        ]);

        return redirect()->route('companies.biological-assets.show', [$company, $biologicalAsset])
            ->with('success', 'Mortality/loss recorded.');
    }

    public function dispose(Company $company, BiologicalAsset $biologicalAsset, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($biologicalAsset->company_id === $company->id, 404);

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

        return redirect()->route('companies.biological-assets.show', [$company, $biologicalAsset])
            ->with('success', 'Disposal recorded.');
    }

    public function history(Company $company, BiologicalAsset $biologicalAsset): JsonResponse
    {
        $this->authorizeCompany($company);
        abort_unless($biologicalAsset->company_id === $company->id, 404);

        $events = BiologicalAssetEvent::where('biological_asset_id', $biologicalAsset->id)
            ->orderBy('event_date')
            ->orderBy('id')
            ->get();

        $txnIds = $events->pluck('transaction_id')->filter()->unique()->all();
        $txnMap = $txnIds ? \App\Models\Transaction::whereIn('id', $txnIds)->get()->keyBy('id') : collect();

        $events = $events->map(function (BiologicalAssetEvent $e) use ($company, $biologicalAsset, $txnMap) {
                $colors = BiologicalAssetEvent::colors()[$e->event_type] ?? ['bg' => '#f3f4f6', 'color' => '#374151'];
                $txnUrl = null;
                if ($e->transaction_id && ($txn = $txnMap->get($e->transaction_id))) {
                    $txnUrl = route('companies.transactions', $company) . '?' . http_build_query([
                        'description' => $txn->reference,
                        'start_date'  => substr($txn->transaction_date, 0, 10),
                        'end_date'    => substr($txn->transaction_date, 0, 10),
                        'highlight'   => $txn->id,
                    ]);
                }
                return [
                    'id'             => $e->id,
                    'type'           => $e->event_type,
                    'label'          => BiologicalAssetEvent::labels()[$e->event_type] ?? $e->event_type,
                    'date'           => $e->event_date->format('d M Y'),
                    'amount'         => number_format((float) $e->amount, 2),
                    'quantity_change' => (float) $e->quantity_change != 0 ? ($e->quantity_change > 0 ? '+' : '') . number_format((float) $e->quantity_change, 2) : null,
                    'description'    => $e->description,
                    'journal_status' => $e->journal_status,
                    'bg'             => $colors['bg'],
                    'color'          => $colors['color'],
                    'transaction_url' => $txnUrl,
                    'retry_url'      => $e->journal_status === BiologicalAssetEvent::STATUS_FAILED
                        ? route('companies.biological-assets.events.retry-posting', [$company, $biologicalAsset, $e])
                        : null,
                ];
            });

        return response()->json($events);
    }

    public function retryPosting(Company $company, BiologicalAsset $biologicalAsset, BiologicalAssetEvent $event): JsonResponse
    {
        $this->authorizeCompany($company);
        abort_unless($biologicalAsset->company_id === $company->id, 404);
        abort_unless($event->biological_asset_id === $biologicalAsset->id, 404);
        abort_unless($event->journal_status === BiologicalAssetEvent::STATUS_FAILED, 422);

        $event->update(['journal_status' => BiologicalAssetEvent::STATUS_PENDING]);

        $action = match ($event->event_type) {
            BiologicalAssetEvent::TYPE_ACQUISITION  => 'acquire',
            BiologicalAssetEvent::TYPE_FAIR_VALUE   => 'fair_value_adjust',
            BiologicalAssetEvent::TYPE_HARVEST      => 'harvest',
            default                                 => $event->event_type,
        };

        $date = $event->event_date->format('Y-m-d');
        $data = match ($event->event_type) {
            BiologicalAssetEvent::TYPE_FAIR_VALUE => ['new_fair_value' => (string) $event->amount, 'date' => $date],
            BiologicalAssetEvent::TYPE_HARVEST    => ['fair_value_at_harvest' => (string) $event->amount, 'quantity' => (string) abs((float) $event->quantity_change), 'date' => $date, 'description' => $event->description ?? ''],
            default                               => [],
        };

        app(RoadRunnerBiologicalAssetPostingDispatcher::class)->dispatch($event->id, $biologicalAsset->id, auth()->id(), $action, $data);

        return response()->json(['ok' => true]);
    }

    private function authorizeCompany(Company $company): void
    {
        abort_unless($company->user_id === auth()->id(), 403);
    }
}
