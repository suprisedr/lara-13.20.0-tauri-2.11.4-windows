<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\GovernmentGrant;
use App\Models\GovernmentGrantEvent;
use App\Services\RoadRunnerGovernmentGrantPostingDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GovernmentGrantController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $company = $this->company($request);

        $grants = $company->governmentGrants()
            ->orderByDesc('grant_date')
            ->get()
            ->map(fn (GovernmentGrant $g) => $this->grantSummary($g));

        return response()->json($grants);
    }

    public function store(Request $request): JsonResponse
    {
        $company = $this->company($request);

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

        return response()->json([
            'success' => true,
            'grant'   => $this->grantSummary($grant),
            'note'    => 'Government grant registered.',
        ], 201);
    }

    public function show(GovernmentGrant $governmentGrant): JsonResponse
    {
        $this->authorise($governmentGrant);

        return response()->json($this->grantSummary($governmentGrant));
    }

    public function update(GovernmentGrant $governmentGrant, Request $request): JsonResponse
    {
        $this->authorise($governmentGrant);

        $data = $request->validate([
            'name'               => ['sometimes', 'string', 'max:200'],
            'granting_authority' => ['sometimes', 'nullable', 'string', 'max:200'],
            'conditions_text'    => ['sometimes', 'nullable', 'string'],
            'notes'              => ['sometimes', 'nullable', 'string'],
        ]);

        $governmentGrant->update($data);

        return response()->json(['success' => true, 'grant' => $this->grantSummary($governmentGrant->fresh())]);
    }

    public function recognise(GovernmentGrant $governmentGrant, Request $request): JsonResponse
    {
        $this->authorise($governmentGrant);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'date'   => ['required', 'date'],
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

        return response()->json([
            'success' => true,
            'grant'   => $this->grantSummary($governmentGrant->fresh()),
            'note'    => 'Recognition recorded. AI will post the journal in the background.',
        ]);
    }

    public function amortise(GovernmentGrant $governmentGrant, Request $request): JsonResponse
    {
        $this->authorise($governmentGrant);

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

        return response()->json([
            'success' => true,
            'grant'   => $this->grantSummary($governmentGrant->fresh()),
            'note'    => 'Amortisation recorded. AI will post the journal in the background.',
        ]);
    }

    public function refund(GovernmentGrant $governmentGrant, Request $request): JsonResponse
    {
        $this->authorise($governmentGrant);

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

        return response()->json([
            'success' => true,
            'grant'   => $this->grantSummary($governmentGrant->fresh()),
            'note'    => 'Refund recorded. AI will post the journal in the background.',
        ]);
    }

    private function company(Request $request): Company
    {
        $company = Company::findOrFail($request->input('company_id'));
        abort_unless($company->user_id === auth()->id(), 403);
        return $company;
    }

    private function authorise(GovernmentGrant $g): void
    {
        abort_unless($g->company->user_id === auth()->id(), 403);
    }

    private function grantSummary(GovernmentGrant $g): array
    {
        return [
            'id'                 => $g->id,
            'name'               => $g->name,
            'grant_type'         => $g->grant_type,
            'grant_reference'    => $g->grant_reference,
            'granting_authority' => $g->granting_authority,
            'grant_date'         => $g->grant_date?->format('Y-m-d'),
            'total_amount'       => (float) $g->total_amount,
            'recognised_amount'  => (float) $g->recognised_amount,
            'deferred_amount'    => (float) $g->deferred_amount,
            'related_asset_type' => $g->related_asset_type,
            'related_asset_id'   => $g->related_asset_id,
            'recognition_method' => $g->recognition_method,
            'conditions_text'    => $g->conditions_text,
            'status'             => $g->status,
            'fulfilment_date'    => $g->fulfilment_date?->format('Y-m-d'),
            'notes'              => $g->notes,
        ];
    }
}
