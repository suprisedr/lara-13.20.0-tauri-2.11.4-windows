<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\InvestmentProperty;
use App\Models\InvestmentPropertyClass;
use App\Models\InvestmentPropertyEvent;
use App\Services\RoadRunnerInvestmentPropertyPostingDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InvestmentPropertyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $company = $this->company($request);

        $properties = $company->investmentProperties()
            ->with('investmentPropertyClass')
            ->orderByDesc('acquisition_date')
            ->get()
            ->map(fn (InvestmentProperty $p) => $this->propertySummary($p));

        return response()->json($properties);
    }

    public function store(Request $request): JsonResponse
    {
        $company = $this->company($request);

        $data = $request->validate([
            'investment_property_class_id' => ['nullable', 'integer', Rule::exists('investment_property_classes', 'id')->where('company_id', $company->id)],
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
            'notes'               => ['nullable', 'string'],
        ]);

        $property = $company->investmentProperties()->create($data);

        $event = InvestmentPropertyEvent::create([
            'investment_property_id' => $property->id,
            'event_type'             => InvestmentPropertyEvent::TYPE_ACQUISITION,
            'event_date'             => $data['acquisition_date'],
            'amount'                 => $data['cost'],
            'description'            => "Acquired: {$property->name} for R " . number_format((float) $data['cost'], 2),
            'journal_status'         => InvestmentPropertyEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerInvestmentPropertyPostingDispatcher::class)->dispatch($event->id, $property->id, auth()->id(), 'acquire', $data);

        return response()->json([
            'success'  => true,
            'property' => $this->propertySummary($property->fresh('investmentPropertyClass')),
            'note'     => 'Investment property created. AI will post the acquisition journal in the background.',
        ], 201);
    }

    public function show(InvestmentProperty $investmentProperty): JsonResponse
    {
        $this->authorise($investmentProperty);
        $investmentProperty->load('investmentPropertyClass');

        return response()->json($this->propertySummary($investmentProperty));
    }

    public function update(InvestmentProperty $investmentProperty, Request $request): JsonResponse
    {
        $this->authorise($investmentProperty);
        $company = $investmentProperty->company;

        $data = $request->validate([
            'name'                => ['sometimes', 'string', 'max:150'],
            'property_reference'  => ['sometimes', 'nullable', 'string', 'max:60'],
            'location'            => ['sometimes', 'nullable', 'string', 'max:200'],
            'residual_value'      => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'useful_life_years'   => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:999.99'],
            'depreciation_method' => ['sometimes', 'nullable', Rule::in(array_keys(InvestmentProperty::METHODS))],
            'notes'               => ['sometimes', 'nullable', 'string'],
        ]);

        $investmentProperty->update($data);

        return response()->json(['success' => true, 'property' => $this->propertySummary($investmentProperty->fresh('investmentPropertyClass'))]);
    }

    public function fairValueAdjust(InvestmentProperty $investmentProperty, Request $request): JsonResponse
    {
        $this->authorise($investmentProperty);

        $data = $request->validate([
            'new_fair_value' => ['required', 'numeric', 'gt:0'],
            'date'           => ['required', 'date'],
        ]);

        $oldValue = (float) ($investmentProperty->fair_value ?? $investmentProperty->cost);
        $change   = (float) $data['new_fair_value'] - $oldValue;
        $label    = $change >= 0 ? 'gain' : 'loss';

        $investmentProperty->update([
            'fair_value'           => $data['new_fair_value'],
            'fair_value_date'      => $data['date'],
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

        return response()->json([
            'success'  => true,
            'property' => $this->propertySummary($investmentProperty->fresh('investmentPropertyClass')),
            'event'    => $this->eventSummary($event),
            'note'     => "Fair value adjustment recorded ({$label} of R " . number_format(abs($change), 2) . "). AI will post the journal in the background.",
        ]);
    }

    public function impair(InvestmentProperty $investmentProperty, Request $request): JsonResponse
    {
        $this->authorise($investmentProperty);

        $data = $request->validate([
            'impairment_amount' => ['required', 'numeric', 'gt:0'],
            'date'              => ['required', 'date'],
            'reason'            => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        $desc = 'Impairment of R ' . number_format((float) $data['impairment_amount'], 2);
        if (!empty($data['reason'])) $desc .= ' — ' . $data['reason'];

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

        return response()->json([
            'success'  => true,
            'property' => $this->propertySummary($investmentProperty->fresh('investmentPropertyClass')),
            'event'    => $this->eventSummary($event),
            'note'     => 'Impairment recorded. AI will post the journal in the background.',
        ]);
    }

    public function reverseImpairment(InvestmentProperty $investmentProperty, Request $request): JsonResponse
    {
        $this->authorise($investmentProperty);

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

        return response()->json([
            'success'  => true,
            'property' => $this->propertySummary($investmentProperty->fresh('investmentPropertyClass')),
            'event'    => $this->eventSummary($event),
            'note'     => 'Impairment reversal recorded. AI will post the journal in the background.',
        ]);
    }

    public function capitalise(InvestmentProperty $investmentProperty, Request $request): JsonResponse
    {
        $this->authorise($investmentProperty);

        $data = $request->validate([
            'amount'      => ['required', 'numeric', 'gt:0'],
            'date'        => ['required', 'date'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $desc = 'Subsequent cost capitalised: R ' . number_format((float) $data['amount'], 2);
        if (!empty($data['description'])) $desc .= ' — ' . $data['description'];

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

        return response()->json([
            'success'  => true,
            'property' => $this->propertySummary($investmentProperty->fresh('investmentPropertyClass')),
            'event'    => $this->eventSummary($event),
            'note'     => 'Subsequent cost recorded. AI will post the journal in the background.',
        ]);
    }

    public function dispose(InvestmentProperty $investmentProperty, Request $request): JsonResponse
    {
        $this->authorise($investmentProperty);

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

        return response()->json([
            'success'  => true,
            'property' => $this->propertySummary($investmentProperty->fresh('investmentPropertyClass')),
            'event'    => $this->eventSummary($event),
            'note'     => 'Disposal recorded. AI will post the journal in the background.',
        ]);
    }

    private function company(Request $request): Company
    {
        $companyId = $request->input('company_id');
        $company   = Company::findOrFail($companyId);
        abort_unless($company->user_id === auth()->id(), 403);
        return $company;
    }

    private function authorise(InvestmentProperty $p): void
    {
        abort_unless($p->company->user_id === auth()->id(), 403);
    }

    private function propertySummary(InvestmentProperty $p): array
    {
        $asOf = now()->format('Y-m-d');
        return [
            'id'                     => $p->id,
            'name'                   => $p->name,
            'property_reference'     => $p->property_reference,
            'location'               => $p->location,
            'acquisition_date'       => $p->acquisition_date?->format('Y-m-d'),
            'cost'                   => (float) $p->cost,
            'residual_value'         => (float) ($p->residual_value ?? 0),
            'useful_life_years'      => $p->useful_life_years,
            'depreciation_method'    => $p->depreciation_method,
            'measurement_model'      => $p->measurementModel(),
            'fair_value'             => $p->fair_value ? (float) $p->fair_value : null,
            'fair_value_date'        => $p->fair_value_date?->format('Y-m-d'),
            'fair_value_gain_loss'   => (float) ($p->fair_value_gain_loss ?? 0),
            'accumulated_impairment' => (float) ($p->accumulated_impairment ?? 0),
            'carrying_amount'        => $p->carryingAmount($asOf),
            'status'                 => $p->status,
            'class'                  => $p->investmentPropertyClass ? [
                'id'                => $p->investmentPropertyClass->id,
                'name'              => $p->investmentPropertyClass->name,
                'measurement_model' => $p->investmentPropertyClass->measurement_model,
            ] : null,
        ];
    }

    private function eventSummary(InvestmentPropertyEvent $e): array
    {
        return [
            'id'             => $e->id,
            'event_type'     => $e->event_type,
            'event_date'     => $e->event_date->format('Y-m-d'),
            'amount'         => (float) $e->amount,
            'journal_status' => $e->journal_status,
        ];
    }
}
