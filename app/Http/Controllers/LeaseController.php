<?php

namespace App\Http\Controllers;

use App\Services\RoadRunnerLeasePostingDispatcher;
use App\Models\Company;
use App\Models\Lease;
use App\Models\LeaseEvent;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LeaseController extends Controller
{
    public function index(Company $company, Request $request): View
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $asOf = $request->input('as_of', now()->format('Y-m-d'));
        $leases = $company->leases()->orderBy('commencement_date', 'desc')->get();

        return view('companies.leases.index', compact('company', 'leases', 'asOf'));
    }

    public function store(Company $company, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);

        $data = $request->validate([
            'role' => 'required|in:lessee,lessor',
            'classification' => 'nullable|in:finance,operating',
            'name' => 'required|string|max:200',
            'asset_tag' => 'nullable|string|max:60',
            'category' => 'required|in:' . implode(',', array_keys(Lease::CATEGORIES)),
            'counterparty' => 'nullable|string|max:200',
            'commencement_date' => 'required|date',
            'end_date' => 'required|date|after:commencement_date',
            'monthly_payment' => 'required|numeric|min:0.01',
            'payment_frequency' => 'required|in:' . implode(',', array_keys(Lease::PAYMENT_FREQUENCIES)),
            'incremental_borrowing_rate' => 'required|numeric|min:0|max:1',
            'initial_direct_costs' => 'nullable|numeric|min:0',
            'residual_value_guarantee' => 'nullable|numeric|min:0',
            'asset_fair_value' => 'nullable|numeric|min:0',
            'unguaranteed_residual' => 'nullable|numeric|min:0',
            'is_short_term' => 'nullable|boolean',
            'is_low_value' => 'nullable|boolean',
            'location' => 'nullable|string|max:200',
            'notes' => 'nullable|string|max:500',
        ]);

        $role = $data['role'];
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
            'is_short_term' => (bool) ($data['is_short_term'] ?? false),
            'is_low_value' => (bool) ($data['is_low_value'] ?? false),
        ];

        if ($role === 'lessee') {
            $pv = Lease::presentValueOfPayments($payment, $termMonths, $ibr);
            $rouCost = $pv + $idc;
            $extra['lease_liability_opening'] = $pv;
            $extra['rou_asset_cost'] = $rouCost;

            $lease = $company->leases()->create(array_merge($data, $extra));

            $event = $lease->events()->create([
                'event_date' => $data['commencement_date'],
                'type' => 'commencement',
                'amount' => $rouCost,
                'description' => 'Lease commenced — ROU asset R' . number_format($rouCost, 2) . ', lease liability R' . number_format($pv, 2),
            ]);

            app(RoadRunnerLeasePostingDispatcher::class)->dispatch($event->id, $lease->id, auth()->id(), 'commencement', $data);
        } else {
            $classification = $data['classification'] ?? 'operating';
            $extra['classification'] = $classification;

            if ($classification === 'finance') {
                $fairValue = (float) ($data['asset_fair_value'] ?? 0);
                $ugr = (float) ($data['unguaranteed_residual'] ?? 0);
                $totalPayments = $payment * $termMonths;
                $netInvestment = Lease::presentValueOfPayments($payment, $termMonths, $ibr) + ($ugr > 0 ? round($ugr / pow(1 + $ibr, $termMonths / 12), 2) : 0);
                $unearnedIncome = $totalPayments + $ugr - $netInvestment;

                $extra['asset_fair_value'] = $fairValue;
                $extra['unguaranteed_residual'] = $ugr;
                $extra['net_investment'] = $netInvestment;
                $extra['unearned_finance_income'] = $unearnedIncome;
                $extra['lease_liability_opening'] = 0;
                $extra['rou_asset_cost'] = 0;

                $lease = $company->leases()->create(array_merge($data, $extra));

                $event = $lease->events()->create([
                    'event_date' => $data['commencement_date'],
                    'type' => 'commencement',
                    'amount' => $netInvestment,
                    'description' => 'Finance lease commenced — net investment R' . number_format($netInvestment, 2) . ', unearned income R' . number_format($unearnedIncome, 2),
                ]);

                app(RoadRunnerLeasePostingDispatcher::class)->dispatch($event->id, $lease->id, auth()->id(), 'commencement', $data);
            } else {
                $extra['lease_liability_opening'] = 0;
                $extra['rou_asset_cost'] = 0;

                $lease = $company->leases()->create(array_merge($data, $extra));

                $monthlyIncome = $payment;
                $event = $lease->events()->create([
                    'event_date' => $data['commencement_date'],
                    'type' => 'commencement',
                    'amount' => $monthlyIncome * $termMonths,
                    'description' => 'Operating lease commenced — straight-line income R' . number_format($monthlyIncome, 2) . '/month for ' . $termMonths . ' months',
                ]);

                app(RoadRunnerLeasePostingDispatcher::class)->dispatch($event->id, $lease->id, auth()->id(), 'commencement', $data);
            }
        }

        return redirect()->route('companies.leases.show', [$company, $lease])
            ->with('success', $lease->name . ' lease recognised.');
    }

    public function show(Company $company, Lease $lease): View
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($lease->company_id === $company->id, 404);

        $events = $lease->events()->get();
        $asOf = now()->format('Y-m-d');

        return view('companies.leases.show', compact('company', 'lease', 'events', 'asOf'));
    }

    public function update(Company $company, Lease $lease, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($lease->company_id === $company->id, 404);

        $data = $request->validate([
            'name' => 'required|string|max:200',
            'asset_tag' => 'nullable|string|max:60',
            'category' => 'required|in:' . implode(',', array_keys(Lease::CATEGORIES)),
            'counterparty' => 'nullable|string|max:200',
            'location' => 'nullable|string|max:200',
            'notes' => 'nullable|string|max:500',
        ]);

        $lease->update($data);

        return redirect()->route('companies.leases.show', [$company, $lease])
            ->with('success', 'Lease details updated.');
    }

    public function destroy(Company $company, Lease $lease): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($lease->company_id === $company->id, 404);

        $lease->delete();

        return redirect()->route('companies.leases.index', $company)
            ->with('success', 'Lease removed from register.');
    }

    public function modify(Company $company, Lease $lease, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($lease->company_id === $company->id, 404);

        $data = $request->validate([
            'event_date' => 'required|date',
            'new_end_date' => 'required|date|after:' . $lease->commencement_date->format('Y-m-d'),
            'new_monthly_payment' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        $eventDate = Carbon::parse($data['event_date']);
        $newEnd = Carbon::parse($data['new_end_date']);
        $newPayment = (float) $data['new_monthly_payment'];
        $remainingMonths = (int) $eventDate->diffInMonths($newEnd);
        $ibr = (float) $lease->incremental_borrowing_rate;

        $oldLiability = $lease->leaseLiabilityBalance($data['event_date']);
        $newLiability = Lease::presentValueOfPayments($newPayment, $remainingMonths, $ibr);
        $adjustment = $newLiability - $oldLiability;

        $event = DB::transaction(function () use ($lease, $data, $newEnd, $newPayment, $remainingMonths, $newLiability, $adjustment) {
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
                    'old_liability' => round($lease->getOriginal('lease_liability_opening'), 2),
                    'new_liability' => $newLiability,
                ],
            ]);
        });

        app(RoadRunnerLeasePostingDispatcher::class)->dispatch($event->id, $lease->id, auth()->id(), 'modification', [
            'adjustment_amount' => $adjustment,
            'date' => $data['event_date'],
        ]);

        return redirect()->route('companies.leases.show', [$company, $lease])
            ->with('success', 'Lease modification recorded.');
    }

    public function impair(Company $company, Lease $lease, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($lease->company_id === $company->id, 404);

        $data = $request->validate([
            'event_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        $event = DB::transaction(function () use ($lease, $data) {
            $lease->increment('accumulated_impairment', (float) $data['amount']);

            return $lease->events()->create([
                'event_date' => $data['event_date'],
                'type' => 'impairment',
                'amount' => $data['amount'],
                'description' => $data['description'] ?? 'ROU asset impaired by R' . number_format((float) $data['amount'], 2),
            ]);
        });

        app(RoadRunnerLeasePostingDispatcher::class)->dispatch($event->id, $lease->id, auth()->id(), 'impairment', [
            'impairment_amount' => (float) $data['amount'],
            'date' => $data['event_date'],
        ]);

        return redirect()->route('companies.leases.show', [$company, $lease])
            ->with('success', 'Impairment recorded.');
    }

    public function reverseImpairment(Company $company, Lease $lease, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($lease->company_id === $company->id, 404);

        $data = $request->validate([
            'event_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01|max:' . (float) $lease->accumulated_impairment,
            'description' => 'nullable|string|max:255',
        ]);

        $event = DB::transaction(function () use ($lease, $data) {
            $lease->decrement('accumulated_impairment', (float) $data['amount']);

            return $lease->events()->create([
                'event_date' => $data['event_date'],
                'type' => 'reverse_impairment',
                'amount' => $data['amount'],
                'description' => $data['description'] ?? 'Impairment reversed by R' . number_format((float) $data['amount'], 2),
            ]);
        });

        app(RoadRunnerLeasePostingDispatcher::class)->dispatch($event->id, $lease->id, auth()->id(), 'reverse_impairment', [
            'reversal_amount' => (float) $data['amount'],
            'date' => $data['event_date'],
        ]);

        return redirect()->route('companies.leases.show', [$company, $lease])
            ->with('success', 'Impairment reversal recorded.');
    }

    public function terminate(Company $company, Lease $lease, Request $request): RedirectResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($lease->company_id === $company->id, 404);

        $data = $request->validate([
            'termination_date' => 'required|date',
            'description' => 'nullable|string|max:255',
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

        return redirect()->route('companies.leases.show', [$company, $lease])
            ->with('success', 'Lease terminated. ' . ($gainLoss >= 0 ? 'Gain' : 'Loss') . ' of R' . number_format(abs($gainLoss), 2) . '.');
    }

    public function amortisationSchedule(Company $company, Lease $lease): \Illuminate\Http\JsonResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($lease->company_id === $company->id, 404);

        if ($lease->isLessor() && $lease->isOperatingLease()) {
            return $this->operatingLeaseSchedule($lease);
        }

        if ($lease->isLessor() && $lease->isFinanceLease()) {
            return $this->lessorFinanceSchedule($lease);
        }

        return $this->lesseeSchedule($lease);
    }

    public function retryPosting(Company $company, Lease $lease, LeaseEvent $event): \Illuminate\Http\JsonResponse
    {
        abort_unless($company->user_id === auth()->id(), 403);
        abort_unless($lease->company_id === $company->id, 404);
        abort_unless($event->lease_id === $lease->id, 404);
        abort_unless($event->journal_status === LeaseEvent::STATUS_FAILED, 422);

        $event->update(['journal_status' => LeaseEvent::STATUS_PENDING]);

        $date    = $event->event_date->format('Y-m-d');
        $details = $event->details ?? [];
        $action  = $event->type;

        $data = match ($event->type) {
            'commencement'      => ['date' => $date, 'amount' => (string) $event->amount],
            'modification'      => ['adjustment_amount' => (string) $event->amount, 'date' => $date],
            'impairment'        => ['impairment_amount' => (string) $event->amount, 'date' => $date],
            'reverse_impairment'=> ['reversal_amount' => (string) $event->amount, 'date' => $date],
            'termination'       => ['date' => $details['gain_loss'] !== null ? $date : $date, 'gain_loss' => $details['gain_loss'] ?? (float) $event->amount],
            default             => [],
        };

        app(RoadRunnerLeasePostingDispatcher::class)->dispatch($event->id, $lease->id, auth()->id(), $action, $data);

        return response()->json(['ok' => true]);
    }

    private function lesseeSchedule(Lease $lease): \Illuminate\Http\JsonResponse
    {
        $schedule = [];
        $balance = (float) $lease->lease_liability_opening;
        $monthlyRate = (float) $lease->incremental_borrowing_rate / 12;
        $payment = (float) $lease->monthly_payment;
        $date = $lease->commencement_date->copy();
        $totalMonths = (int) $lease->lease_term_months;
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
                'acc_depreciation' => $accDep,
                'rou_nbv' => round(max(0, $rouCost - $accDep), 2),
            ];
        }

        return response()->json($schedule);
    }

    private function lessorFinanceSchedule(Lease $lease): \Illuminate\Http\JsonResponse
    {
        $schedule = [];
        $balance = (float) $lease->net_investment;
        $monthlyRate = (float) $lease->incremental_borrowing_rate / 12;
        $payment = (float) $lease->monthly_payment;
        $date = $lease->commencement_date->copy();
        $totalMonths = (int) $lease->lease_term_months;

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

        return response()->json($schedule);
    }

    private function operatingLeaseSchedule(Lease $lease): \Illuminate\Http\JsonResponse
    {
        $schedule = [];
        $payment = (float) $lease->monthly_payment;
        $date = $lease->commencement_date->copy();
        $totalMonths = (int) $lease->lease_term_months;
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

        return response()->json($schedule);
    }
}
