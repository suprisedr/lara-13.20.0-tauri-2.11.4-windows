<?php

namespace App\Services;

use App\Models\Company;
use App\Models\GroupEliminationLine;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Builds consolidated (group) financial statements in line with IFRS 10
 * Consolidated Financial Statements and IFRS 3 Business Combinations,
 * including changes in the parent's ownership interest over time.
 *
 * Ownership changes (see SubsidiaryOwnershipEvent) are handled as follows:
 *  - Goodwill is measured once, at the date control is obtained, and frozen
 *    thereafter (partial-goodwill method).
 *  - Increases/decreases while control is retained are equity transactions:
 *    they do not change goodwill or profit; the difference between the
 *    consideration and the NCI adjustment is recognised directly in equity.
 *    Because NCI is measured proportionately (no NCI goodwill), the NCI line
 *    at any reporting date is simply current NCI% × current net assets, and
 *    the equity-transaction premium falls out into owners' equity via the
 *    accounting identity.
 *  - Obtaining control in stages remeasures the previously held interest to
 *    fair value, with the gain/loss recognised in profit or loss.
 *  - Losing control derecognises the subsidiary and recognises a gain/loss in
 *    profit or loss; the entity is consolidated only up to the disposal date.
 *
 * When a subsidiary has no recorded ownership events the engine falls back to
 * the static snapshot stored on the company (single acquisition, unchanged
 * holding) — preserving the simpler behaviour.
 */
class ConsolidationService
{
    /**
     * Consolidated statement of financial position as at a date.
     *
     * @return array<string, mixed>
     */
    public function balanceSheet(Company $parent, string $asOfDate): array
    {
        $entities = $parent->groupEntities($asOfDate);

        $worksheet = $entities->map(function (Company $entity) use ($asOfDate, $parent) {
            $t = $this->entityBalanceSheetTotals($entity, $asOfDate);
            $t['company'] = $entity;
            $t['is_parent'] = $entity->id === $parent->id;

            return $t;
        });

        $combinedCurrentAssets         = $worksheet->sum('current_assets');
        $combinedNonCurrentAssets      = $worksheet->sum('non_current_assets');
        $combinedCurrentLiabilities    = $worksheet->sum('current_liabilities');
        $combinedNonCurrentLiabilities = $worksheet->sum('non_current_liabilities');

        $eliminatedInvestment = 0.0;
        $goodwill             = 0.0;
        $nci                  = 0.0;
        $ownershipReserve     = 0.0;
        $subDetails           = [];

        foreach ($entities as $entity) {
            if ($entity->id === $parent->id) {
                continue;
            }

            $p          = $entity->ownershipFractionAt($asOfDate);
            $equityCur  = $this->entityBalanceSheetTotals($entity, $asOfDate)['equity'];
            $invCost    = (float) ($entity->investment_cost ?? 0);
            $goodwill_s = $this->goodwillForSubsidiary($entity, $equityCur);
            $nci_s      = (1 - $p) * $equityCur;

            // Cumulative equity-transaction adjustments up to the reporting date.
            $reserve_s = $entity->ownershipEventsAsSubsidiary()
                ->whereIn('type', ['increase', 'decrease'])
                ->whereDate('event_date', '<=', $asOfDate)
                ->get()
                ->sum(fn($e) => $e->equityAdjustment());

            $eliminatedInvestment += $invCost;
            $goodwill             += $goodwill_s;
            $nci                  += $nci_s;
            $ownershipReserve     += $reserve_s;

            $subDetails[] = [
                'company'            => $entity,
                'ownership'          => $p,
                'equity_current'     => $equityCur,
                'investment_cost'    => $invCost,
                'goodwill'           => $goodwill_s,
                'nci'                => $nci_s,
                'equity_reserve'     => $reserve_s,
            ];
        }

        $elim = $this->eliminationEffects($parent);

        $nonCurrentAssets = $combinedNonCurrentAssets - $eliminatedInvestment + $elim['non_current_assets'];
        $currentAssets    = $combinedCurrentAssets + $elim['current_assets'];
        $goodwill        += $elim['goodwill'];

        $currentLiabilities    = $combinedCurrentLiabilities + $elim['current_liabilities'];
        $nonCurrentLiabilities = $combinedNonCurrentLiabilities + $elim['non_current_liabilities'];

        $totalAssets = $currentAssets + $nonCurrentAssets + $goodwill;
        $totalLiabilities = $currentLiabilities + $nonCurrentLiabilities;

        $totalEquity  = $totalAssets - $totalLiabilities;
        $ownersEquity = $totalEquity - $nci;

        return [
            'asOfDate'              => $asOfDate,
            'worksheet'             => $worksheet,
            'subDetails'            => $subDetails,
            'currentAssets'         => $currentAssets,
            'nonCurrentAssets'      => $nonCurrentAssets,
            'goodwill'              => $goodwill,
            'totalAssets'           => $totalAssets,
            'currentLiabilities'    => $currentLiabilities,
            'nonCurrentLiabilities' => $nonCurrentLiabilities,
            'totalLiabilities'      => $totalLiabilities,
            'totalEquity'           => $totalEquity,
            'ownersEquity'          => $ownersEquity,
            'nci'                   => $nci,
            'ownershipReserve'      => $ownershipReserve,
            'eliminatedInvestment'  => $eliminatedInvestment,
            'manualEliminations'    => $elim,
        ];
    }

