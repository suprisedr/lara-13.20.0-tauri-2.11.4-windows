<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Derives business statistics from figures already computed by the statement
 * builders, and shapes them as statement-style tables.
 *
 * Every figure here is a ratio or restatement of numbers that appear in the
 * annual financial statements — nothing is queried independently, so the
 * business plan cannot report a revenue or an asset total that disagrees with
 * the AFS for the same period.
 *
 * The output uses the same row model as {@see AfsStatementModelService}, so the
 * Word renderer draws these tables with the statement treatment and no extra
 * code. Statistics tables carry no note column, hence the explicit grid.
 *
 * Any ratio whose denominator is nil is reported as em dash rather than zero.
 * A current ratio of 0.00 and a current ratio that cannot be computed are
 * different facts, and conflating them misleads a reader of the plan.
 */
class BusinessStatisticsService
{
    /** label / current / comparative — the standard 12150 tw table, note column folded into the label. */
    private const GRID = [8520, 1815, 1815];

    private int $divisor = 1;

    private int $decimals = 2;

    private string $roundingLabel = 'R';

    private bool $compare = true;

    public function forRounding(int $divisor): self
    {
        [$label, $decimals] = match ($divisor) {
            1000 => ["R'000", 0],
            1000000 => ["R'm", 2],
            default => ['R', 2],
        };
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

    // ── Formatting ───────────────────────────────────────────────────────────

    /** Parenthesised negatives and an em dash for nil, as the statements do. */
    private function money(?float $v): string
    {
        if ($v === null || round($v, 2) == 0) {
            return '—';
        }

        $n = number_format(abs($v) / $this->divisor, $this->decimals, '.', ' ');

        return $v < 0 ? "({$n})" : $n;
    }

    private function percent(?float $v): string
    {
        return $v === null ? '—' : number_format($v, 1, '.', ' ') . '%';
    }

    private function ratio(?float $v): string
    {
        return $v === null ? '—' : number_format($v, 2, '.', ' ');
    }

    private function days(?float $v): string
    {
        return $v === null ? '—' : number_format($v, 0, '.', ' ');
    }

    private function count(?int $v): string
    {
        return $v === null ? '—' : number_format($v, 0, '.', ' ');
    }

    /** Guarded division: a nil denominator yields null, never zero or INF. */
    private function div(?float $numerator, ?float $denominator): ?float
    {
        if ($numerator === null || $denominator === null) {
            return null;
        }

        return abs($denominator) < 0.005 ? null : $numerator / $denominator;
    }

    private function pct(?float $numerator, ?float $denominator): ?float
    {
        $q = $this->div($numerator, $denominator);

        return $q === null ? null : $q * 100;
    }

    private function row(string $style, string $label, string $current, string $prior): array
    {
        return [
            'style' => $style,
            'label' => $label,
            'note' => '',
            'indent' => false,
            'values' => $this->compare ? [$current, $prior] : [$current],
        ];
    }

    private function columns(string $currentLabel, string $priorLabel, string $unitLabel): array
    {
        $cols = [
            ['kind' => 'label', 'text' => ''],
            ['kind' => 'amount', 'text' => $currentLabel, 'sub' => $unitLabel, 'current' => true],
        ];
        if ($this->compare) {
            $cols[] = ['kind' => 'amount', 'text' => $priorLabel, 'sub' => $unitLabel, 'current' => false];
        }

        return $cols;
    }

    private function table(string $key, string $title, string $subtitle, array $columns, array $rows): array
    {
        return [
            'key' => $key,
            'title' => $title,
            'subtitle' => $subtitle,
            'layout' => 'standard',
            'grid' => self::GRID,
            'columns' => $columns,
            'rows' => $rows,
            'footnotes' => [],
        ];
    }

    /**
     * @param  array  $in  raw figures assembled by CompanyController::businessPlanModel
     * @return array<int, array> statement-shaped tables
     */
    public function build(array $in, string $startDate, string $endDate): array
    {
        $cur = fn (string $k) => isset($in[$k]) ? (float) $in[$k]['cur'] : null;
        $pri = fn (string $k) => isset($in[$k]) ? (float) $in[$k]['pri'] : null;

        $curYear = Carbon::parse($endDate)->format('Y');
        $priorYear = Carbon::parse($endDate)->subYear()->format('Y');

        // Days in the period drive the working-capital ratios. Using the actual
        // period rather than a hardcoded 365 keeps a part-year plan honest.
        $days = max(1, Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1);

        $revenue = $cur('revenue');
        $revenueP = $pri('revenue');
        $cos = $cur('cost_of_sales');
        $cosP = $pri('cost_of_sales');
        $grossProfit = $revenue - $cos;
        $grossProfitP = $revenueP - $cosP;
        $operating = $grossProfit + $cur('other_income') - $cur('operating_expenses');
        $operatingP = $grossProfitP + $pri('other_income') - $pri('operating_expenses');
        $beforeTax = $operating - $cur('finance_costs');
        $beforeTaxP = $operatingP - $pri('finance_costs');
        $profit = $beforeTax - $cur('tax');
        $profitP = $beforeTaxP - $pri('tax');

        return [
            $this->tradingTable($curYear, $priorYear, [
                'revenue' => [$revenue, $revenueP],
                'cos' => [$cos, $cosP],
                'gross' => [$grossProfit, $grossProfitP],
                'opex' => [$cur('operating_expenses'), $pri('operating_expenses')],
                'other' => [$cur('other_income'), $pri('other_income')],
                'operating' => [$operating, $operatingP],
                'finance' => [$cur('finance_costs'), $pri('finance_costs')],
                'beforeTax' => [$beforeTax, $beforeTaxP],
                'tax' => [$cur('tax'), $pri('tax')],
                'profit' => [$profit, $profitP],
            ]),
            $this->positionTable($curYear, $priorYear, $in),
            $this->ratioTable($curYear, $priorYear, $in, $days, [
                'revenue' => [$revenue, $revenueP],
                'cos' => [$cos, $cosP],
                'gross' => [$grossProfit, $grossProfitP],
                'operating' => [$operating, $operatingP],
                'profit' => [$profit, $profitP],
            ]),
            $this->activityTable($curYear, $in),
        ];
    }

    private function tradingTable(string $curYear, string $priorYear, array $f): array
    {
        $m = fn (string $k, int $i) => $this->money($f[$k][$i]);
        $growth = $this->pct(
            $f['revenue'][0] - $f['revenue'][1],
            $f['revenue'][1] === null ? null : abs($f['revenue'][1])
        );

        $rows = [
            $this->row('item', 'Revenue', $m('revenue', 0), $m('revenue', 1)),
            $this->row('item-last', 'Cost of sales', $m('cos', 0), $m('cos', 1)),
            $this->row('subtotal', 'Gross profit', $m('gross', 0), $m('gross', 1)),
            $this->row('item', 'Other income', $m('other', 0), $m('other', 1)),
            $this->row('item-last', 'Operating expenses', $m('opex', 0), $m('opex', 1)),
            $this->row('subtotal', 'Operating profit', $m('operating', 0), $m('operating', 1)),
            $this->row('item-last', 'Finance costs', $m('finance', 0), $m('finance', 1)),
            $this->row('subtotal', 'Profit before taxation', $m('beforeTax', 0), $m('beforeTax', 1)),
            $this->row('item-last', 'Taxation', $m('tax', 0), $m('tax', 1)),
            $this->row('grand-total', 'Profit for the period', $m('profit', 0), $m('profit', 1)),
        ];

        if ($this->compare) {
            // Single-value metric: the comparative cell stays blank rather
            // than carrying a dash that would read as a nil figure.
            $rows[] = $this->row('item-last', 'Revenue growth on prior period', $this->percent($growth), '');
        }

        return $this->table(
            'trading-performance',
            'Trading performance',
            'Figures in ' . $this->roundingLabel,
            $this->columns($curYear, $priorYear, $this->roundingLabel),
            $rows
        );
    }

    private function positionTable(string $curYear, string $priorYear, array $in): array
    {
        $m = fn (string $k, string $w) => $this->money(isset($in[$k]) ? (float) $in[$k][$w] : null);
        $val = fn (string $k, string $w) => isset($in[$k]) ? (float) $in[$k][$w] : null;

        $workingCapital = $val('current_assets', 'cur') - $val('current_liabilities', 'cur');
        $workingCapitalP = $val('current_assets', 'pri') - $val('current_liabilities', 'pri');

        return $this->table(
            'financial-position',
            'Financial position',
            'Figures in ' . $this->roundingLabel,
            $this->columns($curYear, $priorYear, $this->roundingLabel),
            [
                $this->row('item', 'Non-current assets', $m('non_current_assets', 'cur'), $m('non_current_assets', 'pri')),
                $this->row('item-last', 'Current assets', $m('current_assets', 'cur'), $m('current_assets', 'pri')),
                $this->row('subtotal', 'Total assets', $m('total_assets', 'cur'), $m('total_assets', 'pri')),
                $this->row('item', 'Non-current liabilities', $m('non_current_liabilities', 'cur'), $m('non_current_liabilities', 'pri')),
                $this->row('item-last', 'Current liabilities', $m('current_liabilities', 'cur'), $m('current_liabilities', 'pri')),
                $this->row('subtotal', 'Total liabilities', $m('total_liabilities', 'cur'), $m('total_liabilities', 'pri')),
                $this->row('grand-total', 'Total equity', $m('total_equity', 'cur'), $m('total_equity', 'pri')),

                // Working-capital composition. Shown explicitly because the
                // days ratios below are computed from these lines, and a
                // reader needs to see an abnormal balance rather than only
                // the em dash it produces downstream.
                $this->row('section', 'Working capital composition', '', ''),
                $this->row('item', 'Cash and cash equivalents', $m('cash', 'cur'), $m('cash', 'pri')),
                $this->row('item', 'Trade and other receivables', $m('receivables', 'cur'), $m('receivables', 'pri')),
                $this->row('item', 'Inventories', $m('inventory', 'cur'), $m('inventory', 'pri')),
                $this->row('item', 'Trade and other payables', $m('payables', 'cur'), $m('payables', 'pri')),
                $this->row('item-last', 'Net working capital', $this->money($workingCapital), $this->money($workingCapitalP)),
            ]
        );
    }

    private function ratioTable(string $curYear, string $priorYear, array $in, int $days, array $f): array
    {
        $v = fn (string $k, string $w) => isset($in[$k]) ? (float) $in[$k][$w] : null;

        // Working-capital days use the period's own length, not a fixed year.
        //
        // A negative balance yields a negative day count, which is not a
        // meaningful statistic — it means the account carries the opposite
        // sign to its nature (a debtor account in credit, say). Report it as
        // unavailable and footnote why; the balance itself is printed in the
        // financial-position table above, so nothing is hidden.
        $suppressed = false;
        $dayCount = function (?float $balance, ?float $flow) use ($days, &$suppressed) {
            if ($balance !== null && $balance < 0) {
                $suppressed = true;

                return null;
            }
            $q = $this->div($balance, $flow);

            return $q === null ? null : $q * $days;
        };

        $rows = [
            $this->row('section', 'Profitability', '', ''),
            $this->row('item', 'Gross margin', $this->percent($this->pct($f['gross'][0], $f['revenue'][0])), $this->percent($this->pct($f['gross'][1], $f['revenue'][1]))),
            $this->row('item', 'Operating margin', $this->percent($this->pct($f['operating'][0], $f['revenue'][0])), $this->percent($this->pct($f['operating'][1], $f['revenue'][1]))),
            $this->row('item', 'Net margin', $this->percent($this->pct($f['profit'][0], $f['revenue'][0])), $this->percent($this->pct($f['profit'][1], $f['revenue'][1]))),
            $this->row('item', 'Return on equity', $this->percent($this->pct($f['profit'][0], $v('total_equity', 'cur'))), $this->percent($this->pct($f['profit'][1], $v('total_equity', 'pri')))),
            $this->row('item-last', 'Return on assets', $this->percent($this->pct($f['profit'][0], $v('total_assets', 'cur'))), $this->percent($this->pct($f['profit'][1], $v('total_assets', 'pri')))),

            $this->row('section', 'Liquidity', '', ''),
            $this->row('item', 'Current ratio', $this->ratio($this->div($v('current_assets', 'cur'), $v('current_liabilities', 'cur'))), $this->ratio($this->div($v('current_assets', 'pri'), $v('current_liabilities', 'pri')))),
            $this->row('item-last', 'Quick ratio', $this->ratio($this->div($v('current_assets', 'cur') - $v('inventory', 'cur'), $v('current_liabilities', 'cur'))), $this->ratio($this->div($v('current_assets', 'pri') - $v('inventory', 'pri'), $v('current_liabilities', 'pri')))),

            $this->row('section', 'Solvency', '', ''),
            $this->row('item', 'Debt to equity', $this->ratio($this->div($v('total_liabilities', 'cur'), $v('total_equity', 'cur'))), $this->ratio($this->div($v('total_liabilities', 'pri'), $v('total_equity', 'pri')))),
            $this->row('item-last', 'Equity ratio', $this->percent($this->pct($v('total_equity', 'cur'), $v('total_assets', 'cur'))), $this->percent($this->pct($v('total_equity', 'pri'), $v('total_assets', 'pri')))),

            $this->row('section', 'Working capital', '', ''),
            $this->row('item', 'Debtor days', $this->days($dayCount($v('receivables', 'cur'), $f['revenue'][0])), $this->days($dayCount($v('receivables', 'pri'), $f['revenue'][1]))),
            $this->row('item', 'Creditor days', $this->days($dayCount($v('payables', 'cur'), $f['cos'][0])), $this->days($dayCount($v('payables', 'pri'), $f['cos'][1]))),
            $this->row('item', 'Inventory days', $this->days($dayCount($v('inventory', 'cur'), $f['cos'][0])), $this->days($dayCount($v('inventory', 'pri'), $f['cos'][1]))),
            $this->row('item-last', 'Asset turnover', $this->ratio($this->div($f['revenue'][0], $v('total_assets', 'cur'))), $this->ratio($this->div($f['revenue'][1], $v('total_assets', 'pri')))),
        ];

        $table = $this->table(
            'key-ratios',
            'Key ratios and indicators',
            'Working-capital days calculated over the ' . $days . '-day period',
            $this->columns($curYear, $priorYear, ''),
            $rows
        );

        if ($suppressed) {
            $table['footnotes'][] = 'A working-capital ratio is shown as — where the underlying balance is '
                . 'negative, which indicates an account carrying the opposite sign to its nature. Review the '
                . 'balances in the financial position table above before relying on this section.';
        }

        return $table;
    }

    private function activityTable(string $curYear, array $in): array
    {
        $counts = $in['counts'] ?? [];
        $c = fn (string $k) => $this->count(isset($counts[$k]) ? (int) $counts[$k] : null);

        // Counts are a snapshot of the register today, not a comparative
        // series, so the prior column is deliberately blank.
        return $this->table(
            'business-activity',
            'Business activity',
            'Register counts at the reporting date',
            $this->columns($curYear, '', ''),
            [
                $this->row('item', 'Transactions posted in the period', $c('transactions'), ''),
                $this->row('item', 'Invoices issued in the period', $c('invoices'), ''),
                $this->row('item', 'Active chart of accounts entries', $c('accounts'), ''),
                $this->row('item', 'Property, plant and equipment items', $c('assets'), ''),
                $this->row('item', 'Intangible assets', $c('intangibles'), ''),
                $this->row('item', 'Inventory items', $c('inventory_items'), ''),
                $this->row('item-last', 'Active leases', $c('leases'), ''),
            ]
        );
    }
}
