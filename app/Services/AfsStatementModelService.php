<?php

namespace App\Services;

use App\Models\Company;
use App\Models\FinancialStatementNote;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Turns the statement view data assembled by CompanyController into a
 * presentation-neutral row model.
 *
 * The four primary statements are laid out in exactly one of two shapes,
 * both measured from CGL-YE25-Annual-Financial-Statements.docx:
 *
 *   layout "standard"  label / note / current year / comparative
 *   layout "matrix"    label + one column per equity component (SOCE)
 *
 * Every row carries a style from the document's own vocabulary —
 * section, section-sub, item, item-last, subtotal, named-subtotal,
 * grand-total — and pre-formatted figure strings. Renderers (the docx
 * writer in desktop-mcp, and anything else downstream) only map a style
 * onto type and rules; they never re-derive an accounting figure.
 */
class AfsStatementModelService
{
    public function __construct(private NoteFigureService $noteFigures)
    {
    }

    /** Rounding divisor => [label, decimals] */
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

    // ── Formatting ───────────────────────────────────────────────────────────
    // Space thousands separator, em dash for nil, parentheses for negatives.
    // These mirror the closures in the blade partials exactly.

    private function fmt(float|int|null $v): string
    {
        $v = (float) $v;

        return round($v, 2) != 0
            ? number_format(abs($v) / $this->divisor, $this->decimals, '.', ' ')
            : '—';
    }

    private function fmtSigned(float|int|null $v): string
    {
        $v = (float) $v;
        if (round($v, 2) == 0) {
            return '—';
        }
        $n = number_format(abs($v) / $this->divisor, $this->decimals, '.', ' ');

        return $v < 0 ? "({$n})" : $n;
    }

    /** Expense-style figures are always shown in parentheses. */
    private function fmtParen(float|int|null $v): string
    {
        $v = (float) $v;

        return round($v, 2) != 0
            ? '(' . number_format(abs($v) / $this->divisor, $this->decimals, '.', ' ') . ')'
            : '—';
    }

    /** The SOCE keeps its own sign — no parentheses, no abs(). */
    private function fmtMatrix(float|int|null $v): string
    {
        $v = (float) $v;

        return round($v, 2) != 0
            ? number_format($v / $this->divisor, $this->decimals, '.', ' ')
            : '—';
    }

    private function row(string $style, string $label, array $values, string $note = '', bool $indent = false): array
    {
        return [
            'style' => $style,
            'label' => $label,
            'note' => $note,
            'indent' => $indent,
            'values' => $this->compare ? $values : [$values[0] ?? ''],
        ];
    }

    /** A section head: bold blue label, every figure cell empty. */
    private function sectionRow(string $style, string $label): array
    {
        return $this->row($style, $label, ['', '']);
    }

    private function standardColumns(string $currentLabel, ?string $priorLabel): array
    {
        $cols = [
            ['kind' => 'label', 'text' => ''],
            ['kind' => 'note', 'text' => 'Notes'],
            ['kind' => 'amount', 'text' => $currentLabel, 'sub' => $this->roundingLabel, 'current' => true],
        ];
        if ($this->compare) {
            $cols[] = ['kind' => 'amount', 'text' => $priorLabel, 'sub' => $this->roundingLabel, 'current' => false];
        }

        return $cols;
    }

    private function noteRef(array $noteRefs, ?string $slug): string
    {
        if ($slug === null || ! isset($noteRefs[$slug])) {
            return '';
        }

        return (string) $noteRefs[$slug]['n'];
    }

    // ── Statement of profit or loss and OCI ──────────────────────────────────

