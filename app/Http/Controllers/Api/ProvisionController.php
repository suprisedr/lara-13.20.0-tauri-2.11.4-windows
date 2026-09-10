<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Provision;
use App\Models\ProvisionEvent;
use App\Services\RoadRunnerProvisionPostingDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProvisionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $company = $this->company($request);

        $provisions = $company->provisions()
            ->with('provisionClass')
            ->orderByDesc('recognition_date')
            ->get()
            ->map(fn (Provision $p) => $this->provisionSummary($p));

        return response()->json($provisions);
    }

    public function store(Request $request): JsonResponse
    {
        $company = $this->company($request);

        $data = $request->validate([
            'provision_class_id'       => ['nullable', 'integer', Rule::exists('provision_classes', 'id')->where('company_id', $company->id)],
            'name'                     => ['required', 'string', 'max:200'],
            'provision_type'           => ['required', Rule::in([Provision::TYPE_PROVISION, Provision::TYPE_CONTINGENT_LIABILITY, Provision::TYPE_CONTINGENT_ASSET])],
            'recognition_date'         => ['required', 'date'],
            'expected_settlement_date' => ['nullable', 'date'],
            'initial_estimate'         => ['required', 'numeric', 'min:0'],
            'discount_rate'            => ['nullable', 'numeric', 'min:0', 'max:100'],
            'probability'              => ['nullable', Rule::in([Provision::PROB_PROBABLE, Provision::PROB_POSSIBLE, Provision::PROB_REMOTE])],
            'notes'                    => ['nullable', 'string'],
        ]);

        $data['current_estimate'] = $data['initial_estimate'];

        if (!empty($data['discount_rate']) && !empty($data['expected_settlement_date'])) {
            $years = now()->diffInDays($data['expected_settlement_date']) / 365.25;
            $data['present_value'] = round((float) $data['initial_estimate'] / pow(1 + (float) $data['discount_rate'] / 100, $years), 2);
        }

        $provision = $company->provisions()->create($data);

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

        return response()->json([
            'success'   => true,
            'provision' => $this->provisionSummary($provision->fresh('provisionClass')),
            'note'      => 'Provision created.' . ($provision->isRecognisable() ? ' AI will post the recognition journal in the background.' : ' Contingent item — disclosure only, no journal entry.'),
        ], 201);
    }

    public function show(Provision $provision): JsonResponse
    {
        $this->authorise($provision);
        $provision->load('provisionClass');

        return response()->json($this->provisionSummary($provision));
    }

    public function update(Provision $provision, Request $request): JsonResponse
    {
        $this->authorise($provision);

        $data = $request->validate([
            'name'                     => ['sometimes', 'string', 'max:200'],
            'expected_settlement_date' => ['sometimes', 'nullable', 'date'],
            'probability'              => ['sometimes', 'nullable', Rule::in([Provision::PROB_PROBABLE, Provision::PROB_POSSIBLE, Provision::PROB_REMOTE])],
            'notes'                    => ['sometimes', 'nullable', 'string'],
        ]);

        $provision->update($data);

        return response()->json(['success' => true, 'provision' => $this->provisionSummary($provision->fresh('provisionClass'))]);
    }

    public function remeasure(Provision $provision, Request $request): JsonResponse
    {
        $this->authorise($provision);

        $data = $request->validate([
            'new_estimate' => ['required', 'numeric', 'min:0'],
            'date'         => ['required', 'date'],
        ]);

        $oldEstimate = (float) $provision->current_estimate;
        $change      = (float) $data['new_estimate'] - $oldEstimate;
        $label       = $change >= 0 ? 'increase' : 'decrease';

        $provision->update(['current_estimate' => $data['new_estimate']]);

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

        return response()->json([
            'success'   => true,
            'provision' => $this->provisionSummary($provision->fresh('provisionClass')),
            'event'     => $this->eventSummary($event),
            'note'      => "Remeasurement recorded ({$label}). AI will post the journal in the background.",
        ]);
    }

    public function utilise(Provision $provision, Request $request): JsonResponse
    {
        $this->authorise($provision);

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

        return response()->json([
            'success'   => true,
            'provision' => $this->provisionSummary($provision->fresh('provisionClass')),
            'event'     => $this->eventSummary($event),
            'note'      => 'Utilisation recorded.' . ($newEstimate <= 0 ? ' Provision fully settled.' : '') . ' AI will post the journal in the background.',
        ]);
    }

    public function reverse(Provision $provision, Request $request): JsonResponse
    {
        $this->authorise($provision);

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

        return response()->json([
            'success'   => true,
            'provision' => $this->provisionSummary($provision->fresh('provisionClass')),
            'event'     => $this->eventSummary($event),
            'note'      => 'Provision reversed. AI will post the journal in the background.',
        ]);
    }

    private function company(Request $request): Company
    {
        $company = Company::findOrFail($request->input('company_id'));
        abort_unless($company->user_id === auth()->id(), 403);
        return $company;
    }

    private function authorise(Provision $p): void
    {
        abort_unless($p->company->user_id === auth()->id(), 403);
    }

    private function provisionSummary(Provision $p): array
    {
        return [
            'id'                       => $p->id,
            'name'                     => $p->name,
            'provision_type'           => $p->provision_type,
            'status'                   => $p->status,
            'probability'              => $p->probability,
            'recognition_date'         => $p->recognition_date?->format('Y-m-d'),
            'expected_settlement_date' => $p->expected_settlement_date?->format('Y-m-d'),
            'initial_estimate'         => (float) $p->initial_estimate,
            'current_estimate'         => (float) $p->current_estimate,
            'discount_rate'            => $p->discount_rate !== null ? (float) $p->discount_rate : null,
            'present_value'            => $p->present_value !== null ? (float) $p->present_value : null,
            'carrying_amount'          => $p->carryingAmount(),
            'settlement_date'          => $p->settlement_date?->format('Y-m-d'),
            'settlement_amount'        => $p->settlement_amount !== null ? (float) $p->settlement_amount : null,
            'class'                    => $p->provisionClass ? [
                'id'   => $p->provisionClass->id,
                'name' => $p->provisionClass->name,
            ] : null,
        ];
    }

    private function eventSummary(ProvisionEvent $e): array
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