    /**
     * Consolidated statement of profit or loss for a period, including the
     * results of subsidiaries only while controlled, time-apportioned NCI,
     * step-acquisition remeasurement gains and gains/losses on disposal.
     *
     * @return array<string, mixed>
     */
    public function incomeStatement(Company $parent, string $startDate, string $endDate): array
    {
        // Parent plus every subsidiary whose control window overlaps the period.
        $subs = $parent->subsidiaries()->orderBy('registered_name')->get()
            ->filter(fn(Company $s) => $this->controlWindow($s, $startDate, $endDate) !== null)
            ->values();
        $entities = collect([$parent])->merge($subs);

        $worksheet = collect();
        $combinedRevenue = 0.0;
        $combinedExpenses = 0.0;
        $nciProfit = 0.0;

        foreach ($entities as $entity) {
            $isParent = $entity->id === $parent->id;
            [$winStart, $winEnd] = $isParent
                ? [$startDate, $endDate]
                : $this->controlWindow($entity, $startDate, $endDate);

            $totals = $this->entityIncomeTotals($entity, $winStart, $winEnd);
            $combinedRevenue  += $totals['revenue'];
            $combinedExpenses += $totals['expenses'];

            $nciShare = 0.0;
            if (! $isParent) {
                $nciShare = $this->nciShareOfProfit($entity, $winStart, $winEnd);
                $nciProfit += $nciShare;
            }

            $worksheet->push([
                'company'   => $entity,
                'is_parent' => $isParent,
                'revenue'   => $totals['revenue'],
                'expenses'  => $totals['expenses'],
                'profit'    => $totals['profit'],
                'window'    => [$winStart, $winEnd],
                'nci_share' => $nciShare,
            ]);
        }

        $elim = $this->eliminationEffects($parent);

        $revenue  = $combinedRevenue + $elim['revenue'] + $elim['other_income'];
        $expenses = $combinedExpenses + $elim['cost_of_sales'] + $elim['operating_expenses'];

        // P&L items arising from control changes in the period (owner-attributable).
        $remeasurementGain = 0.0;
        $disposalGain = 0.0;
        $periodEvents = $parent->ownershipEvents()
            ->whereDate('event_date', '>=', $startDate)
            ->whereDate('event_date', '<=', $endDate)
            ->get();
        foreach ($periodEvents as $event) {
            $remeasurementGain += $event->remeasurementGain();
            $disposalGain += $event->disposalGain();
        }

        $operatingProfit = $revenue - $expenses;
        $profit = $operatingProfit + $remeasurementGain + $disposalGain;
        $ownersProfit = $profit - $nciProfit;

        return [
            'startDate'         => $startDate,
            'endDate'           => $endDate,
            'worksheet'         => $worksheet,
            'revenue'           => $revenue,
            'expenses'          => $expenses,
            'operatingProfit'   => $operatingProfit,
            'remeasurementGain' => $remeasurementGain,
            'disposalGain'      => $disposalGain,
            'profit'            => $profit,
            'nciProfit'         => $nciProfit,
            'ownersProfit'      => $ownersProfit,
            'manualEliminations' => $elim,
        ];
    }

