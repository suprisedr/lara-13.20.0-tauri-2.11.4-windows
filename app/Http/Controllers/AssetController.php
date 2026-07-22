<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetEvent;
use App\Models\Company;
use App\Models\PpeClass;
use App\Services\AssetPostingService;
use App\Services\RoadRunnerAssetPostingDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function show(Company $company, Asset $asset): View
    {
        $this->authorizeCompany($company);
        abort_unless($asset->company_id === $company->id, 404);

        $ppeClasses = $company->ppeClasses()->orderBy('sort_order')->orderBy('name')->get();

        return view('companies.assets.show', compact('company', 'asset', 'ppeClasses'));
    }

    public function index(Company $company, Request $request): View
    {
        $this->authorizeCompany($company);

        $asOf = $request->input('as_of', now()->format('Y-m-d'));

        $assets = $company->assets()
            ->with('ppeClass')
            ->orderByDesc('acquisition_date')
            ->orderBy('name')
            ->get();

        $ppeClasses = $company->ppeClasses()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('companies.assets.index', compact('company', 'assets', 'ppeClasses', 'asOf'));
    }

    public function store(Company $company, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);

        $asset = $company->assets()->create($this->validateAsset($company, $request));

        AssetEvent::create([
            'asset_id'       => $asset->id,
            'event_type'     => AssetEvent::TYPE_ACQUISITION,
            'event_date'     => $asset->acquisition_date?->format('Y-m-d') ?? now()->toDateString(),
            'amount'         => $asset->cost,
            'description'    => 'Initial recognition at cost R '.number_format((float) $asset->cost, 2),
            'journal_status' => AssetEvent::STATUS_PENDING,
        ]);

        return redirect()
            ->route('companies.assets.index', $company)
            ->with('success', 'Asset added to the register.');
    }

    public function update(Company $company, Asset $asset, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($asset->company_id === $company->id, 404);

        $asset->update($this->validateAsset($company, $request));

        return redirect()
            ->route('companies.assets.show', [$company, $asset])
            ->with('success', 'Asset updated.');
    }

    public function destroy(Company $company, Asset $asset): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($asset->company_id === $company->id, 404);

        $asset->delete();

        return redirect()
            ->route('companies.assets.index', $company)
            ->with('success', 'Asset removed from the register.');
    }

    public function storeClass(Company $company, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);

        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:120'],
            'useful_life_years'   => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'depreciation_method' => ['nullable', Rule::in(array_keys(Asset::METHODS))],
            'accounting_policy'   => ['nullable', Rule::in(array_keys(\App\Models\PpeClass::POLICIES))],
        ]);

        $maxSort = (int) $company->ppeClasses()->max('sort_order');
        $company->ppeClasses()->create($validated + ['sort_order' => $maxSort + 1]);

        return redirect()
            ->route('companies.assets.index', $company)
            ->with('success', 'PPE class added.');
    }

    public function destroyClass(Company $company, PpeClass $ppeClass): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($ppeClass->company_id === $company->id, 404);

        $ppeClass->delete();

        return redirect()
            ->route('companies.assets.index', $company)
            ->with('success', 'PPE class removed.');
    }

    // ─── IAS 16 / IAS 36 actions ─────────────────────────────────────────

    public function revalue(Company $company, Asset $asset, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($asset->company_id === $company->id, 404);

        $data = $request->validate([
            'new_carrying_amount' => ['required', 'numeric', 'gt:0'],
            'date'                => ['required', 'date'],
        ]);

        $event = AssetEvent::create([
            'asset_id'       => $asset->id,
            'event_type'     => AssetEvent::TYPE_REVALUATION,
            'event_date'     => $data['date'],
            'amount'         => $data['new_carrying_amount'],
            'description'    => 'Revaluation to fair value R '.number_format((float) $data['new_carrying_amount'], 2),
            'journal_status' => AssetEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerAssetPostingDispatcher::class)->dispatch($event->id, $asset->id, auth()->id(), 'revalue', $data);

        return redirect()->route('companies.assets.show', [$company, $asset])
            ->with('success', 'Revaluation recorded. AI is posting the journal in the background.');
    }

    public function impair(Company $company, Asset $asset, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($asset->company_id === $company->id, 404);

        $data = $request->validate([
            'impairment_amount' => ['required', 'numeric', 'gt:0'],
            'date'              => ['required', 'date'],
            'reason'            => ['nullable', 'string', 'max:500'],
        ]);

        $desc = 'Impairment of R '.number_format((float) $data['impairment_amount'], 2);
        if (! empty($data['reason'])) {
            $desc .= ' — '.$data['reason'];
        }

        $event = AssetEvent::create([
            'asset_id'       => $asset->id,
            'event_type'     => AssetEvent::TYPE_IMPAIRMENT,
            'event_date'     => $data['date'],
            'amount'         => $data['impairment_amount'],
            'description'    => $desc,
            'journal_status' => AssetEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerAssetPostingDispatcher::class)->dispatch($event->id, $asset->id, auth()->id(), 'impair', $data);

        return redirect()->route('companies.assets.show', [$company, $asset])
            ->with('success', 'Impairment recorded. AI is posting the journal in the background.');
    }

    public function reverseImpairment(Company $company, Asset $asset, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($asset->company_id === $company->id, 404);

        $data = $request->validate([
            'reversal_amount' => ['required', 'numeric', 'gt:0'],
            'date'            => ['required', 'date'],
        ]);

        $event = AssetEvent::create([
            'asset_id'       => $asset->id,
            'event_type'     => AssetEvent::TYPE_IMPAIRMENT_REVERSAL,
            'event_date'     => $data['date'],
            'amount'         => $data['reversal_amount'],
            'description'    => 'Impairment reversal of R '.number_format((float) $data['reversal_amount'], 2),
            'journal_status' => AssetEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerAssetPostingDispatcher::class)->dispatch($event->id, $asset->id, auth()->id(), 'reverse', $data);

        return redirect()->route('companies.assets.show', [$company, $asset])
            ->with('success', 'Impairment reversal recorded. AI is posting the journal in the background.');
    }

    public function history(Company $company, Asset $asset): \Illuminate\Http\JsonResponse
    {
        $this->authorizeCompany($company);
        abort_unless($asset->company_id === $company->id, 404);

        $events = AssetEvent::where('asset_id', $asset->id)
            ->orderBy('event_date')
            ->orderBy('id')
            ->get();

        $txnIds = $events->pluck('transaction_id')->filter()->unique()->all();
        $txnMap = $txnIds ? \App\Models\Transaction::whereIn('id', $txnIds)->get()->keyBy('id') : collect();

        $events = $events->map(function (AssetEvent $e) use ($company, $asset, $txnMap) {
                $colors = AssetEvent::colors()[$e->event_type] ?? ['bg' => '#f3f4f6', 'color' => '#374151'];
                $labels = AssetEvent::labels();
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
                    'event_type'     => $e->event_type,
                    'label'          => $labels[$e->event_type] ?? ucfirst($e->event_type),
                    'bg'             => $colors['bg'],
                    'color'          => $colors['color'],
                    'date'           => $e->event_date->format('Y-m-d'),
                    'amount'         => (float) ($e->amount ?? 0),
                    'description'    => $e->description,
                    'journal_status' => $e->journal_status,
                    'transaction_url' => $txnUrl,
                    'retry_url'      => $e->journal_status === AssetEvent::STATUS_FAILED
                        ? route('companies.assets.events.retry-posting', [$company, $asset, $e])
                        : null,
                ];
            })
            ->values();

        return response()->json(['events' => $events]);
    }

    public function dispose(Company $company, Asset $asset, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($asset->company_id === $company->id, 404);

        $data = $request->validate([
            'disposal_date'     => ['required', 'date'],
            'disposal_proceeds' => ['nullable', 'numeric', 'min:0'],
        ]);

        $asset->update([
            'disposal_date'     => $data['disposal_date'],
            'disposal_proceeds' => $data['disposal_proceeds'] ?? 0,
        ]);

        // AssetObserver::updated() fires AssetDisposed which triggers
        // PostAssetDisposalWithAi — no separate dispatch needed here.
        AssetEvent::create([
            'asset_id'       => $asset->id,
            'event_type'     => AssetEvent::TYPE_DISPOSAL,
            'event_date'     => $data['disposal_date'],
            'amount'         => $data['disposal_proceeds'] ?? 0,
            'description'    => 'Asset disposed. Proceeds: R '.number_format((float) ($data['disposal_proceeds'] ?? 0), 2),
            'journal_status' => AssetEvent::STATUS_PENDING,
        ]);

        return redirect()->route('companies.assets.show', [$company, $asset])
            ->with('success', 'Disposal recorded. AI is posting the journal in the background.');
    }

    public function capitalise(Company $company, Asset $asset, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($asset->company_id === $company->id, 404);

        $data = $request->validate([
            'amount'      => ['required', 'numeric', 'gt:0'],
            'date'        => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $desc = 'Subsequent cost capitalised: R '.number_format((float) $data['amount'], 2);
        if (! empty($data['description'])) {
            $desc .= ' — '.$data['description'];
        }

        $event = AssetEvent::create([
            'asset_id'       => $asset->id,
            'event_type'     => AssetEvent::TYPE_CAPITALISATION,
            'event_date'     => $data['date'],
            'amount'         => $data['amount'],
            'description'    => $desc,
            'journal_status' => AssetEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerAssetPostingDispatcher::class)->dispatch($event->id, $asset->id, auth()->id(), 'capitalise', $data);

        return redirect()->route('companies.assets.show', [$company, $asset])
            ->with('success', 'Subsequent cost recorded. AI is posting the journal in the background.');
    }

    public function retryPosting(Company $company, Asset $asset, AssetEvent $event): \Illuminate\Http\JsonResponse
    {
        $this->authorizeCompany($company);
        abort_unless($asset->company_id === $company->id, 404);
        abort_unless($event->asset_id === $asset->id, 404);
        abort_unless($event->journal_status === AssetEvent::STATUS_FAILED, 422);

        $event->update(['journal_status' => AssetEvent::STATUS_PENDING]);

        $action = match ($event->event_type) {
            AssetEvent::TYPE_ACQUISITION            => 'acquire',
            AssetEvent::TYPE_CAPITALISATION         => 'capitalise',
            AssetEvent::TYPE_REVALUATION            => 'revalue',
            AssetEvent::TYPE_IMPAIRMENT             => 'impair',
            AssetEvent::TYPE_IMPAIRMENT_REVERSAL    => 'reverse',
            AssetEvent::TYPE_HELD_FOR_SALE          => 'held_for_sale',
            AssetEvent::TYPE_HELD_FOR_SALE_REVERSAL => 'held_for_sale_reversal',
            default                                 => $event->event_type, // disposal
        };

        $date = $event->event_date->format('Y-m-d');
        $data = match ($event->event_type) {
            AssetEvent::TYPE_CAPITALISATION         => ['amount' => (string) $event->amount, 'date' => $date, 'description' => $event->description ?? ''],
            AssetEvent::TYPE_REVALUATION            => ['new_carrying_amount' => (string) $event->amount, 'date' => $date],
            AssetEvent::TYPE_IMPAIRMENT             => ['impairment_amount' => (string) $event->amount, 'date' => $date, 'reason' => $event->description ?? ''],
            AssetEvent::TYPE_IMPAIRMENT_REVERSAL    => ['reversal_amount' => (string) $event->amount, 'date' => $date],
            AssetEvent::TYPE_HELD_FOR_SALE          => ['carrying_amount' => (string) $event->amount, 'impairment' => '0', 'reclassification_date' => $date],
            AssetEvent::TYPE_HELD_FOR_SALE_REVERSAL => ['carrying_amount_at_reclassification' => (string) $event->amount, 'impairment_on_reclassification' => '0', 'reversal_date' => $date],
            default                                 => [],
        };

        app(RoadRunnerAssetPostingDispatcher::class)->dispatch($event->id, $asset->id, auth()->id(), $action, $data);

        return response()->json(['ok' => true]);
    }

    /** @return array<string, mixed> */
    private function validateAsset(Company $company, Request $request): array
    {
        return $request->validate([
            'ppe_class_id' => [
                'nullable',
                'integer',
                Rule::exists('ppe_classes', 'id')->where('company_id', $company->id),
            ],
            'name'                => ['required', 'string', 'max:150'],
            'asset_tag'           => ['nullable', 'string', 'max:60'],
            'location'            => ['nullable', 'string', 'max:120'],
            'acquisition_date'    => ['required', 'date'],
            'depreciation_start_date' => ['nullable', 'date', 'after_or_equal:acquisition_date'],
            'cost'                => ['required', 'numeric', 'min:0'],
            'residual_value'      => ['nullable', 'numeric', 'min:0'],
            'useful_life_years'   => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'sars_wear_tear_years' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'depreciation_method' => ['nullable', Rule::in(array_keys(Asset::METHODS))],
            'disposal_date'       => ['nullable', 'date'],
            'disposal_proceeds'   => ['nullable', 'numeric', 'min:0'],
            'notes'               => ['nullable', 'string'],
        ]);
    }

    private function authorizeCompany(Company $company): void
    {
        abort_unless($company->user_id === auth()->id(), 403);
    }
}
