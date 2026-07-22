<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\InvestmentProperty;
use App\Models\InvestmentPropertyClass;
use App\Models\InvestmentPropertyEvent;
use App\Services\RoadRunnerInvestmentPropertyPostingDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InvestmentPropertyController extends Controller
{
    public function index(Company $company, Request $request): View
    {
        $this->authorizeCompany($company);

        $asOf = $request->input('as_of', now()->format('Y-m-d'));

        $properties = $company->investmentProperties()
            ->with('investmentPropertyClass')
            ->orderByDesc('acquisition_date')
            ->orderBy('name')
            ->get();

        $classes = $company->investmentPropertyClasses()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('companies.investment-properties.index', compact('company', 'properties', 'classes', 'asOf'));
    }

    public function show(Company $company, InvestmentProperty $investmentProperty): View
    {
        $this->authorizeCompany($company);
        abort_unless($investmentProperty->company_id === $company->id, 404);

        $classes = $company->investmentPropertyClasses()->orderBy('sort_order')->orderBy('name')->get();

        return view('companies.investment-properties.show', compact('company', 'investmentProperty', 'classes'));
    }

    public function store(Company $company, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);

        $company->investmentProperties()->create($this->validateProperty($company, $request));

        return redirect()
            ->route('companies.investment-properties.index', $company)
            ->with('success', 'Investment property added to the register.');
    }

    public function update(Company $company, InvestmentProperty $investmentProperty, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($investmentProperty->company_id === $company->id, 404);

        $investmentProperty->update($this->validateProperty($company, $request));

        return redirect()
            ->route('companies.investment-properties.show', [$company, $investmentProperty])
            ->with('success', 'Investment property updated.');
    }

    public function destroy(Company $company, InvestmentProperty $investmentProperty): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($investmentProperty->company_id === $company->id, 404);

        $investmentProperty->delete();

        return redirect()
            ->route('companies.investment-properties.index', $company)
            ->with('success', 'Investment property removed from the register.');
    }

    public function storeClass(Company $company, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);

        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:120'],
            'useful_life_years'   => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'depreciation_method' => ['nullable', Rule::in(array_keys(InvestmentProperty::METHODS))],
            'measurement_model'   => ['nullable', Rule::in(array_keys(InvestmentPropertyClass::MODELS))],
        ]);

        $maxSort = (int) $company->investmentPropertyClasses()->max('sort_order');
        $company->investmentPropertyClasses()->create($validated + ['sort_order' => $maxSort + 1]);

        return redirect()
            ->route('companies.investment-properties.index', $company)
            ->with('success', 'Investment property class added.');
    }

    public function destroyClass(Company $company, InvestmentPropertyClass $investmentPropertyClass): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($investmentPropertyClass->company_id === $company->id, 404);

        $investmentPropertyClass->delete();

        return redirect()
            ->route('companies.investment-properties.index', $company)
            ->with('success', 'Investment property class removed.');
    }

    // ─── IAS 40 / IAS 36 actions ─────────────────────────────────────────

    public function fairValueAdjust(Company $company, InvestmentProperty $investmentProperty, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($investmentProperty->company_id === $company->id, 404);

        $data = $request->validate([
            'new_fair_value' => ['required', 'numeric', 'gt:0'],
            'date'           => ['required', 'date'],
        ]);

        $oldValue = (float) ($investmentProperty->fair_value ?? $investmentProperty->cost);
        $change   = (float) $data['new_fair_value'] - $oldValue;
        $label    = $change >= 0 ? 'gain' : 'loss';

        $investmentProperty->update([
            'fair_value'          => $data['new_fair_value'],
            'fair_value_date'     => $data['date'],
            'fair_value_gain_loss' => (float) $investmentProperty->fair_value_gain_loss + $change,
        ]);

        $event = InvestmentPropertyEvent::create([
            'investment_property_id' => $investmentProperty->id,
            'event_type'             => InvestmentPropertyEvent::TYPE_FAIR_VALUE_ADJUSTMENT,
            'event_date'             => $data['date'],
            'amount'                 => $data['new_fair_value'],
            'description'            => "Fair value adjusted to R " . number_format((float) $data['new_fair_value'], 2) . " ({$label} of R " . number_format(abs($change), 2) . " to P&L)",
            'journal_status'         => InvestmentPropertyEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerInvestmentPropertyPostingDispatcher::class)->dispatch($event->id, $investmentProperty->id, auth()->id(), 'fair_value_adjust', $data);

        return redirect()->route('companies.investment-properties.show', [$company, $investmentProperty])
            ->with('success', 'Fair value adjustment recorded.');
    }

    public function impair(Company $company, InvestmentProperty $investmentProperty, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($investmentProperty->company_id === $company->id, 404);

        $data = $request->validate([
            'impairment_amount' => ['required', 'numeric', 'gt:0'],
            'date'              => ['required', 'date'],
            'reason'            => ['nullable', 'string', 'max:500'],
        ]);

        $desc = 'Impairment of R ' . number_format((float) $data['impairment_amount'], 2);
        if (! empty($data['reason'])) {
            $desc .= ' — ' . $data['reason'];
        }

        $investmentProperty->increment('accumulated_impairment', (float) $data['impairment_amount']);

        $event = InvestmentPropertyEvent::create([
            'investment_property_id' => $investmentProperty->id,
            'event_type'             => InvestmentPropertyEvent::TYPE_IMPAIRMENT,
            'event_date'             => $data['date'],
            'amount'                 => $data['impairment_amount'],
            'description'            => $desc,
            'journal_status'         => InvestmentPropertyEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerInvestmentPropertyPostingDispatcher::class)->dispatch($event->id, $investmentProperty->id, auth()->id(), 'impair', $data);

        return redirect()->route('companies.investment-properties.show', [$company, $investmentProperty])
            ->with('success', 'Impairment recorded.');
    }

    public function reverseImpairment(Company $company, InvestmentProperty $investmentProperty, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($investmentProperty->company_id === $company->id, 404);

        $data = $request->validate([
            'reversal_amount' => ['required', 'numeric', 'gt:0'],
            'date'            => ['required', 'date'],
        ]);

        $capped = min((float) $data['reversal_amount'], (float) $investmentProperty->accumulated_impairment);

        $investmentProperty->decrement('accumulated_impairment', $capped);

        $event = InvestmentPropertyEvent::create([
            'investment_property_id' => $investmentProperty->id,
            'event_type'             => InvestmentPropertyEvent::TYPE_IMPAIRMENT_REVERSAL,
            'event_date'             => $data['date'],
            'amount'                 => $capped,
            'description'            => 'Impairment reversal of R ' . number_format($capped, 2),
            'journal_status'         => InvestmentPropertyEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerInvestmentPropertyPostingDispatcher::class)->dispatch($event->id, $investmentProperty->id, auth()->id(), 'reverse', ['reversal_amount' => $capped, 'date' => $data['date']]);

        return redirect()->route('companies.investment-properties.show', [$company, $investmentProperty])
            ->with('success', 'Impairment reversal recorded.');
    }

    public function capitalise(Company $company, InvestmentProperty $investmentProperty, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($investmentProperty->company_id === $company->id, 404);

        $data = $request->validate([
            'amount'      => ['required', 'numeric', 'gt:0'],
            'date'        => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $desc = 'Subsequent cost capitalised: R ' . number_format((float) $data['amount'], 2);
        if (! empty($data['description'])) {
            $desc .= ' — ' . $data['description'];
        }

        $investmentProperty->increment('cost', (float) $data['amount']);

        $event = InvestmentPropertyEvent::create([
            'investment_property_id' => $investmentProperty->id,
            'event_type'             => InvestmentPropertyEvent::TYPE_CAPITALISATION,
            'event_date'             => $data['date'],
            'amount'                 => $data['amount'],
            'description'            => $desc,
            'journal_status'         => InvestmentPropertyEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerInvestmentPropertyPostingDispatcher::class)->dispatch($event->id, $investmentProperty->id, auth()->id(), 'capitalise', $data);

        return redirect()->route('companies.investment-properties.show', [$company, $investmentProperty])
            ->with('success', 'Subsequent cost recorded.');
    }

    public function dispose(Company $company, InvestmentProperty $investmentProperty, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($investmentProperty->company_id === $company->id, 404);

        $data = $request->validate([
            'disposal_date'     => ['required', 'date'],
            'disposal_proceeds' => ['nullable', 'numeric', 'min:0'],
        ]);

        $investmentProperty->update([
            'disposal_date'     => $data['disposal_date'],
            'disposal_proceeds' => $data['disposal_proceeds'] ?? 0,
            'status'            => InvestmentProperty::STATUS_DISPOSED,
        ]);

        $event = InvestmentPropertyEvent::create([
            'investment_property_id' => $investmentProperty->id,
            'event_type'             => InvestmentPropertyEvent::TYPE_DISPOSAL,
            'event_date'             => $data['disposal_date'],
            'amount'                 => $data['disposal_proceeds'] ?? 0,
            'description'            => 'Property disposed. Proceeds: R ' . number_format((float) ($data['disposal_proceeds'] ?? 0), 2),
            'journal_status'         => InvestmentPropertyEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerInvestmentPropertyPostingDispatcher::class)->dispatch($event->id, $investmentProperty->id, auth()->id(), 'dispose', $data);

        return redirect()->route('companies.investment-properties.show', [$company, $investmentProperty])
            ->with('success', 'Disposal recorded.');
    }

    public function history(Company $company, InvestmentProperty $investmentProperty): JsonResponse
    {
        $this->authorizeCompany($company);
        abort_unless($investmentProperty->company_id === $company->id, 404);

        $events = InvestmentPropertyEvent::where('investment_property_id', $investmentProperty->id)
            ->orderBy('event_date')
            ->orderBy('id')
            ->get();

        $txnIds = $events->pluck('transaction_id')->filter()->unique()->all();
        $txnMap = $txnIds ? \App\Models\Transaction::whereIn('id', $txnIds)->get()->keyBy('id') : collect();

        $events = $events->map(function (InvestmentPropertyEvent $e) use ($company, $investmentProperty, $txnMap) {
                $colors = InvestmentPropertyEvent::colors()[$e->event_type] ?? ['bg' => '#f3f4f6', 'color' => '#374151'];
                $labels = InvestmentPropertyEvent::labels();
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
                    'retry_url'      => $e->journal_status === InvestmentPropertyEvent::STATUS_FAILED
                        ? route('companies.investment-properties.events.retry-posting', [$company, $investmentProperty, $e])
                        : null,
                ];
            })
            ->values();

        return response()->json(['events' => $events]);
    }

    public function retryPosting(Company $company, InvestmentProperty $investmentProperty, InvestmentPropertyEvent $event): JsonResponse
    {
        $this->authorizeCompany($company);
        abort_unless($investmentProperty->company_id === $company->id, 404);
        abort_unless($event->investment_property_id === $investmentProperty->id, 404);
        abort_unless($event->journal_status === InvestmentPropertyEvent::STATUS_FAILED, 422);

        $event->update(['journal_status' => InvestmentPropertyEvent::STATUS_PENDING]);

        $action = match ($event->event_type) {
            InvestmentPropertyEvent::TYPE_CAPITALISATION       => 'capitalise',
            InvestmentPropertyEvent::TYPE_FAIR_VALUE_ADJUSTMENT => 'fair_value_adjust',
            InvestmentPropertyEvent::TYPE_IMPAIRMENT           => 'impair',
            InvestmentPropertyEvent::TYPE_IMPAIRMENT_REVERSAL  => 'reverse',
            default                                            => $event->event_type,
        };

        $date = $event->event_date->format('Y-m-d');
        $data = match ($event->event_type) {
            InvestmentPropertyEvent::TYPE_CAPITALISATION        => ['amount' => (string) $event->amount, 'date' => $date, 'description' => $event->description ?? ''],
            InvestmentPropertyEvent::TYPE_FAIR_VALUE_ADJUSTMENT => ['new_fair_value' => (string) $event->amount, 'date' => $date],
            InvestmentPropertyEvent::TYPE_IMPAIRMENT            => ['impairment_amount' => (string) $event->amount, 'date' => $date, 'reason' => $event->description ?? ''],
            InvestmentPropertyEvent::TYPE_IMPAIRMENT_REVERSAL   => ['reversal_amount' => (string) $event->amount, 'date' => $date],
            default                                             => [],
        };

        app(RoadRunnerInvestmentPropertyPostingDispatcher::class)->dispatch($event->id, $investmentProperty->id, auth()->id(), $action, $data);

        return response()->json(['ok' => true]);
    }

    /** @return array<string, mixed> */
    private function validateProperty(Company $company, Request $request): array
    {
        return $request->validate([
            'investment_property_class_id' => [
                'nullable',
                'integer',
                Rule::exists('investment_property_classes', 'id')->where('company_id', $company->id),
            ],
            'name'                => ['required', 'string', 'max:150'],
            'property_reference'  => ['nullable', 'string', 'max:60'],
            'location'            => ['nullable', 'string', 'max:200'],
            'acquisition_date'    => ['required', 'date'],
            'cost'                => ['required', 'numeric', 'min:0'],
            'residual_value'      => ['nullable', 'numeric', 'min:0'],
            'useful_life_years'   => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'depreciation_method' => ['nullable', Rule::in(array_keys(InvestmentProperty::METHODS))],
            'fair_value'          => ['nullable', 'numeric', 'min:0'],
            'fair_value_date'     => ['nullable', 'date'],
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
