<?php

namespace App\Services;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Presentation model for the monthly management accounts pack.
 *
 * A sibling of AfsStatementModelService, and deliberately shaped like it: every
 * row carries a style from the same vocabulary — section, item, item-last,
 * subtotal, named-subtotal, grand-total — so one renderer vocabulary serves
 * both the statutory Word pack and this one.
 *
 * Two things differ from the AFS model, both because the output is a
 * spreadsheet rather than a document:
 *
 *  1. Rows carry a parallel `raw` array of signed floats alongside the
 *     pre-formatted `values`. The AFS renderer writes strings into a Word
 *     table; a workbook has to hold live numbers or it is not a template, so
 *     the Excel writer takes `raw` and reproduces the document's appearance
 *     with a number format instead. `values` remains authoritative for how a
 *     figure should look — the two must agree.
 *
 *  2. Amounts are stored with their natural sign: income positive, expenses
 *     negative. The AFS model shows expenses as unsigned figures in
 *     parentheses, which is correct on paper but useless in a spreadsheet
 *     column that has to sum. Signing them also makes variance fall out of
 *     plain subtraction — cur - prior is favourable when positive for income
 *     and expense lines alike, with no per-row direction flag to get wrong.
 *
 * Figures come from the same CompanyController builders that feed the
 * statement PDFs and the AFS Word pack, so a management pack and an AFS for
 * an overlapping period cannot disagree.
 */
class ManagementAccountsModelService
{
    /** Rounding divisor => [label, decimals] — the AFS service's ladder. */
    private const ROUNDING = [
        1 => ["R", 2],
        1000 => ["R'000", 0],
        1000000 => ["R'm", 2],
    ];

    private int $divisor = 1;

    private int $decimals = 2;

    private string $roundingLabel = 'R';

    private bool $compare = true;

    public function forRounding(int $divisor): self
    {
        [$label, $decimals] = self::ROUNDING[$divisor] ?? self::ROUNDING[1];
        $this->divisor = $divisor;
        $this->roundingLabel = $label;
        $this->decimals = $decimals;

        return $this;
    }

    public function withComparatives(bool $compare): self
    {
        $this->compare = $compare;

        return $this;
    }

    /** The reporting basis label — R, R'000 or R'm. */
    public function basisLabel(): string
    {
        return $this->roundingLabel;
    }

    // ── Formatting ───────────────────────────────────────────────────────────
    // Space thousands separator, em dash for nil, parentheses for negatives —
    // the document's conventions, matching AfsStatementModelService::fmtSigned.

    private function fmt(float|int|null $v): string
    {
        if ($v === null) {
            return '';
        }
        $v = (float) $v;
        if (round($v, 2) == 0) {
            return '—';
        }
        $n = number_format(abs($v) / $this->divisor, $this->decimals, '.', ' ');

        return $v < 0 ? "({$n})" : $n;
    }

    /** Percentages carry one decimal and an explicit sign convention. */
    private function fmtPercent(?float $v): string
    {
        if ($v === null) {
            return '—';
        }
        $n = number_format(abs($v), 1, '.', ' ');

        return ($v < 0 ? "({$n}" : $n) . ($v < 0 ? '%)' : '%');
    }

    /**
     * Scale a raw figure onto the reporting basis. The workbook holds the
     * scaled number, so R'000 output sums in thousands as the reader expects.
     */
    private function scale(?float $v): ?float
    {
        if ($v === null) {
            return null;
        }

        return round($v / $this->divisor, $this->decimals);
    }

    /**
     * Variance as a percentage of the comparative.
     *
     * A nil comparative has no percentage — an em dash, never zero and never
     * 100%. Movement from nothing is not a rate.
     */
    private function variancePercent(?float $cur, ?float $prior): ?float
    {
        if ($cur === null || $prior === null || round($prior, 2) == 0) {
            return null;
        }

        return (($cur - $prior) / abs($prior)) * 100;
    }

    // ── Row construction ─────────────────────────────────────────────────────

    /**
     * Build one row from a pair of signed period figures.
     *
     * Each pair expands to actual / comparative / variance / variance %, and
     * the pairs are laid side by side — period first, then year to date.
     *
     * @param  array<int, array{0: float|null, 1: float|null}>  $pairs
     */
    private function row(string $style, string $label, array $pairs): array
    {
        $values = [];
        $raw = [];

        foreach ($pairs as [$cur, $prior]) {
            $values[] = $this->fmt($cur);
            $raw[] = $this->scale($cur);

            if (! $this->compare) {
                continue;
            }

            $variance = ($cur === null || $prior === null) ? null : $cur - $prior;
            $percent = $this->variancePercent($cur, $prior);

            $values[] = $this->fmt($prior);
            $raw[] = $this->scale($prior);
            $values[] = $this->fmt($variance);
            $raw[] = $this->scale($variance);
            $values[] = $this->fmtPercent($percent);
            // Percentages are not on the reporting basis, so they are not scaled.
            $raw[] = $percent === null ? null : round($percent, 1);
        }

        return [
            'style' => $style,
            'label' => $label,
            'values' => $values,
            'raw' => $raw,
        ];
    }

