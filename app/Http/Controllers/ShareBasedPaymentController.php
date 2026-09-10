<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\ShareBasedPaymentArrangement;
use App\Models\ShareBasedPaymentEvent;
use App\Services\RoadRunnerShareBasedPaymentPostingDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ShareBasedPaymentController extends Controller
{
    public function index(Company $company): View
    {
        $this->authorizeCompany($company);

        $arrangements = $company->shareBasedPaymentArrangements()
            ->orderByDesc('grant_date')
            ->orderBy('name')
            ->get();

        return view('companies.share-based-payments.index', compact('company', 'arrangements'));
    }

    public function show(Company $company, ShareBasedPaymentArrangement $arrangement): View
    {
        $this->authorizeCompany($company);
        abort_unless($arrangement->company_id === $company->id, 404);

        return view('companies.share-based-payments.show', compact('company', 'arrangement'));
    }

    public function store(Company $company, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);

        $data = $request->validate([
            'name'                 => ['required', 'string', 'max:200'],
            'arrangement_type'     => ['required', Rule::in([ShareBasedPaymentArrangement::TYPE_EQUITY_SETTLED, ShareBasedPaymentArrangement::TYPE_CASH_SETTLED, ShareBasedPaymentArrangement::TYPE_CHOICE])],
            'grant_date'           => ['required', 'date'],
            'vesting_start_date'   => ['nullable', 'date'],
            'vesting_end_date'     => ['nullable', 'date'],
            'number_of_instruments' => ['required', 'integer', 'min:1'],
            'exercise_price'       => ['nullable', 'numeric', 'min:0'],
            'fair_value_at_grant'  => ['required', 'numeric', 'min:0'],
            'vesting_conditions'   => ['nullable', 'string'],
            'notes'                => ['nullable', 'string'],
        ]);

        $arrangement = $company->shareBasedPaymentArrangements()->create($data);

        // Record the grant event (no journal posting for grant itself — expense is recognised over vesting period)
        ShareBasedPaymentEvent::create([
            'share_based_payment_arrangement_id' => $arrangement->id,
            'event_type'                         => ShareBasedPaymentEvent::TYPE_GRANT,
            'event_date'                         => $data['grant_date'],
            'amount'                             => (float) $data['fair_value_at_grant'] * (int) $data['number_of_instruments'],
            'instruments_affected'               => $data['number_of_instruments'],
            'description'                        => "Grant: {$arrangement->name} — {$data['number_of_instruments']} instruments at FV R " . number_format((float) $data['fair_value_at_grant'], 2),
            'journal_status'                     => ShareBasedPaymentEvent::STATUS_POSTED, // No journal for grant
        ]);

        return redirect()->route('companies.share-based-payments.index', $company)
            ->with('success', 'Share-based payment arrangement registered.');
    }

    public function update(Company $company, ShareBasedPaymentArrangement $arrangement, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($arrangement->company_id === $company->id, 404);

        $data = $request->validate([
            'name'               => ['required', 'string', 'max:200'],
            'vesting_start_date' => ['nullable', 'date'],
            'vesting_end_date'   => ['nullable', 'date'],
            'vesting_conditions' => ['nullable', 'string'],
            'notes'              => ['nullable', 'string'],
        ]);

        $arrangement->update($data);

        return redirect()->route('companies.share-based-payments.show', [$company, $arrangement])
            ->with('success', 'Arrangement updated.');
    }

    public function destroy(Company $company, ShareBasedPaymentArrangement $arrangement): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($arrangement->company_id === $company->id, 404);

        $arrangement->delete();

        return redirect()->route('companies.share-based-payments.index', $company)
            ->with('success', 'Arrangement removed.');
    }

    // ─── IFRS 2 Actions ─────────────────────────────────────────────────

    public function vestingExpense(Company $company, ShareBasedPaymentArrangement $arrangement, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($arrangement->company_id === $company->id, 404);

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

        return redirect()->route('companies.share-based-payments.show', [$company, $arrangement])
            ->with('success', 'Vesting expense recorded. AI is posting the journal.');
    }

    public function exercise(Company $company, ShareBasedPaymentArrangement $arrangement, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($arrangement->company_id === $company->id, 404);

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

        return redirect()->route('companies.share-based-payments.show', [$company, $arrangement])
            ->with('success', 'Exercise recorded.' . ($remaining <= 0 ? ' All instruments exercised.' : '') . ' AI is posting the journal.');
    }

    public function forfeit(Company $company, ShareBasedPaymentArrangement $arrangement, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($arrangement->company_id === $company->id, 404);

        $data = $request->validate([
            'instruments' => ['required', 'integer', 'min:1'],
            'date'        => ['required', 'date'],
        ]);

        $remaining = $arrangement->number_of_instruments - (int) $data['instruments'];
        $amount    = (float) $arrangement->fair_value_at_grant * (int) $data['instruments'];

        // Reverse cumulative expense proportionally
        $totalInstruments = $arrangement->number_of_instruments;
        $expenseReversal  = $totalInstruments > 0
            ? round((float) $arrangement->total_expense * ((int) $data['instruments'] / $totalInstruments), 2)
            : $amount;

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

        return redirect()->route('companies.share-based-payments.show', [$company, $arrangement])
            ->with('success', 'Forfeiture recorded.' . ($remaining <= 0 ? ' Arrangement fully forfeited.' : '') . ' AI is posting the journal.');
    }

    public function history(Company $company, ShareBasedPaymentArrangement $arrangement): JsonResponse
    {
        $this->authorizeCompany($company);
        abort_unless($arrangement->company_id === $company->id, 404);

        $events = ShareBasedPaymentEvent::where('share_based_payment_arrangement_id', $arrangement->id)
            ->orderBy('event_date')
            ->orderBy('id')
            ->get();

        $txnIds = $events->pluck('transaction_id')->filter()->unique()->all();
        $txnMap = $txnIds ? \App\Models\Transaction::whereIn('id', $txnIds)->get()->keyBy('id') : collect();

        $events = $events->map(function (ShareBasedPaymentEvent $e) use ($company, $arrangement, $txnMap) {
            $colors = ShareBasedPaymentEvent::colors()[$e->event_type] ?? ['bg' => '#f3f4f6', 'color' => '#374151'];
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
                'label'           => ShareBasedPaymentEvent::labels()[$e->event_type] ?? $e->event_type,
                'date'            => $e->event_date->format('d M Y'),
                'amount'          => number_format((float) $e->amount, 2),
                'instruments'     => $e->instruments_affected,
                'description'     => $e->description,
                'journal_status'  => $e->journal_status,
                'bg'              => $colors['bg'],
                'color'           => $colors['color'],
                'transaction_url' => $txnUrl,
                'retry_url'       => $e->journal_status === ShareBasedPaymentEvent::STATUS_FAILED
                    ? route('companies.share-based-payments.events.retry-posting', [$company, $arrangement, $e])
                    : null,
            ];
        });

        return response()->json($events);
    }

    public function retryPosting(Company $company, ShareBasedPaymentArrangement $arrangement, ShareBasedPaymentEvent $event): JsonResponse
    {
        $this->authorizeCompany($company);
        abort_unless($arrangement->company_id === $company->id, 404);
        abort_unless($event->share_based_payment_arrangement_id === $arrangement->id, 404);
        abort_unless($event->journal_status === ShareBasedPaymentEvent::STATUS_FAILED, 422);

        $event->update(['journal_status' => ShareBasedPaymentEvent::STATUS_PENDING]);

        $action = match ($event->event_type) {
            ShareBasedPaymentEvent::TYPE_VESTING_EXPENSE => 'vesting_expense',
            ShareBasedPaymentEvent::TYPE_EXERCISE        => 'exercise',
            ShareBasedPaymentEvent::TYPE_FORFEITURE      => 'forfeiture',
            default                                      => $event->event_type,
        };

        $date = $event->event_date->format('Y-m-d');
        $data = match ($event->event_type) {
            ShareBasedPaymentEvent::TYPE_VESTING_EXPENSE => ['amount' => (string) $event->amount, 'date' => $date],
            ShareBasedPaymentEvent::TYPE_EXERCISE        => ['amount' => (string) $event->amount, 'instruments' => $event->instruments_affected, 'date' => $date],
            ShareBasedPaymentEvent::TYPE_FORFEITURE      => ['amount' => (string) $event->amount, 'instruments' => $event->instruments_affected, 'date' => $date],
            default                                      => [],
        };

        app(RoadRunnerShareBasedPaymentPostingDispatcher::class)->dispatch($event->id, $arrangement->id, auth()->id(), $action, $data);

        return response()->json(['ok' => true]);
    }

    private function authorizeCompany(Company $company): void
    {
        abort_unless($company->user_id === auth()->id(), 403);
    }
}