    /**
     * @param  array  $d  the compact() payload of CompanyController::incomeStatementPdf
     */
    public function profitOrLoss(array $d): array
    {
        $endDate = $d['endDate'];
        $noteRefs = $d['noteRefs'] ?? [];
        $income = $d['incomeAccounts'];
        $expense = $d['expenseAccounts'];

        // Classification by account code, per ChartOfAccountsAgent conventions.
        $prefix = fn ($a) => (int) substr(ltrim((string) $a->account_code, '0'), 0, 4);
        $between = fn (Collection $c, int $lo, int $hi) => $c->filter(fn ($a) => $prefix($a) >= $lo && $prefix($a) < $hi)->values();

        $revenue = $income->filter(fn ($a) => $prefix($a) < 4500)->values();
        $otherIncome = $income->filter(fn ($a) => $prefix($a) >= 4500)->values();
        $costOfSales = $between($expense, 5000, 6000);
        $opExpenses = $between($expense, 6000, 7000);
        $financeCosts = $between($expense, 7000, 8000);
        $taxExpense = $between($expense, 8000, 9000);

        $cur = fn (Collection $c) => (float) $c->sum('net_amount');
        $pri = fn (Collection $c) => $this->compare ? (float) $c->sum('prior_net_amount') : 0.0;

        $revT = $cur($revenue);
        $revP = $pri($revenue);
        $oiT = $cur($otherIncome);
        $oiP = $pri($otherIncome);
        $cosT = $cur($costOfSales);
        $cosP = $pri($costOfSales);
        $opT = $cur($opExpenses);
        $opP = $pri($opExpenses);
        $finT = $cur($financeCosts);
        $finP = $pri($financeCosts);
        $taxT = $cur($taxExpense);
        $taxP = $pri($taxExpense);

        $grossProfit = $revT - $cosT;
        $grossProfitP = $revP - $cosP;
        $operating = $grossProfit + $oiT - $opT;
        $operatingP = $grossProfitP + $oiP - $opP;
        $beforeTax = $operating - $finT;
        $beforeTaxP = $operatingP - $finP;
        $profit = $beforeTax - $taxT;
        $profitP = $beforeTaxP - $taxP;

        $rows = [];

        // Revenue
        $rows[] = $this->sectionRow('section', 'Revenue');
        $rows = array_merge($rows, $this->lineItems($revenue, 'net_amount', 'prior_net_amount', fn ($v) => $this->fmt($v),
            'No revenue recognised in the period'));
        $rows[] = $this->row('subtotal', 'Total revenue', [$this->fmt($revT), $this->fmt($revP)],
            $this->noteRef($noteRefs, 'revenue'));

        // Cost of sales
        if ($costOfSales->isNotEmpty()) {
            $rows[] = $this->sectionRow('section', 'Cost of sales');
            $rows = array_merge($rows, $this->lineItems($costOfSales, 'net_amount', 'prior_net_amount', fn ($v) => $this->fmtParen($v)));
            $rows[] = $this->row('subtotal', 'Total cost of sales', [$this->fmtParen($cosT), $this->fmtParen($cosP)]);
        }

        $rows[] = $this->row('named-subtotal', 'Gross profit', [$this->fmtSigned($grossProfit), $this->fmtSigned($grossProfitP)]);

        // Other income
        if ($otherIncome->isNotEmpty()) {
            $rows[] = $this->sectionRow('section', 'Other income');
            $rows = array_merge($rows, $this->lineItems($otherIncome, 'net_amount', 'prior_net_amount', fn ($v) => $this->fmt($v)));
            $rows[] = $this->row('subtotal', 'Total other income', [$this->fmt($oiT), $this->fmt($oiP)]);
        }

        // Operating expenses
        if ($opExpenses->isNotEmpty()) {
            $rows[] = $this->sectionRow('section', 'Operating expenses');
            $rows = array_merge($rows, $this->lineItems($opExpenses, 'net_amount', 'prior_net_amount', fn ($v) => $this->fmtParen($v)));
            $rows[] = $this->row('subtotal', 'Total operating expenses', [$this->fmtParen($opT), $this->fmtParen($opP)]);
        }

        $rows[] = $this->row('named-subtotal', 'Operating ' . ($operating >= 0 ? 'profit' : 'loss'),
            [$this->fmtSigned($operating), $this->fmtSigned($operatingP)],
            $this->noteRef($noteRefs, 'operating-profit'));

        // Finance costs
        if ($financeCosts->isNotEmpty()) {
            $rows[] = $this->sectionRow('section', 'Finance costs');
            $rows = array_merge($rows, $this->lineItems($financeCosts, 'net_amount', 'prior_net_amount', fn ($v) => $this->fmtParen($v)));
            $rows[] = $this->row('subtotal', 'Total finance costs', [$this->fmtParen($finT), $this->fmtParen($finP)],
                $this->noteRef($noteRefs, 'finance-income-and-costs'));
        }

        $rows[] = $this->row('named-subtotal', ($beforeTax >= 0 ? 'Profit' : 'Loss') . ' before taxation',
            [$this->fmtSigned($beforeTax), $this->fmtSigned($beforeTaxP)]);

        if ($taxExpense->isNotEmpty()) {
            $rows[] = $this->row('item-last', 'Income tax expense', [$this->fmtParen($taxT), $this->fmtParen($taxP)],
                $this->noteRef($noteRefs, 'taxation'));
        }

        $rows[] = $this->row('grand-total', ($profit >= 0 ? 'Profit' : 'Loss') . ' for the year',
            [$this->fmtSigned($profit), $this->fmtSigned($profitP)]);

        // Other comprehensive income
        $oci = collect($d['ociAccounts'] ?? []);
        $ociT = (float) $oci->sum('oci_net');
        $ociP = (float) $oci->sum('oci_net_prior');

        $rows[] = $this->sectionRow('section', 'Other comprehensive income');
        $ociVisible = $oci->filter(fn ($a) => abs((float) $a->oci_net) >= 0.01
            || ($this->compare && abs((float) ($a->oci_net_prior ?? 0)) >= 0.01))->values();

        if ($ociVisible->isNotEmpty()) {
            foreach ($ociVisible as $i => $acc) {
                $rows[] = $this->row(
                    $i === $ociVisible->count() - 1 ? 'item-last' : 'item',
                    $acc->account_name,
                    [$this->fmtSigned($acc->oci_net), $this->fmtSigned($acc->oci_net_prior ?? 0)]
                );
            }
        } else {
            $rows[] = $this->row('item-last', 'Items that will not be reclassified to profit or loss — nil', ['', '']);
        }
        $rows[] = $this->row('subtotal', 'Total other comprehensive income for the year',
            [$this->fmtSigned($ociT), $this->fmtSigned($ociP)]);

        $comprehensive = $profit + $ociT;
        $comprehensiveP = $profitP + $ociP;
        $rows[] = $this->row('grand-total',
            'Total comprehensive ' . ($comprehensive >= 0 ? 'income' : 'loss') . ' for the year',
            [$this->fmtSigned($comprehensive), $this->fmtSigned($comprehensiveP)]);

        return [
            'key' => 'income-statement',
            'title' => 'Statement of Profit or Loss and Other Comprehensive Income',
            'subtitle' => 'for the year ended ' . Carbon::parse($endDate)->format('d F Y'),
            'layout' => 'standard',
            'columns' => $this->standardColumns(
                Carbon::parse($endDate)->format('Y'),
                Carbon::parse($endDate)->subYear()->format('Y')
            ),
            'rows' => $rows,
            'footnotes' => [],
        ];
    }