    /** A section head: bold blue label, every figure cell empty. */
    private function sectionRow(string $label, int $pairCount): array
    {
        return $this->blankRow('section', $label, $pairCount * ($this->compare ? 4 : 1));
    }

    /** A row carrying a label and no figures at all. */
    private function blankRow(string $style, string $label, int $width): array
    {
        return [
            'style' => $style,
            'label' => $label,
            'values' => array_fill(0, $width, ''),
            'raw' => array_fill(0, $width, null),
        ];
    }

    /**
     * Column headers for a two-pair (period + year to date) statement.
     *
     * Both actual columns are marked current. In the source document exactly
     * one column carries the #005BF0 header cell and the #EAF8FB band, marking
     * the figures the reader is there for; in a management pack that is the
     * period actual and the year-to-date actual together. Comparatives and
     * derived variance columns stay plain.
     */
    private function columns(string $periodLabel, string $ytdLabel, string $priorPeriodLabel, string $priorYtdLabel): array
    {
        $cols = [['kind' => 'label', 'text' => '']];

        $group = function (string $actual, string $prior, string $group) use (&$cols) {
            $cols[] = ['kind' => 'amount', 'text' => $actual, 'sub' => $this->roundingLabel, 'current' => true, 'group' => $group];

            if (! $this->compare) {
                return;
            }

            $cols[] = ['kind' => 'amount', 'text' => $prior, 'sub' => $this->roundingLabel, 'current' => false, 'group' => $group];
            $cols[] = ['kind' => 'variance', 'text' => 'Variance', 'sub' => 'fav/(adv)', 'current' => false, 'group' => $group];
            $cols[] = ['kind' => 'percent', 'text' => 'Variance', 'sub' => '%', 'current' => false, 'group' => $group];
        };

        $group($periodLabel, $priorPeriodLabel, 'period');
        $group($ytdLabel, $priorYtdLabel, 'ytd');

        return $cols;
    }

    // ── Trading statement ────────────────────────────────────────────────────

