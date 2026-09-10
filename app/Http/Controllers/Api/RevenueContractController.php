<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PerformanceObligation;
use App\Models\RevenueContract;
use App\Models\RevenueContractEvent;
use App\Services\RoadRunnerRevenuePostingDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RevenueContractController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $company = $this->company($request);

        $contracts = $company->revenueContracts()
            ->with('performanceObligations')
            ->orderByDesc('contract_date')
            ->get()
            ->map(fn (RevenueContract $c) => $this->contractSummary($c));

        return response()->json($contracts);
    }

    public function store(Request $request): JsonResponse
    {
        $company = $this->company($request);

        $data = $request->validate([
            'name'                    => ['required', 'string', 'max:200'],
            'contract_reference'      => ['nullable', 'string', 'max:60'],
            'customer_name'           => ['required', 'string', 'max:200'],
            'contract_date'           => ['required', 'date'],
            'total_transaction_price' => ['required', 'numeric', 'min:0'],
            'currency'                => ['nullable', 'string', 'max:3'],
            'notes'                   => ['nullable', 'string'],
        ]);

        $contract = $company->revenueContracts()->create($data);

        return response()->json([
            'success'  => true,
            'contract' => $this->contractSummary($contract),
        ], 201);
    }

    public function show(RevenueContract $revenueContract): JsonResponse
    {
        $this->authorise($revenueContract);
        $revenueContract->load('performanceObligations');

        return response()->json($this->contractSummary($revenueContract));
    }

    public function update(RevenueContract $revenueContract, Request $request): JsonResponse
    {
        $this->authorise($revenueContract);

        $data = $request->validate([
            'name'                    => ['sometimes', 'string', 'max:200'],
            'contract_reference'      => ['nullable', 'string', 'max:60'],
            'customer_name'           => ['sometimes', 'string', 'max:200'],
            'total_transaction_price' => ['sometimes', 'numeric', 'min:0'],
            'notes'                   => ['nullable', 'string'],
        ]);

        $revenueContract->update($data);

        return response()->json($this->contractSummary($revenueContract->fresh('performanceObligations')));
    }

    public function storeObligation(RevenueContract $revenueContract, Request $request): JsonResponse
    {
        $this->authorise($revenueContract);

        $data = $request->validate([
            'name'               => ['required', 'string', 'max:200'],
            'standalone_price'   => ['required', 'numeric', 'min:0'],
            'allocated_price'    => ['nullable', 'numeric', 'min:0'],
            'recognition_method' => ['required', Rule::in(['point_in_time', 'over_time'])],
            'over_time_method'   => ['nullable', 'string'],
        ]);

        $data['revenue_contract_id'] = $revenueContract->id;
        $obligation = PerformanceObligation::create($data);

        return response()->json(['success' => true, 'obligation' => $obligation], 201);
    }

    public function recogniseRevenue(RevenueContract $revenueContract, Request $request): JsonResponse
    {
        $this->authorise($revenueContract);

        $data = $request->validate([
            'performance_obligation_id' => ['required', 'integer', Rule::exists('performance_obligations', 'id')->where('revenue_contract_id', $revenueContract->id)],
            'amount'                    => ['required', 'numeric', 'gt:0'],
            'date'                      => ['required', 'date'],
        ]);

        $obligation = PerformanceObligation::findOrFail($data['performance_obligation_id']);

        $event = RevenueContractEvent::create([
            'revenue_contract_id' => $revenueContract->id,
            'event_type'          => RevenueContractEvent::TYPE_REVENUE_RECOGNISED,
            'event_date'          => $data['date'],
            'amount'              => $data['amount'],
            'description'         => "Revenue recognised: {$obligation->name} — R " . number_format((float) $data['amount'], 2),
            'journal_status'      => RevenueContractEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerRevenuePostingDispatcher::class)->dispatch($event->id, $revenueContract->id, auth()->id(), 'recognise_revenue', $data);

        return response()->json(['success' => true, 'note' => 'Revenue recognition posted by AI in background.']);
    }

    public function advanceReceipt(RevenueContract $revenueContract, Request $request): JsonResponse
    {
        $this->authorise($revenueContract);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'date'   => ['required', 'date'],
        ]);

        $event = RevenueContractEvent::create([
            'revenue_contract_id' => $revenueContract->id,
            'event_type'          => RevenueContractEvent::TYPE_ADVANCE_RECEIVED,
            'event_date'          => $data['date'],
            'amount'              => $data['amount'],
            'description'         => "Advance received: R " . number_format((float) $data['amount'], 2),
            'journal_status'      => RevenueContractEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerRevenuePostingDispatcher::class)->dispatch($event->id, $revenueContract->id, auth()->id(), 'advance_receipt', $data);

        return response()->json(['success' => true, 'note' => 'Advance receipt posted by AI in background.']);
    }

    public function releaseLiability(RevenueContract $revenueContract, Request $request): JsonResponse
    {
        $this->authorise($revenueContract);

        $data = $request->validate([
            'performance_obligation_id' => ['required', 'integer', Rule::exists('performance_obligations', 'id')->where('revenue_contract_id', $revenueContract->id)],
            'amount'                    => ['required', 'numeric', 'gt:0'],
            'date'                      => ['required', 'date'],
        ]);

        $obligation = PerformanceObligation::findOrFail($data['performance_obligation_id']);

        $event = RevenueContractEvent::create([
            'revenue_contract_id' => $revenueContract->id,
            'event_type'          => RevenueContractEvent::TYPE_LIABILITY_RELEASED,
            'event_date'          => $data['date'],
            'amount'              => $data['amount'],
            'description'         => "Liability released: {$obligation->name} — R " . number_format((float) $data['amount'], 2),
            'journal_status'      => RevenueContractEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerRevenuePostingDispatcher::class)->dispatch($event->id, $revenueContract->id, auth()->id(), 'release_liability', $data);

        return response()->json(['success' => true, 'note' => 'Liability release posted by AI in background.']);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────

    private function contractSummary(RevenueContract $c): array
    {
        return [
            'id'                      => $c->id,
            'name'                    => $c->name,
            'contract_reference'      => $c->contract_reference,
            'customer_name'           => $c->customer_name,
            'contract_date'           => $c->contract_date?->format('Y-m-d'),
            'total_transaction_price' => (float) $c->total_transaction_price,
            'total_revenue_recognised' => $c->totalRevenueRecognised(),
            'status'                  => $c->status,
            'performance_obligations' => $c->relationLoaded('performanceObligations')
                ? $c->performanceObligations->map(fn (PerformanceObligation $o) => [
                    'id'                  => $o->id,
                    'name'                => $o->name,
                    'standalone_price'    => (float) $o->standalone_price,
                    'allocated_price'     => (float) ($o->allocated_price ?? $o->standalone_price),
                    'recognition_method'  => $o->recognition_method,
                    'revenue_recognised'  => (float) ($o->revenue_recognised ?? 0),
                    'percentage_complete' => (float) ($o->percentage_complete ?? 0),
                    'status'              => $o->status,
                ])
                : null,
        ];
    }

    private function authorise(RevenueContract $contract): void
    {
        abort_unless($contract->company->user_id === auth()->id(), 403);
    }

    private function company(Request $request)
    {
        return $request->user()->companies()->firstOrFail();
    }
}