    /**
     * Flat account lines, dropping anything nil in both years — the same
     * visibility filter the blades apply. The last line carries the hairline.
     */
    private function lineItems(Collection $accounts, string $curKey, string $priKey, callable $fmt, ?string $emptyLabel = null): array
    {
        $visible = $accounts->filter(fn ($a) => abs((float) $a->{$curKey}) >= 0.01
            || ($this->compare && abs((float) ($a->{$priKey} ?? 0)) >= 0.01))->values();

        if ($visible->isEmpty()) {
            return $emptyLabel === null ? [] : [$this->row('item-last', $emptyLabel, ['—', '—'])];
        }

        $rows = [];
        foreach ($visible as $i => $account) {
            $rows[] = $this->row(
                $i === $visible->count() - 1 ? 'item-last' : 'item',
                $account->account_name,
                [$fmt($account->{$curKey}), $fmt($account->{$priKey} ?? 0)],
                '',
                (bool) ($account->is_separate_child ?? false)
            );
        }

        return $rows;
    }

    // ── Statement of financial position ──────────────────────────────────────

    /**
     * @param  array  $d  the compact() payload of CompanyController::balanceSheetPdf
     */
    public function financialPosition(array $d): array
    {
        $asOfDate = $d['asOfDate'];
        $priorAsOfDate = $d['priorAsOfDate'] ?? null;
        $noteRefs = $d['noteRefs'] ?? [];

        $rows = [];

        // ASSETS
        $rows[] = $this->sectionRow('section', 'Assets');
        $rows[] = $this->sectionRow('section-sub', 'Non-Current Assets');

        // Register-backed lines replace their linked GL accounts.
        $registerLines = [
            ['ppeCarrying', 'Property, Plant and Equipment', 'property-plant-equipment'],
            ['intangibleCarrying', 'Intangible Assets', 'intangible-assets'],
            ['ipCarrying', 'Investment Properties', 'investment-properties'],
            ['baCarrying', 'Biological Assets', 'biological-assets'],
            ['rouCarrying', 'Right-of-Use Assets', 'right-of-use-assets'],
        ];
        $ncaRows = [];
        foreach ($registerLines as [$key, $name, $slug]) {
            if (empty($d[$key] ?? [])) {
                continue;
            }
            $ncaRows[] = [
                'name' => $name,
                'bal' => (float) collect($d[$key])->sum('carrying'),
                'prior' => (float) collect($d[$key . 'Prior'] ?? [])->sum('carrying'),
                'note_slug' => $slug,
            ];
        }
        $suppressed = array_merge(
            $d['ppeLinkedAccountIds'] ?? [],
            $d['intangibleLinkedAccountIds'] ?? [],
            $d['ipLinkedAccountIds'] ?? [],
            $d['baLinkedAccountIds'] ?? [],
            $d['rouLinkedAccountIds'] ?? [],
        );
        $ncaRows = array_merge($ncaRows, $this->balanceRows($d['nonCurrentAssets'], $suppressed));
        $rows = array_merge($rows, $this->emitBalanceRows($ncaRows, $noteRefs));
        $rows[] = $this->row('subtotal', 'Total Non-Current Assets',
            [$this->fmt($d['totalNonCurrentAssets']), $this->fmt($d['totalNonCurrentAssetsPrior'] ?? 0)]);

        $rows[] = $this->sectionRow('section-sub', 'Current Assets');
        $caRows = [];
        if (! empty($d['inventoryCarrying'] ?? [])) {
            $caRows[] = [
                'name' => 'Inventories',
                'bal' => (float) collect($d['inventoryCarrying'])->sum('carrying'),
                'prior' => (float) collect($d['inventoryCarryingPrior'] ?? [])->sum('carrying'),
                'note_slug' => 'inventories',
            ];
        }
        $caRows = array_merge($caRows, $this->balanceRows($d['currentAssets'], $d['inventoryLinkedAccountIds'] ?? []));
        $rows = array_merge($rows, $this->emitBalanceRows($caRows, $noteRefs));
        $rows[] = $this->row('subtotal', 'Total Current Assets',
            [$this->fmt($d['totalCurrentAssets']), $this->fmt($d['totalCurrentAssetsPrior'] ?? 0)]);

        $rows[] = $this->row('grand-total', 'Total Assets',
            [$this->fmt($d['totalAssets']), $this->fmt($d['totalAssetsPrior'] ?? 0)]);

        // EQUITY AND LIABILITIES
        $rows[] = $this->sectionRow('section', 'Equity and Liabilities');

        $rows[] = $this->sectionRow('section-sub', 'Equity');
        $eqRows = $this->balanceRows($d['equityAccounts']);
        $curEarnings = (float) ($d['currentEarnings'] ?? 0);
        $priorEarnings = (float) ($d['currentEarningsPrior'] ?? 0);
        if (abs($curEarnings) >= 0.01 || ($this->compare && abs($priorEarnings) >= 0.01)) {
            $eqRows[] = [
                'name' => 'Retained income for the period',
                'bal' => $curEarnings,
                'prior' => $priorEarnings,
                'note_slug' => null,
            ];
        }
        $rows = array_merge($rows, $this->emitBalanceRows($eqRows, $noteRefs));
        $rows[] = $this->row('subtotal', 'Total Equity',
            [$this->fmt($d['totalEquity']), $this->fmt($d['totalEquityPrior'] ?? 0)]);

        $rows[] = $this->sectionRow('section-sub', 'Non-Current Liabilities');
        $nclRows = [];
        if (! empty($d['leaseLiabCarrying'] ?? [])) {
            $nclRows[] = [
                'name' => 'Lease Liabilities',
                'bal' => (float) collect($d['leaseLiabCarrying'])->sum('carrying'),
                'prior' => (float) collect($d['leaseLiabCarryingPrior'] ?? [])->sum('carrying'),
                'note_slug' => 'lease-liabilities',
            ];
        }
        $nclRows = array_merge($nclRows, $this->balanceRows($d['nonCurrentLiabilities'], $d['leaseLiabLinkedAccountIds'] ?? []));
        $rows = array_merge($rows, $this->emitBalanceRows($nclRows, $noteRefs));
        $rows[] = $this->row('subtotal', 'Total Non-Current Liabilities',
            [$this->fmt($d['totalNonCurrentLiabilities'] ?? 0), $this->fmt($d['totalNonCurrentLiabilitiesPrior'] ?? 0)]);

        $rows[] = $this->sectionRow('section-sub', 'Current Liabilities');
        $rows = array_merge($rows, $this->emitBalanceRows($this->balanceRows($d['currentLiabilities']), $noteRefs));
        $rows[] = $this->row('subtotal', 'Total Current Liabilities',
            [$this->fmt($d['totalCurrentLiabilities'] ?? 0), $this->fmt($d['totalCurrentLiabilitiesPrior'] ?? 0)]);

        $rows[] = $this->row('named-subtotal', 'Total Liabilities',
            [$this->fmt($d['totalLiabilities']), $this->fmt($d['totalLiabilitiesPrior'] ?? 0)]);

        $eqLiab = (float) $d['totalLiabilities'] + (float) $d['totalEquity'];
        $eqLiabPrior = (float) ($d['totalLiabilitiesPrior'] ?? 0) + (float) ($d['totalEquityPrior'] ?? 0);
        $rows[] = $this->row('grand-total', 'Total Equity and Liabilities',
            [$this->fmt($eqLiab), $this->fmt($eqLiabPrior)]);

        $footnotes = [];
        if (abs((float) $d['totalAssets'] - $eqLiab) >= 0.01) {
            $footnotes[] = 'Warning: Statement of Financial Position is out of balance.';
        }

        return [
            'key' => 'balance-sheet',
            'title' => 'Statement of Financial Position',
            'subtitle' => 'as at ' . Carbon::parse($asOfDate)->format('d F Y'),
            'layout' => 'standard',
            'columns' => $this->standardColumns(
                Carbon::parse($asOfDate)->format('d M Y'),
                $priorAsOfDate ? Carbon::parse($priorAsOfDate)->format('d M Y') : null
            ),
            'rows' => $rows,
            'footnotes' => $footnotes,
        ];
    }

