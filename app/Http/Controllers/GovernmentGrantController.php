<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\GovernmentGrant;
use App\Models\GovernmentGrantEvent;
use App\Services\RoadRunnerGovernmentGrantPostingDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GovernmentGrantController extends Controller
{
    public function index(Company $company): View
    {
        $this->authorizeCompany($company);

        $grants = $company->governmentGrants()
            ->orderByDesc('grant_date')
            ->orderBy('name')
            ->get();

        return view('companies.government-grants.index', compact('company', 'grants'));
    }

    public function show(Company $company, GovernmentGrant $governmentGrant): View
    {
        $this->authorizeCompany($company);
        abort_unless($governmentGrant->company_id === $company->id, 404);

        return view('companies.government-grants.show', compact('company', 'governmentGrant'));
    }

    public function store(Company $company, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);

        $data = $request->validate([
            'name'               => ['required', 'string', 'max:200'],
            'grant_type'         => ['required', Rule::in([GovernmentGrant::TYPE_INCOME, GovernmentGrant::TYPE_ASSET])],
            'grant_reference'    => ['nullable', 'string', 'max:80'],
            'granting_authority' => ['nullable', 'string', 'max:200'],
            'grant_date'         => ['required', 'date'],
            'total_amount'       => ['required', 'numeric', 'min:0'],
            'related_asset_type' => ['nullable', 'string', 'max:100'],
            'related_asset_id'   => ['nullable', 'integer'],
            'recognition_method' => ['nullable', Rule::in([GovernmentGrant::METHOD_SYSTEMATIC, GovernmentGrant::METHOD_IMMEDIATE])],
            'conditions_text'    => ['nullable', 'string'],
            'notes'              => ['nullable', 'string'],
        ]);

        $data['deferred_amount'] = $data['total_amount'];
        $data['recognition_method'] = $data['recognition_method'] ?? GovernmentGrant::METHOD_SYSTEMATIC;

        $grant = $company->governmentGrants()->create($data);

        return redirect()->route('companies.government-grants.index', $company)
            ->with('success', 'Government grant registered.');
    }

    public function update(Company $company, GovernmentGrant $governmentGrant, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($governmentGrant->company_id === $company->id, 404);

        $data = $request->validate([
            'name'               => ['required', 'string', 'max:200'],
            'granting_authority' => ['nullable', 'string', 'max:200'],
            'conditions_text'    => ['nullable', 'string'],
            'notes'              => ['nullable', 'string'],
        ]);

        $governmentGrant->update($data);

        return redirect()->route('companies.government-grants.show', [$company, $governmentGrant])
            ->with('success', 'Grant updated.');
    }

    public function destroy(Company $company, GovernmentGrant $governmentGrant): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($governmentGrant->company_id === $company->id, 404);

        $governmentGrant->delete();

        return redirect()->route('companies.government-grants.index', $company)
            ->with('success', 'Grant removed.');
    }

    // ─── IAS 20 Actions ─────────────────────────────────────────────────

    public function recognise(Company $company, GovernmentGrant $governmentGrant, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($governmentGrant->company_id === $company->id, 404);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'date'   => ['required', 'date'],
        ]);

        $governmentGrant->update([
            'deferred_amount' => (float) $governmentGrant->deferred_amount,
        ]);

        $event = GovernmentGrantEvent::create([
            'government_grant_id' => $governmentGrant->id,
            'event_type'          => GovernmentGrantEvent::TYPE_RECOGNITION,
            'event_date'          => $data['date'],
            'amount'              => $data['amount'],
            'description'         => "Grant recognised: {$governmentGrant->name} — R " . number_format((float) $data['amount'], 2),
            'journal_status'      => GovernmentGrantEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerGovernmentGrantPostingDispatcher::class)->dispatch($event->id, $governmentGrant->id, auth()->id(), 'recognise', $data);

        return redirect()->route('companies.government-grants.show', [$company, $governmentGrant])
            ->with('success', 'Recognition recorded. AI is posting the journal.');
    }

    public function amortise(Company $company, GovernmentGrant $governmentGrant, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($governmentGrant->company_id === $company->id, 404);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'date'   => ['required', 'date'],
        ]);

        $newRecognised = (float) $governmentGrant->recognised_amount + (float) $data['amount'];
        $newDeferred   = max((float) $governmentGrant->deferred_amount - (float) $data['amount'], 0);

        $governmentGrant->update([
            'recognised_amount' => $newRecognised,
            'deferred_amount'   => $newDeferred,
        ]);

        if ($newDeferred <= 0) {
            $governmentGrant->update([
                'status'          => GovernmentGrant::STATUS_FULFILLED,
                'fulfilment_date' => $data['date'],
            ]);
        }

        $event = GovernmentGrantEvent::create([
            'government_grant_id' => $governmentGrant->id,
            'event_type'          => GovernmentGrantEvent::TYPE_AMORTISATION,
            'event_date'          => $data['date'],
            'amount'              => $data['amount'],
            'description'         => "Amortised R " . number_format((float) $data['amount'], 2) . " to income" . ($newDeferred <= 0 ? ' (fully recognised)' : ''),
            'journal_status'      => GovernmentGrantEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerGovernmentGrantPostingDispatcher::class)->dispatch($event->id, $governmentGrant->id, auth()->id(), 'amortise', $data);

        return redirect()->route('companies.government-grants.show', [$company, $governmentGrant])
            ->with('success', 'Amortisation recorded.' . ($newDeferred <= 0 ? ' Grant fully recognised.' : ''));
    }

    public function refund(Company $company, GovernmentGrant $governmentGrant, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($governmentGrant->company_id === $company->id, 404);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'date'   => ['required', 'date'],
        ]);

        $governmentGrant->update([
            'status'          => GovernmentGrant::STATUS_REFUNDED,
            'deferred_amount' => 0,
        ]);

        $event = GovernmentGrantEvent::create([
            'government_grant_id' => $governmentGrant->id,
            'event_type'          => GovernmentGrantEvent::TYPE_REFUND,
            'event_date'          => $data['date'],
            'amount'              => $data['amount'],
            'description'         => "Grant refunded: R " . number_format((float) $data['amount'], 2),
            'journal_status'      => GovernmentGrantEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerGovernmentGrantPostingDispatcher::class)->dispatch($event->id, $governmentGrant->id, auth()->id(), 'refund', $data);

        return redirect()->route('companies.government-grants.show', [$company, $governmentGrant])
            ->with('success', 'Refund recorded.');
    }

    public function history(Company $company, GovernmentGrant $governmentGrant): JsonResponse
    {
        $this->authorizeCompany($company);
        abort_unless($governmentGrant->company_id === $company->id, 404);

        $events = GovernmentGrantEvent::where('government_grant_id', $governmentGrant->id)
            ->orderBy('event_date')
            ->orderBy('id')
            ->get();

        $txnIds = $events->pluck('transaction_id')->filter()->unique()->all();
        $txnMap = $txnIds ? \App\Models\Transaction::whereIn('id', $txnIds)->get()->keyBy('id') : collect();

        $events = $events->map(function (GovernmentGrantEvent $e) use ($company, $governmentGrant, $txnMap) {
            $colors = GovernmentGrantEvent::colors()[$e->event_type] ?? ['bg' => '#f3f4f6', 'color' => '#374151'];
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
                'id'              => $e->id,
                'type'            => $e->event_type,
                'label'           => GovernmentGrantEvent::labels()[$e->event_type] ?? $e->event_type,
                'date'            => $e->event_date->format('d M Y'),
                'amount'          => number_format((float) $e->amount, 2),
                'description'     => $e->description,
                'journal_status'  => $e->journal_status,
                'bg'              => $colors['bg'],
                'color'           => $colors['color'],
                'transaction_url' => $txnUrl,
                'retry_url'       => $e->journal_status === GovernmentGrantEvent::STATUS_FAILED
                    ? route('companies.government-grants.events.retry-posting', [$company, $governmentGrant, $e])
                    : null,
            ];
        });

        return response()->json($events);
    }

    public function retryPosting(Company $company, GovernmentGrant $governmentGrant, GovernmentGrantEvent $event): JsonResponse
    {
        $this->authorizeCompany($company);
        abort_unless($governmentGrant->company_id === $company->id, 404);
        abort_unless($event->government_grant_id === $governmentGrant->id, 404);
        abort_unless($event->journal_status === GovernmentGrantEvent::STATUS_FAILED, 422);

        $event->update(['journal_status' => GovernmentGrantEvent::STATUS_PENDING]);

        $action = match ($event->event_type) {
            GovernmentGrantEvent::TYPE_RECOGNITION  => 'recognise',
            GovernmentGrantEvent::TYPE_AMORTISATION => 'amortise',
            GovernmentGrantEvent::TYPE_REFUND       => 'refund',
            default                                 => $event->event_type,
        };

        $date = $event->event_date->format('Y-m-d');
        $data = ['amount' => (string) $event->amount, 'date' => $date];

        app(RoadRunnerGovernmentGrantPostingDispatcher::class)->dispatch($event->id, $governmentGrant->id, auth()->id(), $action, $data);

        return response()->json(['ok' => true]);
    }

    private function authorizeCompany(Company $company): void
    {
        abort_unless($company->user_id === auth()->id(), 403);
    }
}