    /**
     * Profit or loss for the period and year to date, each against the
     * equivalent prior-year span.
     *
     * Accepts two payloads of CompanyController::buildProfitOrLossRows — one
     * run over the reporting period, one over the year to date. Both already
     * carry net_amount and prior_net_amount per account.
     *
     * @param  array  $d  period / ytd account collections plus the period bounds
     */
    public function trading(array $d): array
    {
        $period = $this->classify($d['periodIncome'], $d['periodExpense']);
        $ytd = $this->classify($d['ytdIncome'], $d['ytdExpense']);

        $rows = [];

        // A line is visible when it moved in any of the four spans; a nil row
        // in every column is noise on a management pack.
        $lines = function (string $bucket, int $sign) use ($period, $ytd) {
            $out = [];
            $names = collect($period[$bucket]['accounts'])
                ->merge($ytd[$bucket]['accounts'])
                ->pluck('account_name')
                ->unique()
                ->values();

            $visible = $names->filter(function ($name) use ($period, $ytd, $bucket) {
                foreach ([$period, $ytd] as $set) {
                    foreach (['cur', 'pri'] as $key) {
                        if (abs($this->named($set[$bucket]['accounts'], $name, $key)) >= 0.01) {
                            return true;
                        }
                    }
                }

                return false;
            })->values();

            foreach ($visible as $i => $name) {
                $out[] = $this->row(
                    $i === $visible->count() - 1 ? 'item-last' : 'item',
                    $name,
                    [
                        [$sign * $this->named($period[$bucket]['accounts'], $name, 'cur'), $sign * $this->named($period[$bucket]['accounts'], $name, 'pri')],
                        [$sign * $this->named($ytd[$bucket]['accounts'], $name, 'cur'), $sign * $this->named($ytd[$bucket]['accounts'], $name, 'pri')],
                    ]
                );
            }

            return $out;
        };

        // Paired period / year-to-date figures for one classified bucket.
        $pair = fn (string $bucket, int $sign) => [
            [$sign * $period[$bucket]['cur'], $sign * $period[$bucket]['pri']],
            [$sign * $ytd[$bucket]['cur'], $sign * $ytd[$bucket]['pri']],
        ];

        // Derived lines are computed from the signed bucket totals, so the
        // arithmetic is the same in both spans and cannot fall out of step.
        $derive = function (callable $f) use ($period, $ytd) {
            return [
                [$f($period, 'cur'), $f($period, 'pri')],
                [$f($ytd, 'cur'), $f($ytd, 'pri')],
            ];
        };

        $rows[] = $this->sectionRow('Revenue', 2);
        $rows = array_merge($rows, $lines('revenue', 1));
        $rows[] = $this->row('subtotal', 'Total revenue', $pair('revenue', 1));

        if ($this->populated($period, $ytd, 'costOfSales')) {
            $rows[] = $this->sectionRow('Cost of sales', 2);
            $rows = array_merge($rows, $lines('costOfSales', -1));
            $rows[] = $this->row('subtotal', 'Total cost of sales', $pair('costOfSales', -1));
        }

        $grossProfit = fn ($s, $k) => $s['revenue'][$k] - $s['costOfSales'][$k];
        $rows[] = $this->row('named-subtotal', 'Gross profit', $derive($grossProfit));

        // Gross margin is a rate, not a figure on the reporting basis, so it
        // sits outside the money rows as its own percentage line.
        $rows[] = $this->marginRow('Gross margin', $period, $ytd, $grossProfit);

        if ($this->populated($period, $ytd, 'otherIncome')) {
            $rows[] = $this->sectionRow('Other income', 2);
            $rows = array_merge($rows, $lines('otherIncome', 1));
            $rows[] = $this->row('subtotal', 'Total other income', $pair('otherIncome', 1));
        }

        if ($this->populated($period, $ytd, 'opExpenses')) {
            $rows[] = $this->sectionRow('Operating expenses', 2);
            $rows = array_merge($rows, $lines('opExpenses', -1));
            $rows[] = $this->row('subtotal', 'Total operating expenses', $pair('opExpenses', -1));
        }

        $operating = fn ($s, $k) => $s['revenue'][$k] - $s['costOfSales'][$k] + $s['otherIncome'][$k] - $s['opExpenses'][$k];
        $rows[] = $this->row('named-subtotal', 'Operating profit', $derive($operating));
        $rows[] = $this->marginRow('Operating margin', $period, $ytd, $operating);

        if ($this->populated($period, $ytd, 'financeCosts')) {
            $rows[] = $this->sectionRow('Finance costs', 2);
            $rows = array_merge($rows, $lines('financeCosts', -1));
            $rows[] = $this->row('subtotal', 'Total finance costs', $pair('financeCosts', -1));
        }

        $beforeTax = fn ($s, $k) => $operating($s, $k) - $s['financeCosts'][$k];
        $rows[] = $this->row('named-subtotal', 'Profit before taxation', $derive($beforeTax));

        if ($this->populated($period, $ytd, 'taxExpense')) {
            $rows[] = $this->row('item-last', 'Income tax expense', $pair('taxExpense', -1));
        }

        $profit = fn ($s, $k) => $beforeTax($s, $k) - $s['taxExpense'][$k];
        $rows[] = $this->row('grand-total', 'Profit for the period', $derive($profit));
        $rows[] = $this->marginRow('Net margin', $period, $ytd, $profit);

        // Retained earnings roll-forward. The reference pack closes its income
        // statement by carrying profit into equity, which is what ties the
        // statement to the balance sheet on the same page.
        //
        // Only the year-to-date column is populated: opening retained earnings
        // and dividends are year-to-date facts taken from the equity movement,
        // and there is no monthly equivalent to set against them. A figure
        // repeated into the period column would assert a movement for the
        // month that nobody computed.
        $equity = $d['equity'] ?? null;
        if ($equity !== null) {
            $open = (float) ($equity['retained_open'] ?? 0);
            $dividends = (float) ($equity['dividends'] ?? 0);
            $ytdProfit = $profit($ytd, 'cur');

            $rows[] = $this->ytdOnlyRow('item', 'Retained earnings at the beginning of the year', $open);
            if (round($dividends, 2) != 0) {
                $rows[] = $this->ytdOnlyRow('item', 'Dividends declared', -abs($dividends));
            }
            $rows[] = $this->ytdOnlyRow('grand-total', 'Retained earnings at the end of the period',
                $open + $ytdProfit - abs($dividends));
        }

        return [
            'key' => 'trading',
            'title' => 'Trading Statement',
            'subtitle' => $d['subtitle'],
            'columns' => $this->columns($d['periodLabel'], $d['ytdLabel'], $d['priorPeriodLabel'], $d['priorYtdLabel']),
            'rows' => $rows,
            'footnotes' => [
                'Variance is stated favourable/(adverse): a positive figure improves profit, whether by earning more or spending less.',
                'A variance percentage is shown only where the comparative is non-nil — movement from a nil base is not a rate.',
            ],
        ];
    }