    /**
     * Flatten a balance-sheet section into raw rows. Grouped accounts roll
     * their non-separate children into the parent; separate children follow
     * as indented lines. Register-linked accounts are dropped.
     */
    private function balanceRows(iterable $accounts, array $suppressedIds = []): array
    {
        $rows = [];
        foreach ($accounts as $account) {
            if ($account->items->isNotEmpty()) {
                $visible = $account->items->filter(fn ($item) => ! in_array($item->id, $suppressedIds));
                if ($visible->isEmpty()) {
                    continue;
                }
                $rolled = $visible->where('show_separately', false);
                $rows[] = [
                    'name' => $account->account_name,
                    'bal' => (float) $account->balance + (float) $rolled->sum('balance'),
                    'prior' => (float) ($account->prior_balance ?? 0)
                        + (float) $rolled->sum(fn ($i) => (float) ($i->prior_balance ?? 0)),
                ];
                foreach ($visible->where('show_separately', true) as $item) {
                    $rows[] = [
                        'name' => $item->account_name,
                        'bal' => (float) $item->balance,
                        'prior' => (float) ($item->prior_balance ?? 0),
                        'indent' => true,
                    ];
                }

                continue;
            }

            if (in_array($account->id, $suppressedIds)) {
                continue;
            }
            $rows[] = [
                'name' => $account->account_name,
                'bal' => (float) $account->groupBalance,
                'prior' => (float) ($account->prior_groupBalance ?? 0),
            ];
        }

        return array_values(array_filter($rows, fn ($r) => abs($r['bal']) >= 0.01
            || ($this->compare && abs($r['prior'] ?? 0) >= 0.01)));
    }

