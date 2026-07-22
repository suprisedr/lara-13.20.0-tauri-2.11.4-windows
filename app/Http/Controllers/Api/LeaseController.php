<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RoadRunnerLeasePostingDispatcher;
use App\Models\Lease;
use App\Models\LeaseEvent;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'status' => ['nullable', 'in:active,terminated,expired,exempt,all'],
        ]);

        $asOf = now()->toDateString();
        $query = Lease::where('company_id', $data['company_id'])
            ->orderByDesc('commencement_date');

        $status = $data['status'] ?? 'all';

        $leases = $query->get()->map(function (Lease $l) use ($asOf) {
            return $this->serialize($l, $asOf);
        });

        if ($status !== 'all') {
            $leases = $leases->filter(fn ($l) => $l['status'] === $status)->values();
        }

        return response()->json(['data' => $leases]);
    }

    public function show(Lease $lease): JsonResponse
    {
        $asOf = now()->toDateString();
        $lease->load('events');

        $data = $this->serialize($lease, $asOf);
        $data['events'] = $lease->events->map(fn (LeaseEvent $e) => [
            'id' => $e->id,
            'event_date' => $e->event_date->format('Y-m-d'),
            'type' => $e->type,
            'type_label' => $e->type_label,
            'amount' => (float) $e->amount,
            'description' => $e->description,
            'details' => $e->details,
        ]);

        return response()->json(['data' => $data]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'role' => ['nullable', 'in:lessee,lessor'],
            'classification' => ['nullable', 'in:finance,operating'],
            'name' => ['required', 'string', 'max:200'],
            'asset_tag' => ['nullable', 'string', 'max:60'],
            'category' => ['required', 'in:' . implode(',', array_keys(Lease::CATEGORIES))],
            'counterparty' => ['nullable', 'string', 'max:200'],
            'commencement_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:commencement_date'],
            'monthly_payment' => ['required', 'numeric', 'min:0.01'],
            'payment_frequency' => ['nullable', 'in:' . implode(',', array_keys(Lease::PAYMENT_FREQUENCIES))],
            'incremental_borrowing_rate' => ['required', 'numeric', 'min:0', 'max:1'],
            'initial_direct_costs' => ['nullable', 'numeric', 'min:0'],
            'residual_value_guarantee' => ['nullable', 'numeric', 'min:0'],
            'asset_fair_value' => ['nullable', 'numeric', 'min:0'],
            'unguaranteed_residual' => ['nullable', 'numeric', 'min:0'],
            'is_short_term' => ['nullable', 'boolean'],
            'is_low_value' => ['nullable', 'boolean'],
            'location' => ['nullable', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $role = $data['role'] ?? 'lessee';
        $commence = Carbon::parse($data['commencement_date']);
        $end = Carbon::parse($data['end_date']);
        $termMonths = (int) $commence->diffInMonths($end);
        $ibr = (float) $data['incremental_borrowing_rate'];
        $payment = (float) $data['monthly_payment'];
        $idc = (float) ($data['initial_direct_costs'] ?? 0);

        $extra = [
            'role' => $role,
            'lease_term_months' => $termMonths,
            'initial_direct_costs' => $idc,
            'payment_frequency' => $data['payment_frequency'] ?? 'monthly',
            'is_short_term' => (bool) ($data['is_short_term'] ?? false),
            'is_low_value' => (bool) ($data['is_low_value'] ?? false),
        ];

        [$lease, $event] = DB::transaction(function () use ($data, $role, $termMonths, $ibr, $payment, $idc, $extra) {
            if ($role === 'lessee') {
                $pv = Lease::presentValueOfPayments($payment, $termMonths, $ibr);
                $rouCost = $pv + $idc;
                $extra['lease_liability_opening'] = $pv;
                $extra['rou_asset_cost'] = $rouCost;

                $lease = Lease::create(array_merge($data, $extra));
                $event = $lease->events()->create([
                    'event_date' => $data['commencement_date'],
                    'type' => 'commencement',
                    'amount' => $rouCost,
                    'description' => 'Lease commenced — ROU asset R' . number_format($rouCost, 2) . ', lease liability R' . number_format($pv, 2),
                ]);
            } else {
                $classification = $data['classification'] ?? 'operating';
                $extra['classification'] = $classification;

                if ($classification === 'finance') {
                    $ugr = (float) ($data['unguaranteed_residual'] ?? 0);
                    $netInvestment = Lease::presentValueOfPayments($payment, $termMonths, $ibr)
                        + ($ugr > 0 ? round($ugr / pow(1 + $ibr, $termMonths / 12), 2) : 0);
                    $unearnedIncome = ($payment * $termMonths) + $ugr - $netInvestment;

                    $extra['asset_fair_value'] = (float) ($data['asset_fair_value'] ?? 0);
                    $extra['unguaranteed_residual'] = $ugr;
                    $extra['net_investment'] = $netInvestment;
                    $extra['unearned_finance_income'] = $unearnedIncome;
                    $extra['lease_liability_opening'] = 0;
                    $extra['rou_asset_cost'] = 0;

                    $lease = Lease::create(array_merge($data, $extra));
                    $event = $lease->events()->create([
                        'event_date' => $data['commencement_date'],
                        'type' => 'commencement',
                        'amount' => $netInvestment,
                        'description' => 'Finance lease commenced — net investment R' . number_format($netInvestment, 2),
                    ]);
                } else {
                    $extra['lease_liability_opening'] = 0;
                    $extra['rou_asset_cost'] = 0;

                    $lease = Lease::create(array_merge($data, $extra));
                    $event = $lease->events()->create([
                        'event_date' => $data['commencement_date'],
                        'type' => 'commencement',
                        'amount' => $payment * $termMonths,
                        'description' => 'Operating lease commenced — straight-line income R' . number_format($payment, 2) . '/month',
                    ]);
                }
            }

            return [$lease, $event];
        });

        app(RoadRunnerLeasePostingDispatcher::class)->dispatch($event->id, $lease->id, auth()->id(), 'commencement', $data);

        return response()->json([
            'data' => $this->serialize($lease, now()->toDateString()),
            'message' => 'Lease recognised successfully.',
        ], 201);
    }

    public function update(Lease $lease, Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:200'],
            'asset_tag' => ['nullable', 'string', 'max:60'],
            'category' => ['sometimes', 'in:' . implode(',', array_keys(Lease::CATEGORIES))],
            'counterparty' => ['nullable', 'string', 'max:200'],
            'location' => ['nullable', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $lease->update($data);

        return response()->json([
            'data' => $this->serialize($lease->fresh(), now()->toDateString()),
            'message' => 'Lease updated.',
        ]);
    }

    public function modify(Lease $lease, Request $request): JsonResponse
    {
        $data = $request->validate([
            'event_date' => ['required', 'date'],
            'new_end_date' => ['required', 'date', 'after:' . $lease->commencement_date->format('Y-m-d')],
            'new_monthly_payment' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $eventDate = Carbon::parse($data['event_date']);
        $newEnd = Carbon::parse($data['new_end_date']);
        $newPayment = (float) $data['new_monthly_payment'];
        $remainingMonths = (int) $eventDate->diffInMonths($newEnd);
        $ibr = (float) $lease->incremental_borrowing_rate;

        $oldLiability = $lease->leaseLiabilityBalance($data['event_date']);
        $newLiability = Lease::presentValueOfPayments($newPayment, $remainingMonths, $ibr);
        $adjustment = $newLiability - $oldLiability;

        $event = DB::transaction(function () use ($lease, $data, $newEnd, $newPayment, $adjustment, $newLiability, $remainingMonths) {
            $newTermMonths = (int) $lease->commencement_date->diffInMonths($newEnd);

            $lease->update([
                'end_date' => $newEnd,
                'monthly_payment' => $newPayment,
                'lease_term_months' => $newTermMonths,
                'rou_asset_cost' => (float) $lease->rou_asset_cost + $adjustment,
                'lease_liability_opening' => (float) $lease->lease_liability_opening + $adjustment,
            ]);

            return $lease->events()->create([
                'event_date' => $data['event_date'],
                'type' => 'modification',
                'amount' => $adjustment,
                'description' => $data['description'] ?? 'Lease modified — liability adjusted by R' . number_format($adjustment, 2),
                'details' => [
                    'new_end_date' => $newEnd->format('Y-m-d'),
                    'new_monthly_payment' => $newPayment,
                    'remaining_months' => $remainingMonths,
                    'new_liability' => $newLiability,
                ],
            ]);
        });

        app(RoadRunnerLeasePostingDispatcher::class)->dispatch($event->id, $lease->id, auth()->id(), 'modification', [
            'adjustment_amount' => $adjustment,
            'date' => $data['event_date'],
        ]);

        return response()->json([
            'data' => $this->serialize($lease->fresh(), now()->toDateString()),
            'message' => 'Lease modification recorded. Liability adjusted by R' . number_format($adjustment, 2) . '.',
        ]);
    }

    public function impair(Lease $lease, Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $event = DB::transaction(function () use ($lease, $data) {
            $lease->increment('accumulated_impairment', (float) $data['amount']);

            return $lease->events()->create([
                'event_date' => $data['date'],
                'type' => 'impairment',
                'amount' => $data['amount'],
                'description' => $data['reason'] ?? 'ROU asset impaired by R' . number_format((float) $data['amount'], 2),
            ]);
        });

        app(RoadRunnerLeasePostingDispatcher::class)->dispatch($event->id, $lease->id, auth()->id(), 'impairment', [
            'impairment_amount' => (float) $data['amount'],
            'date' => $data['date'],
        ]);

        return response()->json([
            'data' => $this->serialize($lease->fresh(), now()->toDateString()),
            'message' => 'Impairment of R' . number_format((float) $data['amount'], 2) . ' recorded.',
        ]);
    }

    public function reverseImpairment(Lease $lease, Request $request): JsonResponse
    {
        $maxReverse = (float) $lease->accumulated_impairment;
        if ($maxReverse <= 0) {
            return response()->json(['message' => 'No accumulated impairment to reverse.'], 422);
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:' . $maxReverse],
            'date' => ['required', 'date'],
        ]);

        $event = DB::transaction(function () use ($lease, $data) {
            $lease->decrement('accumulated_impairment', (float) $data['amount']);

            return $lease->events()->create([
                'event_date' => $data['date'],
                'type' => 'reverse_impairment',
                'amount' => $data['amount'],
                'description' => 'Impairment reversed by R' . number_format((float) $data['amount'], 2),
            ]);
        });

        app(RoadRunnerLeasePostingDispatcher::class)->dispatch($event->id, $lease->id, auth()->id(), 'reverse_impairment', [
            'reversal_amount' => (float) $data['amount'],
            'date' => $data['date'],
        ]);

        return response()->json([
            'data' => $this->serialize($lease->fresh(), now()->toDateString()),
            'message' => 'Impairment reversal of R' . number_format((float) $data['amount'], 2) . ' recorded.',
        ]);
    }

    public function terminate(Lease $lease, Request $request): JsonResponse
    {
        if ($lease->isTerminated()) {
            return response()->json(['message' => 'Lease already terminated.'], 422);
        }

        $data = $request->validate([
            'termination_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $termDate = $data['termination_date'];
        $rouNbv = $lease->rouNetBookValue($termDate);
        $liabilityBal = $lease->leaseLiabilityBalance($termDate);
        $gainLoss = $liabilityBal - $rouNbv;

        $event = DB::transaction(function () use ($lease, $data, $termDate, $gainLoss, $rouNbv, $liabilityBal) {
            $lease->update([
                'status' => 'terminated',
                'termination_date' => $termDate,
                'termination_gain_loss' => $gainLoss,
            ]);

            return $lease->events()->create([
                'event_date' => $termDate,
                'type' => 'termination',
                'amount' => $gainLoss,
                'description' => $data['description'] ?? 'Lease terminated early',
                'details' => [
                    'rou_nbv_derecognised' => $rouNbv,
                    'liability_derecognised' => $liabilityBal,
                    'gain_loss' => $gainLoss,
                ],
            ]);
        });

        app(RoadRunnerLeasePostingDispatcher::class)->dispatch($event->id, $lease->id, auth()->id(), 'termination', [
            'date' => $termDate,
            'gain_loss' => $gainLoss,
        ]);

        return response()->json([
            'data' => $this->serialize($lease->fresh(), now()->toDateString()),
            'message' => 'Lease terminated. ' . ($gainLoss >= 0 ? 'Gain' : 'Loss') . ' of R' . number_format(abs($gainLoss), 2) . '.',
        ]);
    }

    public function schedule(Lease $lease): JsonResponse
    {
        $schedule = [];
        $payment = (float) $lease->monthly_payment;
        $date = $lease->commencement_date->copy();
        $totalMonths = (int) $lease->lease_term_months;
        $monthlyRate = (float) $lease->incremental_borrowing_rate / 12;

        if ($lease->isLessor() && $lease->isOperatingLease()) {
            $accIncome = 0.0;
            for ($i = 1; $i <= $totalMonths; $i++) {
                $accIncome += $payment;
                $schedule[] = [
                    'month' => $i,
                    'date' => $date->addMonth()->format('Y-m-d'),
                    'rental_income' => $payment,
                    'accumulated_income' => round($accIncome, 2),
                ];
            }
        } elseif ($lease->isLessor() && $lease->isFinanceLease()) {
            $balance = (float) $lease->net_investment;
            for ($i = 1; $i <= $totalMonths && $balance > 0; $i++) {
                $financeIncome = round($balance * $monthlyRate, 2);
                $capital = round(min($payment - $financeIncome, $balance), 2);
                $balance = round(max(0, $balance + $financeIncome - $payment), 2);
                $schedule[] = [
                    'month' => $i,
                    'date' => $date->addMonth()->format('Y-m-d'),
                    'payment_received' => $payment,
                    'finance_income' => $financeIncome,
                    'capital_repayment' => $capital,
                    'net_investment_balance' => $balance,
                ];
            }
        } else {
            $balance = (float) $lease->lease_liability_opening;
            $rouCost = (float) $lease->rou_asset_cost;
            $rvg = (float) ($lease->residual_value_guarantee ?? 0);
            $depreciable = $rouCost - $rvg;

            for ($i = 1; $i <= $totalMonths && $balance > 0; $i++) {
                $interest = round($balance * $monthlyRate, 2);
                $capital = round(min($payment - $interest, $balance), 2);
                $balance = round(max(0, $balance + $interest - $payment), 2);
                $depMonth = round($depreciable / $totalMonths, 2);
                $accDep = round($depMonth * $i, 2);

                $schedule[] = [
                    'month' => $i,
                    'date' => $date->addMonth()->format('Y-m-d'),
                    'payment' => $payment,
                    'interest' => $interest,
                    'capital' => $capital,
                    'liability_balance' => $balance,
                    'depreciation' => $depMonth,
                    'rou_nbv' => round(max(0, $rouCost - $accDep), 2),
                ];
            }
        }

        return response()->json(['data' => $schedule]);
    }

    private function serialize(Lease $l, string $asOf): array
    {
        $terminated = $l->isTerminated();
        $expired = $l->isExpired($asOf);
        $exempt = $l->isExempt();
        $status = $terminated ? 'terminated' : ($expired ? 'expired' : ($exempt ? 'exempt' : 'active'));
        $isLessee = $l->isLessee();
        $isFinance = $l->isFinanceLease();
        $isOperating = $l->isOperatingLease();

        $result = [
            'id' => $l->id,
            'company_id' => $l->company_id,
            'role' => $l->role ?? 'lessee',
            'classification' => $l->classification,
            'name' => $l->name,
            'asset_tag' => $l->asset_tag,
            'category' => $l->category,
            'category_label' => Lease::CATEGORIES[$l->category] ?? $l->category,
            'counterparty' => $l->counterparty,
            'commencement_date' => $l->commencement_date->format('Y-m-d'),
            'end_date' => $l->end_date->format('Y-m-d'),
            'lease_term_months' => (int) $l->lease_term_months,
            'monthly_payment' => (float) $l->monthly_payment,
            'payment_frequency' => $l->payment_frequency,
            'incremental_borrowing_rate' => (float) $l->incremental_borrowing_rate,
            'initial_direct_costs' => (float) ($l->initial_direct_costs ?? 0),
            'residual_value_guarantee' => (float) ($l->residual_value_guarantee ?? 0),
            'remaining_months' => $l->remainingMonths($asOf),
            'is_short_term' => (bool) $l->is_short_term,
            'is_low_value' => (bool) $l->is_low_value,
            'status' => $status,
            'termination_date' => $l->termination_date?->format('Y-m-d'),
            'termination_gain_loss' => $l->termination_gain_loss ? (float) $l->termination_gain_loss : null,
            'location' => $l->location,
            'notes' => $l->notes,
        ];

        if ($isLessee) {
            $result['rou_asset_cost'] = (float) $l->rou_asset_cost;
            $result['lease_liability_opening'] = (float) $l->lease_liability_opening;
            $result['accumulated_depreciation'] = $l->accumulatedDepreciation($asOf);
            $result['accumulated_impairment'] = (float) ($l->accumulated_impairment ?? 0);
            $result['rou_net_book_value'] = $l->rouNetBookValue($asOf);
            $result['lease_liability_balance'] = $l->leaseLiabilityBalance($asOf);
        } elseif ($isFinance) {
            $result['net_investment'] = (float) $l->net_investment;
            $result['unearned_finance_income'] = (float) $l->unearned_finance_income;
            $result['asset_fair_value'] = (float) ($l->asset_fair_value ?? 0);
            $result['unguaranteed_residual'] = (float) ($l->unguaranteed_residual ?? 0);
            $result['net_investment_balance'] = $l->netInvestmentBalance($asOf);
            $result['finance_income_earned'] = $l->financeIncomeToDate($asOf);
            $result['unearned_income_balance'] = $l->unearnedIncomeBalance($asOf);
        } elseif ($isOperating) {
            $result['operating_income_to_date'] = $l->operatingLeaseIncomeToDate($asOf);
            $result['total_lease_income'] = (float) $l->monthly_payment * (int) $l->lease_term_months;
        }

        return $result;
    }
}