    /**
     * A row on the trading statement whose figure is a year-to-date fact with
     * no monthly counterpart — the period group is left genuinely empty rather
     * than repeating the year-to-date number into it.
     */
    private function ytdOnlyRow(string $style, string $label, float $ytdValue): array
    {
        $width = $this->compare ? 4 : 1;
        $values = array_fill(0, $width, '');
        $raw = array_fill(0, $width, null);

        $values[] = $this->fmt($ytdValue);
        $raw[] = $this->scale($ytdValue);
        for ($i = 1; $i < $width; $i++) {
            $values[] = '';
            $raw[] = null;
        }

        return ['style' => $style, 'label' => $label, 'values' => $values, 'raw' => $raw];
    }

    /**
     * A margin line: percentage of revenue in each of the four spans.
     *
     * Carried as a percent row so the renderer formats it as a rate rather
     * than a figure on the reporting basis.
     */
    private function marginRow(string $label, array $period, array $ytd, callable $numerator): array
    {
        $margin = function (array $set, string $key) use ($numerator): ?float {
            $revenue = $set['revenue'][$key];
            if (round($revenue, 2) == 0) {
                return null;
            }

            return ($numerator($set, $key) / $revenue) * 100;
        };

        $values = [];
        $raw = [];

        foreach ([$period, $ytd] as $set) {
            $cur = $margin($set, 'cur');
            $prior = $margin($set, 'pri');

            $values[] = $this->fmtPercent($cur);
            $raw[] = $cur === null ? null : round($cur, 1);

            if (! $this->compare) {
                continue;
            }

            // The movement between two margins is percentage points, not a
            // percentage of a percentage — so the fourth column is left empty
            // rather than carrying a meaningless rate-of-a-rate.
            $points = ($cur === null || $prior === null) ? null : $cur - $prior;

            $values[] = $this->fmtPercent($prior);
            $raw[] = $prior === null ? null : round($prior, 1);
            $values[] = $this->fmtPercent($points);
            $raw[] = $points === null ? null : round($points, 1);
            $values[] = '';
            $raw[] = null;
        }

        return ['style' => 'margin', 'label' => $label, 'values' => $values, 'raw' => $raw];
    }

    /** True when a bucket carries anything in any of the four spans. */
    private function populated(array $period, array $ytd, string $bucket): bool
    {
        foreach ([$period, $ytd] as $set) {
            foreach (['cur', 'pri'] as $key) {
                if (abs($set[$bucket][$key]) >= 0.01) {
                    return true;
                }
            }
        }

        return false;
    }

    /** Sum one named account's figure out of a classified bucket. */
    private function named(Collection $accounts, string $name, string $key): float
    {
        return (float) $accounts
            ->filter(fn ($a) => $a->account_name === $name)
            ->sum($key === 'cur' ? 'net_amount' : 'prior_net_amount');
    }

    /**
     * Split income and expense accounts into the presentation buckets, by the
     * account-code bands ChartOfAccountsAgent assigns. Same classification as
     * AfsStatementModelService::profitOrLoss — kept identical so the two
     * documents group a given account the same way.
     */
    private function classify(Collection $income, Collection $expense): array
    {
        $prefix = fn ($a) => (int) substr(ltrim((string) $a->account_code, '0'), 0, 4);
        $between = fn (Collection $c, int $lo, int $hi) => $c->filter(fn ($a) => $prefix($a) >= $lo && $prefix($a) < $hi)->values();

        $bucket = function (Collection $accounts): array {
            return [
                'accounts' => $accounts,
                'cur' => (float) $accounts->sum('net_amount'),
                'pri' => $this->compare ? (float) $accounts->sum('prior_net_amount') : 0.0,
            ];
        };

        return [
            'revenue' => $bucket($income->filter(fn ($a) => $prefix($a) < 4500)->values()),
            'otherIncome' => $bucket($income->filter(fn ($a) => $prefix($a) >= 4500)->values()),
            'costOfSales' => $bucket($between($expense, 5000, 6000)),
            'opExpenses' => $bucket($between($expense, 6000, 7000)),
            'financeCosts' => $bucket($between($expense, 7000, 8000)),
            'taxExpense' => $bucket($between($expense, 8000, 9000)),
        ];
    }

    // ── Monthly trend ────────────────────────────────────────────────────────

