<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Credit Note {{ $creditNote->credit_note_number }}</title>
    <style>
        @page { margin:0; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif; font-size:7.5pt; color:#000; background:#fff; line-height:1.35; padding:48px 58px 100px 58px; }
        .company-name-fallback { font-size:14pt; font-weight:bold; }
        .company-logo { max-width:240px; max-height:80px; }
        .doc-title { font-size:16pt; font-weight:bold; text-align:right; margin-bottom:3px; color:#dc2626; }
        .meta-label { font-weight:bold; display:inline-block; width:90px; font-size:7pt; }
        .section-label { font-size:6pt; font-weight:bold; letter-spacing:0.08em; text-transform:uppercase; color:#005bf0; margin-bottom:3px; }
        .divider { border:none; border-top:2pt solid #000; margin:12px 0 14px; }
        table.items { width:100%; border-collapse:collapse; margin-bottom:14px; }
        table.items thead td { font-size:6.5pt; font-weight:bold; letter-spacing:0.05em; text-transform:uppercase; border-bottom:1.5pt solid #000; padding-bottom:4px; }
        table.items tbody td { padding:4px 0; font-size:7.5pt; border-bottom:0.75pt solid #d3e2f5; vertical-align:top; }
        table.items tbody tr:last-child td { border-bottom:none; }
        td.r { text-align:right; }
        .totals { width:190px; border-collapse:collapse; margin-left:auto; margin-top:6px; }
        .totals td { padding:2px 0; font-size:7.5pt; }
        .totals td.r { text-align:right; }
        .totals tr.total td { font-weight:bold; border-top:1.5pt solid #000; padding-top:4px; }
        .footer-wrap { position:fixed; bottom:18px; left:58px; right:58px; }
        .footer-bar { border-top:0.75pt solid #d3e2f5; padding-top:6px; }
        .footer-bar table { width:100%; border-collapse:collapse; }
        .footer-bar td { font-size:6.5pt; color:#5a7186; }
        .footer-bar td.r { text-align:right; }
        .notice-bar { background:#fff0f0; border:1pt solid #fecaca; padding:5px 8px; font-size:7pt; color:#b91c1c; margin-bottom:10px; }
    </style>
</head>
<body>

    {{-- Header --}}
    <table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
        <tr>
            <td style="vertical-align:top;width:55%;">
                @php
                    $logoData = null;
                    if ($company->logo_path) {
                        $logoPath = storage_path('app/public/' . $company->logo_path);
                        if (file_exists($logoPath)) {
                            $ext  = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
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
                <div style="font-size:7pt;color:#5a7186;margin-top:6px;line-height:1.6;">
                    @if ($company->address_line_1)<div>{{ $company->address_line_1 }}</div>@endif
                    @if ($company->address_line_2)<div>{{ $company->address_line_2 }}</div>@endif
                    @if ($company->city)<div>{{ $company->city }}@if ($company->postal_code), {{ $company->postal_code }}@endif</div>@endif
                    @if ($company->registration_number)<div>Reg. No: {{ $company->registration_number }}</div>@endif
                    @if ($company->vat_number)<div>VAT No: {{ $company->vat_number }}</div>@endif
                </div>
            </td>
            <td style="vertical-align:top;width:45%;text-align:right;">
                <div class="doc-title">CREDIT NOTE</div>
                <div style="font-size:7pt;text-align:right;line-height:1.7;">
                    <div><span class="meta-label">CN No:</span> <strong>{{ $creditNote->credit_note_number }}</strong></div>
                    <div><span class="meta-label">Date:</span> {{ $creditNote->credit_note_date->format('d M Y') }}</div>
                    @if ($creditNote->invoice)
                        <div><span class="meta-label">Re Invoice:</span> {{ $creditNote->invoice->invoice_number }}</div>
                    @endif
                    @if ($creditNote->reason)
                        <div><span class="meta-label">Reason:</span> {{ $creditNote->reason }}</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <hr class="divider">

    {{-- Credit to --}}
    <div style="margin-bottom:12px;">
        <div class="section-label">Credit To</div>
        <div style="font-size:7.5pt;line-height:1.6;">
            <strong>{{ $creditNote->customer_name }}</strong>
            @if ($creditNote->customer_email)<div>{{ $creditNote->customer_email }}</div>@endif
            @if ($creditNote->customer_address)<div style="white-space:pre-line;">{{ $creditNote->customer_address }}</div>@endif
        </div>
    </div>

    <hr class="divider">

    {{-- Items --}}
    <table class="items">
        <thead>
            <tr>
                <td style="width:38%;">Description</td>
                <td class="r" style="width:8%;">Qty</td>
                <td class="r" style="width:14%;">Unit Price</td>
                <td class="r" style="width:8%;">VAT %</td>
                <td class="r" style="width:12%;">Tax</td>
                <td class="r" style="width:14%;">Line Total</td>
                <td class="r" style="width:6%;">Return↩</td>
            </tr>
        </thead>
        <tbody>
            @foreach ($creditNote->items as $item)
                @php
                    $base    = (float)$item->quantity * (float)$item->unit_price;
                    $itemTax = $item->tax_rate !== null ? $base * ((float)$item->tax_rate / 100) : 0;
                @endphp
                <tr>
                    <td>{{ $item->description }}@if ($item->inventoryItem?->sku)<div style="font-size:6pt;color:#6f869b;">SKU: {{ $item->inventoryItem->sku }}</div>@endif</td>
                    <td class="r">{{ rtrim(rtrim(number_format((float)$item->quantity, 2), '0'), '.') }}</td>
                    <td class="r">{{ number_format((float)$item->unit_price, 2) }}</td>
                    <td class="r">{{ $item->tax_rate !== null ? number_format((float)$item->tax_rate, 2) . '%' : '—' }}</td>
                    <td class="r">{{ number_format($itemTax, 2) }}</td>
                    <td class="r">{{ number_format($base + $itemTax, 2) }}</td>
                    <td class="r">{{ $item->return_to_stock ? '✓' : '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="r">R {{ number_format($creditNote->subtotal(), 2) }}</td></tr>
        <tr><td>VAT</td><td class="r">R {{ number_format($creditNote->taxTotal(), 2) }}</td></tr>
        <tr class="total"><td>Total Credit</td><td class="r">R {{ number_format($creditNote->total(), 2) }}</td></tr>
    </table>

    @if ($creditNote->notes)
        <div style="margin-top:12px;">
            <div class="section-label">Notes</div>
            <div style="font-size:7pt;color:#191919;white-space:pre-line;">{{ $creditNote->notes }}</div>
        </div>
    @endif

    <div class="footer-wrap">
        <div class="footer-bar">
            <table>
                <tr>
                    <td>{{ $company->registered_name }} &mdash; {{ $creditNote->credit_note_number }}</td>
                    <td class="r">Printed: {{ now()->format('d M Y') }}</td>
                </tr>
            </table>
        </div>
    </div>

    @include('pdf._attribution', ['attrLeft' => '15mm', 'attrWidth' => '180mm'])
</body>
</html>
