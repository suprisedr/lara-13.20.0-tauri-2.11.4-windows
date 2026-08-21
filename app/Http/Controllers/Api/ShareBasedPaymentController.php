<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\ShareBasedPaymentArrangement;
use App\Models\ShareBasedPaymentEvent;
use App\Services\RoadRunnerShareBasedPaymentPostingDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShareBasedPaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $company = $this->company($request);

        $arrangements = $company->shareBasedPaymentArrangements()
            ->orderByDesc('grant_date')
            ->get()
            ->map(fn (ShareBasedPaymentArrangement $a) => $this->arrangementSummary($a));

        return response()->json($arrangements);
    }

    public function store(Request $request): JsonResponse
    {
        $company = $this->company($request);

        $data = $request->validate([
            'name'                  => ['required', 'string', 'max:200'],
            'arrangement_type'      => ['required', Rule::in([ShareBasedPaymentArrangement::TYPE_EQUITY_SETTLED, ShareBasedPaymentArrangement::TYPE_CASH_SETTLED, ShareBasedPaymentArrangement::TYPE_CHOICE])],
            'grant_date'            => ['required', 'date'],
            'vesting_start_date'    => ['nullable', 'date'],
            'vesting_end_date'      => ['nullable', 'date'],
            'number_of_instruments' => ['required', 'integer', 'min:1'],
            'exercise_price'        => ['nullable', 'numeric', 'min:0'],
            'fair_value_at_grant'   => ['required', 'numeric', 'min:0'],
            'vesting_conditions'    => ['nullable', 'string'],
            'notes'                 => ['nullable', 'string'],
        ]);

        $arrangement = $company->shareBasedPaymentArrangements()->create($data);

        ShareBasedPaymentEvent::create([
            'share_based_payment_arrangement_id' => $arrangement->id,
            'event_type'                         => ShareBasedPaymentEvent::TYPE_GRANT,
            'event_date'                         => $data['grant_date'],
            'amount'                             => (float) $data['fair_value_at_grant'] * (int) $data['number_of_instruments'],
            'instruments_affected'               => $data['number_of_instruments'],
            'description'                        => "Grant: {$arrangement->name} — {$data['number_of_instruments']} instruments at FV R " . number_format((float) $data['fair_value_at_grant'], 2),
            'journal_status'                     => ShareBasedPaymentEvent::STATUS_POSTED,
        ]);

        return response()->json([
            'success'     => true,
            'arrangement' => $this->arrangementSummary($arrangement->fresh()),
            'note'        => 'Share-based payment arrangement created. Record vesting expenses periodically over the vesting period.',
        ], 201);
    }

    public function show(ShareBasedPaymentArrangement $arrangement): JsonResponse
    {
        $this->authorise($arrangement);

        return response()->json($this->arrangementSummary($arrangement));
    }

    public function update(ShareBasedPaymentArrangement $arrangement, Request $request): JsonResponse
    {
        $this->authorise($arrangement);

        $data = $request->validate([
            'name'               => ['sometimes', 'string', 'max:200'],
            'vesting_start_date' => ['sometimes', 'nullable', 'date'],
            'vesting_end_date'   => ['sometimes', 'nullable', 'date'],
            'vesting_conditions' => ['sometimes', 'nullable', 'string'],
            'notes'              => ['sometimes', 'nullable', 'string'],
        ]);

        $arrangement->update($data);

        return response()->json(['success' => true, 'arrangement' => $this->arrangementSummary($arrangement->fresh())]);
    }

    public function vestingExpense(ShareBasedPaymentArrangement $arrangement, Request $request): JsonResponse
    {
        $this->authorise($arrangement);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'date'   => ['required', 'date'],
        ]);

        $arrangement->increment('total_expense', (float) $data['amount']);

        $event = ShareBasedPaymentEvent::create([
            'share_based_payment_arrangement_id' => $arrangement->id,
            'event_type'                         => ShareBasedPaymentEvent::TYPE_VESTING_EXPENSE,
            'event_date'                         => $data['date'],
            'amount'                             => $data['amount'],
            'description'                        => "Vesting expense: R " . number_format((float) $data['amount'], 2) . " — {$arrangement->name}",
            'journal_status'                     => ShareBasedPaymentEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerShareBasedPaymentPostingDispatcher::class)->dispatch($event->id, $arrangement->id, auth()->id(), 'vesting_expense', $data);

        return response()->json([
            'success'     => true,
            'arrangement' => $this->arrangementSummary($arrangement->fresh()),
            'event'       => $this->eventSummary($event),
            'note'        => 'Vesting expense recorded. AI will post the journal in the background.',
        ]);
    }

    public function exercise(ShareBasedPaymentArrangement $arrangement, Request $request): JsonResponse
    {
        $this->authorise($arrangement);

        $data = $request->validate([
            'instruments' => ['required', 'integer', 'min:1'],
            'date'        => ['required', 'date'],
        ]);

        $remaining = $arrangement->number_of_instruments - (int) $data['instruments'];
        $amount    = (float) $arrangement->fair_value_at_grant * (int) $data['instruments'];

        $arrangement->update([
            'number_of_instruments' => max($remaining, 0),
            'status'                => $remaining <= 0 ? ShareBasedPaymentArrangement::STATUS_VESTED : $arrangement->status,
        ]);

        $event = ShareBasedPaymentEvent::create([
            'share_based_payment_arrangement_id' => $arrangement->id,
            'event_type'                         => ShareBasedPaymentEvent::TYPE_EXERCISE,
            'event_date'                         => $data['date'],
            'amount'                             => $amount,
            'instruments_affected'               => $data['instruments'],
            'description'                        => "Exercised {$data['instruments']} instruments — R " . number_format($amount, 2),
            'journal_status'                     => ShareBasedPaymentEvent::STATUS_PENDING,
        ]);

        $data['amount']      = $amount;
        $data['instruments'] = (int) $data['instruments'];

        app(RoadRunnerShareBasedPaymentPostingDispatcher::class)->dispatch($event->id, $arrangement->id, auth()->id(), 'exercise', $data);

        return response()->json([
            'success'     => true,
            'arrangement' => $this->arrangementSummary($arrangement->fresh()),
            'event'       => $this->eventSummary($event),
            'note'        => 'Exercise recorded.' . ($remaining <= 0 ? ' All instruments exercised.' : '') . ' AI will post the journal in the background.',
        ]);
    }

    public function forfeit(ShareBasedPaymentArrangement $arrangement, Request $request): JsonResponse
    {
        $this->authorise($arrangement);

        $data = $request->validate([
            'instruments' => ['required', 'integer', 'min:1'],
            'date'        => ['required', 'date'],
        ]);

        $remaining = $arrangement->number_of_instruments - (int) $data['instruments'];
        $totalInstruments = $arrangement->number_of_instruments;
        $expenseReversal  = $totalInstruments > 0
            ? round((float) $arrangement->total_expense * ((int) $data['instruments'] / $totalInstruments), 2)
            : (float) $arrangement->fair_value_at_grant * (int) $data['instruments'];

        $arrangement->update([
            'number_of_instruments' => max($remaining, 0),
            'total_expense'         => max((float) $arrangement->total_expense - $expenseReversal, 0),
            'status'                => $remaining <= 0 ? ShareBasedPaymentArrangement::STATUS_FORFEITED : $arrangement->status,
        ]);

        $event = ShareBasedPaymentEvent::create([
            'share_based_payment_arrangement_id' => $arrangement->id,
            'event_type'                         => ShareBasedPaymentEvent::TYPE_FORFEITURE,
            'event_date'                         => $data['date'],
            'amount'                             => $expenseReversal,
            'instruments_affected'               => $data['instruments'],
            'description'                        => "Forfeited {$data['instruments']} instruments — reversed R " . number_format($expenseReversal, 2),
            'journal_status'                     => ShareBasedPaymentEvent::STATUS_PENDING,
        ]);

        $data['amount']      = $expenseReversal;
        $data['instruments'] = (int) $data['instruments'];

        app(RoadRunnerShareBasedPaymentPostingDispatcher::class)->dispatch($event->id, $arrangement->id, auth()->id(), 'forfeiture', $data);

        return response()->json([
            'success'     => true,
            'arrangement' => $this->arrangementSummary($arrangement->fresh()),
            'event'       => $this->eventSummary($event),
            'note'        => 'Forfeiture recorded.' . ($remaining <= 0 ? ' Arrangement fully forfeited.' : '') . ' AI will post the journal in the background.',
        ]);
    }

    private function company(Request $request): Company
    {
        $company = Company::findOrFail($request->input('company_id'));
        abort_unless($company->user_id === auth()->id(), 403);
        return $company;
    }

    private function authorise(ShareBasedPaymentArrangement $a): void
    {
        abort_unless($a->company->user_id === auth()->id(), 403);
    }

    private function arrangementSummary(ShareBasedPaymentArrangement $a): array
    {
        return [
            'id'                    => $a->id,
            'name'                  => $a->name,
            'arrangement_type'      => $a->arrangement_type,
            'grant_date'            => $a->grant_date?->format('Y-m-d'),
            'vesting_start_date'    => $a->vesting_start_date?->format('Y-m-d'),
            'vesting_end_date'      => $a->vesting_end_date?->format('Y-m-d'),
            'number_of_instruments' => $a->number_of_instruments,
            'exercise_price'        => $a->exercise_price !== null ? (float) $a->exercise_price : null,
            'fair_value_at_grant'   => (float) $a->fair_value_at_grant,
            'total_expense'         => (float) $a->total_expense,
            'vesting_percentage'    => $a->vestingPercentage(),
            'status'                => $a->status,
        ];
    }

    private function eventSummary(ShareBasedPaymentEvent $e): array
    {
        return [
            'id'                   => $e->id,
            'event_type'           => $e->event_type,
            'event_date'           => $e->event_date->format('Y-m-d'),
            'amount'               => (float) $e->amount,
            'instruments_affected' => $e->instruments_affected,
            'journal_status'       => $e->journal_status,
        ];
    }
}
