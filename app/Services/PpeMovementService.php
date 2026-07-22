<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Company;
use App\Models\PpeClass;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds the IAS 16 / IFRS for SMEs §17 movement schedule for Property, Plant
 * & Equipment, per class.
 *
 * Source of truth: the ASSET REGISTER.
 *
 *   • Cost, additions and disposals: register dates & amounts
 *   • Accumulated depreciation: register straight-line / reducing-balance calc
 *   • Subsequent expenditure (IAS 16.7): SUBCAP- transactions tagged per asset
 *   • Revaluation (IAS 16.31): REVAL- transactions tagged per asset
 *   • Impairment (IAS 36) & reversal (IAS 36.114): IMP- / IMPREV- per asset
 *   • Revaluation surplus (OCI): IAS 16.39 movement = revaluation increment − decrement
 *
 * Every IAS 16 / IAS 36 transaction posted via AssetPostingService carries a
 * `source_document = 'asset:ID'` tag — we aggregate those tags per class.
 * No chart-of-account linking is required for the schedule.
 */
class PpeMovementService
{
    public function build(Company $company, string $startDate, string $endDate): array
    {
        $assets = $company->assets()->with('ppeClass')->get();
        $classes = $company->ppeClasses()->orderBy('sort_order')->orderBy('name')->get();

        if ($assets->isEmpty() && $classes->isEmpty()) {
            return [];
        }

        $dayBefore = $this->dayBefore($startDate);
        $assetsByClass = $assets->groupBy(fn($a) => $a->ppe_class_id ?? 0);
        $classList = $classes->keyBy('id');

        // Include any orphan-class buckets that exist in the register
        foreach ($assetsByClass->keys() as $cid) {
            if ($cid && ! $classList->has($cid)) {
                $cls = $assetsByClass[$cid]->first()->ppeClass;
                if ($cls) {
                    $classList->put($cls->id, $cls);
                }
            }
        }
        if ($assetsByClass->has(0)) {
            $classList->put(0, new PpeClass(['id' => 0, 'name' => 'Unclassified', 'sort_order' => PHP_INT_MAX]));
        }

        $rows = [];

        foreach ($classList as $cid => $class) {
            $classAssets = $assetsByClass->get($cid, collect());

            // ── Register-derived: cost & accumulated depreciation ──────────
            $inServiceAt = fn(Asset $a, string $d): bool =>
                $a->acquisition_date->toDateString() <= $d
                && (! $a->isDisposed() || $a->disposal_date->toDateString() > $d);

            $acquiredInPeriod = fn(Asset $a): bool =>
                $a->acquisition_date->toDateString() >= $startDate
                && $a->acquisition_date->toDateString() <= $endDate;

            $disposedInPeriod = fn(Asset $a): bool =>
                $a->isDisposed()
                && $a->disposal_date->toDateString() >= $startDate
                && $a->disposal_date->toDateString() <= $endDate;

            $costOpening   = $classAssets->filter(fn($a) => $inServiceAt($a, $dayBefore))->sum(fn($a) => (float) $a->cost);
            $additions     = $classAssets->filter($acquiredInPeriod)->sum(fn($a) => (float) $a->cost);
            $costDisposals = $classAssets->filter($disposedInPeriod)->sum(fn($a) => (float) $a->cost);
            $costClosing   = $classAssets->filter(fn($a) => $inServiceAt($a, $endDate))->sum(fn($a) => (float) $a->cost);

            $adOpening   = $classAssets->filter(fn($a) => $inServiceAt($a, $dayBefore))->sum(fn($a) => $a->accumulatedDepreciation($dayBefore));
            $adClosing   = $classAssets->filter(fn($a) => $inServiceAt($a, $endDate))->sum(fn($a) => $a->accumulatedDepreciation($endDate));
            $adDisposals = $classAssets->filter($disposedInPeriod)->sum(fn($a) => $a->accumulatedDepreciation($a->disposal_date->toDateString()));

            $depreciationCharge = $adClosing + $adDisposals - $adOpening;

            // ── Register-derived (via per-asset source_document tags) ───────
            $assetTags = $classAssets->map(fn($a) => 'asset:'.$a->id)->all();

            $subsequentCosts = $this->sumByPrefix($company, $assetTags, $startDate, $endDate, 'SUBCAP-');

            // Impairment net total before dayBefore and up to endDate
            $impBefore = $this->sumByPrefix($company, $assetTags, '1970-01-01', $dayBefore, 'IMP-')
                       - $this->sumByPrefix($company, $assetTags, '1970-01-01', $dayBefore, 'IMPREV-');
            $impAfter  = $this->sumByPrefix($company, $assetTags, '1970-01-01', $endDate,   'IMP-')
                       - $this->sumByPrefix($company, $assetTags, '1970-01-01', $endDate,   'IMPREV-');
            $impCharge   = $this->sumByPrefix($company, $assetTags, $startDate, $endDate, 'IMP-');
            $impReversal = $this->sumByPrefix($company, $assetTags, $startDate, $endDate, 'IMPREV-');

            // Revaluation movements in period (net effect on cost)
            $revalIncrement = $this->sumByPrefix($company, $assetTags, $startDate, $endDate, 'REVAL-');
            $revalSurplusOpen  = $this->revaluationSurplusBalance($classAssets, $dayBefore);
            $revalSurplusClose = $this->revaluationSurplusBalance($classAssets, $endDate);

            $rows[] = [
                'class' => $class,

                // Cost section
                'cost_opening'        => round($costOpening, 2),
                'additions'           => round($additions, 2),
                'subsequent_costs'    => round($subsequentCosts, 2),
                'revaluations'        => round($revalIncrement, 2),
                'cost_disposals'      => round($costDisposals, 2),
                'cost_other'          => 0.0,
                'cost_closing'        => round($costClosing, 2),

                // Accumulated depreciation
                'ad_opening'          => round($adOpening, 2),
                'ad_reval_eliminated' => 0.0,
                'depreciation_charge' => round($depreciationCharge, 2),
                'ad_disposals'        => round($adDisposals, 2),
                'ad_closing'          => round($adClosing, 2),

                // Accumulated impairment (per-asset register)
                'ai_opening'          => round($impBefore, 2),
                'impairment_charge'   => round($impCharge, 2),
                'impairment_reversal' => round($impReversal, 2),
                'ai_disposals'        => 0.0,
                'ai_closing'          => round($impAfter, 2),

                // Revaluation surplus — OCI (per-asset register)
                'rev_surplus_opening' => round($revalSurplusOpen, 2),
                'rev_surplus_gain'    => max(0, round($revalSurplusClose - $revalSurplusOpen, 2)),
                'rev_surplus_loss'    => max(0, round($revalSurplusOpen  - $revalSurplusClose, 2)),
                'rev_surplus_closing' => round($revalSurplusClose, 2),

                // Carrying amount
                'carrying_opening' => round($costOpening - $adOpening - $impBefore, 2),
                'carrying_closing' => round($costClosing - $adClosing - $impAfter,  2),

                'has_cost_links'     => true,
                'has_ad_links'       => true,
                'has_ai_links'       => true,
                'has_disposal_links' => true,
            ];
        }

        return collect($rows)
            ->sortBy([
                fn($r) => (int) ($r['class']->sort_order ?? 0),
                fn($r) => (string) $r['class']->name,
            ])
            ->values()
            ->all();
    }