    /**
     * Profit or loss month by month across the year to date, with a total.
     *
     * The reference pack's ISMonth sheet: the same line items as the trading
     * statement, one column per month, so seasonality and one-off months are
     * visible in a way no single period column can show.
     *
     * Only the total column is banded. The point of a trend sheet is to
     * compare months against each other, so tinting one of them would put a
     * thumb on the scale; the total is the summary the band belongs to.
     *
     * @param  array  $d  months: [['label' => 'Mar 2026', 'income' => Collection, 'expense' => Collection], ...]
     */
    public function monthlyTrading(array $d): array
    {
        $months = $d['months'];
        $sets = array_map(fn ($m) => $this->classify($m['income'], $m['expense']), $months);
        $width = count($months) + 1;

        $rows = [];

        // Every account that moved in any month, in the order the months
        // introduce them — a line nil in all months is noise.
        $lines = function (string $bucket, int $sign) use ($sets, $width) {
            $out = [];
            $names = collect($sets)
                ->flatMap(fn ($set) => $set[$bucket]['accounts']->pluck('account_name'))
                ->unique()
                ->values();

            $visible = $names->filter(function ($name) use ($sets, $bucket) {
                foreach ($sets as $set) {
                    if (abs($this->named($set[$bucket]['accounts'], $name, 'cur')) >= 0.01) {
                        return true;
                    }
                }

                return false;
            })->values();

            foreach ($visible as $i => $name) {
                $out[] = $this->trendRow(
                    $i === $visible->count() - 1 ? 'item-last' : 'item',
                    $name,
                    array_map(fn ($set) => $sign * $this->named($set[$bucket]['accounts'], $name, 'cur'), $sets)
                );
            }

            return $out;
        };

        $bucketFigures = fn (string $bucket, int $sign) => array_map(
            fn ($set) => $sign * $set[$bucket]['cur'],
            $sets
        );
        $derived = fn (callable $f) => array_map(fn ($set) => $f($set, 'cur'), $sets);

        $populated = function (string $bucket) use ($sets): bool {
            foreach ($sets as $set) {
                if (abs($set[$bucket]['cur']) >= 0.01) {
                    return true;
                }
            }

            return false;
        };

        $rows[] = $this->blankRow('section', 'Revenue', $width);
        $rows = array_merge($rows, $lines('revenue', 1));
        $rows[] = $this->trendRow('subtotal', 'Total revenue', $bucketFigures('revenue', 1));

        if ($populated('costOfSales')) {
            $rows[] = $this->blankRow('section', 'Cost of sales', $width);
            $rows = array_merge($rows, $lines('costOfSales', -1));
            $rows[] = $this->trendRow('subtotal', 'Total cost of sales', $bucketFigures('costOfSales', -1));
        }

        $grossProfit = fn ($s, $k) => $s['revenue'][$k] - $s['costOfSales'][$k];
        $rows[] = $this->trendRow('named-subtotal', 'Gross profit', $derived($grossProfit));
        $rows[] = $this->trendMarginRow('Gross margin', $sets, $grossProfit);

        if ($populated('otherIncome')) {
            $rows[] = $this->blankRow('section', 'Other income', $width);
            $rows = array_merge($rows, $lines('otherIncome', 1));
            $rows[] = $this->trendRow('subtotal', 'Total other income', $bucketFigures('otherIncome', 1));
        }

        if ($populated('opExpenses')) {
            $rows[] = $this->blankRow('section', 'Operating expenses', $width);
            $rows = array_merge($rows, $lines('opExpenses', -1));
            $rows[] = $this->trendRow('subtotal', 'Total operating expenses', $bucketFigures('opExpenses', -1));
        }

        $operating = fn ($s, $k) => $s['revenue'][$k] - $s['costOfSales'][$k] + $s['otherIncome'][$k] - $s['opExpenses'][$k];
        $rows[] = $this->trendRow('named-subtotal', 'Operating profit', $derived($operating));

        if ($populated('financeCosts')) {
            $rows[] = $this->trendRow('item-last', 'Finance costs', $bucketFigures('financeCosts', -1));
        }

        $beforeTax = fn ($s, $k) => $operating($s, $k) - $s['financeCosts'][$k];
        $rows[] = $this->trendRow('named-subtotal', 'Profit before taxation', $derived($beforeTax));

        if ($populated('taxExpense')) {
            $rows[] = $this->trendRow('item-last', 'Income tax expense', $bucketFigures('taxExpense', -1));
        }

        $profit = fn ($s, $k) => $beforeTax($s, $k) - $s['taxExpense'][$k];
        $rows[] = $this->trendRow('grand-total', 'Profit for the month', $derived($profit));
        $rows[] = $this->trendMarginRow('Net margin', $sets, $profit);

        $columns = [['kind' => 'label', 'text' => '']];
        foreach ($months as $month) {
            $columns[] = ['kind' => 'amount', 'text' => $month['label'], 'sub' => $this->roundingLabel, 'current' => false, 'group' => 'month'];
        }
        $columns[] = ['kind' => 'amount', 'text' => 'Total', 'sub' => $this->roundingLabel, 'current' => true, 'group' => 'month'];

        return [
            'key' => 'monthly-trend',
            'title' => 'Monthly Income Statement',
            'subtitle' => $d['subtitle'],
            'columns' => $columns,
            'rows' => $rows,
            'footnotes' => [
                'Each column is that month alone; the total column is the year to date.',
            ],
        ];
    }