    private function emitBalanceRows(array $rawRows, array $noteRefs): array
    {
        if ($rawRows === []) {
            return [$this->row('item-last', 'None', ['', ''])];
        }

        $rows = [];
        foreach ($rawRows as $i => $r) {
            $rows[] = $this->row(
                $i === count($rawRows) - 1 ? 'item-last' : 'item',
                $r['name'],
                [$this->fmt($r['bal']), $this->fmt($r['prior'] ?? 0)],
                $this->noteRef($noteRefs, $r['note_slug'] ?? $this->noteForName($r['name'])),
                (bool) ($r['indent'] ?? false)
            );
        }

        return $rows;
    }

    /** Match a balance-sheet line to its AFS note by keyword, as sofp does. */
    private function noteForName(string $name): ?string
    {
        $n = strtolower($name);
        $map = [
            'intangible-assets' => ['intangible', 'goodwill', 'software', 'patent', 'trademark'],
            'inventories' => ['inventor', 'stock'],
            'trade-and-other-receivables' => ['receivable', 'debtor'],
            'cash-and-cash-equivalents' => ['cash', 'bank'],
            'share-capital' => ['share capital', 'ordinary share', 'stated capital'],
            'trade-and-other-payables' => ['payable', 'creditor'],
            'borrowings' => ['borrow', 'loan', 'finance lease', 'mortgage'],
        ];
        foreach ($map as $slug => $keywords) {
            foreach ($keywords as $k) {
                if (str_contains($n, $k)) {
                    return $slug;
                }
            }
        }

        return null;
    }

