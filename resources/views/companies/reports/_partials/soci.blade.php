{{--
    Statement of Profit or Loss and Other Comprehensive Income (IFRS for SMEs)

    Required data (from CompanyController::incomeStatement[Pdf]):
      $company, $startDate, $endDate, $rounding, $compare
      $incomeAccounts, $expenseAccounts (flat collections with net_amount, prior_net_amount, account_code)
      $totalRevenue, $totalExpenses, $netIncome (+ prior_* when $compare)

    AFS classification is derived from account_code ranges (per ChartOfAccountsAgent conventions):
      Revenue          : income codes  < 4500
      Other income     : income codes >= 4500
      Cost of sales    : expense codes 5000–5999
      Operating exp.   : expense codes 6000–6999
      Finance costs    : expense codes 7000–7999
      Income tax       : expense codes 8000–8999
--}}
@php
    $roundingLabel = match ($rounding) {
        1000 => "R'000",
        1000000 => "R'm",
        default => 'R',
    };
    $roundingDecimals = match ($rounding) {
        1000 => 0,
        1000000 => 2,
        default => 2,
    };
    $cols = $compare ? 4 : 3;
    $currentYearLabel = \Carbon\Carbon::parse($endDate)->format('Y');
    $priorYearLabel = $compare ? \Carbon\Carbon::parse($endDate)->subYear()->format('Y') : null;

    // Normalise account codes to a 4-digit prefix for classification (supports both 4-digit and 7-digit codes).
    $codePrefix = fn($a) => (int) substr(ltrim((string) $a->account_code, '0'), 0, 4);

    $revenueAccounts = $incomeAccounts->filter(fn($a) => $codePrefix($a) < 4500)->values();
    $otherIncome = $incomeAccounts->filter(fn($a) => $codePrefix($a) >= 4500)->values();

    $costOfSales = $expenseAccounts
        ->filter(fn($a) => $codePrefix($a) >= 5000 && $codePrefix($a) < 6000)
        ->values();
    $opExpenses = $expenseAccounts
        ->filter(fn($a) => $codePrefix($a) >= 6000 && $codePrefix($a) < 7000)
        ->values();
    $financeCosts = $expenseAccounts
        ->filter(fn($a) => $codePrefix($a) >= 7000 && $codePrefix($a) < 8000)
        ->values();
    $taxExpense = $expenseAccounts
        ->filter(fn($a) => $codePrefix($a) >= 8000 && $codePrefix($a) < 9000)
        ->values();

    $sumNet = fn($c) => (float) $c->sum('net_amount');
    $sumPriorNet = fn($c) => (float) $c->sum('prior_net_amount');

    $revenueTotal = $sumNet($revenueAccounts);
    $revenueTotalPrior = $compare ? $sumPriorNet($revenueAccounts) : 0;
    $otherIncomeTotal = $sumNet($otherIncome);
    $otherIncomeTotalPrior = $compare ? $sumPriorNet($otherIncome) : 0;
    $costOfSalesTotal = $sumNet($costOfSales);
    $costOfSalesTotalPrior = $compare ? $sumPriorNet($costOfSales) : 0;
    $opExpensesTotal = $sumNet($opExpenses);
    $opExpensesTotalPrior = $compare ? $sumPriorNet($opExpenses) : 0;
    $financeCostsTotal = $sumNet($financeCosts);
    $financeCostsTotalPrior = $compare ? $sumPriorNet($financeCosts) : 0;
    $taxTotal = $sumNet($taxExpense);
    $taxTotalPrior = $compare ? $sumPriorNet($taxExpense) : 0;

    $grossProfit = $revenueTotal - $costOfSalesTotal;
    $grossProfitPrior = $revenueTotalPrior - $costOfSalesTotalPrior;
    $operatingProfit = $grossProfit + $otherIncomeTotal - $opExpensesTotal;
    $operatingProfitPrior = $grossProfitPrior + $otherIncomeTotalPrior - $opExpensesTotalPrior;
    $profitBeforeTax = $operatingProfit - $financeCostsTotal;
    $profitBeforeTaxPrior = $operatingProfitPrior - $financeCostsTotalPrior;
    $profitForYear = $profitBeforeTax - $taxTotal;
    $profitForYearPrior = $profitBeforeTaxPrior - $taxTotalPrior;

    $fmt = function ($v) use ($rounding, $roundingDecimals) {
        return $v != 0 ? number_format(abs($v) / $rounding, $roundingDecimals) : '—';
    };
    // Render with parentheses for negative figures
    $fmtSigned = function ($v) use ($rounding, $roundingDecimals) {
        if ($v == 0) {
            return '—';
        }
        $n = number_format(abs($v) / $rounding, $roundingDecimals);
        return $v < 0 ? "({$n})" : $n;
    };
    // Expense figures are always shown in parentheses (they reduce profit).
    $fmtParen = function ($v) use ($rounding, $roundingDecimals) {
        return $v != 0 ? '(' . number_format(abs($v) / $rounding, $roundingDecimals) . ')' : '—';
    };

    $amtClass = fn($v) => $v == 0 ? 'afs-amount afs-dim' : ($v < 0 ? 'afs-amount afs-abnormal' : 'afs-amount');

    $isPdf = $isPdf ?? false;
    // On web views: wrap abnormal amounts in a link to the account's transactions.
    $acctLink = function ($v, $accountId, $accountName, $fmtFn) use ($company, $isPdf, $amtClass) {
        $text = $fmtFn($v);
        $class = $amtClass($v);
        if (!$isPdf && $v < 0 && $accountId) {
            $url = route('companies.transactions', [$company, 'account_id' => $accountId]);
            return "<td class=\"{$class}\"><a href=\"{$url}\" style=\"color:inherit;text-decoration:underline dotted;text-underline-offset:2px;\" title=\"View transactions for {$accountName}\">{$text}</a></td>";
        }
        return "<td class=\"{$class}\">{$text}</td>";
    };

    // Note reference cell — links a statement line to its note in the AFS notes.
    $noteRefs = $noteRefs ?? [];
    $noteCell = function ($slug) use ($noteRefs, $company) {
        if (!isset($noteRefs[$slug])) {
            return '';
        }
        $ref = $noteRefs[$slug];
        $url = route('companies.notes-to-afs.show', [$company, $slug]);
        return '<a href="' .
            $url .
            '" style="color:#5e17eb;text-decoration:none;font-weight:600;" title="See note ' .
            $ref['n'] .
            '">' .
            $ref['n'] .
            '</a>';
    };
