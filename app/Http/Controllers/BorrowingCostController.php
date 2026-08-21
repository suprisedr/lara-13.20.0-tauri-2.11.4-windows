<?php

namespace App\Http\Controllers;

use App\Models\BorrowingCostCapitalisation;
use App\Models\BorrowingCostEvent;
use App\Models\Company;
use App\Services\RoadRunnerBorrowingCostPostingDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BorrowingCostController extends Controller
{
    public function index(Company $company): View
    {
        $this->authorizeCompany($company);

        $capitalisations = $company->borrowingCostCapitalisations()
            ->orderByDesc('capitalisation_start_date')
            ->orderBy('borrowing_source')
            ->get();

        return view('companies.borrowing-costs.index', compact('company', 'capitalisations'));
    }

    public function show(Company $company, BorrowingCostCapitalisation $borrowingCostCapitalisation): View
    {
        $this->authorizeCompany($company);
        abort_unless($borrowingCostCapitalisation->company_id === $company->id, 404);

        return view('companies.borrowing-costs.show', compact('company', 'borrowingCostCapitalisation'));
    }

    public function store(Company $company, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);

        $data = $request->validate([
            'qualifying_asset_type'      => ['required', 'string', 'max:50'],
            'qualifying_asset_id'        => ['required', 'integer'],
            'borrowing_source'           => ['required', 'string', 'max:200'],
            'capitalisation_start_date'  => ['required', 'date'],
            'borrowing_rate'             => ['required', 'numeric', 'min:0'],
            'weighted_average_rate'      => ['nullable', 'numeric', 'min:0'],
            'notes'                      => ['nullable', 'string'],
        ]);

        $company->borrowingCostCapitalisations()->create($data);

        return redirect()->route('companies.borrowing-costs.index', $company)
            ->with('success', 'Borrowing cost capitalisation added.');
    }

    public function update(Company $company, BorrowingCostCapitalisation $borrowingCostCapitalisation, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($borrowingCostCapitalisation->company_id === $company->id, 404);

        $data = $request->validate([
            'borrowing_source'           => ['required', 'string', 'max:200'],
            'borrowing_rate'             => ['required', 'numeric', 'min:0'],
            'weighted_average_rate'      => ['nullable', 'numeric', 'min:0'],
            'notes'                      => ['nullable', 'string'],
        ]);

        $borrowingCostCapitalisation->update($data);

        return redirect()->route('companies.borrowing-costs.show', [$company, $borrowingCostCapitalisation])
            ->with('success', 'Borrowing cost capitalisation updated.');
    }

    public function destroy(Company $company, BorrowingCostCapitalisation $borrowingCostCapitalisation): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($borrowingCostCapitalisation->company_id === $company->id, 404);

        $borrowingCostCapitalisation->delete();

        return redirect()->route('companies.borrowing-costs.index', $company)
            ->with('success', 'Borrowing cost capitalisation removed.');
    }

    // ─── IAS 23 Actions ──────────────────────────────────────────────────

    public function capitalise(Company $company, BorrowingCostCapitalisation $borrowingCostCapitalisation, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($borrowingCostCapitalisation->company_id === $company->id, 404);

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

        return redirect()->route('companies.borrowing-costs.show', [$company, $borrowingCostCapitalisation])
            ->with('success', 'Capitalisation recorded. AI is posting the journal.');
    }

    public function suspend(Company $company, BorrowingCostCapitalisation $borrowingCostCapitalisation, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($borrowingCostCapitalisation->company_id === $company->id, 404);

        $data = $request->validate([
            'date' => ['required', 'date'],
        ]);

        $borrowingCostCapitalisation->update(['status' => BorrowingCostCapitalisation::STATUS_SUSPENDED]);

        BorrowingCostEvent::create([
            'borrowing_cost_capitalisation_id' => $borrowingCostCapitalisation->id,
            'event_type'                       => BorrowingCostEvent::TYPE_SUSPENSION,
            'event_date'                       => $data['date'],
            'amount'                           => 0,
            'description'                      => 'Capitalisation suspended per IAS 23.20',
            'journal_status'                   => BorrowingCostEvent::STATUS_POSTED,
        ]);

        return redirect()->route('companies.borrowing-costs.show', [$company, $borrowingCostCapitalisation])
            ->with('success', 'Capitalisation suspended.');
    }

    public function complete(Company $company, BorrowingCostCapitalisation $borrowingCostCapitalisation, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($borrowingCostCapitalisation->company_id === $company->id, 404);

        $data = $request->validate([
            'date' => ['required', 'date'],
        ]);

        $borrowingCostCapitalisation->update([
            'status'                  => BorrowingCostCapitalisation::STATUS_COMPLETED,
            'capitalisation_end_date' => $data['date'],
        ]);

        BorrowingCostEvent::create([
            'borrowing_cost_capitalisation_id' => $borrowingCostCapitalisation->id,
            'event_type'                       => BorrowingCostEvent::TYPE_COMPLETION,
            'event_date'                       => $data['date'],
            'amount'                           => 0,
            'description'                      => 'Capitalisation completed — qualifying asset ready for intended use/sale',
            'journal_status'                   => BorrowingCostEvent::STATUS_POSTED,
        ]);

        return redirect()->route('companies.borrowing-costs.show', [$company, $borrowingCostCapitalisation])
            ->with('success', 'Capitalisation completed.');
    }

    public function history(Company $company, BorrowingCostCapitalisation $borrowingCostCapitalisation): JsonResponse
    {
        $this->authorizeCompany($company);
        abort_unless($borrowingCostCapitalisation->company_id === $company->id, 404);

        $events = BorrowingCostEvent::where('borrowing_cost_capitalisation_id', $borrowingCostCapitalisation->id)
            ->orderBy('event_date')
            ->orderBy('id')
            ->get();

        $txnIds = $events->pluck('transaction_id')->filter()->unique()->all();
        $txnMap = $txnIds ? \App\Models\Transaction::whereIn('id', $txnIds)->get()->keyBy('id') : collect();

        $events = $events->map(function (BorrowingCostEvent $e) use ($company, $borrowingCostCapitalisation, $txnMap) {
                $colors = BorrowingCostEvent::colors()[$e->event_type] ?? ['bg' => '#f3f4f6', 'color' => '#374151'];
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
                    'type'           => $e->event_type,
                    'label'          => BorrowingCostEvent::labels()[$e->event_type] ?? $e->event_type,
                    'date'           => $e->event_date->format('d M Y'),
                    'amount'         => number_format((float) $e->amount, 2),
                    'description'    => $e->description,
                    'journal_status' => $e->journal_status,
                    'bg'             => $colors['bg'],
                    'color'          => $colors['color'],
                    'transaction_url' => $txnUrl,
                    'retry_url'      => $e->journal_status === BorrowingCostEvent::STATUS_FAILED
                        ? route('companies.borrowing-costs.events.retry-posting', [$company, $borrowingCostCapitalisation, $e])
                        : null,
                ];
            });

        return response()->json($events);
    }

    public function retryPosting(Company $company, BorrowingCostCapitalisation $borrowingCostCapitalisation, BorrowingCostEvent $event): JsonResponse
    {
        $this->authorizeCompany($company);
        abort_unless($borrowingCostCapitalisation->company_id === $company->id, 404);
        abort_unless($event->borrowing_cost_capitalisation_id === $borrowingCostCapitalisation->id, 404);
        abort_unless($event->journal_status === BorrowingCostEvent::STATUS_FAILED, 422);

        $event->update(['journal_status' => BorrowingCostEvent::STATUS_PENDING]);

        $action = match ($event->event_type) {
            BorrowingCostEvent::TYPE_CAPITALISATION => 'capitalise',
            BorrowingCostEvent::TYPE_SUSPENSION     => 'suspend',
            BorrowingCostEvent::TYPE_COMPLETION     => 'complete',
            default                                 => $event->event_type,
        };

        $date = $event->event_date->format('Y-m-d');
        $data = match ($event->event_type) {
            BorrowingCostEvent::TYPE_CAPITALISATION => ['amount' => (string) $event->amount, 'date' => $date],
            default                                 => ['date' => $date],
        };

        app(RoadRunnerBorrowingCostPostingDispatcher::class)->dispatch($event->id, $borrowingCostCapitalisation->id, auth()->id(), $action, $data);

        return response()->json(['ok' => true]);
    }

    private function authorizeCompany(Company $company): void
    {
        abort_unless($company->user_id === auth()->id(), 403);
    }
}
