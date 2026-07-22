<?php

namespace App\Http\Controllers;

use App\Services\RoadRunnerIntangiblePostingDispatcher;
use App\Models\Company;
use App\Models\IntangibleAsset;
use App\Models\IntangibleAssetEvent;
use App\Models\IntangibleClass;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IntangibleAssetController extends Controller
{
    public function show(Company $company, IntangibleAsset $intangible): View
    {
        $this->authorizeCompany($company);
        abort_unless($intangible->company_id === $company->id, 404);

        $intangibleClasses = $company->intangibleClasses()->orderBy('sort_order')->orderBy('name')->get();
        $asset = $intangible;

        return view('companies.intangibles.show', compact('company', 'asset', 'intangibleClasses'));
    }

    public function index(Company $company): View
    {
        $this->authorizeCompany($company);

        $assets = $company->intangibleAssets()
            ->with('intangibleClass')
            ->orderByDesc('acquisition_date')
            ->orderBy('name')
            ->get();

        $intangibleClasses = $company->intangibleClasses()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('companies.intangibles.index', compact('company', 'assets', 'intangibleClasses'));
    }

    public function store(Company $company, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);

        $company->intangibleAssets()->create($this->validateAsset($company, $request));

        return redirect()
            ->route('companies.intangibles.index', $company)
            ->with('success', 'Intangible asset added to the register.');
    }

    public function update(Company $company, IntangibleAsset $intangible, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($intangible->company_id === $company->id, 404);

        $intangible->update($this->validateAsset($company, $request));

        return redirect()
            ->route('companies.intangibles.show', [$company, $intangible])
            ->with('success', 'Intangible asset updated.');
    }

    public function destroy(Company $company, IntangibleAsset $intangible): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($intangible->company_id === $company->id, 404);

        $intangible->delete();

        return redirect()
            ->route('companies.intangibles.index', $company)
            ->with('success', 'Intangible asset removed from the register.');
    }

    public function storeClass(Company $company, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);

        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:120'],
            'useful_life_years'   => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'amortisation_method' => ['nullable', Rule::in(array_keys(IntangibleAsset::METHODS))],
            'accounting_policy'   => ['nullable', Rule::in(array_keys(IntangibleClass::POLICIES))],
            'indefinite_life'     => ['nullable', 'boolean'],
        ]);

        $validated['indefinite_life'] = (bool) ($validated['indefinite_life'] ?? false);

        $maxSort = (int) $company->intangibleClasses()->max('sort_order');
        $company->intangibleClasses()->create($validated + ['sort_order' => $maxSort + 1]);

        return redirect()
            ->route('companies.intangibles.index', $company)
            ->with('success', 'Intangible class added.');
    }

    public function destroyClass(Company $company, IntangibleClass $intangibleClass): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($intangibleClass->company_id === $company->id, 404);

        $intangibleClass->delete();

        return redirect()
            ->route('companies.intangibles.index', $company)
            ->with('success', 'Intangible class removed.');
    }

    // ─── IAS 38 / IAS 36 actions (event + async AI posting) ─────────────

    public function revalue(Company $company, IntangibleAsset $intangible, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($intangible->company_id === $company->id, 404);

        $data = $request->validate([
            'new_carrying_amount' => ['required', 'numeric', 'gt:0'],
            'date'                => ['required', 'date'],
        ]);

        $event = IntangibleAssetEvent::create([
            'intangible_asset_id' => $intangible->id,
            'event_type'          => IntangibleAssetEvent::TYPE_REVALUATION,
            'event_date'          => $data['date'],
            'amount'              => $data['new_carrying_amount'],
            'description'         => 'Revaluation to fair value R '.number_format((float) $data['new_carrying_amount'], 2),
            'journal_status'      => IntangibleAssetEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerIntangiblePostingDispatcher::class)->dispatch($event->id, $intangible->id, auth()->id(), 'revalue', $data);

        return redirect()->route('companies.intangibles.show', [$company, $intangible])
            ->with('success', 'Revaluation recorded. AI is posting the journal in the background.');
    }

    public function impair(Company $company, IntangibleAsset $intangible, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($intangible->company_id === $company->id, 404);

        $data = $request->validate([
            'impairment_amount' => ['required', 'numeric', 'gt:0'],
            'date'              => ['required', 'date'],
            'reason'            => ['nullable', 'string', 'max:500'],
        ]);

        $desc = 'Impairment of R '.number_format((float) $data['impairment_amount'], 2);
        if (! empty($data['reason'])) {
            $desc .= ' — '.$data['reason'];
        }

        $event = IntangibleAssetEvent::create([
            'intangible_asset_id' => $intangible->id,
            'event_type'          => IntangibleAssetEvent::TYPE_IMPAIRMENT,
            'event_date'          => $data['date'],
            'amount'              => $data['impairment_amount'],
            'description'         => $desc,
            'journal_status'      => IntangibleAssetEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerIntangiblePostingDispatcher::class)->dispatch($event->id, $intangible->id, auth()->id(), 'impair', $data);

        return redirect()->route('companies.intangibles.show', [$company, $intangible])
            ->with('success', 'Impairment recorded. AI is posting the journal in the background.');
    }

    public function reverseImpairment(Company $company, IntangibleAsset $intangible, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($intangible->company_id === $company->id, 404);

        $data = $request->validate([
            'reversal_amount' => ['required', 'numeric', 'gt:0'],
            'date'            => ['required', 'date'],
        ]);

        $event = IntangibleAssetEvent::create([
            'intangible_asset_id' => $intangible->id,
            'event_type'          => IntangibleAssetEvent::TYPE_IMPAIRMENT_REVERSAL,
            'event_date'          => $data['date'],
            'amount'              => $data['reversal_amount'],
            'description'         => 'Impairment reversal of R '.number_format((float) $data['reversal_amount'], 2),
            'journal_status'      => IntangibleAssetEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerIntangiblePostingDispatcher::class)->dispatch($event->id, $intangible->id, auth()->id(), 'reverse', $data);

        return redirect()->route('companies.intangibles.show', [$company, $intangible])
            ->with('success', 'Impairment reversal recorded. AI is posting the journal in the background.');
    }

    public function capitalise(Company $company, IntangibleAsset $intangible, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($intangible->company_id === $company->id, 404);

        $data = $request->validate([
            'amount'      => ['required', 'numeric', 'gt:0'],
            'date'        => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $desc = 'Subsequent cost capitalised: R '.number_format((float) $data['amount'], 2);
        if (! empty($data['description'])) {
            $desc .= ' — '.$data['description'];
        }

        $event = IntangibleAssetEvent::create([
            'intangible_asset_id' => $intangible->id,
            'event_type'          => IntangibleAssetEvent::TYPE_CAPITALISATION,
            'event_date'          => $data['date'],
            'amount'              => $data['amount'],
            'description'         => $desc,
            'journal_status'      => IntangibleAssetEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerIntangiblePostingDispatcher::class)->dispatch($event->id, $intangible->id, auth()->id(), 'capitalise', $data);

        return redirect()->route('companies.intangibles.show', [$company, $intangible])
            ->with('success', 'Subsequent cost recorded. AI is posting the journal in the background.');
    }

    public function dispose(Company $company, IntangibleAsset $intangible, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($intangible->company_id === $company->id, 404);

        $data = $request->validate([
            'disposal_date'     => ['required', 'date'],
            'disposal_proceeds' => ['nullable', 'numeric', 'min:0'],
        ]);

        $intangible->update([
            'disposal_date'     => $data['disposal_date'],
            'disposal_proceeds' => $data['disposal_proceeds'] ?? 0,
        ]);

        $event = IntangibleAssetEvent::create([
            'intangible_asset_id' => $intangible->id,
            'event_type'          => IntangibleAssetEvent::TYPE_DISPOSAL,
            'event_date'          => $data['disposal_date'],
            'amount'              => $data['disposal_proceeds'] ?? 0,
            'description'         => 'Intangible disposed. Proceeds: R '.number_format((float) ($data['disposal_proceeds'] ?? 0), 2),
            'journal_status'      => IntangibleAssetEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerIntangiblePostingDispatcher::class)->dispatch($event->id, $intangible->id, auth()->id(), 'dispose', $data);

        return redirect()->route('companies.intangibles.show', [$company, $intangible])
            ->with('success', 'Disposal recorded. AI is posting the journal in the background.');
    }

    public function history(Company $company, IntangibleAsset $intangible): \Illuminate\Http\JsonResponse
    {
        $this->authorizeCompany($company);
        abort_unless($intangible->company_id === $company->id, 404);

        $events = IntangibleAssetEvent::where('intangible_asset_id', $intangible->id)
            ->orderBy('event_date')
            ->orderBy('id')
            ->get();

        $txnIds = $events->pluck('transaction_id')->filter()->unique()->all();
        $txnMap = $txnIds ? \App\Models\Transaction::whereIn('id', $txnIds)->get()->keyBy('id') : collect();

        $events = $events->map(function (IntangibleAssetEvent $e) use ($company, $intangible, $txnMap) {
                $colors = IntangibleAssetEvent::colors()[$e->event_type] ?? ['bg' => '#f3f4f6', 'color' => '#374151'];
                $labels = IntangibleAssetEvent::labels();
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
                    'retry_url'      => $e->journal_status === IntangibleAssetEvent::STATUS_FAILED
                        ? route('companies.intangibles.events.retry-posting', [$company, $intangible, $e])
                        : null,
                ];
            })
            ->values();

        return response()->json(['events' => $events]);
    }

    public function retryPosting(Company $company, IntangibleAsset $intangible, IntangibleAssetEvent $event): \Illuminate\Http\JsonResponse
    {
        $this->authorizeCompany($company);
        abort_unless($intangible->company_id === $company->id, 404);
        abort_unless($event->intangible_asset_id === $intangible->id, 404);
        abort_unless($event->journal_status === IntangibleAssetEvent::STATUS_FAILED, 422);

        $event->update(['journal_status' => IntangibleAssetEvent::STATUS_PENDING]);

        $action = match ($event->event_type) {
            IntangibleAssetEvent::TYPE_CAPITALISATION      => 'capitalise',
            IntangibleAssetEvent::TYPE_REVALUATION         => 'revalue',
            IntangibleAssetEvent::TYPE_IMPAIRMENT          => 'impair',
            IntangibleAssetEvent::TYPE_IMPAIRMENT_REVERSAL => 'reverse',
            default                                        => $event->event_type,
        };

        $date = $event->event_date->format('Y-m-d');
        $data = match ($event->event_type) {
            IntangibleAssetEvent::TYPE_CAPITALISATION      => ['amount' => (string) $event->amount, 'date' => $date, 'description' => $event->description ?? ''],
            IntangibleAssetEvent::TYPE_REVALUATION         => ['new_carrying_amount' => (string) $event->amount, 'date' => $date],
            IntangibleAssetEvent::TYPE_IMPAIRMENT          => ['impairment_amount' => (string) $event->amount, 'date' => $date, 'reason' => $event->description ?? ''],
            IntangibleAssetEvent::TYPE_IMPAIRMENT_REVERSAL => ['reversal_amount' => (string) $event->amount, 'date' => $date],
            default                                        => [],
        };

        app(RoadRunnerIntangiblePostingDispatcher::class)->dispatch($event->id, $intangible->id, auth()->id(), $action, $data);

        return response()->json(['ok' => true]);
    }

    /** @return array<string, mixed> */
    private function validateAsset(Company $company, Request $request): array
    {
        $data = $request->validate([
            'intangible_class_id' => [
                'nullable',
                'integer',
                Rule::exists('intangible_classes', 'id')->where('company_id', $company->id),
            ],
            'name'                   => ['required', 'string', 'max:150'],
            'reference'              => ['nullable', 'string', 'max:60'],
            'category'               => ['nullable', 'string', 'max:120'],
            'acquisition_date'       => ['required', 'date'],
            'cost'                   => ['required', 'numeric', 'min:0'],
            'residual_value'         => ['nullable', 'numeric', 'min:0'],
            'useful_life_years'      => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'useful_life_indefinite' => ['nullable', 'boolean'],
            'amortisation_method'    => ['nullable', Rule::in(array_keys(IntangibleAsset::METHODS))],
            'notes'                  => ['nullable', 'string'],
        ]);

        $data['useful_life_indefinite'] = (bool) ($data['useful_life_indefinite'] ?? false);
        return $data;
    }

    private function authorizeCompany(Company $company): void
    {
        abort_unless($company->user_id === auth()->id(), 403);
    }
}
