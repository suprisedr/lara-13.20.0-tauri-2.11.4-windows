<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 0;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;
            font-size: 7.5pt;
            color: #000;
            background: #fff;
            line-height: 1.35;
            padding: 50px 60px 100px 60px;
        }

        .company-logo {
            max-width: 280px;
            max-height: 100px;
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

        .meta-label {
            font-weight: bold;
            font-size: 7.5pt;
            display: inline-block;
            width: 120px;
        }

        .meta-value {
            font-size: 7.5pt;
        }

        .doc-title {
            font-size: 16pt;
            font-weight: bold;
            text-align: right;
            margin-bottom: 4px;
        }

        .divider {
            border: none;
            border-top: 2px solid #000;
            margin: 14px 0 16px 0;
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
            border-bottom: 1px solid #d3e2f5;
            vertical-align: top;
        }

        table.items-table tbody td.amt { text-align: right; }

        table.items-table tbody tr:last-child td { border-bottom: none; }

        .sku { font-size: 6.5pt; color: #888; margin-top: 1px; }

        .totals-table {
            width: 200px;
            border-collapse: collapse;
            margin-left: auto;
            margin-top: 8px;
        }

        .totals-table td {
            padding: 2px 0;
            font-size: 7.5pt;
        }

        .totals-table td.amt { text-align: right; }

        .info-section {
            margin-top: 16px;
        }

        .info-section .section-header {
            font-weight: bold;
            font-size: 8pt;
            border-bottom: 1.5px solid #000;
            padding-bottom: 3px;
            margin-bottom: 5px;
        }

        .payment-table {
            width: 100%;
            border-collapse: collapse;
        }

        .payment-table td {
            vertical-align: top;
            padding-right: 20px;
            font-size: 7.5pt;
        }

        .payment-table .lbl {
            display: block;
            font-weight: bold;
            font-size: 6.5pt;
            margin-bottom: 1px;
        }

        .notes-text {
            font-size: 7.5pt;
            color: #191919;
            white-space: pre-line;
        }

        .total-due-wrapper {
            position: fixed;
            bottom: 20px;
            left: 60px;
            right: 60px;
        }

        .total-due-bar {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 8px 0;
        }

        .total-due-bar table {
            width: 100%;
            border-collapse: collapse;
        }

        .total-due-bar td {
            font-size: 10pt;
            font-weight: bold;
        }

        .total-due-bar td.amt { text-align: right; }

        .footer-text {
            text-align: center;
            font-size: 7pt;
            color: #5a7186;
            margin-top: 6px;
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
                    <img src="{{ $logoData }}" class="company-logo" alt="">
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

    {{-- Invoice meta --}}
    <table style="width:100%;border-collapse:collapse;">
        <tr>
            <td style="vertical-align:top;width:50%;">
                <div><span class="meta-label">Bill To</span><span class="meta-value">{{ optional($invoice->customer)->name ?? $invoice->customer_name }}</span></div>
                @if ($invoice->customer_email)
                    <div><span class="meta-label">Email</span><span class="meta-value">{{ $invoice->customer_email }}</span></div>
                @endif
                @if ($invoice->customer_address)
                    <div><span class="meta-label">Address</span><span class="meta-value" style="white-space:pre-line;">{{ $invoice->customer_address }}</span></div>
                @endif
            </td>
            <td style="vertical-align:top;width:50%;">
                <div class="doc-title">INVOICE</div>
                <div style="text-align:right;font-size:7.5pt;">Invoice No: <strong>{{ $invoice->invoice_number }}</strong></div>
                <div style="text-align:right;font-size:7.5pt;">Date: <strong>{{ $invoice->invoice_date->format('Y-m-d') }}</strong></div>
                @if ($invoice->due_date)
                    <div style="text-align:right;font-size:7.5pt;">Due: <strong>{{ $invoice->due_date->format('Y-m-d') }}</strong></div>
                @endif
                <div style="text-align:right;margin-top:4px;"><span style="font-weight:bold;text-transform:uppercase;border:1px solid #000;padding:1px 5px;font-size:6pt;letter-spacing:0.08em;">{{ $invoice->status }}</span></div>
            </td>
        </tr>
    </table>

    <hr class="divider">

    {{-- Line items --}}
    <table class="items-table">
        <thead>
            <tr>
                <td style="width:40%;">Description</td>
                <td class="amt" style="width:8%;">Qty</td>
                <td class="amt" style="width:14%;">Unit Price</td>
                <td class="amt" style="width:8%;">VAT %</td>
                <td class="amt" style="width:12%;">Tax</td>
                <td class="amt" style="width:18%;">Line Total</td>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                @php
                    $base    = (float) $item->quantity * (float) $item->unit_price;
                    $itemTax = $item->tax_rate !== null ? $base * ((float) $item->tax_rate / 100) : 0;
                @endphp
                <tr>
                    <td>
                        {{ $item->description }}
                        @if ($item->inventoryItem && $item->inventoryItem->sku)
                            <div class="sku">SKU: {{ $item->inventoryItem->sku }}</div>
                        @endif
                    </td>
                    <td class="amt">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                    <td class="amt">{{ number_format((float) $item->unit_price, 2) }}</td>
                    <td class="amt">{{ $item->tax_rate !== null ? number_format((float) $item->tax_rate, 2) . '%' : '—' }}</td>
                    <td class="amt">{{ number_format($itemTax, 2) }}</td>
                    <td class="amt">{{ number_format($base + $itemTax, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <table class="totals-table">
        <tr>
            <td>Subtotal</td>
            <td class="amt">R {{ number_format($invoice->subtotal(), 2) }}</td>
        </tr>
        <tr>
            <td>VAT</td>
            <td class="amt">R {{ number_format($invoice->taxTotal(), 2) }}</td>
        </tr>
    </table>

    {{-- Payment / banking details --}}
    @if ($company->bank_name || $company->bank_account_number)
        <div class="info-section">
            <div class="section-header">Payment Details</div>
            <table class="payment-table">
                <tr>
                    @if ($company->bank_name)
                        <td><span class="lbl">Bank</span>{{ $company->bank_name }}</td>
                    @endif
                    @if ($company->bank_account_number)
                        <td><span class="lbl">Account Number</span>{{ $company->bank_account_number }}</td>
                    @endif
                    @if ($company->bank_account_type)
                        <td><span class="lbl">Account Type</span>{{ ucfirst($company->bank_account_type) }}</td>
                    @endif
                    @if ($company->bank_branch_code)
                        <td><span class="lbl">Branch Code</span>{{ $company->bank_branch_code }}</td>
                    @endif
                    <td><span class="lbl">Reference</span>{{ $invoice->invoice_number }}</td>
                </tr>
            </table>
        </div>
    @endif

    {{-- Notes --}}
    @if ($invoice->notes)
        <div class="info-section">
            <div class="section-header">Notes</div>
            <div class="notes-text">{{ $invoice->notes }}</div>
        </div>
    @endif

    {{-- Total Due pinned to bottom --}}
    <div class="total-due-wrapper">
        <div class="total-due-bar">
            <table>
                <tr>
                    <td>{{ $invoice->status === \App\Enums\InvoiceStatus::Paid->value ? 'TOTAL PAID' : 'TOTAL DUE' }}</td>
                    <td class="amt">R {{ number_format($invoice->total(), 2) }}</td>
                </tr>
            </table>
        </div>
        <div class="footer-text">
            {{ $company->registered_name }}
        </div>
    </div>

    @include('pdf._attribution', ['attrLeft' => '15mm', 'attrWidth' => '180mm'])
</body>
</html>
