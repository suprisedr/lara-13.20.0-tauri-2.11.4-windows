<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Trial Balance &mdash; {{ $company->registered_name }}</title>
    @include('pdf._report-header-styles')
    <style>
        /* Page geometry from CGL-YE25 sectPr: 28800 x 16200 twips
           landscape = 508mm x 285.75mm. dompdf ignores @page margins,
           so the inset lives on .page-frame below. The controller
           also sets this explicitly via setPaper([0,0,1440,810]). */
        @page {
            size: 508mm 285.75mm;
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;
            font-size: 10.5pt;
            color: #191919;
            background: #fff;
            line-height: 1.2857;
        }

        .page-frame {
            border: none;
            padding: 26.46mm 26.46mm 19.32mm 26.46mm;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            font-size: 10.5pt;
            font-weight: bold;
            text-transform: none;
            letter-spacing: 0;
            padding: 3pt 4pt;
            color: #ffffff;
            border-top: none;
            border-bottom: 0.5pt solid #000000;
            text-align: left;
            background: #005bf0;
        }

        thead th.right {
            text-align: right;
        }

        tbody tr {
            border-bottom: 0.4px solid #d3e2f5;
        }

        tbody td {
            padding: 2pt 4pt;
            font-size: 10.5pt;
            color: #191919;
            vertical-align: middle;
        }

        td.right {
            text-align: right;
            font-family: inherit;
            font-variant-numeric: tabular-nums;
            font-size: 10.5pt;
        }

        td.code {
            font-family: inherit;
            font-variant-numeric: tabular-nums;
            font-size: 10.5pt;
            white-space: nowrap;
            color: #6f869b;
        }

        td.dim {
            color: #9ec1f5;
        }

        tr.group-header td {
            font-size: 10.5pt;
            font-weight: bold;
            padding: 3pt 4pt;
            color: #005bf0;
            border-top: 0.5px solid #d3e2f5;
            background: #f4fafc;
        }

        tr.subtotal td {
            padding: 3pt 4pt;
            font-size: 10.5pt;
            font-weight: bold;
            color: #1a345b;
            background: #eaf8fb;
            border-top: 1px solid #2674f2;
        }

        tr.section-total td {
            padding: 4pt 4pt;
            font-size: 10.5pt;
            font-weight: bold;
            color: #1a345b;
            background: #d3e2f5;
            border-top: 0.5pt solid #000000;
        }

        tfoot tr.grand-total td {
            padding: 4pt 4pt;
            font-size: 10.5pt;
            font-weight: bold;
            color: #1a345b;
            background: #eaf8fb;
            border-top: 0.5pt solid #000000;
            border-bottom: 0.5pt solid #000000;
        }

        .status-line {
            font-size: 10pt;
            text-align: right;
            margin-bottom: 10px;
            color: #1a345b;
        }

        .footer {
            margin-top: 18px;
            padding-top: 6px;
            border-top: 1px solid #9ec1f5;
            font-size: 7pt;
            color: #6f869b;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="page-frame">

        @php
            $debitNormal = ['assets', 'expenses'];
            $typeLabels = [
                'assets' => 'Assets',
                'liabilities' => 'Liabilities',
                'equity' => 'Equity',
                'income' => 'Income',
                'expenses' => 'Expenses',
            ];
            $typeOrder = ['assets', 'liabilities', 'equity', 'income', 'expenses'];
            $grouped = $accounts->groupBy('account_type');

            $totalDebit = 0;
            $totalCredit = 0;
            foreach ($accounts as $a) {
                $opening = (float) $a->opening_balance;
                $pDebits = (float) $a->posted_debits;
                $pCredits = (float) $a->posted_credits;
                $isDebitNormal = in_array($a->account_type, $debitNormal);
                $balance = $isDebitNormal ? $opening + $pDebits - $pCredits : $opening + $pCredits - $pDebits;
                if ($isDebitNormal) {
                    $balance >= 0 ? ($totalDebit += $balance) : ($totalCredit += abs($balance));
                } else {
                    $balance >= 0 ? ($totalCredit += $balance) : ($totalDebit += abs($balance));
                }
            }
            $isBalanced = round($totalDebit, 2) === round($totalCredit, 2);
            $difference = round(abs($totalDebit - $totalCredit), 2);
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
        @endphp

        @include('pdf._report-header', [
            'company'      => $company,
            'reportTitle'  => 'Trial Balance',
            'reportPeriod' => 'For the period ' . \Carbon\Carbon::parse($startDate)->format('d F Y') . ' to ' . \Carbon\Carbon::parse($endDate)->format('d F Y'),
            'roundingLabel'=> $roundingLabel,
            'compare'      => false,
        ])

        <div class="status-line">
            Status:&nbsp;
            @if ($isBalanced)
                <strong>BALANCED &#10003;</strong>
            @else
                <strong>OUT OF BALANCE &mdash; Difference:
                    {{ $roundingLabel }}&nbsp;{{ number_format($difference / $rounding, $roundingDecimals, '.', ' ') }}</strong>
            @endif
            @if ($rounding > 1)
                &nbsp;&nbsp;&bull;&nbsp;&nbsp;Amounts in {{ $roundingLabel }}
            @endif
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:60px;">Code</th>
                    <th>Account Name</th>
                    <th style="width:90px;">Type</th>
                    <th class="right" style="width:100px;">Debit ({{ $roundingLabel }})</th>
                    <th class="right" style="width:100px;">Credit ({{ $roundingLabel }})</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($typeOrder as $type)
                    @php
                        $rowsInType = collect();
                        if ($grouped->has($type)) {
                            foreach ($grouped[$type] as $account) {
                                $opening  = (float) $account->opening_balance;
                                $pDebits  = (float) $account->posted_debits;
                                $pCredits = (float) $account->posted_credits;
                                $isDebitNormal = in_array($account->account_type, $debitNormal);
                                $balance = $isDebitNormal
                                    ? $opening + $pDebits - $pCredits
                                    : $opening + $pCredits - $pDebits;
                                $debit  = $isDebitNormal
                                    ? ($balance >= 0 ? $balance : 0)
                                    : ($balance < 0 ? abs($balance) : 0);
                                $credit = $isDebitNormal
                                    ? ($balance < 0 ? abs($balance) : 0)
                                    : ($balance >= 0 ? $balance : 0);
                                if ($debit == 0 && $credit == 0) {
                                    continue;
                                }
                                $rowsInType->push(compact('account', 'debit', 'credit'));
                            }
                        }
                    @endphp
                    @if ($rowsInType->isNotEmpty())
                        <tr class="group-header">
                            <td colspan="5">{{ $typeLabels[$type] ?? $type }}</td>
                        </tr>
                        @foreach ($rowsInType as $r)
                            <tr>
                                <td class="code">{{ $r['account']->account_code }}</td>
                                <td>{{ $r['account']->account_name }}</td>
                                <td style="font-size: 7pt;color:#5a7186;">
                                    {{ $typeLabels[$r['account']->account_type] ?? $r['account']->account_type }}</td>
                                <td class="{{ $r['debit'] > 0 ? 'right' : 'right dim' }}">
                                    {{ $r['debit'] > 0 ? number_format($r['debit'] / $rounding, $roundingDecimals, '.', ' ') : '—' }}</td>
                                <td class="{{ $r['credit'] > 0 ? 'right' : 'right dim' }}">
                                    {{ $r['credit'] > 0 ? number_format($r['credit'] / $rounding, $roundingDecimals, '.', ' ') : '—' }}
                                </td>
                            </tr>
                        @endforeach
                    @endif
                @endforeach
            </tbody>
            <tfoot>
                <tr class="grand-total">
                    <td colspan="3">TOTAL</td>
                    <td class="right">{{ number_format($totalDebit / $rounding, $roundingDecimals, '.', ' ') }}</td>
                    <td class="right">{{ number_format($totalCredit / $rounding, $roundingDecimals, '.', ' ') }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="footer">
            {{ $company->registered_name }} &bull; Trial Balance &bull;
            Generated {{ now()->format('d F Y \a\t H:i') }}
        </div>

    </div>
    @include('pdf._attribution')
</body>

</html>