    // ── Statement of cash flows ──────────────────────────────────────────────

    /**
     * @param  array  $d  the compact() payload of CompanyController::cashFlowPdf ($cf + $endDate)
     */
    public function cashFlows(array $d): array
    {
        $cf = $d['cf'];
        $endDate = $d['endDate'];
        $prior = $cf['prior'] ?? null;
        $pv = fn (string $k) => (float) ($prior[$k] ?? 0);
        $pair = fn (string $k) => [$this->fmtSigned($cf[$k]), $this->fmtSigned($pv($k))];

        $rows = [];

        // Operating
        $rows[] = $this->sectionRow('section', 'Cash flows from operating activities');
        $rows[] = $this->row('item', 'Cash receipts from customers', $pair('receipts'));
        $noInterestOrTax = round((float) $cf['interest'], 2) == 0 && round((float) $cf['tax'], 2) == 0;
        $rows[] = $this->row($noInterestOrTax ? 'item-last' : 'item', 'Cash paid to suppliers and employees', $pair('payments'));
        $rows[] = $this->row('subtotal', 'Cash generated from operations', $pair('cashGenerated'));

        if (round((float) $cf['interest'], 2) != 0 || ($prior && round($pv('interest'), 2) != 0)) {
            $rows[] = $this->row('item', 'Finance costs paid', $pair('interest'));
        }
        if (round((float) $cf['tax'], 2) != 0 || ($prior && round($pv('tax'), 2) != 0)) {
            $rows[] = $this->row('item', 'Tax paid', $pair('tax'));
        }
        $rows[] = $this->row('named-subtotal',
            'Net cash ' . ($cf['netOperating'] >= 0 ? 'from' : 'used in') . ' operating activities',
            $pair('netOperating'));

        // Investing
        $rows[] = $this->sectionRow('section', 'Cash flows from investing activities');
        $rows = array_merge($rows, $this->cashFlowLines($cf['investingLines'] ?? [], 'No investing activities for this period'));
        $rows[] = $this->row('named-subtotal',
            'Net cash ' . ($cf['netInvesting'] >= 0 ? 'from' : 'used in') . ' investing activities',
            $pair('netInvesting'));

        // Financing
        $rows[] = $this->sectionRow('section', 'Cash flows from financing activities');
        $rows = array_merge($rows, $this->cashFlowLines($cf['financingLines'] ?? [], 'No financing activities for this period'));
        $rows[] = $this->row('named-subtotal',
            'Net cash ' . ($cf['netFinancing'] >= 0 ? 'from' : 'used in') . ' financing activities',
            $pair('netFinancing'));

        // Reconciliation
        $rows[] = $this->row('subtotal',
            'Net ' . ($cf['netMovement'] >= 0 ? 'increase' : 'decrease') . ' in cash and cash equivalents',
            $pair('netMovement'));
        $rows[] = $this->row('item', 'Cash and cash equivalents at the beginning of the year', $pair('cashBegin'));
        $rows[] = $this->row('grand-total', 'Cash and cash equivalents at the end of the year', $pair('cashEnd'));

        return [
            'key' => 'cash-flow',
            'title' => 'Statement of Cash Flows',
            'subtitle' => 'for the year ended ' . Carbon::parse($endDate)->format('d F Y'),
            'layout' => 'standard',
            // The document narrows the label column on the cash flow
            // (7500/750/1950/1950 tw against the income statement's 7725/795/1815/1815).
            'grid' => [7500, 750, 1950, 1950],
            'columns' => $this->standardColumns(
                Carbon::parse($endDate)->format('Y'),
                Carbon::parse($endDate)->subYear()->format('Y')
            ),
            'rows' => $rows,
            'footnotes' => [],
        ];
    }