    /** One trend row: a figure per month, then their total. */
    private function trendRow(string $style, string $label, array $figures): array
    {
        $all = [...$figures, array_sum($figures)];

        return [
            'style' => $style,
            'label' => $label,
            'values' => array_map(fn ($v) => $this->fmt($v), $all),
            'raw' => array_map(fn ($v) => $this->scale($v), $all),
        ];
    }

    /** A margin trend: percentage of that month's revenue, then of the total. */
    private function trendMarginRow(string $label, array $sets, callable $numerator): array
    {
        $margin = function (array $set) use ($numerator): ?float {
            $revenue = $set['revenue']['cur'];

            return round($revenue, 2) == 0 ? null : ($numerator($set, 'cur') / $revenue) * 100;
        };

        $figures = array_map($margin, $sets);

        $totalRevenue = array_sum(array_map(fn ($s) => $s['revenue']['cur'], $sets));
        $totalNumerator = array_sum(array_map(fn ($s) => $numerator($s, 'cur'), $sets));
        // The margin on the total, not the sum or mean of the monthly margins —
        // averaging rates over unequal revenue bases is simply wrong.
        $figures[] = round($totalRevenue, 2) == 0 ? null : ($totalNumerator / $totalRevenue) * 100;

        return [
            'style' => 'margin',
            'label' => $label,
            'values' => array_map(fn ($v) => $this->fmtPercent($v), $figures),
            'raw' => array_map(fn ($v) => $v === null ? null : round($v, 1), $figures),
        ];
    }

    // ── Trial balance control ────────────────────────────────────────────────

    /**
     * The trial balance behind the pack, with its control totals.
     *
     * The reference pack carries a TBCheck sheet for the same reason: a set of
     * management accounts is only worth reading if it ties to the ledger, and
     * the check belongs in the pack rather than in the preparer's head.
     *
     * Neither column is banded. Debits and credits are a balancing pair with
     * no "current" side, the same reason the statement of changes in equity
     * carries no tint — see [[afs-design-system]].
     *
     * @param  array  $d  accounts carrying posted_debits / posted_credits
     */
    public function trialBalance(array $d): array
    {
        $accounts = collect($d['accounts'])
            ->filter(fn ($a) => abs((float) $a->posted_debits) >= 0.01 || abs((float) $a->posted_credits) >= 0.01)
            ->values();

        $rows = [];
        foreach ($accounts as $i => $account) {
            $rows[] = [
                'style' => $i === $accounts->count() - 1 ? 'item-last' : 'item',
                'label' => trim($account->account_code . '  ' . $account->account_name),
                'values' => [$this->fmt((float) $account->posted_debits), $this->fmt((float) $account->posted_credits)],
                'raw' => [$this->scale((float) $account->posted_debits), $this->scale((float) $account->posted_credits)],
            ];
        }

        $totalDebits = (float) $accounts->sum('posted_debits');
        $totalCredits = (float) $accounts->sum('posted_credits');
        $difference = round($totalDebits - $totalCredits, 2);

        $rows[] = [
            'style' => 'grand-total',
            'label' => 'Total',
            'values' => [$this->fmt($totalDebits), $this->fmt($totalCredits)],
            'raw' => [$this->scale($totalDebits), $this->scale($totalCredits)],
        ];

        // Stated as a figure, not a word: "in balance" hides the size of a
        // rounding difference, and a difference is the one thing the reader of
        // this sheet is looking for.
        $rows[] = [
            'style' => 'subtotal',
            'label' => $difference == 0 ? 'Difference — the trial balance is in balance' : 'Difference — the trial balance does NOT balance',
            'values' => ['', $this->fmt($difference)],
            'raw' => [null, $this->scale($difference)],
        ];

        return [
            'key' => 'trial-balance',
            'title' => 'Trial Balance',
            'subtitle' => $d['subtitle'],
            'columns' => [
                ['kind' => 'label', 'text' => ''],
                ['kind' => 'amount', 'text' => 'Debit', 'sub' => $this->roundingLabel, 'current' => false, 'group' => 'tb'],
                ['kind' => 'amount', 'text' => 'Credit', 'sub' => $this->roundingLabel, 'current' => false, 'group' => 'tb'],
            ],
            'rows' => $rows,
            'footnotes' => [
                'Posted and reversed journals for the year to date. Draft journals are excluded, so this ties to the statements rather than to everything captured.',
            ],
        ];
    }

