<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\DeferredTaxItem;
use App\Models\DeferredTaxEvent;
use App\Services\RoadRunnerDeferredTaxPostingDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeferredTaxController extends Controller
{
    public function index(Company $company): View
    {
        $this->authorizeCompany($company);

        $items = $company->deferredTaxItems()
            ->orderByDesc('measurement_date')
            ->orderBy('name')
            ->get();

        return view('companies.deferred-tax.index', compact('company', 'items'));
    }

    public function show(Company $company, DeferredTaxItem $deferredTaxItem): View
    {
        $this->authorizeCompany($company);
        abort_unless($deferredTaxItem->company_id === $company->id, 404);

        return view('companies.deferred-tax.show', compact('company', 'deferredTaxItem'));
    }

    public function store(Company $company, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);

        $data = $request->validate([
            'name'            => ['required', 'string', 'max:200'],
            'source_type'     => ['required', Rule::in([
                DeferredTaxItem::SOURCE_PPE,
                DeferredTaxItem::SOURCE_INTANGIBLE,
                DeferredTaxItem::SOURCE_LEASE,
                DeferredTaxItem::SOURCE_PROVISION,
                DeferredTaxItem::SOURCE_REVENUE_CONTRACT,
                DeferredTaxItem::SOURCE_INVENTORY,
                DeferredTaxItem::SOURCE_OTHER,
            ])],
            'source_id'       => ['nullable', 'integer'],
            'tax_base'        => ['required', 'numeric', 'min:0'],
            'carrying_amount' => ['required', 'numeric', 'min:0'],
            'tax_rate'        => ['required', 'numeric', 'min:0', 'max:100'],
            'is_taxable'      => ['boolean'],
            'measurement_date'=> ['required', 'date'],
            'notes'           => ['nullable', 'string'],
        ]);

        $temporaryDifference = (float) $data['carrying_amount'] - (float) $data['tax_base'];
        $deferredTax = abs($temporaryDifference) * (float) $data['tax_rate'] / 100;

        $data['temporary_difference']    = $temporaryDifference;
        $data['deferred_tax_asset']      = $temporaryDifference < 0 ? $deferredTax : 0;
        $data['deferred_tax_liability']  = $temporaryDifference > 0 ? $deferredTax : 0;
        $data['is_taxable']              = $data['is_taxable'] ?? true;
        $data['status']                  = DeferredTaxItem::STATUS_ACTIVE;

        $item = $company->deferredTaxItems()->create($data);

        DeferredTaxEvent::create([
            'deferred_tax_item_id' => $item->id,
            'event_type'           => DeferredTaxEvent::TYPE_INITIAL_RECOGNITION,
            'event_date'           => $data['measurement_date'],
            'amount'               => $deferredTax,
            'previous_balance'     => 0,
            'new_balance'          => $deferredTax,
            'description'          => "Initial recognition: {$item->name} — R " . number_format($deferredTax, 2),
            'journal_status'       => DeferredTaxEvent::STATUS_PENDING,
        ]);

        return redirect()->route('companies.deferred-tax.index', $company)
            ->with('success', 'Deferred tax item registered.');
    }

    public function update(Company $company, DeferredTaxItem $deferredTaxItem, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($deferredTaxItem->company_id === $company->id, 404);

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:200'],
            'tax_rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'notes'    => ['nullable', 'string'],
        ]);

        $deferredTaxItem->update($data);

        return redirect()->route('companies.deferred-tax.show', [$company, $deferredTaxItem])
            ->with('success', 'Deferred tax item updated.');
    }

    public function destroy(Company $company, DeferredTaxItem $deferredTaxItem): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($deferredTaxItem->company_id === $company->id, 404);

        $deferredTaxItem->delete();

        return redirect()->route('companies.deferred-tax.index', $company)
            ->with('success', 'Deferred tax item removed.');
    }

    // ─── IAS 12 Actions ─────────────────────────────────────────────────

    public function remeasure(Company $company, DeferredTaxItem $deferredTaxItem, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($deferredTaxItem->company_id === $company->id, 404);

        $data = $request->validate([
            'new_carrying_amount' => ['required', 'numeric'],
            'new_tax_base'        => ['required', 'numeric'],
            'date'                => ['required', 'date'],
        ]);

        $previousBalance = $deferredTaxItem->netPosition();

        $temporaryDifference = (float) $data['new_carrying_amount'] - (float) $data['new_tax_base'];
        $deferredTax = abs($temporaryDifference) * (float) $deferredTaxItem->tax_rate / 100;

        $deferredTaxItem->update([
            'carrying_amount'       => $data['new_carrying_amount'],
            'tax_base'              => $data['new_tax_base'],
            'temporary_difference'  => $temporaryDifference,
            'deferred_tax_asset'    => $temporaryDifference < 0 ? $deferredTax : 0,
            'deferred_tax_liability'=> $temporaryDifference > 0 ? $deferredTax : 0,
            'measurement_date'      => $data['date'],
        ]);

        $newBalance = $deferredTaxItem->fresh()->netPosition();

        $event = DeferredTaxEvent::create([
            'deferred_tax_item_id' => $deferredTaxItem->id,
            'event_type'           => DeferredTaxEvent::TYPE_REMEASUREMENT,
            'event_date'           => $data['date'],
            'amount'               => abs($newBalance - $previousBalance),
            'previous_balance'     => $previousBalance,
            'new_balance'          => $newBalance,
            'description'          => "Remeasured: {$deferredTaxItem->name} — R " . number_format($previousBalance, 2) . " → R " . number_format($newBalance, 2),
            'journal_status'       => DeferredTaxEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerDeferredTaxPostingDispatcher::class)->dispatch($event->id, $deferredTaxItem->id, auth()->id(), 'remeasure', $data);

        return redirect()->route('companies.deferred-tax.show', [$company, $deferredTaxItem])
            ->with('success', 'Remeasurement recorded. AI is posting the journal.');
    }

    public function reverse(Company $company, DeferredTaxItem $deferredTaxItem, Request $request): RedirectResponse
    {
        $this->authorizeCompany($company);
        abort_unless($deferredTaxItem->company_id === $company->id, 404);

        $data = $request->validate([
            'date' => ['required', 'date'],
        ]);

        $previousBalance = $deferredTaxItem->netPosition();
        $amount = abs($previousBalance);

        $deferredTaxItem->update([
            'status'                => DeferredTaxItem::STATUS_REVERSED,
            'deferred_tax_asset'    => 0,
            'deferred_tax_liability'=> 0,
            'temporary_difference'  => 0,
        ]);

        $event = DeferredTaxEvent::create([
            'deferred_tax_item_id' => $deferredTaxItem->id,
            'event_type'           => DeferredTaxEvent::TYPE_REVERSAL,
            'event_date'           => $data['date'],
            'amount'               => $amount,
            'previous_balance'     => $previousBalance,
            'new_balance'          => 0,
            'description'          => "Reversed: {$deferredTaxItem->name} — R " . number_format($amount, 2),
            'journal_status'       => DeferredTaxEvent::STATUS_PENDING,
        ]);

        app(RoadRunnerDeferredTaxPostingDispatcher::class)->dispatch($event->id, $deferredTaxItem->id, auth()->id(), 'reverse', $data);

        return redirect()->route('companies.deferred-tax.show', [$company, $deferredTaxItem])
            ->with('success', 'Reversal recorded. AI is posting the journal.');
    }

    public function history(Company $company, DeferredTaxItem $deferredTaxItem): JsonResponse
    {
        $this->authorizeCompany($company);
        abort_unless($deferredTaxItem->company_id === $company->id, 404);

        $events = DeferredTaxEvent::where('deferred_tax_item_id', $deferredTaxItem->id)
            ->orderBy('event_date')
            ->orderBy('id')
            ->get();

        $txnIds = $events->pluck('transaction_id')->filter()->unique()->all();
        $txnMap = $txnIds ? \App\Models\Transaction::whereIn('id', $txnIds)->get()->keyBy('id') : collect();

        $events = $events->map(function (DeferredTaxEvent $e) use ($company, $deferredTaxItem, $txnMap) {
            $colors = DeferredTaxEvent::colors()[$e->event_type] ?? ['bg' => '#f3f4f6', 'color' => '#374151'];
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
                'id'               => $e->id,
                'type'             => $e->event_type,
                'label'            => DeferredTaxEvent::labels()[$e->event_type] ?? $e->event_type,
                'date'             => $e->event_date->format('d M Y'),
                'amount'           => number_format((float) $e->amount, 2),
                'previous_balance' => number_format((float) $e->previous_balance, 2),
                'new_balance'      => number_format((float) $e->new_balance, 2),
                'description'      => $e->description,
                'journal_status'   => $e->journal_status,
                'bg'               => $colors['bg'],
                'color'            => $colors['color'],
                'transaction_url'  => $txnUrl,
                'retry_url'        => $e->journal_status === DeferredTaxEvent::STATUS_FAILED
                    ? route('companies.deferred-tax.events.retry-posting', [$company, $deferredTaxItem, $e])
                    : null,
            ];
        });

        return response()->json($events);
    }

    public function retryPosting(Company $company, DeferredTaxItem $deferredTaxItem, DeferredTaxEvent $event): JsonResponse
    {
        $this->authorizeCompany($company);
        abort_unless($deferredTaxItem->company_id === $company->id, 404);
        abort_unless($event->deferred_tax_item_id === $deferredTaxItem->id, 404);
        abort_unless($event->journal_status === DeferredTaxEvent::STATUS_FAILED, 422);

        $event->update(['journal_status' => DeferredTaxEvent::STATUS_PENDING]);

        $action = match ($event->event_type) {
            DeferredTaxEvent::TYPE_INITIAL_RECOGNITION => 'initial_recognition',
            DeferredTaxEvent::TYPE_REMEASUREMENT       => 'remeasure',
            DeferredTaxEvent::TYPE_RATE_CHANGE         => 'rate_change',
            DeferredTaxEvent::TYPE_REVERSAL            => 'reverse',
            default                                    => $event->event_type,
        };

        $date = $event->event_date->format('Y-m-d');
        $data = ['amount' => (string) $event->amount, 'date' => $date];

        app(RoadRunnerDeferredTaxPostingDispatcher::class)->dispatch($event->id, $deferredTaxItem->id, auth()->id(), $action, $data);

        return response()->json(['ok' => true]);
    }

    private function authorizeCompany(Company $company): void
    {
        abort_unless($company->user_id === auth()->id(), 403);
    }
}
