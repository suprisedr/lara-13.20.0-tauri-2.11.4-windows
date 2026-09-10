<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\PerformanceObligation;
use App\Models\RevenueContract;
use App\Models\RevenueContractEvent;
use App\Services\RoadRunnerRevenuePostingDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RevenueContractController extends Controller
{
    public function index(Company $company): View
    {
        $this->authorizeCompany($company);

        $contracts = $company->revenueContracts()
            ->withCount('performanceObligations')
            ->orderByDesc('contract_date')
            ->orderBy('name')
            ->get();

        return view('companies.revenue-contracts.index', compact('company', 'contracts'));
    }

    public function show(Company $company, RevenueContract $revenueContract): View
    {
        $this->authorizeCompany($company);
        abort_unless($revenueContract->company_id === $company->id, 404);

        $revenueContract->load('performanceObligations');

        return view('companies.revenue-contracts.show', compact('company', 'revenueContract'));
    }

    public function store(Company $company, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);

        $data = $request->validate([
            'name'                    => ['required', 'string', 'max:200'],
            'contract_reference'      => ['nullable', 'string', 'max:60'],
            'customer_name'           => ['required', 'string', 'max:200'],
            'contract_date'           => ['required', 'date'],
            'total_transaction_price' => ['required', 'numeric', 'min:0'],
            'currency'                => ['nullable', 'string', 'max:3'],
            'notes'                   => ['nullable', 'string'],
        ]);

        $company->revenueContracts()->create($data);

        return redirect()->route('companies.revenue-contracts.index', $company)
            ->with('success', 'Revenue contract added.');
    }

    public function update(Company $company, RevenueContract $revenueContract, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($revenueContract->company_id === $company->id, 404);

        $data = $request->validate([
            'name'                    => ['required', 'string', 'max:200'],
            'contract_reference'      => ['nullable', 'string', 'max:60'],
            'customer_name'           => ['required', 'string', 'max:200'],
            'total_transaction_price' => ['required', 'numeric', 'min:0'],
            'notes'                   => ['nullable', 'string'],
        ]);

        $revenueContract->update($data);

        return redirect()->route('companies.revenue-contracts.show', [$company, $revenueContract])
            ->with('success', 'Revenue contract updated.');
    }

    public function destroy(Company $company, RevenueContract $revenueContract): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($revenueContract->company_id === $company->id, 404);

        $revenueContract->delete();

        return redirect()->route('companies.revenue-contracts.index', $company)
            ->with('success', 'Revenue contract removed.');
    }

    // ─── Performance Obligations ─────────────────────────────────────────

    public function storeObligation(Company $company, RevenueContract $revenueContract, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($revenueContract->company_id === $company->id, 404);

        $data = $request->validate([
            'name'                 => ['required', 'string', 'max:200'],
            'standalone_price'     => ['required', 'numeric', 'min:0'],
            'allocated_price'      => ['nullable', 'numeric', 'min:0'],
            'recognition_method'   => ['required', Rule::in([PerformanceObligation::METHOD_POINT_IN_TIME, PerformanceObligation::METHOD_OVER_TIME])],
            'over_time_method'     => ['nullable', 'string'],
        ]);

        $data['revenue_contract_id'] = $revenueContract->id;
        PerformanceObligation::create($data);

        return redirect()->route('companies.revenue-contracts.show', [$company, $revenueContract])
            ->with('success', 'Performance obligation added.');
    }

    public function destroyObligation(Company $company, RevenueContract $revenueContract, PerformanceObligation $obligation): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($revenueContract->company_id === $company->id, 404);
        abort_unless($obligation->revenue_contract_id === $revenueContract->id, 404);

        $obligation->delete();

        return redirect()->route('companies.revenue-contracts.show', [$company, $revenueContract])
            ->with('success', 'Performance obligation removed.');
    }

    // ─── IFRS 15 Actions ─────────────────────────────────────────────────

    public function recogniseRevenue(Company $company, RevenueContract $revenueContract, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($revenueContract->company_id === $company->id, 404);

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

        return redirect()->route('companies.revenue-contracts.show', [$company, $revenueContract])
            ->with('success', 'Revenue recognition recorded. AI is posting the journal.');
    }

    public function advanceReceipt(Company $company, RevenueContract $revenueContract, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($revenueContract->company_id === $company->id, 404);

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

        return redirect()->route('companies.revenue-contracts.show', [$company, $revenueContract])
            ->with('success', 'Advance receipt recorded. AI is posting the journal.');
    }

    public function releaseLiability(Company $company, RevenueContract $revenueContract, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($revenueContract->company_id === $company->id, 404);

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

        return redirect()->route('companies.revenue-contracts.show', [$company, $revenueContract])
            ->with('success', 'Liability release recorded. AI is posting the journal.');
    }

    public function history(Company $company, RevenueContract $revenueContract): JsonResponse
    {
        $this->authorizeCompany($company);
        abort_unless($revenueContract->company_id === $company->id, 404);

        $events = RevenueContractEvent::where('revenue_contract_id', $revenueContract->id)
            ->orderBy('event_date')
            ->orderBy('id')
            ->get();

        $txnIds = $events->pluck('transaction_id')->filter()->unique()->all();
        $txnMap = $txnIds ? \App\Models\Transaction::whereIn('id', $txnIds)->get()->keyBy('id') : collect();

        $events = $events->map(function (RevenueContractEvent $e) use ($company, $revenueContract, $txnMap) {
            $colors = RevenueContractEvent::colors()[$e->event_type] ?? ['bg' => '#f3f4f6', 'color' => '#374151'];
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
                'label'           => RevenueContractEvent::labels()[$e->event_type] ?? $e->event_type,
                'date'            => $e->event_date->format('d M Y'),
                'amount'          => number_format((float) $e->amount, 2),
                'description'     => $e->description,
                'journal_status'  => $e->journal_status,
                'bg'              => $colors['bg'],
                'color'           => $colors['color'],
                'transaction_url' => $txnUrl,
                'retry_url'       => $e->journal_status === RevenueContractEvent::STATUS_FAILED
                    ? route('companies.revenue-contracts.events.retry-posting', [$company, $revenueContract, $e])
                    : null,
            ];
        });

        return response()->json($events);
    }

    public function retryPosting(Company $company, RevenueContract $revenueContract, RevenueContractEvent $event): JsonResponse
    {
        $this->authorizeCompany($company);
        abort_unless($revenueContract->company_id === $company->id, 404);
        abort_unless($event->revenue_contract_id === $revenueContract->id, 404);
        abort_unless($event->journal_status === RevenueContractEvent::STATUS_FAILED, 422);

        $event->update(['journal_status' => RevenueContractEvent::STATUS_PENDING]);

        $action = match ($event->event_type) {
            RevenueContractEvent::TYPE_REVENUE_RECOGNISED => 'recognise_revenue',
            RevenueContractEvent::TYPE_ADVANCE_RECEIVED   => 'advance_receipt',
            RevenueContractEvent::TYPE_LIABILITY_RELEASED  => 'release_liability',
            default                                        => $event->event_type,
        };

        $date = $event->event_date->format('Y-m-d');
        app(RoadRunnerRevenuePostingDispatcher::class)->dispatch($event->id, $revenueContract->id, auth()->id(), $action, ['amount' => (string) $event->amount, 'date' => $date]);

        return response()->json(['ok' => true]);
    }

    private function authorizeCompany(Company $company): void
    {
        abort_unless($company->user_id === auth()->id(), 403);
    }
}