    /**
     * Goodwill carried for a subsidiary: frozen at the control-acquisition
     * event if recorded, otherwise the static snapshot formula.
     */
    private function goodwillForSubsidiary(Company $sub, float $equityCurrent): float
    {
        $acquisition = $sub->ownershipEventsAsSubsidiary()
            ->where('type', 'acquisition')
            ->orderBy('event_date')
            ->first();

        if ($acquisition) {
            return $acquisition->goodwillOnAcquisition();
        }

        $equityAcq = $sub->equity_at_acquisition !== null
            ? (float) $sub->equity_at_acquisition
            : $equityCurrent;

        return (float) ($sub->investment_cost ?? 0) - $sub->ownershipFraction() * $equityAcq;
    }

    /**
     * The portion of the requested period during which the parent controlled
     * the subsidiary, or null if there is no overlap.
     *
     * @return array{0:string,1:string}|null
     */
    private function controlWindow(Company $sub, string $startDate, string $endDate): ?array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($sub->control_acquired_date && $sub->control_acquired_date->gt($start)) {
            $start = $sub->control_acquired_date->copy();
        }
        // Control is lost on the disposal date; consolidate up to the day before.
        if ($sub->control_lost_date && $sub->control_lost_date->lte($end)) {
            $end = $sub->control_lost_date->copy()->subDay();
        }

        if ($start->gt($end)) {
            return null;
        }

