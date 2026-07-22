<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>General Ledger &mdash; {{ $company->registered_name }}</title>
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
            line-height: 1.5;
        }

        .page-frame {
            border: none;
            padding: 8mm;
        }

        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #c9dff0;
            font-size: 6pt;
            color: #7a90a5;
            text-align: center;
        }

        .account-card {
            margin-bottom: 12pt;
        }

        .account-head {
            background: #e7f3fb;
            border-top: 1.5px solid #16355c;
            border-bottom: 1px solid #c9dff0;
            padding: 4pt 6pt;
        }

        .account-code {
            font-family: DejaVu Sans Mono, monospace;
            font-size: 7pt;
            font-weight: bold;
            color: #16355c;
        }

        .account-name {
            font-size: 7pt;
            font-weight: bold;
            letter-spacing: 0.01em;
            margin-left: 8px;
            color: #16355c;
        }

        .account-type {
            font-size: 6.5pt;
            color: #0079c8;
            float: right;
            text-transform: uppercase;
        }

        .account-table {
            width: 100%;
            border-collapse: collapse;
        }

        .account-table thead th {
            font-size: 6.5pt;
            padding: 3pt 4pt;
            border-top: 1px solid #16355c;
            border-bottom: 1px solid #9cc3e0;
            background: #eef6fc;
            font-weight: bold;
            color: #16355c;
        }

        .account-table tbody td {
            padding: 2pt 4pt;
            font-size: 7pt;
            color: #23282d;
        }

        .account-table td.right {
            font-size: 7pt;
            text-align: right;
            font-family: DejaVu Sans Mono, monospace;
        }

        .account-table td.dim {
            color: #a9bccd;
        }

        tr.ob-row td,
        tr.cb-row td {
            font-size: 7pt;
            font-weight: bold;
            padding: 3pt 4pt;
            background: #eef6fc;
            color: #0079c8;
        }

        tr.ob-row td {
            border-top: 1px solid #c9dff0;
        }

        tr.cb-row td {
            border-top: 1px solid #9cc3e0;
            border-bottom: 2px solid #16355c;
        }

        .no-tx {
            font-style: italic;
            color: #aaa;
            padding: 3pt 4pt;
            font-size: 6pt;
        }
    </style>
</head>

<body>
    <div class="page-frame">

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
        @endphp

        @include('pdf._report-header', [
            'company'      => $company,
            'reportTitle'  => 'General Ledger',
            'reportPeriod' => 'For the period ' . \Carbon\Carbon::parse($startDate)->format('d F Y') . ' to ' . \Carbon\Carbon::parse($endDate)->format('d F Y'),
            'roundingLabel'=> $roundingLabel,
            'compare'      => false,
        ])

        @php
            // Hide ledger accounts with no opening, no movement, and no closing balance.
            $ledgerToShow = collect($ledger)->filter(function ($entry) {
                $hasOpening = (float) $entry->opening_balance != 0;
                $hasClosing = (float) $entry->closing_balance != 0;
                $hasActivity = isset($entry->lines) && count($entry->lines) > 0;
                return $hasOpening || $hasClosing || $hasActivity;
            });
        @endphp

        @forelse ($ledgerToShow as $entry)
            <div class="account-card">
                <div class="account-head clearfix">
                    <span class="account-code">{{ $entry->account->account_code }}</span>
                    <span class="account-name">{{ $entry->account->account_name }}</span>
                    <span class="account-type">{{ ucfirst($entry->account->account_type) }}</span>
                </div>
                <table class="account-table">
                    <thead>
                        <tr>
                            <th style="width:55px;">Date</th>
                            <th style="width:120px;">Reference</th>
                            <th>Description</th>
                            <th class="right" style="width:70px;">Debit ({{ $roundingLabel }})</th>
                            <th class="right" style="width:70px;">Credit ({{ $roundingLabel }})</th>
                            <th class="right" style="width:80px;">Balance ({{ $roundingLabel }})</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="ob-row">
                            <td colspan="5">Opening Balance</td>
                            <td class="right">
                                {{ number_format($entry->opening_balance / $rounding, $roundingDecimals) }}</td>
                        </tr>
                        @forelse ($entry->lines as $line)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($line->date)->format('d/m/Y') }}</td>
                                <td>{{ $line->reference ?? '&mdash;' }}</td>
                                <td>{{ $line->description ?? '&mdash;' }}</td>
                                <td class="{{ $line->debit ? 'right' : 'right dim' }}">
                                    {{ $line->debit ? number_format($line->debit / $rounding, $roundingDecimals) : '&mdash;' }}
                                </td>
                                <td class="{{ $line->credit ? 'right' : 'right dim' }}">
                                    {{ $line->credit ? number_format($line->credit / $rounding, $roundingDecimals) : '&mdash;' }}
                                </td>
                                <td class="right">
                                    {{ number_format($line->running_balance / $rounding, $roundingDecimals) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="no-tx">No posted transactions in this period.</td>
                            </tr>
                        @endforelse
                        <tr class="cb-row">
                            <td colspan="3">
                                Closing Balance &mdash;
                                Debits: {{ number_format($entry->period_debits / $rounding, $roundingDecimals) }} /
                                Credits: {{ number_format($entry->period_credits / $rounding, $roundingDecimals) }}
                            </td>
                            <td colspan="3" class="right">
                                {{ number_format($entry->closing_balance / $rounding, $roundingDecimals) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @empty
            <p style="font-style:italic;color:#aaa;padding:12px 0;">No accounts with activity in the selected period.
            </p>
        @endforelse

        <div class="footer">
            {{ $company->registered_name }} &bull; General Ledger &bull;
            Generated {{ now()->format('d F Y \a\t H:i') }}
        </div>

    </div>
</body>

</html>
