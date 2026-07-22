<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>VAT201 &mdash; {{ $company->registered_name }}</title>
    <style>
        @page { margin: 12mm 15mm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
            font-size: 7pt;
            color: #16355c;
            background: #fff;
            line-height: 1.4;
        }

        /* ── Header ─────────────────────────────── */
        .doc-header {
            background: #16355c;
            color: #fff;
            padding: 6mm 8mm;
            margin-bottom: 0;
        }
        .doc-header table { width: 100%; border-collapse: collapse; }
        .doc-header td { vertical-align: top; padding: 0; }
        .form-no {
            font-size: 5pt;
            font-weight: bold;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.5);
            margin-bottom: 2pt;
        }
        .doc-title {
            font-size: 13pt;
            font-weight: bold;
            color: #fff;
            margin-bottom: 1pt;
        }
        .doc-subtitle { font-size: 6.5pt; color: rgba(255,255,255,0.6); }
        .reg-label {
            font-size: 5pt;
            font-weight: bold;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.45);
            margin-bottom: 2pt;
        }
        .reg-no {
            font-size: 10pt;
            font-weight: bold;
            letter-spacing: 0.08em;
            color: #fff;
        }
        .period-label { font-size: 6pt; color: rgba(255,255,255,0.5); margin-top: 3pt; }

        /* ── Vendor bar ──────────────────────────── */
        .vendor-bar { border-bottom: 1.5pt solid #16355c; }
        .vendor-bar table { width: 100%; border-collapse: collapse; }
        .vendor-bar td {
            padding: 3mm 4mm;
            border-right: 0.5pt solid #d5e6f2;
            vertical-align: top;
        }
        .vendor-bar td:last-child { border-right: none; }
        .vc-label {
            font-size: 5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #4a5f78;
            margin-bottom: 1.5pt;
        }
        .vc-value { font-size: 7.5pt; font-weight: bold; color: #16355c; }
        .vc-draft { color: #92400e; }

        /* ── Section heading ─────────────────────── */
        .section-head {
            background: #16355c;
            color: #fff;
            padding: 2.5mm 4mm;
        }
        .section-head table { width: 100%; border-collapse: collapse; }
        .section-head td { padding: 0; }
        .sh-label {
            font-size: 6pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #fff;
        }
        .sh-total {
            font-size: 7.5pt;
            font-weight: bold;
            color: #fff;
            text-align: right;
        }

        /* ── Field rows ──────────────────────────── */
        .field-row { border-bottom: 0.5pt solid #e4f0f9; }
        .field-row table { width: 100%; border-collapse: collapse; }
        .field-row td { vertical-align: middle; padding: 0; }
        .field-no {
            width: 18pt;
            background: #f2f8fd;
            border-right: 0.5pt solid #d5e6f2;
            text-align: center;
            padding: 3mm 1mm;
            font-size: 6pt;
            font-weight: bold;
            color: #4a5f78;
        }
        .field-desc { padding: 3mm 4mm; }
        .field-desc strong { display: block; font-weight: bold; font-size: 7pt; color: #16355c; }
        .field-desc span { font-size: 5.5pt; color: #4a5f78; }
        .field-amount {
            width: 80pt;
            padding: 3mm 4mm;
            text-align: right;
            border-left: 0.5pt solid #d5e6f2;
            font-weight: bold;
            font-size: 8pt;
            color: #16355c;
        }

        /* ── Net payable row ─────────────────────── */
        .net-row { background: #e7f3fb; border-top: 1.5pt solid #16355c; }
        .net-row .field-no { background: #d8ecf9; border-right: 0.5pt solid #b8d6ec; font-size: 7pt; }
        .net-row .field-desc strong { font-size: 8pt; }
        .net-row .field-amount { font-size: 10pt; border-left: 0.5pt solid #b8d6ec; }

        /* ── Detail table ────────────────────────── */
        .detail-head {
            background: #f2f8fd;
            border-top: 1.5pt solid #16355c;
            border-bottom: 0.5pt solid #d5e6f2;
            padding: 2mm 4mm;
        }
        .detail-head table { width: 100%; border-collapse: collapse; }
        .dh-label { font-size: 5.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.08em; color: #4a5f78; }
        .dh-count { font-size: 5.5pt; color: #4a5f78; text-align: right; }

        table.tx-table { width: 100%; border-collapse: collapse; }
        table.tx-table thead th {
            background: #16355c;
            color: rgba(255,255,255,0.85);
            font-size: 5.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 2mm 3mm;
            text-align: left;
            border: none;
        }
        table.tx-table thead th.r { text-align: right; }
        table.tx-table tbody tr { border-bottom: 0.5pt solid #e4f0f9; }
        table.tx-table td { padding: 1.8mm 3mm; font-size: 6.5pt; color: #16355c; }
        table.tx-table td.r { text-align: right; font-weight: bold; }
        table.tx-table td.dim { color: #4a5f78; }
        table.tx-table tfoot td {
            padding: 2mm 3mm;
            font-weight: bold;
            background: #e7f3fb;
            border-top: 1pt solid #16355c;
            font-size: 7pt;
        }
        table.tx-table tfoot td.r { text-align: right; }

        .empty-row { padding: 4mm; text-align: center; color: #9ca3af; font-size: 6.5pt; background: #fafafa; }

        /* ── Footer ──────────────────────────────── */
        .doc-footer {
            margin-top: 0;
            padding: 2.5mm 4mm;
            background: #f2f8fd;
            border-top: 0.5pt solid #d5e6f2;
            font-size: 5pt;
            color: #4a5f78;
            line-height: 1.5;
        }
        .clearfix::after { content: ''; display: table; clear: both; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>

@php
    $netPositive = $netVatPayable > 0;
    $netNegative = $netVatPayable < 0;
    $netLabel    = $netPositive ? 'VAT Payable to SARS' : ($netNegative ? 'VAT Refund Due from SARS' : 'No VAT Due / Refund');
    $fieldLabel  = $netPositive ? '35' : '36';
@endphp

{{-- ── Header ── --}}
<div class="doc-header">
    <table>
        <tr>
            <td style="width:60%;">
                <p class="form-no">VAT201 &mdash; Value-Added Tax Return</p>
                <p class="doc-title">{{ $company->registered_name }}</p>
                <p class="doc-subtitle">{{ $company->company_type_label ?? '' }}</p>
            </td>
            <td style="text-align:right;">
                <p class="reg-label">VAT Registration No.</p>
                <p class="reg-no">{{ $company->vat_number ?? '—' }}</p>
                <p class="period-label">
                    {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}
                    &ndash;
                    {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                </p>
            </td>
        </tr>
    </table>
</div>

{{-- ── Vendor info ── --}}
<div class="vendor-bar">
    <table>
        <tr>
            <td style="width:34%;">
                <p class="vc-label">Tax Period</p>
                <p class="vc-value">
                    {{ \Carbon\Carbon::parse($startDate)->format('M Y') }}
                    &ndash;
                    {{ \Carbon\Carbon::parse($endDate)->format('M Y') }}
                </p>
            </td>
            <td style="width:33%;">
                <p class="vc-label">Income Tax Number</p>
                <p class="vc-value">{{ $company->income_tax_number ?? '—' }}</p>
            </td>
            <td style="width:33%;">
                <p class="vc-label">Return Status</p>
                <p class="vc-value vc-draft">Draft &mdash; Not submitted</p>
            </td>
        </tr>
    </table>
</div>

{{-- ── Section A: Output ── --}}
<div class="section-head">
    <table><tr>
        <td><span class="sh-label">Section A &mdash; Output Tax</span></td>
        <td><span class="sh-total">R {{ number_format($totalOutputVat, 2) }}</span></td>
    </tr></table>
</div>

<div class="field-row">
    <table><tr>
        <td class="field-no">1</td>
        <td class="field-desc">
            <strong>Standard-rated supplies (incl. VAT)</strong>
            <span>Sales, services and other taxable supplies at 15%</span>
        </td>
        <td class="field-amount">R {{ number_format($totalOutputVat / 0.15 * 1.15, 2) }}</td>
    </tr></table>
</div>
<div class="field-row">
    <table><tr>
        <td class="field-no">4</td>
        <td class="field-desc">
            <strong>Output tax (VAT on standard-rated supplies)</strong>
            <span>15% of standard-rated supplies excluding VAT</span>
        </td>
        <td class="field-amount">R {{ number_format($totalOutputVat, 2) }}</td>
    </tr></table>
</div>

{{-- ── Section B: Input ── --}}
<div class="section-head">
    <table><tr>
        <td><span class="sh-label">Section B &mdash; Input Tax</span></td>
        <td><span class="sh-total">R {{ number_format($totalInputVat, 2) }}</span></td>
    </tr></table>
</div>

<div class="field-row">
    <table><tr>
        <td class="field-no">14</td>
        <td class="field-desc">
            <strong>Input tax deductible (purchases and expenses)</strong>
            <span>VAT paid on qualifying business expenses at 15%</span>
        </td>
        <td class="field-amount">R {{ number_format($totalInputVat, 2) }}</td>
    </tr></table>
</div>

{{-- ── Net payable ── --}}
<div class="net-row">
    <table><tr>
        <td class="field-no">{{ $fieldLabel }}</td>
        <td class="field-desc">
            <strong>{{ $netLabel }}</strong>
            <span>Field 4 (Output tax) minus Field 14 (Input tax)</span>
        </td>
        <td class="field-amount">R {{ number_format(abs($netVatPayable), 2) }}</td>
    </tr></table>
</div>

{{-- ── Output detail ── --}}
<div class="detail-head">
    <table><tr>
        <td><span class="dh-label">Supporting Detail &mdash; Output VAT Transactions</span></td>
        <td><span class="dh-count">{{ $outputVatLines->count() }} entries</span></td>
    </tr></table>
</div>

@if ($outputVatLines->isEmpty())
    <div class="empty-row">No output VAT entries for this period.</div>
@else
    <table class="tx-table">
        <thead>
            <tr>
                <th style="width:60pt;">Date</th>
                <th>Description</th>
                <th>Reference</th>
                <th>Account</th>
                <th class="r" style="width:35pt;">Rate</th>
                <th class="r" style="width:70pt;">VAT Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($outputVatLines as $line)
            <tr>
                <td class="dim">{{ \Carbon\Carbon::parse($line['date'])->format('d M Y') }}</td>
                <td>{{ $line['description'] }}</td>
                <td class="dim">{{ $line['reference'] ?? '—' }}</td>
                <td class="dim">{{ $line['account'] }}</td>
                <td class="r dim">{{ $line['vat_rate'] !== null ? number_format($line['vat_rate'], 0).'%' : '—' }}</td>
                <td class="r">R {{ number_format($line['amount'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5">Total Output VAT (Field 4)</td>
                <td class="r">R {{ number_format($totalOutputVat, 2) }}</td>
            </tr>
        </tfoot>
    </table>
@endif

{{-- ── Input detail ── --}}
<div class="detail-head">
    <table><tr>
        <td><span class="dh-label">Supporting Detail &mdash; Input VAT Transactions</span></td>
        <td><span class="dh-count">{{ $inputVatLines->count() }} entries</span></td>
    </tr></table>
</div>

@if ($inputVatLines->isEmpty())
    <div class="empty-row">No input VAT entries for this period.</div>
@else
    <table class="tx-table">
        <thead>
            <tr>
                <th style="width:60pt;">Date</th>
                <th>Description</th>
                <th>Reference</th>
                <th>Account</th>
                <th class="r" style="width:35pt;">Rate</th>
                <th class="r" style="width:70pt;">VAT Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($inputVatLines as $line)
            <tr>
                <td class="dim">{{ \Carbon\Carbon::parse($line['date'])->format('d M Y') }}</td>
                <td>{{ $line['description'] }}</td>
                <td class="dim">{{ $line['reference'] ?? '—' }}</td>
                <td class="dim">{{ $line['account'] }}</td>
                <td class="r dim">{{ $line['vat_rate'] !== null ? number_format($line['vat_rate'], 0).'%' : '—' }}</td>
                <td class="r">R {{ number_format($line['amount'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5">Total Input VAT (Field 14)</td>
                <td class="r">R {{ number_format($totalInputVat, 2) }}</td>
            </tr>
        </tfoot>
    </table>
@endif

{{-- ── Footer ── --}}
<div class="doc-footer">
    This VAT201 summary is generated from the company&rsquo;s journal entries for the selected tax period.
    It is a <strong>draft</strong> and must be reviewed and submitted via the SARS eFiling portal.
    Generated: {{ now()->format('d M Y H:i') }}
</div>

</body>
</html>