    private function cashFlowLines(iterable $lines, string $emptyLabel): array
    {
        $visible = collect($lines)
            ->filter(fn ($l) => round((float) $l['cur'], 2) != 0 || round((float) $l['pri'], 2) != 0)
            ->values();

        if ($visible->isEmpty()) {
            return [$this->row('item-last', $emptyLabel, ['', ''])];
        }

        return $visible->map(fn ($l) => $this->row('item', $l['name'],
            [$this->fmtSigned($l['cur']), $this->fmtSigned($l['pri'])]))->all();
    }

    // ── Statement of changes in equity ───────────────────────────────────────

    /**
     * The SOCE is a matrix: one column per equity component, no tint band and
     * no filled header cell. Emphasis rows are bold throughout.
     *
     * @param  array  $d  the compact() payload of CompanyController::changesInEquityPdf
     */
    public function changesInEquity(array $d): array
    {
        $endDate = $d['endDate'];
        $eq = $d['equityMovement'];
        $rv = $d['revalSurplus'] ?? [
            'open_prior' => 0, 'close_prior' => 0, 'movement_prior' => 0,
            'open_cur' => 0, 'close_cur' => 0, 'movement_cur' => 0,
        ];
        $detailsCur = $d['equityDetails'] ?? [];
        $detailsPri = $d['equityDetailsPrior'] ?? [];

        $curYear = Carbon::parse($endDate)->format('Y');
        $priorYear = Carbon::parse($endDate)->subYear()->format('Y');

        $hasReval = $rv['open_prior'] != 0 || $rv['close_cur'] != 0
            || $rv['movement_prior'] != 0 || $rv['movement_cur'] != 0;

        $oci = collect($d['ociAccounts'] ?? []);
        $ociC = (float) $oci->sum('oci_net');
        $ociP = (float) $oci->sum('oci_net_prior');

        // share / [reval] / retained / total
        $line = function (string $style, string $label, float $share, float $reval, float $retained, ?float $total = null) use ($hasReval) {
            $values = [$this->fmtMatrix($share)];
            if ($hasReval) {
                $values[] = $this->fmtMatrix($reval);
            }
            $values[] = $this->fmtMatrix($retained);
            $values[] = $this->fmtMatrix($total ?? ($share + $reval + $retained));

            return ['style' => $style, 'label' => $label, 'note' => '', 'indent' => false, 'values' => $values];
        };

        $rows = [];

        // Prior year
        $rows[] = $line('item', "Balance at beginning of {$priorYear}",
            (float) $eq['share_open_prior'], (float) $rv['open_prior'], (float) $eq['retained_open_prior']);
        $rows[] = $line('item', ($eq['profit_prior'] >= 0 ? 'Profit' : 'Loss') . ' for the year',
            0, 0, (float) $eq['profit_prior']);
        if ($ociP != 0) {
            $rows[] = $line('item', 'Other comprehensive income', 0, 0, $ociP);
        }
        if ($rv['movement_prior'] != 0) {
            $rows[] = $line('item', 'Revaluation surplus movement', 0, (float) $rv['movement_prior'], 0);
        }
        foreach ($detailsPri as $detail) {
            $rows[] = $line('item', $detail['name'], 0, 0, (float) $detail['amount']);
        }
        $shareIssuePrior = round((float) $eq['share_open'] - (float) $eq['share_open_prior'], 2);
        if ($shareIssuePrior != 0) {
            $rows[] = $line('item', 'Issue of shares', $shareIssuePrior, 0, 0);
        }

        // Current year
        $rows[] = $line('subtotal', "Balance at beginning of {$curYear}",
            (float) $eq['share_open'], (float) $rv['open_cur'], (float) $eq['retained_open']);
        if ($eq['share_issue'] != 0) {
            $rows[] = $line('item', 'Issue of shares', (float) $eq['share_issue'], 0, 0);
        }
        $rows[] = $line('item', ($eq['profit_current'] >= 0 ? 'Profit' : 'Loss') . ' for the year',
            0, 0, (float) $eq['profit_current']);
        if ($ociC != 0) {
            $rows[] = $line('item', 'Other comprehensive income', 0, 0, $ociC);
        }
        if ($rv['movement_cur'] != 0) {
            $rows[] = $line('item', 'Revaluation surplus movement', 0, (float) $rv['movement_cur'], 0);
        }
        foreach ($detailsCur as $detail) {
            $rows[] = $line('item', $detail['name'], 0, 0, (float) $detail['amount']);
        }

        $rows[] = $line('grand-total', "Balance at end of {$curYear}",
            (float) $eq['share_close'], (float) $rv['close_cur'], (float) $eq['retained_close']);

        $columns = [
            ['kind' => 'label', 'text' => 'Figures in ' . $this->roundingLabel],
            ['kind' => 'amount', 'text' => 'Share capital'],
        ];
        if ($hasReval) {
            $columns[] = ['kind' => 'amount', 'text' => 'Revaluation surplus'];
        }
        $columns[] = ['kind' => 'amount', 'text' => 'Retained income'];
        $columns[] = ['kind' => 'amount', 'text' => 'Total equity'];

        return [
            'key' => 'changes-in-equity',
            'title' => 'Statement of Changes in Equity',
            'subtitle' => 'for the year ended ' . Carbon::parse($endDate)->format('d F Y'),
            'layout' => 'matrix',
            'columns' => $columns,
            'rows' => $rows,
            'footnotes' => [],
        ];
    }