@endphp

<table class="afs-table">
    <thead>
        <tr>
            <th class="afs-col-label">Figures in {{ $roundingLabel }}</th>
            <th class="afs-col-note">Note(s)</th>
            <th class="afs-col-amount">{{ $currentYearLabel }}</th>
            @if ($compare)
                <th class="afs-col-amount">{{ $priorYearLabel }}</th>
            @endif
        </tr>
    </thead>
    <tbody>

        {{-- Revenue --}}
        <tr class="afs-section-main">
            <td colspan="{{ $cols }}">Revenue</td>
        </tr>
        @php $revenueVisible = $revenueAccounts->filter(fn($a) => abs($a->net_amount) >= 0.01 || ($compare && abs($a->prior_net_amount ?? 0) >= 0.01)); @endphp
        @forelse ($revenueVisible as $i => $account)
            <tr class="afs-item-row {{ $loop->last ? 'afs-item-last' : '' }}">
                <td class="afs-name"@if($account->is_separate_child ?? false) style="padding-left:1.75rem;color:#555;"@endif>{{ $account->account_name }}</td>
                <td class="afs-note"></td>
                {!! $acctLink($account->net_amount, $account->id, $account->account_name, $fmt) !!}
                @if ($compare)
                    {!! $acctLink($account->prior_net_amount ?? 0, $account->id, $account->account_name, $fmt) !!}
                @endif
            </tr>
        @empty
            <tr class="afs-item-row afs-item-last">
                <td class="afs-name afs-empty">No revenue recognised in the period</td>
                <td class="afs-note"></td>
                <td class="afs-amount afs-dim">—</td>
                @if ($compare)
                    <td class="afs-amount afs-dim">—</td>
                @endif
            </tr>
        @endforelse
        <tr class="afs-subtotal">
            <td class="afs-name">Total revenue</td>
            <td class="afs-note">{!! $noteCell('revenue') !!}</td>
            <td class="{{ $amtClass($revenueTotal) }}">{{ $fmt($revenueTotal) }}</td>
            @if ($compare)
                <td class="{{ $amtClass($revenueTotalPrior) }}">{{ $fmt($revenueTotalPrior) }}</td>
            @endif
        </tr>

        {{-- Cost of sales --}}
        @if ($costOfSales->isNotEmpty())
            <tr class="afs-section-main">
                <td colspan="{{ $cols }}">Cost of sales</td>
            </tr>
            @php $cosVisible = $costOfSales->filter(fn($a) => abs($a->net_amount) >= 0.01 || ($compare && abs($a->prior_net_amount ?? 0) >= 0.01)); @endphp
            @foreach ($cosVisible as $i => $account)
                <tr class="afs-item-row {{ $loop->last ? 'afs-item-last' : '' }}">
                    <td class="afs-name"@if($account->is_separate_child ?? false) style="padding-left:1.75rem;color:#555;"@endif>{{ $account->account_name }}</td>
                    <td class="afs-note"></td>
                    {!! $acctLink($account->net_amount, $account->id, $account->account_name, $fmtParen) !!}
                    @if ($compare)
                        {!! $acctLink($account->prior_net_amount ?? 0, $account->id, $account->account_name, $fmtParen) !!}
                    @endif
                </tr>
            @endforeach
            <tr class="afs-subtotal">
                <td class="afs-name">Total cost of sales</td>
                <td class="afs-note"></td>
                <td class="{{ $amtClass($costOfSalesTotal) }}">{{ $fmtParen($costOfSalesTotal) }}</td>
                @if ($compare)
                    <td class="{{ $amtClass($costOfSalesTotalPrior) }}">{{ $fmtParen($costOfSalesTotalPrior) }}</td>
                @endif
            </tr>
        @endif

        {{-- Gross profit --}}
        <tr class="afs-named-subtotal">
            <td class="afs-name">Gross profit</td>
            <td class="afs-note"></td>
            <td class="{{ $amtClass($grossProfit) }}">{{ $fmtSigned($grossProfit) }}</td>
            @if ($compare)
                <td class="{{ $amtClass($grossProfitPrior) }}">{{ $fmtSigned($grossProfitPrior) }}</td>
            @endif
        </tr>

        {{-- Other income --}}
        @if ($otherIncome->isNotEmpty())
            <tr class="afs-section-main">
                <td colspan="{{ $cols }}">Other income</td>
            </tr>
            @php $oiVisible = $otherIncome->filter(fn($a) => abs($a->net_amount) >= 0.01 || ($compare && abs($a->prior_net_amount ?? 0) >= 0.01)); @endphp
            @foreach ($oiVisible as $i => $account)
                <tr class="afs-item-row {{ $loop->last ? 'afs-item-last' : '' }}">
                    <td class="afs-name"@if($account->is_separate_child ?? false) style="padding-left:1.75rem;color:#555;"@endif>{{ $account->account_name }}</td>
                    <td class="afs-note"></td>
                    {!! $acctLink($account->net_amount, $account->id, $account->account_name, $fmt) !!}
                    @if ($compare)
                        {!! $acctLink($account->prior_net_amount ?? 0, $account->id, $account->account_name, $fmt) !!}
                    @endif
                </tr>
            @endforeach
            <tr class="afs-subtotal">
                <td class="afs-name">Total other income</td>
                <td class="afs-note"></td>
                <td class="{{ $amtClass($otherIncomeTotal) }}">{{ $fmt($otherIncomeTotal) }}</td>
                @if ($compare)
                    <td class="{{ $amtClass($otherIncomeTotalPrior) }}">{{ $fmt($otherIncomeTotalPrior) }}</td>
                @endif
            </tr>
        @endif

        {{-- Operating expenses --}}
        @if ($opExpenses->isNotEmpty())
            <tr class="afs-section-main">
                <td colspan="{{ $cols }}">Operating expenses</td>
            </tr>
            @php $opVisible = $opExpenses->filter(fn($a) => abs($a->net_amount) >= 0.01 || ($compare && abs($a->prior_net_amount ?? 0) >= 0.01)); @endphp
            @foreach ($opVisible as $i => $account)
                <tr class="afs-item-row {{ $loop->last ? 'afs-item-last' : '' }}">
                    <td class="afs-name"@if($account->is_separate_child ?? false) style="padding-left:1.75rem;color:#555;"@endif>{{ $account->account_name }}</td>
                    <td class="afs-note"></td>
                    {!! $acctLink($account->net_amount, $account->id, $account->account_name, $fmtParen) !!}
                    @if ($compare)
                        {!! $acctLink($account->prior_net_amount ?? 0, $account->id, $account->account_name, $fmtParen) !!}
                    @endif
                </tr>
            @endforeach
            <tr class="afs-subtotal">
                <td class="afs-name">Total operating expenses</td>
                <td class="afs-note"></td>
                <td class="{{ $amtClass($opExpensesTotal) }}">{{ $fmtParen($opExpensesTotal) }}</td>
                @if ($compare)
                    <td class="{{ $amtClass($opExpensesTotalPrior) }}">{{ $fmtParen($opExpensesTotalPrior) }}</td>
                @endif
            </tr>
        @endif

        {{-- Operating profit --}}
        <tr class="afs-named-subtotal">
            <td class="afs-name">Operating {{ $operatingProfit >= 0 ? 'profit' : 'loss' }}</td>
            <td class="afs-note">{!! $noteCell('operating-profit') !!}</td>
            <td class="{{ $amtClass($operatingProfit) }}">{{ $fmtSigned($operatingProfit) }}</td>
            @if ($compare)
                <td class="{{ $amtClass($operatingProfitPrior) }}">{{ $fmtSigned($operatingProfitPrior) }}</td>
            @endif
        </tr>

        {{-- Finance costs --}}
        @if ($financeCosts->isNotEmpty())
            <tr class="afs-section-main">
                <td colspan="{{ $cols }}">Finance costs</td>
            </tr>
            @php $finVisible = $financeCosts->filter(fn($a) => abs($a->net_amount) >= 0.01 || ($compare && abs($a->prior_net_amount ?? 0) >= 0.01)); @endphp
            @foreach ($finVisible as $i => $account)
                <tr class="afs-item-row {{ $loop->last ? 'afs-item-last' : '' }}">
                    <td class="afs-name"@if($account->is_separate_child ?? false) style="padding-left:1.75rem;color:#555;"@endif>{{ $account->account_name }}</td>
                    <td class="afs-note"></td>
                    {!! $acctLink($account->net_amount, $account->id, $account->account_name, $fmtParen) !!}
                    @if ($compare)
                        {!! $acctLink($account->prior_net_amount ?? 0, $account->id, $account->account_name, $fmtParen) !!}
                    @endif
                </tr>
            @endforeach
            <tr class="afs-subtotal">
                <td class="afs-name">Total finance costs</td>
                <td class="afs-note">{!! $noteCell('finance-income-and-costs') !!}</td>
                <td class="{{ $amtClass($financeCostsTotal) }}">{{ $fmtParen($financeCostsTotal) }}</td>
                @if ($compare)
                    <td class="{{ $amtClass($financeCostsTotalPrior) }}">{{ $fmtParen($financeCostsTotalPrior) }}</td>
                @endif
            </tr>
        @endif

        {{-- Profit before tax --}}
        <tr class="afs-named-subtotal">
            <td class="afs-name">{{ $profitBeforeTax >= 0 ? 'Profit' : 'Loss' }} before taxation</td>
            <td class="afs-note"></td>
            <td class="{{ $amtClass($profitBeforeTax) }}">{{ $fmtSigned($profitBeforeTax) }}</td>
            @if ($compare)
                <td class="{{ $amtClass($profitBeforeTaxPrior) }}">{{ $fmtSigned($profitBeforeTaxPrior) }}</td>
            @endif
        </tr>

        {{-- Taxation (presented as a single line per IFRS) --}}
        @if ($taxExpense->isNotEmpty())
            <tr class="afs-item-row afs-item-last">
                <td class="afs-name">Income tax expense</td>
                <td class="afs-note">{!! $noteCell('taxation') !!}</td>
                <td class="{{ $amtClass($taxTotal) }}">{{ $fmtParen($taxTotal) }}</td>
                @if ($compare)
                    <td class="{{ $amtClass($taxTotalPrior) }}">{{ $fmtParen($taxTotalPrior) }}</td>
                @endif
            </tr>
        @endif

        {{-- Profit for the year --}}
        <tr class="afs-grand-total">
            <td class="afs-name">{{ $profitForYear >= 0 ? 'Profit' : 'Loss' }} for the year</td>
            <td class="afs-note"></td>
            <td class="{{ $amtClass($profitForYear) }}">{{ $fmtSigned($profitForYear) }}</td>
            @if ($compare)
                <td class="{{ $amtClass($profitForYearPrior) }}">{{ $fmtSigned($profitForYearPrior) }}</td>
            @endif
        </tr>

        {{-- Other Comprehensive Income (IAS 1 / §5 IFRS for SMEs) --}}
        @php
            $ociList               = $ociAccounts ?? collect();
            $ociTotal              = (float) $ociList->sum('oci_net');
            $ociTotalPrior         = (float) $ociList->sum('oci_net_prior');
            $totalComprehensive    = $profitForYear + $ociTotal;
            $totalComprehensivePrior = $profitForYearPrior + $ociTotalPrior;
        @endphp
        <tr class="afs-section-main">
            <td colspan="{{ $cols }}">Other comprehensive income</td>
        </tr>
        @php $ociVisible = $ociList->filter(fn($a) => abs($a->oci_net) >= 0.01 || ($compare && abs($a->oci_net_prior ?? 0) >= 0.01)); @endphp
        @if ($ociVisible->isNotEmpty())
            @foreach ($ociVisible as $i => $ociAcc)
                <tr class="afs-item-row {{ $loop->last ? 'afs-item-last' : '' }}">
                    <td class="afs-name">{{ $ociAcc->account_name }}</td>
                    <td class="afs-note"></td>
                    {!! $acctLink($ociAcc->oci_net, $ociAcc->id, $ociAcc->account_name, $fmtSigned) !!}
                    @if ($compare)
                        {!! $acctLink($ociAcc->oci_net_prior ?? 0, $ociAcc->id, $ociAcc->account_name, $fmtSigned) !!}
                    @endif
                </tr>
            @endforeach
        @else
            <tr class="afs-item-row afs-item-last">
                <td colspan="{{ $cols }}" class="afs-empty">Items that will not be reclassified to profit or loss — nil</td>
            </tr>
        @endif
        <tr class="afs-subtotal">
            <td class="afs-name">Total other comprehensive income for the year</td>
            <td class="afs-note"></td>
            <td class="{{ $amtClass($ociTotal) }}">{{ $fmtSigned($ociTotal) }}</td>
            @if ($compare)
                <td class="{{ $amtClass($ociTotalPrior) }}">{{ $fmtSigned($ociTotalPrior) }}</td>
            @endif
        </tr>

    </tbody>
    <tfoot>
        <tr class="afs-grand-total">
            <td class="afs-name">Total comprehensive {{ $totalComprehensive >= 0 ? 'income' : 'loss' }} for the year</td>
            <td class="afs-note"></td>
            <td class="{{ $amtClass($totalComprehensive) }}">{{ $fmtSigned($totalComprehensive) }}</td>
            @if ($compare)
                <td class="{{ $amtClass($totalComprehensivePrior) }}">{{ $fmtSigned($totalComprehensivePrior) }}</td>
            @endif
        </tr>
    </tfoot>
</table>
