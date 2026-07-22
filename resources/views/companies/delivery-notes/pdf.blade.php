<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Delivery Note {{ $deliveryNote->delivery_note_number }}</title>
    <style>
        @page { margin: 0; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 7.5pt;
            color: #000;
            background: #fff;
            line-height: 1.35;
            padding: 48px 58px 100px 58px;
        }
        .company-name-fallback { font-size:14pt; font-weight:bold; }
        .company-logo { max-width:240px; max-height:80px; }
        .address-block { text-align:right; font-size:7pt; line-height:1.6; }
        .doc-title { font-size:16pt; font-weight:bold; text-align:right; margin-bottom:3px; }
        .meta-label { font-weight:bold; display:inline-block; width:100px; font-size:7pt; }
        .meta-value { font-size:7pt; }
        .section-label {
            font-size:6pt; font-weight:bold; letter-spacing:0.08em; text-transform:uppercase;
            color:#5e17eb; margin-bottom:3px;
        }
        .divider { border:none; border-top:2pt solid #000; margin:12px 0 14px; }
        .thin-divider { border:none; border-top:1pt solid #ccc; margin:10px 0; }

        table.items { width:100%; border-collapse:collapse; margin-bottom:14px; }
        table.items thead td {
            font-size:6.5pt; font-weight:bold; letter-spacing:0.05em; text-transform:uppercase;
            border-bottom:1.5pt solid #000; padding-bottom:4px;
        }
        table.items tbody td {
            padding:4px 0; font-size:7.5pt; border-bottom:0.75pt solid #ddd; vertical-align:top;
        }
        table.items tbody tr:last-child td { border-bottom:none; }
        td.num { text-align:right; }

        .sig-section { margin-top:40px; }
        .sig-row { width:100%; border-collapse:collapse; }
        .sig-row td { width:45%; padding-top:3px; font-size:7pt; color:#555; }
        .sig-row td.gap { width:10%; }
        .sig-line { border-top:1.5pt solid #000; padding-top:4px; }

        .footer-wrap { position:fixed; bottom:18px; left:58px; right:58px; }
        .footer-bar { border-top:0.75pt solid #ccc; padding-top:6px; }
        .footer-bar table { width:100%; border-collapse:collapse; }
        .footer-bar td { font-size:6.5pt; color:#555; }
        .footer-bar td.right { text-align:right; }
    </style>
</head>
<body>

    {{-- Company header --}}
    <table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
        <tr>
            <td style="vertical-align:top;width:55%;">
                @php
                    $logoData = null;
                    if ($company->logo_path) {
                        $logoPath = storage_path('app/public/' . $company->logo_path);
                        if (file_exists($logoPath)) {
                            $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
                            $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';
                            $logoData = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
                        }
                    }
                @endphp
                @if ($logoData)
                    <img src="{{ $logoData }}" class="company-logo" alt="">
                @else
                    <div class="company-name-fallback">{{ $company->registered_name }}</div>
                @endif
                <div class="address-block" style="text-align:left;margin-top:6px;">
                    @if ($company->address_line_1)<div>{{ $company->address_line_1 }}</div>@endif
                    @if ($company->address_line_2)<div>{{ $company->address_line_2 }}</div>@endif
                    @if ($company->city)<div>{{ $company->city }}@if ($company->postal_code), {{ $company->postal_code }}@endif</div>@endif
                    @if ($company->registration_number)<div>Reg. No: {{ $company->registration_number }}</div>@endif
                    @if ($company->vat_number)<div>VAT No: {{ $company->vat_number }}</div>@endif
                </div>
            </td>
            <td style="vertical-align:top;width:45%;">
                <div class="doc-title">DELIVERY NOTE</div>
                <div class="address-block">
                    <div><span class="meta-label">DN No:</span> <strong>{{ $deliveryNote->delivery_note_number }}</strong></div>
                    <div><span class="meta-label">Date:</span> {{ $deliveryNote->delivery_date->format('d M Y') }}</div>
                    @if ($deliveryNote->expected_delivery_date)
                        <div><span class="meta-label">Exp. Delivery:</span> {{ $deliveryNote->expected_delivery_date->format('d M Y') }}</div>
                    @endif
                    @if ($deliveryNote->invoice)
                        <div><span class="meta-label">Invoice Ref:</span> {{ $deliveryNote->invoice->invoice_number }}</div>
                    @endif
                    <div style="margin-top:4px;">
                        <span style="font-weight:bold;text-transform:uppercase;border:0.75pt solid #000;padding:1px 5px;font-size:6pt;letter-spacing:0.08em;">
                            {{ $deliveryNote->statusLabel() }}
                        </span>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <hr class="divider">

    {{-- Deliver to + dispatch info --}}
    <table style="width:100%;border-collapse:collapse;margin-bottom:14px;">
        <tr>
            <td style="vertical-align:top;width:50%;">
                <div class="section-label">Deliver To</div>
                <div style="font-size:7.5pt;line-height:1.6;">
                    <strong>{{ $deliveryNote->customer_name }}</strong><br>
                    @if ($deliveryNote->customer_email)<div>{{ $deliveryNote->customer_email }}</div>@endif
                    @if ($deliveryNote->delivery_address)
                        <div style="white-space:pre-line;">{{ $deliveryNote->delivery_address }}</div>
                    @endif
                </div>
            </td>
            @if ($deliveryNote->dispatched_by || $deliveryNote->vehicle_registration || $deliveryNote->tracking_reference)
                <td style="vertical-align:top;width:50%;padding-left:24px;">
                    <div class="section-label">Dispatch Information</div>
                    <div style="font-size:7.5pt;line-height:1.7;">
                        @if ($deliveryNote->dispatched_by)
                            <div><span class="meta-label">Dispatched By:</span> {{ $deliveryNote->dispatched_by }}</div>
                        @endif
                        @if ($deliveryNote->vehicle_registration)
                            <div><span class="meta-label">Vehicle:</span> {{ $deliveryNote->vehicle_registration }}</div>
                        @endif
                        @if ($deliveryNote->tracking_reference)
                            <div><span class="meta-label">Tracking Ref:</span> {{ $deliveryNote->tracking_reference }}</div>
                        @endif
                    </div>
                </td>
            @endif
        </tr>
    </table>

    <hr class="divider">

    {{-- Items --}}
    <table class="items">
        <thead>
            <tr>
                <td style="width:5%;">#</td>
                <td style="width:52%;">Description</td>
                <td style="width:20%;">Item Code / SKU</td>
                <td class="num" style="width:12%;">Quantity</td>
                <td style="width:11%;">Unit</td>
            </tr>
        </thead>
        <tbody>
            @foreach ($deliveryNote->items as $i => $item)
                <tr>
                    <td style="color:#aaa;">{{ $i + 1 }}</td>
                    <td>{{ $item->description }}</td>
                    <td style="color:#666;font-size:6.5pt;">{{ $item->inventoryItem?->sku ?? '—' }}</td>
                    <td class="num">{{ rtrim(rtrim(number_format((float)$item->quantity, 2), '0'), '.') }}</td>
                    <td style="color:#666;">{{ $item->unit ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Notes --}}
    @if ($deliveryNote->notes)
        <hr class="thin-divider">
        <div class="section-label">Notes</div>
        <div style="font-size:7pt;color:#333;white-space:pre-line;">{{ $deliveryNote->notes }}</div>
    @endif

    {{-- Signature section --}}
    <div class="sig-section">
        <table class="sig-row">
            <tr>
                <td>
                    <div class="sig-line">Dispatched by (name, signature &amp; date)</div>
                </td>
                <td class="gap"></td>
                <td>
                    <div class="sig-line">Received by (name, signature &amp; date)</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Footer --}}
    <div class="footer-wrap">
        <div class="footer-bar">
            <table>
                <tr>
                    <td>{{ $company->registered_name }} &mdash; {{ $deliveryNote->delivery_note_number }}</td>
                    <td class="right">Printed: {{ now()->format('d M Y') }}</td>
                </tr>
            </table>
        </div>
    </div>

</body>
</html>
