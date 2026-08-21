<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Transactions &mdash; {{ $company->registered_name }}</title>
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
            border-top: none;
            border-bottom: 0.5pt solid #000000;
            text-align: left;
            color: #ffffff;
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
            vertical-align: top;
        }

        td.right {
            text-align: right;
            font-family: inherit;
            font-variant-numeric: tabular-nums;
            font-size: 10.5pt;
        }

        td.muted {
            color: #5a7186;
        }

        tr.tx-header td {
            background: #1a345b;
            color: #fff;
            font-size: 10.5pt;
            font-weight: bold;
            padding: 3pt 4pt;
        }

        tr.tx-header td.right {
            color: #fff;
        }

        tr.line-row td {
            padding: 1.5pt 4pt;
            font-size: 10.5pt;
            background: #f4fafc;
        }

        tr.line-row td:first-child {
            padding-left: 12px;
        }

        tr.tx-spacer td {
            height: 4px;
        }

        tr.tx-source td {
            padding: 2pt 4pt 3pt 12px;
            font-size: 10.5pt;
            color: #5a7186;
            background: #f7fbfd;
            font-family: inherit;
            font-variant-numeric: tabular-nums;
            white-space: pre-wrap;
            word-break: break-word;
            border-bottom: 0.5px solid #d3e2f5;
        }

        tr.tx-source td span.src-label {
            font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;
            font-weight: bold;
            font-size: 7pt;
            text-transform: none;
            letter-spacing: 0;
            color: #6f869b;
            display: block;
            margin-bottom: 1px;
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

        .badge {
            display: inline-block;
            font-size: 7pt;
            font-weight: bold;
            padding: 1px 3px;
            border-radius: 2px;
        }

        .badge-posted {
            background: #eaf8fb;
            color: #1a345b;
        }

        .badge-draft {
            background: #f4fafc;
            color: #005bf0;
        }

        .footer {
            margin-top: 14px;
            padding-top: 5px;
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
            $totalDebits = 0;
            $totalCredits = 0;
            foreach ($transactions as $tx) {
                $totalDebits += $tx->journalLines->where('type', 'debit')->sum('amount');
                $totalCredits += $tx->journalLines->where('type', 'credit')->sum('amount');
            }
        @endphp

        @include('pdf._report-header', [
            'company'      => $company,
            'reportTitle'  => 'Transaction Journal',
            'reportPeriod' => $periodLabel . ' — ' . $transactions->count() . ' ' . \Illuminate\Support\Str::plural('transaction', $transactions->count()),
            'roundingLabel'=> 'R',
            'compare'      => false,
        ])

        <table>
            <thead>
                <tr>
                    <th style="width:8%">Date</th>
                    <th style="width:8%">Reference</th>
                    <th style="width:24%">Description</th>
                    <th style="width:7%">Status</th>
                    <th style="width:15%">Account</th>
                    <th style="width:18%">Line Note</th>
                    <th style="width:10%" class="right">Debit</th>
                    <th style="width:10%" class="right">Credit</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($transactions as $tx)
                    @php
                        $txDebits = $tx->journalLines->where('type', 'debit')->sum('amount');
                        $txCredits = $tx->journalLines->where('type', 'credit')->sum('amount');
                    @endphp
                    <tr class="tx-header">
                        <td>{{ $tx->transaction_date->format('d M Y') }}</td>
                        <td class="muted">{{ $tx->reference ?? '—' }}</td>
                        <td colspan="2">{{ $tx->description }}</td>
                        <td colspan="2" style="color:#9ec1f5;font-size: 7pt;">{{ $tx->notes ?? '' }}</td>
                        <td class="right">{{ number_format($txDebits, 2, '.', ' ') }}</td>
                        <td class="right">{{ number_format($txCredits, 2, '.', ' ') }}</td>
                    </tr>
                    @if ($tx->source_document)
                        <tr class="tx-source">
                            <td colspan="8"><span class="src-label">Source Document</span>{{ $tx->source_document }}</td>
                        </tr>
                    @endif
                    @foreach ($tx->journalLines as $line)
                        <tr class="line-row">
                            <td colspan="4"></td>
                            <td class="muted">{{ $line->account->account_code }} {{ $line->account->account_name }}
                            </td>
                            <td class="muted">{{ \Illuminate\Support\Str::limit($line->description ?? '', 35) }}</td>
                            <td class="right">{{ $line->type === 'debit' ? number_format($line->amount, 2, '.', ' ') : '' }}
                            </td>
                            <td class="right">{{ $line->type === 'credit' ? number_format($line->amount, 2, '.', ' ') : '' }}
                            </td>
                        </tr>
                    @endforeach
                    <tr class="tx-spacer">
                        <td colspan="8"></td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="grand-total">
                    <td colspan="6" style="text-align:right;padding-right:8px;">Totals</td>
                    <td class="right">{{ number_format($totalDebits, 2, '.', ' ') }}</td>
                    <td class="right">{{ number_format($totalCredits, 2, '.', ' ') }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="footer">
            Generated on {{ now()->format('d F Y \a\t H:i') }} &mdash; For internal use only.
        </div>

    </div>
    @include('pdf._attribution')
</body>

</html>