    // ── Statements adopted from the AFS model ────────────────────────────────

    /**
     * Reshape a statement built by AfsStatementModelService into the
     * management column layout.
     *
     * The balance sheet and cash flow are not re-derived here. Their
     * presentation logic is subtle — the balance sheet replaces GL accounts
     * with register-backed carrying amounts and suppresses the accounts those
     * registers cover — and duplicating it is exactly the drift this
     * architecture exists to prevent. So the AFS shaper builds them and this
     * decorator only changes the columns: it drops the note reference, which a
     * management pack has no use for, and appends movement and movement %.
     *
     * Raw figures are read back out of the formatted strings rather than
     * recomputed. The AFS conventions are unambiguous in reverse — em dash and
     * empty are nil, parentheses are negative — so the workbook necessarily
     * holds the number the document displays. Recomputing from the ledger
     * instead would reintroduce the possibility of the two disagreeing.
     *
     * @param  array  $statement  as returned by AfsStatementModelService
     */
    public function fromAfsStatement(array $statement, array $options = []): array
    {
        $varianceLabel = $options['varianceLabel'] ?? 'Variance';
        $varianceSub = $options['varianceSub'] ?? 'fav/(adv)';
        // The AFS shaper labels its columns with financial years and titles its
        // subtitle "for the year ended". Both are wrong on a pack cut for a
        // month or a year to date, so the caller can replace them.
        $labels = $options['columnLabels'] ?? [];

        $columns = [['kind' => 'label', 'text' => '']];
        $amountIndex = 0;

        // The AFS grid is label / note / current / prior. Keep the amounts,
        // drop the note, and carry the current-column marking through.
        foreach ($statement['columns'] as $column) {
            if (($column['kind'] ?? null) !== 'amount') {
                continue;
            }
            $columns[] = [
                'kind' => 'amount',
                'text' => $labels[$amountIndex] ?? $column['text'],
                'sub' => $column['sub'] ?? $this->roundingLabel,
                'current' => (bool) ($column['current'] ?? false),
                'group' => 'position',
            ];
            $amountIndex++;
        }

        if ($this->compare) {
            $columns[] = ['kind' => 'variance', 'text' => $varianceLabel, 'sub' => $varianceSub, 'current' => false, 'group' => 'position'];
            $columns[] = ['kind' => 'percent', 'text' => $varianceLabel, 'sub' => '%', 'current' => false, 'group' => 'position'];
        }

        $rows = array_map(function (array $row) {
            $values = array_values($row['values']);
            $raw = array_map(fn ($v) => $this->parse($v), $values);

            if (! $this->compare) {
                return ['style' => $row['style'], 'label' => $row['label'], 'values' => $values, 'raw' => $raw];
            }

            [$cur, $prior] = [$raw[0] ?? null, $raw[1] ?? null];

            // A row with no figures at all — a section head, or a placeholder
            // line such as "None" — takes empty variance cells, not an em
            // dash. An em dash asserts a nil figure; there is no figure here.
            $blank = trim((string) ($values[0] ?? '')) === '' && trim((string) ($values[1] ?? '')) === '';

            if ($blank) {
                $values[] = '';
                $raw[] = null;
                $values[] = '';
                $raw[] = null;

                return ['style' => $row['style'], 'label' => $row['label'], 'values' => $values, 'raw' => $raw];
            }

            $variance = ($cur === null || $prior === null) ? null : $cur - $prior;
            // parse() returns figures already on the reporting basis, so the
            // percentage is a ratio of scaled figures — unaffected by scaling.
            $percent = $this->variancePercent($cur, $prior);

            $values[] = $variance === null ? '—' : $this->fmtScaled($variance);
            $raw[] = $variance;
            $values[] = $this->fmtPercent($percent);
            $raw[] = $percent === null ? null : round($percent, 1);

            return ['style' => $row['style'], 'label' => $row['label'], 'values' => $values, 'raw' => $raw];
        }, $statement['rows']);

        return [
            'key' => $statement['key'],
            'title' => $statement['title'],
            'subtitle' => $options['subtitle'] ?? $statement['subtitle'],
            'columns' => $columns,
            'rows' => $rows,
            'footnotes' => array_merge($statement['footnotes'] ?? [], $options['footnotes'] ?? []),
        ];
    }

