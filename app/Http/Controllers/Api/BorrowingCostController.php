<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BorrowingCostCapitalisation;
use App\Models\BorrowingCostEvent;
use App\Models\Company;
use App\Services\RoadRunnerBorrowingCostPostingDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BorrowingCostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $company = $this->company($request);

        $capitalisations = $company->borrowingCostCapitalisations()
            ->orderByDesc('capitalisation_start_date')
            ->get()
            ->map(fn (BorrowingCostCapitalisation $c) => $this->capitalisationSummary($c));

        return response()->json($capitalisations);
    }

    public function store(Request $request): JsonResponse
    {
        $company = $this->company($request);

        $data = $request->validate([
            'qualifying_asset_type'      => ['required', 'string', 'max:50'],
            'qualifying_asset_id'        => ['required', 'integer'],
            'borrowing_source'           => ['required', 'string', 'max:200'],
            'capitalisation_start_date'  => ['required', 'date'],
            'borrowing_rate'             => ['required', 'numeric', 'min:0'],
            'weighted_average_rate'      => ['nullable', 'numeric', 'min:0'],
            'notes'                      => ['nullable', 'string'],
        ]);

        $capitalisation = $company->borrowingCostCapitalisations()->create($data);

        return response()->json([
            'success'        => true,
            'capitalisation' => $this->capitalisationSummary($capitalisation),
            'note'           => 'Borrowing cost capitalisation created.',
        ], 201);
    }

    public function show(BorrowingCostCapitalisation $borrowingCostCapitalisation): JsonResponse
    {
        $this->authorise($borrowingCostCapitalisation);

        return response()->json($this->capitalisationSummary($borrowingCostCapitalisation));
    }

    public function update(BorrowingCostCapitalisation $borrowingCostCapitalisation, Request $request): JsonResponse
    {
        $this->authorise($borrowingCostCapitalisation);

        $data = $request->validate([
            'borrowing_source'      => ['sometimes', 'string', 'max:200'],
            'borrowing_rate'        => ['sometimes', 'numeric', 'min:0'],
            'weighted_average_rate' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'notes'                 => ['sometimes', 'nullable', 'string'],
        ]);

        $borrowingCostCapitalisation->update($data);

        return response()->json(['success' => true, 'capitalisation' => $this->capitalisationSummary($borrowingCostCapitalisation->fresh())]);
    }

    public function capitalise(BorrowingCostCapitalisation $borrowingCostCapitalisation, Request $request): JsonResponse
    {
        $this->authorise($borrowingCostCapitalisation);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'date'   => ['required', 'date'],
        ]);

        $event = BorrowingCostEvent::create([
            'borrowing_cost_capitalisation_id' => $borrowingCostCapitalisation->id,
            'event_type'                       => BorrowingCostEvent::TYPE_CAPITALISATION,
            'event_date'                       => $data['date'],
            'amount'                           => $data['amount'],
            'description'                      => 'Capitalised R ' . number_format((float) $data['amount'], 2) . ' borrowing costs to qualifying asset',
            'journal_status'                   => BorrowingCostEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerBorrowingCostPostingDispatcher::class)->dispatch($event->id, $borrowingCostCapitalisation->id, auth()->id(), 'capitalise', $data);

        return response()->json([
            'success'        => true,
            'capitalisation' => $this->capitalisationSummary($borrowingCostCapitalisation->fresh()),
            'event'          => $this->eventSummary($event),
            'note'           => 'Capitalisation recorded. AI will post the journal in the background.',
        ]);
    }

    public function suspend(BorrowingCostCapitalisation $borrowingCostCapitalisation, Request $request): JsonResponse
    {
        $this->authorise($borrowingCostCapitalisation);

        $data = $request->validate([
            'date' => ['required', 'date'],
        ]);

        $borrowingCostCapitalisation->update(['status' => BorrowingCostCapitalisation::STATUS_SUSPENDED]);

        $event = BorrowingCostEvent::create([
            'borrowing_cost_capitalisation_id' => $borrowingCostCapitalisation->id,
            'event_type'                       => BorrowingCostEvent::TYPE_SUSPENSION,
            'event_date'                       => $data['date'],
            'amount'                           => 0,
            'description'                      => 'Capitalisation suspended per IAS 23.20',
            'journal_status'                   => BorrowingCostEvent::STATUS_POSTED,
        ]);

        return response()->json([
            'success'        => true,
            'capitalisation' => $this->capitalisationSummary($borrowingCostCapitalisation->fresh()),
            'event'          => $this->eventSummary($event),
            'note'           => 'Capitalisation suspended.',
        ]);
    }

    public function complete(BorrowingCostCapitalisation $borrowingCostCapitalisation, Request $request): JsonResponse
    {
        $this->authorise($borrowingCostCapitalisation);

        $data = $request->validate([
            'date' => ['required', 'date'],
        ]);

        $borrowingCostCapitalisation->update([
            'status'                  => BorrowingCostCapitalisation::STATUS_COMPLETED,
            'capitalisation_end_date' => $data['date'],
        ]);

        $event = BorrowingCostEvent::create([
            'borrowing_cost_capitalisation_id' => $borrowingCostCapitalisation->id,
            'event_type'                       => BorrowingCostEvent::TYPE_COMPLETION,
            'event_date'                       => $data['date'],
            'amount'                           => 0,
            'description'                      => 'Capitalisation completed — qualifying asset ready for intended use/sale',
            'journal_status'                   => BorrowingCostEvent::STATUS_POSTED,
        ]);

        return response()->json([
            'success'        => true,
            'capitalisation' => $this->capitalisationSummary($borrowingCostCapitalisation->fresh()),
            'event'          => $this->eventSummary($event),
            'note'           => 'Capitalisation completed.',
        ]);
    }

    private function company(Request $request): Company
    {
        $company = Company::findOrFail($request->input('company_id'));
        abort_unless($company->user_id === auth()->id(), 403);
        return $company;
    }

    private function authorise(BorrowingCostCapitalisation $c): void
    {
        abort_unless($c->company->user_id === auth()->id(), 403);
    }

    private function capitalisationSummary(BorrowingCostCapitalisation $c): array
    {
        return [
            'id'                        => $c->id,
            'qualifying_asset_id'       => $c->qualifying_asset_id,
            'qualifying_asset_type'     => $c->qualifying_asset_type,
            'borrowing_source'          => $c->borrowing_source,
            'capitalisation_start_date' => $c->capitalisation_start_date?->format('Y-m-d'),
            'capitalisation_end_date'   => $c->capitalisation_end_date?->format('Y-m-d'),
            'status'                    => $c->status,
            'total_capitalised'         => (float) $c->total_capitalised,
            'borrowing_rate'            => (float) $c->borrowing_rate,
            'weighted_average_rate'     => $c->weighted_average_rate !== null ? (float) $c->weighted_average_rate : null,
            'monthly_capitalisable'     => $c->monthlyCapitalisableAmount(),
            'notes'                     => $c->notes,
        ];
    }

    private function eventSummary(BorrowingCostEvent $e): array
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