        return [$start->toDateString(), $end->toDateString()];
    }

    /**
     * NCI share of a subsidiary's profit for a window, apportioned across
     * sub-periods with different ownership percentages.
     */
    private function nciShareOfProfit(Company $sub, string $winStart, string $winEnd): float
    {
        $changeDates = $sub->ownershipEventsAsSubsidiary()
            ->whereIn('type', ['increase', 'decrease'])
            ->whereDate('event_date', '>', $winStart)
            ->whereDate('event_date', '<=', $winEnd)
            ->orderBy('event_date')
            ->pluck('event_date')
            ->map(fn($d) => Carbon::parse($d))
            ->all();

        $nci = 0.0;
        $segStart = Carbon::parse($winStart);
        $end = Carbon::parse($winEnd);

        foreach ($changeDates as $changeDate) {
            $segEnd = $changeDate->copy()->subDay();
            if (! $segStart->gt($segEnd)) {
                $nci += $this->segmentNci($sub, $segStart->toDateString(), $segEnd->toDateString());
            }
            $segStart = $changeDate->copy();
        }

        if (! $segStart->gt($end)) {
            $nci += $this->segmentNci($sub, $segStart->toDateString(), $end->toDateString());
        }

        return $nci;
    }

    private function segmentNci(Company $sub, string $segStart, string $segEnd): float
    {
        $profit = $this->entityIncomeTotals($sub, $segStart, $segEnd)['profit'];

        return (1 - $sub->ownershipFractionAt($segStart)) * $profit;
    }

    /**
     * Aggregate manual elimination journals into a signed effect per bucket.
     *
     * @return array<string, float>
     */
    private function eliminationEffects(Company $parent): array
    {
        $effects = array_fill_keys(array_keys(GroupEliminationLine::BUCKETS), 0.0);

        $lines = GroupEliminationLine::whereIn(
            'group_elimination_id',
            $parent->groupEliminations()->pluck('id')
        )->get();

        foreach ($lines as $line) {
            if (isset($effects[$line->bucket])) {
                $effects[$line->bucket] += $line->signedEffect();
            }
        }

        return $effects;
    }

    /**
     * Balance-sheet totals for a single entity as at a date.
     *
     * @return array{current_assets:float,non_current_assets:float,current_liabilities:float,non_current_liabilities:float,equity:float,total_assets:float,total_liabilities:float}
     */
    private function entityBalanceSheetTotals(Company $company, string $asOfDate): array
    {
        $accounts = $company->chartOfAccounts()
            ->whereIn('account_type', ['assets', 'liabilities', 'equity'])
            ->where('is_active', true)
            ->get(['id', 'account_type', 'account_code', 'opening_balance']);

        // Period-aware opening balances: sum movements only from the relevant
        // period's start, on top of that period's opening balances. Falls back to
        // cumulative + legacy opening_balance column when no periods are defined.
        $ctx = $company->openingContext($asOfDate);

        $movementsQuery = DB::table('journal_lines')
            ->join('transactions', 'journal_lines.transaction_id', '=', 'transactions.id')
            ->where('transactions.company_id', $company->id)
            ->whereIn('transactions.status', ['posted', 'reversed'])
            ->where('transactions.transaction_date', '<=', $asOfDate);

        if ($ctx['start']) {
            $movementsQuery->where('transactions.transaction_date', '>=', $ctx['start']);
        }

        $totals = $movementsQuery
            ->select([
                'journal_lines.chart_of_account_id',
                DB::raw("SUM(CASE WHEN journal_lines.type = 'debit' THEN journal_lines.amount ELSE 0 END) as total_debits"),
                DB::raw("SUM(CASE WHEN journal_lines.type = 'credit' THEN journal_lines.amount ELSE 0 END) as total_credits"),
            ])
            ->groupBy('journal_lines.chart_of_account_id')
            ->get()
            ->keyBy('chart_of_account_id');

        $sum = [
            'current_assets' => 0.0,
            'non_current_assets' => 0.0,
            'current_liabilities' => 0.0,
            'non_current_liabilities' => 0.0,
            'equity' => 0.0,
        ];

        foreach ($accounts as $account) {
            $row = $totals->get($account->id);
            $debits = $row ? (float) $row->total_debits : 0.0;
            $credits = $row ? (float) $row->total_credits : 0.0;
            $opening = $ctx['amounts'] !== null
                ? (float) ($ctx['amounts'][$account->id] ?? 0)
                : (float) $account->opening_balance;
            $code = (int) $account->account_code;

            if ($account->account_type === 'assets') {
                $balance = $opening + ($debits - $credits);
                $code < 1500
                    ? $sum['current_assets'] += $balance
                    : $sum['non_current_assets'] += $balance;
            } elseif ($account->account_type === 'liabilities') {
                $balance = $opening + ($credits - $debits);
                $code < 2500
                    ? $sum['current_liabilities'] += $balance
                    : $sum['non_current_liabilities'] += $balance;
            } else {
                $sum['equity'] += $opening + ($credits - $debits);
            }
        }

        $sum['total_assets'] = $sum['current_assets'] + $sum['non_current_assets'];
        $sum['total_liabilities'] = $sum['current_liabilities'] + $sum['non_current_liabilities'];

        return $sum;
    }

    /**
     * Income-statement totals for a single entity for a period.
     *
     * @return array{revenue:float,expenses:float,profit:float}
     */
    private function entityIncomeTotals(Company $company, string $startDate, string $endDate): array
    {
        $accounts = $company->chartOfAccounts()
            ->whereIn('account_type', ['income', 'expenses'])
            ->where('is_active', true)
            ->get(['id', 'account_type']);

        $totals = DB::table('journal_lines')
            ->join('transactions', 'journal_lines.transaction_id', '=', 'transactions.id')
            ->where('transactions.company_id', $company->id)
            ->whereIn('transactions.status', ['posted', 'reversed'])
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
            ->select([
                'journal_lines.chart_of_account_id',
                DB::raw("SUM(CASE WHEN journal_lines.type = 'debit' THEN journal_lines.amount ELSE 0 END) as total_debits"),
                DB::raw("SUM(CASE WHEN journal_lines.type = 'credit' THEN journal_lines.amount ELSE 0 END) as total_credits"),
            ])
            ->groupBy('journal_lines.chart_of_account_id')
            ->get()
            ->keyBy('chart_of_account_id');

        $revenue = 0.0;
        $expenses = 0.0;

        foreach ($accounts as $account) {
            $row = $totals->get($account->id);
            $debits = $row ? (float) $row->total_debits : 0.0;
            $credits = $row ? (float) $row->total_credits : 0.0;

            if ($account->account_type === 'income') {
                $revenue += $credits - $debits;
            } else {
                $expenses += $debits - $credits;
            }
        }

        return [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'profit' => $revenue - $expenses,
        ];
    }
}
