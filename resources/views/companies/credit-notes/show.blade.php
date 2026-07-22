@extends('layouts.public')

@section('title', $company->registered_name . ' — ' . $creditNote->credit_note_number)
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .mgmt-bar { display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem; }
        .mgmt-btn { display:inline-flex; align-items:center; gap:0.35rem; border:1px solid #ccc; background:#fff; color:#1b1b18; font-size:0.75rem; font-weight:700; padding:0.38rem 0.85rem; cursor:pointer; text-decoration:none; transition:all 0.15s; font-family:inherit; }
        .mgmt-btn:hover { background:#000; color:#fff; border-color:#000; }
        .mgmt-btn.primary { background:#000; color:#fff; border-color:#000; }
        .mgmt-btn.primary:hover { background:#333; }
        .mgmt-btn.danger  { border-color:#dc2626; color:#dc2626; }
        .mgmt-btn.danger:hover { background:#dc2626; color:#fff; }

        .cn-badge { display:inline-block; padding:0.2rem 0.65rem; font-size:0.7rem; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; }
        .cn-badge-draft   { background:#f3f4f6; color:#6b7280; }
        .cn-badge-issued  { background:#fef3c7; color:#92400e; }
        .cn-badge-applied { background:#dcfce7; color:#15803d; }
        .cn-badge-voided  { background:#fee2e2; color:#b91c1c; }

        .cust-doc-wrap { background:#f9fafb; padding:1.5rem 0; }
        .cust-doc { background:#fff; border:1px solid #e5e7eb; max-width:780px; margin:0 auto; padding:2.5rem 2.75rem; }
        .doc-title { font-size:1.4rem; font-weight:900; letter-spacing:-0.02em; margin-bottom:0.25rem; color:#dc2626; }
        .doc-meta { font-size:0.72rem; color:#555; line-height:1.7; }
        .doc-meta strong { color:#000; }
        .doc-divider { border:none; border-top:2px solid #000; margin:1.25rem 0; }
        .doc-table { width:100%; border-collapse:collapse; margin-bottom:1rem; }
        .doc-table th { font-size:0.62rem; font-weight:700; letter-spacing:0.07em; text-transform:uppercase; padding:0 0 0.4rem; border-bottom:1.5px solid #000; text-align:left; }
        .doc-table th.r { text-align:right; }
        .doc-table td { padding:0.45rem 0; border-bottom:1px solid #e5e7eb; font-size:0.8rem; vertical-align:top; }
        .doc-table tr:last-child td { border-bottom:none; }
        .doc-table td.r { text-align:right; font-variant-numeric:tabular-nums; }
        .totals-table { width:200px; border-collapse:collapse; margin-left:auto; }
        .totals-table td { padding:0.2rem 0; font-size:0.8rem; }
        .totals-table td.r { text-align:right; }
        .totals-table tr.total td { font-weight:800; border-top:1.5px solid #000; padding-top:0.4rem; }
        .section-label { font-size:0.6rem; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:#5e17eb; margin:1rem 0 0.4rem; }
    </style>
@endpush

@section('content')
    <div class="co-wrap">
        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')
            <main class="co-main">

                @if (session('success'))
                    <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:0.75rem 1.25rem;font-size:0.855rem;font-weight:600;margin-bottom:1rem;">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="mgmt-bar">
                    <div style="display:flex;align-items:center;gap:0.75rem;">
                        <span class="cn-badge cn-badge-{{ $creditNote->status }}">{{ $creditNote->statusLabel() }}</span>
                        @if ($creditNote->posting_transaction_id)
                            <span style="font-size:0.72rem;color:#16a34a;font-weight:700;">✓ Journal Posted</span>
                        @elseif ($creditNote->status === 'issued')
                            <span style="font-size:0.72rem;color:#d97706;font-weight:600;">⏳ Posting in progress…</span>
                        @endif
                    </div>
                    <div style="display:flex;align-items:center;gap:0.6rem;flex-wrap:wrap;">
                        @if ($creditNote->status === 'draft')
                            <form method="POST" action="{{ route('companies.credit-notes.update-status', [$company, $creditNote]) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="issued">
                                <button type="submit" class="mgmt-btn primary">Issue &amp; Post Journal</button>
                            </form>
                        @endif
                        @if ($creditNote->status === 'issued')
                            <form method="POST" action="{{ route('companies.credit-notes.update-status', [$company, $creditNote]) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="applied">
                                <button type="submit" class="mgmt-btn primary">Mark Applied</button>
                            </form>
                        @endif
                        @if (!in_array($creditNote->status, ['voided', 'applied']))
                            <form method="POST" action="{{ route('companies.credit-notes.update-status', [$company, $creditNote]) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="voided">
                                <button type="submit" class="mgmt-btn danger" onclick="return confirm('Void this credit note?')">Void</button>
                            </form>
                        @endif
                        <a href="{{ route('companies.credit-notes.pdf', [$company, $creditNote]) }}" target="_blank" class="mgmt-btn">
                            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            Download PDF
                        </a>
                        @if ($creditNote->invoice)
                            <a href="{{ route('companies.invoices.show', [$company, $creditNote->invoice]) }}" class="mgmt-btn">
                                Invoice {{ $creditNote->invoice->invoice_number }}
                            </a>
                        @endif
                    </div>
                </div>

                <div class="cust-doc-wrap">
                    <div class="cust-doc">

                        {{-- Header --}}
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.5rem;">
                            <div>
                                @if ($company->logo_path && Storage::disk('public')->exists($company->logo_path))
                                    <img src="{{ Storage::url($company->logo_path) }}" style="max-height:60px;max-width:200px;object-fit:contain;" alt="">
                                @else
                                    <div style="font-size:1.1rem;font-weight:900;letter-spacing:-0.02em;">{{ $company->registered_name }}</div>
                                @endif
                                <div style="font-size:0.7rem;color:#555;margin-top:0.5rem;line-height:1.6;">
                                    @if ($company->address_line_1)<div>{{ $company->address_line_1 }}</div>@endif
                                    @if ($company->city)<div>{{ $company->city }}@if ($company->postal_code), {{ $company->postal_code }}@endif</div>@endif
                                    @if ($company->vat_number)<div>VAT No: {{ $company->vat_number }}</div>@endif
                                </div>
                            </div>
                            <div style="text-align:right;">
                                <div class="doc-title">CREDIT NOTE</div>
                                <div class="doc-meta">
                                    <div><strong>CN No:</strong> {{ $creditNote->credit_note_number }}</div>
                                    <div><strong>Date:</strong> {{ $creditNote->credit_note_date->format('d M Y') }}</div>
                                    @if ($creditNote->invoice)
                                        <div><strong>Re Invoice:</strong> {{ $creditNote->invoice->invoice_number }}</div>
                                    @endif
                                    @if ($creditNote->reason)
                                        <div><strong>Reason:</strong> {{ $creditNote->reason }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <hr class="doc-divider">

                        {{-- Customer --}}
                        <div>
                            <p class="section-label">Credit To</p>
                            <div style="font-size:0.82rem;line-height:1.7;">
                                <strong>{{ $creditNote->customer_name }}</strong>
                                @if ($creditNote->customer_email)<div style="color:#555;">{{ $creditNote->customer_email }}</div>@endif
                                @if ($creditNote->customer_address)<div style="white-space:pre-line;color:#555;">{{ $creditNote->customer_address }}</div>@endif
                            </div>
                        </div>

                        <hr class="doc-divider">

                        {{-- Items --}}
                        <table class="doc-table">
                            <thead>
                                <tr>
                                    <th style="width:40%;">Description</th>
                                    <th class="r" style="width:8%;">Qty</th>
                                    <th class="r" style="width:14%;">Unit Price</th>
                                    <th class="r" style="width:8%;">VAT %</th>
                                    <th class="r" style="width:12%;">Tax</th>
                                    <th class="r" style="width:14%;">Line Total</th>
                                    <th style="width:4%;text-align:center;" title="Return to stock">↩</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($creditNote->items as $item)
                                    @php
                                        $base    = (float)$item->quantity * (float)$item->unit_price;
                                        $itemTax = $item->tax_rate !== null ? $base * ((float)$item->tax_rate / 100) : 0;
                                    @endphp
                                    <tr>
                                        <td>
                                            {{ $item->description }}
                                            @if ($item->inventoryItem?->sku)
                                                <div style="font-size:0.68rem;color:#9ca3af;">SKU: {{ $item->inventoryItem->sku }}</div>
                                            @endif
                                        </td>
                                        <td class="r">{{ rtrim(rtrim(number_format((float)$item->quantity, 2), '0'), '.') }}</td>
                                        <td class="r">{{ number_format((float)$item->unit_price, 2) }}</td>
                                        <td class="r">{{ $item->tax_rate !== null ? number_format((float)$item->tax_rate, 2) . '%' : '—' }}</td>
                                        <td class="r">{{ number_format($itemTax, 2) }}</td>
                                        <td class="r">{{ number_format($base + $itemTax, 2) }}</td>
                                        <td style="text-align:center;font-size:0.75rem;">
                                            {{ $item->return_to_stock ? '✓' : '' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <table class="totals-table">
                            <tr><td>Subtotal</td><td class="r">R {{ number_format($creditNote->subtotal(), 2) }}</td></tr>
                            <tr><td>VAT</td><td class="r">R {{ number_format($creditNote->taxTotal(), 2) }}</td></tr>
                            <tr class="total"><td>Total Credit</td><td class="r">R {{ number_format($creditNote->total(), 2) }}</td></tr>
                        </table>

                        @if ($creditNote->notes)
                            <p class="section-label" style="margin-top:1.25rem;">Notes</p>
                            <div style="font-size:0.78rem;color:#333;white-space:pre-line;border:1px solid #e5e7eb;padding:0.6rem 0.75rem;background:#f9fafb;">{{ $creditNote->notes }}</div>
                        @endif

                    </div>
                </div>

            </main>
        </div>
    </div>
@endsection
