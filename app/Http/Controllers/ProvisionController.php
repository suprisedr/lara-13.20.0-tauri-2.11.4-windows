<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Provision;
use App\Models\ProvisionClass;
use App\Models\ProvisionEvent;
use App\Services\RoadRunnerProvisionPostingDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProvisionController extends Controller
{
    public function index(Company $company): View
    {
        $this->authorizeCompany($company);

        $provisions = $company->provisions()
            ->with('provisionClass')
            ->orderByDesc('recognition_date')
            ->orderBy('name')
            ->get();

        $classes = $company->provisionClasses()->withCount('provisions')->orderBy('sort_order')->get();

        return view('companies.provisions.index', compact('company', 'provisions', 'classes'));
    }

    public function show(Company $company, Provision $provision): View
    {
        $this->authorizeCompany($company);
        abort_unless($provision->company_id === $company->id, 404);

        $provision->load('provisionClass');

        return view('companies.provisions.show', compact('company', 'provision'));
    }

    public function store(Company $company, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);

        $data = $request->validate([
            'provision_class_id'      => ['nullable', 'integer', Rule::exists('provision_classes', 'id')->where('company_id', $company->id)],
            'name'                    => ['required', 'string', 'max:200'],
            'provision_type'          => ['required', Rule::in([Provision::TYPE_PROVISION, Provision::TYPE_CONTINGENT_LIABILITY, Provision::TYPE_CONTINGENT_ASSET])],
            'recognition_date'        => ['required', 'date'],
            'expected_settlement_date' => ['nullable', 'date'],
            'initial_estimate'        => ['required', 'numeric', 'min:0'],
            'discount_rate'           => ['nullable', 'numeric', 'min:0', 'max:100'],
            'probability'             => ['nullable', Rule::in([Provision::PROB_PROBABLE, Provision::PROB_POSSIBLE, Provision::PROB_REMOTE])],
            'notes'                   => ['nullable', 'string'],
        ]);

        $data['current_estimate'] = $data['initial_estimate'];

        // Calculate present value if discount rate provided
        if (!empty($data['discount_rate']) && !empty($data['expected_settlement_date'])) {
            $years = now()->diffInDays($data['expected_settlement_date']) / 365.25;
            $data['present_value'] = round((float) $data['initial_estimate'] / pow(1 + (float) $data['discount_rate'] / 100, $years), 2);
        }

        $provision = $company->provisions()->create($data);

        // Only post journal for recognisable provisions (type=provision, probability=probable)
        if ($provision->isRecognisable()) {
            $event = ProvisionEvent::create([
                'provision_id'   => $provision->id,
                'event_type'     => ProvisionEvent::TYPE_RECOGNITION,
                'event_date'     => $data['recognition_date'],
                'amount'         => $data['current_estimate'],
                'description'    => "Provision recognised: {$provision->name} — R " . number_format((float) $data['current_estimate'], 2),
                'journal_status' => ProvisionEvent::STATUS_PENDING,
            ]);

            app(RoadRunnerProvisionPostingDispatcher::class)->dispatch($event->id, $provision->id, auth()->id(), 'recognise', $data);
        }

        return redirect()->route('companies.provisions.index', $company)
            ->with('success', 'Provision added.' . ($provision->isRecognisable() ? ' AI is posting the recognition journal.' : ''));
    }

    public function update(Company $company, Provision $provision, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($provision->company_id === $company->id, 404);

        $data = $request->validate([
            'provision_class_id'       => ['nullable', 'integer', Rule::exists('provision_classes', 'id')->where('company_id', $company->id)],
            'name'                     => ['required', 'string', 'max:200'],
            'expected_settlement_date' => ['nullable', 'date'],
            'probability'              => ['nullable', Rule::in([Provision::PROB_PROBABLE, Provision::PROB_POSSIBLE, Provision::PROB_REMOTE])],
            'notes'                    => ['nullable', 'string'],
        ]);

        $provision->update($data);

        return redirect()->route('companies.provisions.show', [$company, $provision])
            ->with('success', 'Provision updated.');
    }

    public function destroy(Company $company, Provision $provision): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($provision->company_id === $company->id, 404);

        $provision->delete();

        return redirect()->route('companies.provisions.index', $company)
            ->with('success', 'Provision removed.');
    }

    // ─── Classes ─────────────────────────────────────────────────────────

    public function storeClass(Company $company, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
        ]);

        $maxSort = (int) $company->provisionClasses()->max('sort_order');
        $company->provisionClasses()->create($validated + ['sort_order' => $maxSort + 1]);

        return redirect()->route('companies.provisions.index', $company)
            ->with('success', 'Provision class added.');
    }

    public function destroyClass(Company $company, ProvisionClass $provisionClass): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($provisionClass->company_id === $company->id, 404);

        $provisionClass->delete();

        return redirect()->route('companies.provisions.index', $company)
            ->with('success', 'Provision class removed.');
    }

    // ─── IAS 37 Actions ──────────────────────────────────────────────────

    public function remeasure(Company $company, Provision $provision, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($provision->company_id === $company->id, 404);

        $data = $request->validate([
            'new_estimate' => ['required', 'numeric', 'min:0'],
            'date'         => ['required', 'date'],
        ]);

        $oldEstimate = (float) $provision->current_estimate;
        $change      = (float) $data['new_estimate'] - $oldEstimate;
        $label       = $change >= 0 ? 'increase' : 'decrease';

        $provision->update(['current_estimate' => $data['new_estimate']]);

        // Recalculate present value if discounted
        if ($provision->discount_rate && $provision->expected_settlement_date) {
            $years = now()->diffInDays($provision->expected_settlement_date) / 365.25;
            $provision->update([
                'present_value' => round((float) $data['new_estimate'] / pow(1 + (float) $provision->discount_rate / 100, $years), 2),
            ]);
        }

        $event = ProvisionEvent::create([
            'provision_id'   => $provision->id,
            'event_type'     => ProvisionEvent::TYPE_REMEASUREMENT,
            'event_date'     => $data['date'],
            'amount'         => $data['new_estimate'],
            'description'    => "Remeasured to R " . number_format((float) $data['new_estimate'], 2) . " ({$label} of R " . number_format(abs($change), 2) . ")",
            'journal_status' => ProvisionEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerProvisionPostingDispatcher::class)->dispatch($event->id, $provision->id, auth()->id(), 'remeasure', $data);

        return redirect()->route('companies.provisions.show', [$company, $provision])
            ->with('success', 'Remeasurement recorded.');
    }

    public function unwind(Company $company, Provision $provision, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($provision->company_id === $company->id, 404);

        $data = $request->validate([
            'unwinding_amount' => ['required', 'numeric', 'gt:0'],
            'date'             => ['required', 'date'],
        ]);

        $newPV = (float) ($provision->present_value ?? $provision->current_estimate) + (float) $data['unwinding_amount'];
        $provision->update([
            'present_value'             => $newPV,
            'last_unwinding_posted_on'  => $data['date'],
        ]);

        $event = ProvisionEvent::create([
            'provision_id'   => $provision->id,
            'event_type'     => ProvisionEvent::TYPE_UNWINDING,
            'event_date'     => $data['date'],
            'amount'         => $data['unwinding_amount'],
            'description'    => "Unwinding of discount: R " . number_format((float) $data['unwinding_amount'], 2),
            'journal_status' => ProvisionEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerProvisionPostingDispatcher::class)->dispatch($event->id, $provision->id, auth()->id(), 'unwind', $data);

        return redirect()->route('companies.provisions.show', [$company, $provision])
            ->with('success', 'Unwinding recorded.');
    }

    public function utilise(Company $company, Provision $provision, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($provision->company_id === $company->id, 404);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'date'   => ['required', 'date'],
        ]);

        $newEstimate = max((float) $provision->current_estimate - (float) $data['amount'], 0);
        $provision->update([
            'current_estimate'  => $newEstimate,
            'settlement_amount' => (float) ($provision->settlement_amount ?? 0) + (float) $data['amount'],
        ]);

        if ($newEstimate <= 0) {
            $provision->update([
                'status'          => Provision::STATUS_SETTLED,
                'settlement_date' => $data['date'],
            ]);
        }

        // Recalculate present value if discounted
        if ($provision->discount_rate && $provision->expected_settlement_date && $newEstimate > 0) {
            $years = now()->diffInDays($provision->expected_settlement_date) / 365.25;
            $provision->update([
                'present_value' => round($newEstimate / pow(1 + (float) $provision->discount_rate / 100, $years), 2),
            ]);
        } elseif ($newEstimate <= 0) {
            $provision->update(['present_value' => 0]);
        }

        $event = ProvisionEvent::create([
            'provision_id'   => $provision->id,
            'event_type'     => ProvisionEvent::TYPE_UTILISATION,
            'event_date'     => $data['date'],
            'amount'         => $data['amount'],
            'description'    => "Utilised R " . number_format((float) $data['amount'], 2) . ($newEstimate <= 0 ? ' (fully settled)' : ''),
            'journal_status' => ProvisionEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerProvisionPostingDispatcher::class)->dispatch($event->id, $provision->id, auth()->id(), 'utilise', $data);

        return redirect()->route('companies.provisions.show', [$company, $provision])
            ->with('success', 'Utilisation recorded.' . ($newEstimate <= 0 ? ' Provision fully settled.' : ''));
    }

    public function reverse(Company $company, Provision $provision, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($provision->company_id === $company->id, 404);

        $data = $request->validate([
            'date' => ['required', 'date'],
        ]);

        $amount = (float) $provision->current_estimate;

        $provision->update([
            'status'           => Provision::STATUS_REVERSED,
            'current_estimate' => 0,
            'present_value'    => 0,
        ]);

        $event = ProvisionEvent::create([
            'provision_id'   => $provision->id,
            'event_type'     => ProvisionEvent::TYPE_REVERSAL,
            'event_date'     => $data['date'],
            'amount'         => $amount,
            'description'    => "Provision reversed: R " . number_format($amount, 2),
            'journal_status' => ProvisionEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerProvisionPostingDispatcher::class)->dispatch($event->id, $provision->id, auth()->id(), 'reverse', $data);

        return redirect()->route('companies.provisions.show', [$company, $provision])
            ->with('success', 'Provision reversed.');
    }

    public function history(Company $company, Provision $provision): JsonResponse
    {
        $this->authorizeCompany($company);
        abort_unless($provision->company_id === $company->id, 404);

        $events = ProvisionEvent::where('provision_id', $provision->id)
            ->orderBy('event_date')
            ->orderBy('id')
            ->get();

        $txnIds = $events->pluck('transaction_id')->filter()->unique()->all();
        $txnMap = $txnIds ? \App\Models\Transaction::whereIn('id', $txnIds)->get()->keyBy('id') : collect();

        $events = $events->map(function (ProvisionEvent $e) use ($company, $provision, $txnMap) {
            $colors = ProvisionEvent::colors()[$e->event_type] ?? ['bg' => '#f3f4f6', 'color' => '#374151'];
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
                'label'           => ProvisionEvent::labels()[$e->event_type] ?? $e->event_type,
                'date'            => $e->event_date->format('d M Y'),
                'amount'          => number_format((float) $e->amount, 2),
                'description'     => $e->description,
                'journal_status'  => $e->journal_status,
                'bg'              => $colors['bg'],
                'color'           => $colors['color'],
                'transaction_url' => $txnUrl,
                'retry_url'       => $e->journal_status === ProvisionEvent::STATUS_FAILED
                    ? route('companies.provisions.events.retry-posting', [$company, $provision, $e])
                    : null,
            ];
        });

        return response()->json($events);
    }

    public function retryPosting(Company $company, Provision $provision, ProvisionEvent $event): JsonResponse
    {
        $this->authorizeCompany($company);
        abort_unless($provision->company_id === $company->id, 404);
        abort_unless($event->provision_id === $provision->id, 404);
        abort_unless($event->journal_status === ProvisionEvent::STATUS_FAILED, 422);

        $event->update(['journal_status' => ProvisionEvent::STATUS_PENDING]);

        $action = match ($event->event_type) {
            ProvisionEvent::TYPE_RECOGNITION   => 'recognise',
            ProvisionEvent::TYPE_REMEASUREMENT => 'remeasure',
            ProvisionEvent::TYPE_UNWINDING     => 'unwind',
            ProvisionEvent::TYPE_UTILISATION   => 'utilise',
            ProvisionEvent::TYPE_REVERSAL      => 'reverse',
            default                            => $event->event_type,
        };

        $date = $event->event_date->format('Y-m-d');
        $data = match ($event->event_type) {
            ProvisionEvent::TYPE_REMEASUREMENT => ['new_estimate' => (string) $event->amount, 'date' => $date],
            ProvisionEvent::TYPE_UNWINDING     => ['unwinding_amount' => (string) $event->amount, 'date' => $date],
            ProvisionEvent::TYPE_UTILISATION   => ['amount' => (string) $event->amount, 'date' => $date],
            ProvisionEvent::TYPE_REVERSAL      => ['date' => $date],
            default                            => [],
        };

        app(RoadRunnerProvisionPostingDispatcher::class)->dispatch($event->id, $provision->id, auth()->id(), $action, $data);

        return response()->json(['ok' => true]);
    }

    private function authorizeCompany(Company $company): void
    {
        abort_unless($company->user_id === auth()->id(), 403);
    }
}