    // ── Notes to the financial statements ────────────────────────────────────

    /**
     * The notes the primary statements' note column links to (see `noteRef()`
     * above), in numbering order. Each note carries its body as a list of
     * paragraphs, each paragraph a list of lines — the seeded and user-edited
     * bodies use a blank line between paragraphs and a single newline for
     * items within one (see FinancialStatementNotesSeeder) — plus its
     * account-linked figures table, if it has one.
     *
     * @param  Collection<int, FinancialStatementNote>  $noteModels  active, include_in_afs, ordered by sort_order
     */
    public function notes(Company $company, Collection $noteModels, string $startDate, string $endDate, array $noteRefs): array
    {
        $out = [];

        foreach ($noteModels as $note) {
            $ref = $noteRefs[$note->slug] ?? null;
            if ($ref === null) {
                continue;
            }

            $figures = $this->noteFigures->figuresFor($company, $note, $startDate, $endDate);

            $rows = array_map(function ($r) {
                $row = ['label' => $r['label'], 'current' => $this->fmtSigned($r['current'])];
                if ($this->compare) {
                    $row['prior'] = $this->fmtSigned($r['prior']);
                }

                return $row;
            }, $figures['rows']);

            $out[] = [
                'number' => $ref['n'],
                'title' => $note->title,
                'paragraphs' => $this->splitParagraphs((string) ($note->body ?? '')),
                'figures' => $figures['has'] ? [
                    'rows' => $rows,
                    'total_current' => $this->fmtSigned($figures['total_current']),
                    'total_prior' => $this->compare ? $this->fmtSigned($figures['total_prior']) : null,
                ] : null,
            ];
        }

        usort($out, fn ($a, $b) => $a['number'] <=> $b['number']);

        return $out;
    }

    /** Blank line separates paragraphs; a single newline is a line break within one (bullets). */
    private function splitParagraphs(string $body): array
    {
        $body = trim($body);
        if ($body === '') {
            return [];
        }

        return array_map(
            fn ($p) => explode("\n", trim($p)),
            preg_split('/\n{2,}/', $body)
        );
    }

    // ── Envelope ─────────────────────────────────────────────────────────────

    public function envelope(Company $company, string $startDate, string $endDate, array $statements): array
    {
        return [
            'company' => [
                // Company has no `name` column — the legal name is registered_name.
                'name' => $company->registered_name,
                'registration_number' => $company->registration_number,
                'income_tax_number' => $company->income_tax_number,
            ],
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'label' => 'for the year ended ' . Carbon::parse($endDate)->format('d F Y'),
            ],
            'rounding' => [
                'divisor' => $this->divisor,
                'label' => $this->roundingLabel,
                'decimals' => $this->decimals,
            ],
            'compare' => $this->compare,
            'statements' => array_values($statements),
        ];
    }
}
