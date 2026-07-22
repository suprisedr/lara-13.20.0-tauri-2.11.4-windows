<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Transactions &mdash; {{ $company->registered_name }}</title>
    @include('pdf._report-header-styles')
    <style>
        @page {
            margin: 15mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
            font-size: 7pt;
            color: #23282d;
            background: #fff;
            line-height: 1.4;
        }

        .page-frame {
            border: none;
            padding: 8mm;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            font-size: 6.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 3pt 4pt;
            border-top: 1.5px solid #16355c;
            border-bottom: 1.5px solid #16355c;
            text-align: left;
            color: #16355c;
            background: #e7f3fb;
        }

        thead th.right {
            text-align: right;
        }

        tbody tr {
            border-bottom: 0.4px solid #ddebf5;
        }

        tbody td {
            padding: 2pt 4pt;
            font-size: 7pt;
            color: #23282d;
            vertical-align: top;
        }

        td.right {
            text-align: right;
            font-family: DejaVu Sans Mono, monospace;
            font-size: 7pt;
        }

        td.muted {
            color: #4a5f78;
        }

        tr.tx-header td {
            background: #16355c;
            color: #fff;
            font-size: 7pt;
            font-weight: bold;
            padding: 3pt 4pt;
        }

        tr.tx-header td.right {
            color: #fff;
        }

        tr.line-row td {
            padding: 1.5pt 4pt;
            font-size: 6.5pt;
            background: #eef6fc;
        }

        tr.line-row td:first-child {
            padding-left: 12px;
        }

        tr.tx-spacer td {
            height: 4px;
        }

        tr.tx-source td {
            padding: 2pt 4pt 3pt 12px;
            font-size: 6pt;
            color: #4a5f78;
            background: #f8fbfe;
            font-family: DejaVu Sans Mono, monospace;
            white-space: pre-wrap;
            word-break: break-word;
            border-bottom: 0.5px solid #ddebf5;
        }

        tr.tx-source td span.src-label {
            font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
            font-weight: bold;
            font-size: 5pt;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #7a90a5;
            display: block;
            margin-bottom: 1px;
        }

        tfoot tr.grand-total td {
            padding: 4pt 4pt;
            font-size: 7pt;
            font-weight: bold;
            color: #16355c;
            background: #e7f3fb;
            border-top: 1.5px solid #16355c;
            border-bottom: 2px solid #16355c;
        }

        .badge {
            display: inline-block;
            font-size: 5.5pt;
            font-weight: bold;
            padding: 1px 3px;
            border-radius: 2px;
        }

        .badge-posted {
            background: #e7f3fb;
            color: #16355c;
        }

        .badge-draft {
            background: #eef6fc;
            color: #0079c8;
        }

        .footer {
            margin-top: 14px;
            padding-top: 5px;
            border-top: 1px solid #c9dff0;
            font-size: 6pt;
            color: #7a90a5;
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
                        <td colspan="2" style="color:#c9dff0;font-size:6pt;">{{ $tx->notes ?? '' }}</td>
                        <td class="right">{{ number_format($txDebits, 2) }}</td>
                        <td class="right">{{ number_format($txCredits, 2) }}</td>
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
                            <td class="right">{{ $line->type === 'debit' ? number_format($line->amount, 2) : '' }}
                            </td>
                            <td class="right">{{ $line->type === 'credit' ? number_format($line->amount, 2) : '' }}
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
                    <td class="right">{{ number_format($totalDebits, 2) }}</td>
                    <td class="right">{{ number_format($totalCredits, 2) }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="footer">
            Generated on {{ now()->format('d F Y \a\t H:i') }} &mdash; For internal use only.
        </div>

    </div>
</body>

</html>