    /**
     * Sum the debit-side amounts of journal lines on transactions tagged with
     * any of $sourceDocs, whose reference starts with $prefix, in the given
     * date range. The debit total represents the gross movement value (each
     * asset transaction is balanced, so debits = credits).
     */
    private function sumByPrefix(
        Company $company,
        array $sourceDocs,
        string $startDate,
        string $endDate,
        string $prefix,
    ): float {
        if (empty($sourceDocs)) {
            return 0.0;
        }

        $total = DB::table('transactions')
            ->join('journal_lines', 'transactions.id', '=', 'journal_lines.transaction_id')
            ->where('transactions.company_id', $company->id)
            ->whereIn('transactions.status', ['posted', 'reversed', 'draft'])
            ->whereIn('transactions.source_document', $sourceDocs)
            ->where('transactions.reference', 'like', $prefix.'%')
            ->whereRaw("DATE(transactions.transaction_date) BETWEEN ? AND ?", [$startDate, $endDate])
            ->where('journal_lines.type', 'debit')
            ->sum('journal_lines.amount');

        return (float) $total;
    }

    /**
     * Approximate the revaluation surplus balance as at a date by replaying
     * each asset's revaluation events. This is an approximation built from
     * the GL — for exact balances the per-asset `revaluation_surplus` column
     * is authoritative as at "now".
     */
    private function revaluationSurplusBalance(Collection $classAssets, string $asOfDate): float
    {
        // For dates at or after today, prefer the per-asset stored balance.
        $today = now()->format('Y-m-d');
        if ($asOfDate >= $today) {
            return $classAssets->sum(fn($a) => (float) ($a->revaluation_surplus ?? 0));
        }

        // Otherwise replay the revaluation gains net of subsequent losses up to that date.
        if ($classAssets->isEmpty()) {
            return 0.0;
        }

        $company = $classAssets->first()->company;
        if (! $company) {
            return 0.0;
        }
        $tags = $classAssets->map(fn($a) => 'asset:'.$a->id)->all();

        $gains = (float) DB::table('transactions')
            ->join('journal_lines', 'transactions.id', '=', 'journal_lines.transaction_id')
            ->where('transactions.company_id', $company->id)
            ->whereIn('transactions.status', ['posted', 'reversed', 'draft'])
            ->whereIn('transactions.source_document', $tags)
            ->where('transactions.reference', 'like', 'REVAL-%')
            ->whereRaw("DATE(transactions.transaction_date) <= ?", [$asOfDate])
            ->where('journal_lines.type', 'credit')
            ->sum('journal_lines.amount');

        $losses = (float) DB::table('transactions')
            ->join('journal_lines', 'transactions.id', '=', 'journal_lines.transaction_id')
            ->where('transactions.company_id', $company->id)
            ->whereIn('transactions.status', ['posted', 'reversed', 'draft'])
            ->whereIn('transactions.source_document', $tags)
            ->where('transactions.reference', 'like', 'REVAL-%')
            ->whereRaw("DATE(transactions.transaction_date) <= ?", [$asOfDate])
            ->where('journal_lines.type', 'debit')
            ->sum('journal_lines.amount');

        return max(0, $gains - $losses);
    }

    private function dayBefore(string $date): string
    {
        return \Carbon\Carbon::parse($date)->subDay()->format('Y-m-d');
    }
}
