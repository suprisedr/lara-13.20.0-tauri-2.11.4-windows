<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Statement — {{ $supplier->name }}</title>
    <style>
        @page {
            margin: 0;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 7.5pt;
            color: #000;
            background: #fff;
            line-height: 1.35;
            padding: 50px 60px 70px 60px;
        }

        .company-name-fallback {
            font-size: 16pt;
            font-weight: bold;
        }

        .address-block {
            text-align: right;
            font-size: 7.5pt;
            line-height: 1.5;
        }

        .doc-title {
            font-size: 16pt;
            font-weight: bold;
            text-align: right;
            margin-bottom: 4px;
        }

        .meta-line {
            text-align: right;
            font-size: 7.5pt;
        }

        .divider {
            border: none;
            border-top: 2px solid #000;
            margin: 14px 0 16px 0;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .summary-table td {
            padding: 0 20px 0 0;
            font-size: 7.5pt;
            vertical-align: top;
        }

        .summary-table .lbl {
            display: block;
            font-weight: bold;
            font-size: 6.5pt;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .summary-table .amt {
            font-size: 9pt;
            font-weight: bold;
        }

        table.items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        table.items-table thead td {
            font-weight: bold;
            font-size: 7.5pt;
            border-bottom: 1.5px solid #000;
            padding-bottom: 4px;
        }

        table.items-table thead td.amt { text-align: right; }

        table.items-table tbody td {
            padding: 4px 0;
            font-size: 7.5pt;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }

        table.items-table tbody td.amt { text-align: right; }

        table.items-table tbody tr:last-child td { border-bottom: none; }

        table.items-table tfoot td {
            padding-top: 6px;
            border-top: 2px solid #000;
            font-weight: bold;
            font-size: 8pt;
        }

        table.items-table tfoot td.amt { text-align: right; }

        .footer-text {
            position: fixed;
            bottom: 20px;
            left: 60px;
            right: 60px;
            text-align: center;
            font-size: 7pt;
            color: #666;
        }
    </style>
</head>
<body>

    {{-- Header --}}
    <table style="width:100%;border-collapse:collapse;margin-bottom:18px;">
        <tr>
            <td style="vertical-align:top;width:55%;">
                @php
                    $logoData = null;
                    if ($company->logo_path) {
                        $logoPath = storage_path('app/public/' . $company->logo_path);
                        if (file_exists($logoPath)) {
                            $ext  = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
                            $mime = $ext === 'png' ? 'image/png' : ($ext === 'jpg' || $ext === 'jpeg' ? 'image/jpeg' : 'image/png');
                            $logoData = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
                        }
                    }
                @endphp
                @if ($logoData)
                    <img src="{{ $logoData }}" style="max-width:280px;max-height:100px;" alt="">
                @else
                    <div class="company-name-fallback">{{ $company->registered_name }}</div>
                @endif
            </td>
            <td style="vertical-align:top;width:45%;">
                <div class="address-block">
                    @if ($company->address_line_1)<div>{{ $company->address_line_1 }}</div>@endif
                    @if ($company->address_line_2)<div>{{ $company->address_line_2 }}</div>@endif
                    @if ($company->city)<div>{{ $company->city }}</div>@endif
                    @if ($company->province)<div>{{ $company->province }}</div>@endif
                    @if ($company->postal_code)<div>{{ $company->postal_code }}</div>@endif
                    @if ($company->registration_number)<div>Reg. No: {{ $company->registration_number }}</div>@endif
                    @if ($company->vat_number)<div>VAT No: {{ $company->vat_number }}</div>@endif
                </div>
            </td>
        </tr>
    </table>

    {{-- Statement meta --}}
    <table style="width:100%;border-collapse:collapse;">
        <tr>
            <td style="vertical-align:top;width:50%;">
                <div style="font-weight:bold;font-size:9pt;margin-bottom:2px;">{{ $supplier->name }}</div>
                @if ($supplier->contact_name)<div>Attn: {{ $supplier->contact_name }}</div>@endif
                @if ($supplier->email)<div>{{ $supplier->email }}</div>@endif
                @if ($supplier->phone)<div>{{ $supplier->phone }}</div>@endif
                @if ($supplier->address)<div style="white-space:pre-line;">{{ $supplier->address }}</div>@endif
            </td>
            <td style="vertical-align:top;width:50%;">
                <div class="doc-title">STATEMENT</div>
                <div class="meta-line">Customer: <strong>{{ $company->registered_name }}</strong></div>
                <div class="meta-line">
                    Period: <strong>{{ \Illuminate\Support\Carbon::parse($startDate)->format('Y-m-d') }} to {{ \Illuminate\Support\Carbon::parse($endDate)->format('Y-m-d') }}</strong>
                </div>
            </td>
        </tr>
    </table>

    <hr class="divider">

    {{-- Summary --}}
    <table class="summary-table">
        <tr>
            <td>
                <span class="lbl">Opening Balance</span>
                <span class="amt">R {{ number_format($openingBalance, 2) }}</span>
            </td>
            <td>
                <span class="lbl">Total Purchases</span>
                <span class="amt">R {{ number_format($lines->sum('credit'), 2) }}</span>
            </td>
            <td>
                <span class="lbl">Total Paid</span>
                <span class="amt">R {{ number_format($lines->sum('debit'), 2) }}</span>
            </td>
            <td>
                <span class="lbl">Closing Balance</span>
                <span class="amt">R {{ number_format($closingBalance, 2) }}</span>
            </td>
        </tr>
    </table>

    {{-- Transactions --}}
    <table class="items-table">
        <thead>
            <tr>
                <td style="width:12%;">Date</td>
                <td style="width:12%;">Type</td>
                <td style="width:42%;">Description</td>
                <td class="amt" style="width:12%;">Debit</td>
                <td class="amt" style="width:12%;">Credit</td>
                <td class="amt" style="width:10%;">Balance</td>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ \Illuminate\Support\Carbon::parse($startDate)->format('Y-m-d') }}</td>
                <td>&mdash;</td>
                <td>Balance brought forward</td>
                <td class="amt">&mdash;</td>
                <td class="amt">&mdash;</td>
                <td class="amt">{{ number_format($openingBalance, 2) }}</td>
            </tr>
            @forelse ($lines as $line)
                <tr>
                    <td>{{ $line['date']->format('Y-m-d') }}</td>
                    <td>{{ $line['type'] }}</td>
                    <td>{{ $line['description'] }}</td>
                    <td class="amt">{{ $line['debit'] > 0 ? number_format($line['debit'], 2) : '—' }}</td>
                    <td class="amt">{{ $line['credit'] > 0 ? number_format($line['credit'], 2) : '—' }}</td>
                    <td class="amt">{{ number_format($line['balance'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;color:#999;padding:8px 0;">No transactions in this period.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5">Closing Balance</td>
                <td class="amt">{{ number_format($closingBalance, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer-text">
        {{ $company->registered_name }}
    </div>

</body>
</html>
