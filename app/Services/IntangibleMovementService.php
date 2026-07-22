<?php

namespace App\Services;

use App\Models\Company;
use App\Models\IntangibleAsset;
use App\Models\IntangibleClass;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IntangibleMovementService
{
    public function build(Company $company, string $startDate, string $endDate): array
    {
        $assets  = $company->intangibleAssets()->with('intangibleClass')->get();
        $classes = $company->intangibleClasses()->orderBy('sort_order')->orderBy('name')->get();

        if ($assets->isEmpty() && $classes->isEmpty()) {
            return [];
        }

        $dayBefore    = $this->dayBefore($startDate);
        $assetsByClass = $assets->groupBy(fn($a) => $a->intangible_class_id ?? 0);
        $classList     = $classes->keyBy('id');

        foreach ($assetsByClass->keys() as $cid) {
            if ($cid && ! $classList->has($cid)) {
                $cls = $assetsByClass[$cid]->first()->intangibleClass;
                if ($cls) {
                    $classList->put($cls->id, $cls);
                }
            }
        }
        if ($assetsByClass->has(0)) {
            $classList->put(0, new IntangibleClass(['id' => 0, 'name' => 'Unclassified', 'sort_order' => PHP_INT_MAX]));
        }

        $rows = [];

        foreach ($classList as $cid => $class) {
            $classAssets = $assetsByClass->get($cid, collect());

            $inServiceAt = fn(IntangibleAsset $a, string $d): bool =>
                $a->acquisition_date->toDateString() <= $d
                && (! $a->isDisposed() || $a->disposal_date->toDateString() > $d);

            $acquiredInPeriod = fn(IntangibleAsset $a): bool =>
                $a->acquisition_date->toDateString() >= $startDate
                && $a->acquisition_date->toDateString() <= $endDate;

            $disposedInPeriod = fn(IntangibleAsset $a): bool =>
                $a->isDisposed()
                && $a->disposal_date->toDateString() >= $startDate
                && $a->disposal_date->toDateString() <= $endDate;

            $costOpening   = $classAssets->filter(fn($a) => $inServiceAt($a, $dayBefore))->sum(fn($a) => (float) $a->cost);
            $additions     = $classAssets->filter($acquiredInPeriod)->sum(fn($a) => (float) $a->cost);
            $costDisposals = $classAssets->filter($disposedInPeriod)->sum(fn($a) => (float) $a->cost);
            $costClosing   = $classAssets->filter(fn($a) => $inServiceAt($a, $endDate))->sum(fn($a) => (float) $a->cost);

            $adOpening   = $classAssets->filter(fn($a) => $inServiceAt($a, $dayBefore))->sum(fn($a) => $a->accumulatedAmortisation($dayBefore));
            $adClosing   = $classAssets->filter(fn($a) => $inServiceAt($a, $endDate))->sum(fn($a) => $a->accumulatedAmortisation($endDate));
            $adDisposals = $classAssets->filter($disposedInPeriod)->sum(fn($a) => $a->accumulatedAmortisation($a->disposal_date->toDateString()));

            $amortisationCharge = $adClosing + $adDisposals - $adOpening;

            $assetTags = $classAssets->map(fn($a) => 'intangible:'.$a->id)->all();

            $subsequentCosts = $this->sumByPrefix($company, $assetTags, $startDate, $endDate, 'INT-SUBCAP-');

            $impBefore = $this->sumByPrefix($company, $assetTags, '1970-01-01', $dayBefore, 'INT-IMP-')
                       - $this->sumByPrefix($company, $assetTags, '1970-01-01', $dayBefore, 'INT-IMPREV-');
            $impAfter  = $this->sumByPrefix($company, $assetTags, '1970-01-01', $endDate,   'INT-IMP-')
                       - $this->sumByPrefix($company, $assetTags, '1970-01-01', $endDate,   'INT-IMPREV-');
            $impCharge   = $this->sumByPrefix($company, $assetTags, $startDate, $endDate, 'INT-IMP-');
            $impReversal = $this->sumByPrefix($company, $assetTags, $startDate, $endDate, 'INT-IMPREV-');

            $revalIncrement    = $this->sumByPrefix($company, $assetTags, $startDate, $endDate, 'INT-REVAL-');
            $revalSurplusOpen  = $this->revaluationSurplusBalance($classAssets, $company, $dayBefore);
            $revalSurplusClose = $this->revaluationSurplusBalance($classAssets, $company, $endDate);

            $rows[] = [
                'class' => $class,

                'cost_opening'        => round($costOpening, 2),
                'additions'           => round($additions, 2),
                'subsequent_costs'    => round($subsequentCosts, 2),
                'revaluations'        => round($revalIncrement, 2),
                'cost_disposals'      => round($costDisposals, 2),
                'cost_closing'        => round($costClosing, 2),

                'ad_opening'          => round($adOpening, 2),
                'amortisation_charge' => round($amortisationCharge, 2),
                'ad_disposals'        => round($adDisposals, 2),
                'ad_closing'          => round($adClosing, 2),

                'ai_opening'          => round($impBefore, 2),
                'impairment_charge'   => round($impCharge, 2),
                'impairment_reversal' => round($impReversal, 2),
                'ai_closing'          => round($impAfter, 2),

                'rev_surplus_opening' => round($revalSurplusOpen, 2),
                'rev_surplus_gain'    => max(0, round($revalSurplusClose - $revalSurplusOpen, 2)),
                'rev_surplus_loss'    => max(0, round($revalSurplusOpen  - $revalSurplusClose, 2)),
                'rev_surplus_closing' => round($revalSurplusClose, 2),

                'carrying_opening' => round($costOpening - $adOpening - $impBefore, 2),
                'carrying_closing' => round($costClosing - $adClosing - $impAfter,  2),
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

    private function sumByPrefix(Company $company, array $sourceDocs, string $startDate, string $endDate, string $prefix): float
    {
        if (empty($sourceDocs)) {
            return 0.0;
        }

        return (float) DB::table('transactions')
            ->join('journal_lines', 'transactions.id', '=', 'journal_lines.transaction_id')
            ->where('transactions.company_id', $company->id)
            ->whereIn('transactions.status', ['posted', 'reversed', 'draft'])
            ->whereIn('transactions.source_document', $sourceDocs)
            ->where('transactions.reference', 'like', $prefix.'%')
            ->whereRaw("DATE(transactions.transaction_date) BETWEEN ? AND ?", [$startDate, $endDate])
            ->where('journal_lines.type', 'debit')
            ->sum('journal_lines.amount');
    }

    private function revaluationSurplusBalance(Collection $classAssets, Company $company, string $asOfDate): float
    {
        $today = now()->format('Y-m-d');
        if ($asOfDate >= $today) {
            return $classAssets->sum(fn($a) => (float) ($a->revaluation_surplus ?? 0));
        }

        if ($classAssets->isEmpty()) {
            return 0.0;
        }

        $tags = $classAssets->map(fn($a) => 'intangible:'.$a->id)->all();

        $gains = (float) DB::table('transactions')
            ->join('journal_lines', 'transactions.id', '=', 'journal_lines.transaction_id')
            ->where('transactions.company_id', $company->id)
            ->whereIn('transactions.status', ['posted', 'reversed', 'draft'])
            ->whereIn('transactions.source_document', $tags)
            ->where('transactions.reference', 'like', 'INT-REVAL-%')
            ->whereRaw("DATE(transactions.transaction_date) <= ?", [$asOfDate])
            ->where('journal_lines.type', 'credit')
            ->sum('journal_lines.amount');

        $losses = (float) DB::table('transactions')
            ->join('journal_lines', 'transactions.id', '=', 'journal_lines.transaction_id')
            ->where('transactions.company_id', $company->id)
            ->whereIn('transactions.status', ['posted', 'reversed', 'draft'])
            ->whereIn('transactions.source_document', $tags)
            ->where('transactions.reference', 'like', 'INT-REVAL-%')
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