    /**
     * Read a figure back out of its formatted form.
     *
     * The inverse of the AFS formatters. The document uses two distinct
     * markers and they must not be collapsed together:
     *
     *   ''   no figure belongs in this cell — a section head, a placeholder
     *        line. Returns null, and no variance is computed against it.
     *   '—'  a figure that is nil. Returns 0.0, so that a movement off a nil
     *        base still reports as the full movement rather than vanishing.
     *
     * Parentheses are negative; spaces are the thousands separator.
     */
    private function parse(?string $value): ?float
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }
        if ($value === '—') {
            return 0.0;
        }

        $negative = str_starts_with($value, '(') && str_ends_with($value, ')');
        $digits = preg_replace('/[^0-9.]/', '', $value);

        // A value cell carrying prose rather than a figure.
        if ($digits === '' || ! is_numeric($digits)) {
            return null;
        }

        return $negative ? -(float) $digits : (float) $digits;
    }

    /** Format a figure that is already on the reporting basis. */
    private function fmtScaled(float $v): string
    {
        if (round($v, 2) == 0) {
            return '—';
        }
        $n = number_format(abs($v), $this->decimals, '.', ' ');

        return $v < 0 ? "({$n})" : $n;
    }

    /**
     * Lay a month statement and a year-to-date statement side by side.
     *
     * The reference pack shows cash flow for the current month; ours showed
     * only the year to date. Rather than choose, both groups sit on one sheet
     * in the same shape as the trading statement.
     *
     * Rows are matched by label, not by index. `AfsStatementModelService::
     * cashFlows` includes its finance-cost and tax lines only when they moved,
     * and a month and a year to date do not necessarily agree on that, so the
     * two row lists can differ in length. The year to date is the spine, since
     * it contains the month; anything appearing only in the month is appended
     * rather than silently dropped.
     */
    public function mergeGroups(array $month, array $ytd): array
    {
        $blank = $this->compare ? ['', '', '', ''] : [''];
        $blankRaw = array_fill(0, count($blank), null);

        $monthByLabel = [];
        foreach ($month['rows'] as $row) {
            $monthByLabel[$row['label']] = $row;
        }

        $rows = [];
        $used = [];

        foreach ($ytd['rows'] as $ytdRow) {
            $label = $ytdRow['label'];
            $monthRow = $monthByLabel[$label] ?? null;
            $used[$label] = true;

            $rows[] = [
                'style' => $ytdRow['style'],
                'label' => $label,
                'values' => [...($monthRow['values'] ?? $blank), ...$ytdRow['values']],
                'raw' => [...($monthRow['raw'] ?? $blankRaw), ...$ytdRow['raw']],
            ];
        }

        foreach ($month['rows'] as $monthRow) {
            if (isset($used[$monthRow['label']])) {
                continue;
            }
            $rows[] = [
                'style' => $monthRow['style'],
                'label' => $monthRow['label'],
                'values' => [...$monthRow['values'], ...$blank],
                'raw' => [...$monthRow['raw'], ...$blankRaw],
            ];
        }

        // Column headers: the month group, then the year-to-date group. Each
        // keeps its own current-column marking, so both actual columns band.
        $columns = [['kind' => 'label', 'text' => '']];
        foreach ([$month, $ytd] as $group) {
            foreach (array_slice($group['columns'], 1) as $column) {
                $columns[] = $column;
            }
        }

        return [
            'key' => $ytd['key'],
            'title' => $ytd['title'],
            'subtitle' => $ytd['subtitle'],
            'columns' => $columns,
            'rows' => $rows,
            'footnotes' => $ytd['footnotes'] ?? [],
        ];
    }

    // ── Envelope ─────────────────────────────────────────────────────────────

    public function envelope(Company $company, array $period, array $statements, array $statistics): array
    {
        return [
            'company' => [
                // Company has no `name` column — the legal name is registered_name.
                'name' => $company->registered_name,
                'registration_number' => $company->registration_number,
                'income_tax_number' => $company->income_tax_number,
                'vat_number' => $company->vat_number,
            ],
            'period' => $period,
            'rounding' => [
                'divisor' => $this->divisor,
                'label' => $this->roundingLabel,
                'decimals' => $this->decimals,
            ],
            'compare' => $this->compare,
            'statements' => array_values($statements),
            'statistics' => array_values($statistics),
            'generated_at' => Carbon::now()->format('d F Y H:i'),
        ];
    }
}
