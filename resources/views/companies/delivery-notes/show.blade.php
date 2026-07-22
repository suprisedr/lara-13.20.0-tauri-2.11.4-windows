@extends('layouts.public')

@section('title', $company->registered_name . ' — ' . $deliveryNote->delivery_note_number)
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .inv-mgmt-bar {
            display:flex; align-items:center; justify-content:space-between;
            gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem;
        }
        .inv-mgmt-bar a { font-size:0.78rem; color:#6b7280; text-decoration:none; display:inline-flex; align-items:center; gap:0.3rem; transition:color 0.15s; }
        .inv-mgmt-bar a:hover { color:#5e17eb; }
        .mgmt-btn {
            display:inline-flex; align-items:center; gap:0.35rem; border:1px solid #ccc;
            background:#fff; color:#1b1b18; font-size:0.75rem; font-weight:700;
            padding:0.38rem 0.85rem; cursor:pointer; text-decoration:none; transition:all 0.15s; font-family:inherit;
        }
        .mgmt-btn:hover { background:#000; color:#fff; border-color:#000; }
        .mgmt-btn.primary { background:#000; color:#fff; border-color:#000; }
        .mgmt-btn.primary:hover { background:#333; }
        .mgmt-btn.danger { border-color:#dc2626; color:#dc2626; }
        .mgmt-btn.danger:hover { background:#dc2626; color:#fff; }

        .dn-badge { display:inline-block; padding:0.2rem 0.65rem; font-size:0.7rem; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; }
        .dn-badge-draft      { background:#f3f4f6; color:#6b7280; }
        .dn-badge-dispatched { background:#fef3c7; color:#92400e; }
        .dn-badge-delivered  { background:#dcfce7; color:#15803d; }
        .dn-badge-cancelled  { background:#fee2e2; color:#b91c1c; }

        /* Document area */
        .cust-doc-wrap { background:#f9fafb; padding:1.5rem 0; }
        .cust-doc { background:#fff; border:1px solid #e5e7eb; max-width:780px; margin:0 auto; padding:2.5rem 2.75rem; }
        .cust-doc-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; }
        .doc-title { font-size:1.4rem; font-weight:900; letter-spacing:-0.02em; margin-bottom:0.25rem; }
        .doc-meta { font-size:0.72rem; color:#555; line-height:1.7; }
        .doc-meta strong { color:#000; }
        .doc-divider { border:none; border-top:2px solid #000; margin:1.25rem 0; }
        .doc-table { width:100%; border-collapse:collapse; margin-bottom:1.25rem; }
        .doc-table th { font-size:0.62rem; font-weight:700; letter-spacing:0.07em; text-transform:uppercase; padding:0 0 0.4rem; border-bottom:1.5px solid #000; text-align:left; }
        .doc-table td { padding:0.45rem 0; border-bottom:1px solid #e5e7eb; font-size:0.8rem; vertical-align:top; }
        .doc-table tr:last-child td { border-bottom:none; }
        .doc-table td.num { text-align:right; font-variant-numeric:tabular-nums; }
        .doc-section-label { font-size:0.6rem; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; color:#5e17eb; margin:1.25rem 0 0.4rem; }
        .sig-row { display:flex; gap:3rem; margin-top:2.5rem; }
        .sig-block { flex:1; border-top:1.5px solid #000; padding-top:0.4rem; font-size:0.7rem; color:#555; }
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

                {{-- Management bar --}}
                <div class="inv-mgmt-bar">
                    <div style="display:flex;align-items:center;gap:0.75rem;">
                        <span class="dn-badge dn-badge-{{ $deliveryNote->status }}">{{ $deliveryNote->statusLabel() }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:0.6rem;flex-wrap:wrap;">
                        {{-- Status change --}}
                        @if ($deliveryNote->status !== 'delivered' && $deliveryNote->status !== 'cancelled')
                            @if ($deliveryNote->status === 'draft')
                                <form method="POST" action="{{ route('companies.delivery-notes.update-status', [$company, $deliveryNote]) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="dispatched">
                                    <button type="submit" class="mgmt-btn primary">Mark Dispatched</button>
                                </form>
                            @endif
                            @if ($deliveryNote->status === 'dispatched')
                                <form method="POST" action="{{ route('companies.delivery-notes.update-status', [$company, $deliveryNote]) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="delivered">
                                    <button type="submit" class="mgmt-btn primary">Mark Delivered</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('companies.delivery-notes.update-status', [$company, $deliveryNote]) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="cancelled">
                                <button type="submit" class="mgmt-btn danger" onclick="return confirm('Cancel this delivery note?')">Cancel</button>
                            </form>
                        @endif

                        <a href="{{ route('companies.delivery-notes.pdf', [$company, $deliveryNote]) }}" target="_blank" class="mgmt-btn">
                            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            Download PDF
                        </a>

                        @if ($deliveryNote->invoice)
                            <a href="{{ route('companies.invoices.show', [$company, $deliveryNote->invoice]) }}" class="mgmt-btn">
                                View Invoice {{ $deliveryNote->invoice->invoice_number }}
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Document preview --}}
                <div class="cust-doc-wrap">
                    <div class="cust-doc">

                        {{-- Header --}}
                        <div class="cust-doc-header">
                            <div>
                                @if ($company->logo_path && Storage::disk('public')->exists($company->logo_path))
                                    <img src="{{ Storage::url($company->logo_path) }}" style="max-height:60px;max-width:200px;object-fit:contain;" alt="">
                                @else
                                    <div style="font-size:1.1rem;font-weight:900;letter-spacing:-0.02em;">{{ $company->registered_name }}</div>
                                @endif
                                <div style="font-size:0.7rem;color:#555;margin-top:0.5rem;line-height:1.6;">
                                    @if ($company->address_line_1)<div>{{ $company->address_line_1 }}</div>@endif
                                    @if ($company->address_line_2)<div>{{ $company->address_line_2 }}</div>@endif
                                    @if ($company->city)<div>{{ $company->city }}@if ($company->postal_code), {{ $company->postal_code }}@endif</div>@endif
                                    @if ($company->registration_number)<div>Reg. No: {{ $company->registration_number }}</div>@endif
                                </div>
                            </div>
                            <div style="text-align:right;">
                                <div class="doc-title">DELIVERY NOTE</div>
                                <div class="doc-meta">
                                    <div><strong>DN No:</strong> {{ $deliveryNote->delivery_note_number }}</div>
                                    <div><strong>Date:</strong> {{ $deliveryNote->delivery_date->format('d M Y') }}</div>
                                    @if ($deliveryNote->expected_delivery_date)
                                        <div><strong>Expected:</strong> {{ $deliveryNote->expected_delivery_date->format('d M Y') }}</div>
                                    @endif
                                    @if ($deliveryNote->invoice)
                                        <div><strong>Invoice:</strong> {{ $deliveryNote->invoice->invoice_number }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <hr class="doc-divider">

                        {{-- Deliver to --}}
                        <div style="display:flex;gap:3rem;">
                            <div>
                                <p class="doc-section-label">Deliver To</p>
                                <div style="font-size:0.82rem;line-height:1.7;">
                                    <strong>{{ $deliveryNote->customer_name }}</strong><br>
                                    @if ($deliveryNote->customer_email)<div style="color:#555;">{{ $deliveryNote->customer_email }}</div>@endif
                                    @if ($deliveryNote->delivery_address)
                                        <div style="white-space:pre-line;color:#555;">{{ $deliveryNote->delivery_address }}</div>
                                    @endif
                                </div>
                            </div>
                            @if ($deliveryNote->dispatched_by || $deliveryNote->vehicle_registration || $deliveryNote->tracking_reference)
                                <div>
                                    <p class="doc-section-label">Dispatch Info</p>
                                    <div class="doc-meta">
                                        @if ($deliveryNote->dispatched_by)<div><strong>Dispatched By:</strong> {{ $deliveryNote->dispatched_by }}</div>@endif
                                        @if ($deliveryNote->vehicle_registration)<div><strong>Vehicle:</strong> {{ $deliveryNote->vehicle_registration }}</div>@endif
                                        @if ($deliveryNote->tracking_reference)<div><strong>Tracking:</strong> {{ $deliveryNote->tracking_reference }}</div>@endif
                                    </div>
                                </div>
                            @endif
                        </div>

                        <hr class="doc-divider">

                        {{-- Items --}}
                        <table class="doc-table">
                            <thead>
                                <tr>
                                    <th style="width:5%;">#</th>
                                    <th style="width:55%;">Description</th>
                                    <th style="width:20%;">Item Code / SKU</th>
                                    <th class="num" style="width:10%;">Qty</th>
                                    <th style="width:10%;">Unit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($deliveryNote->items as $i => $item)
                                    <tr>
                                        <td style="color:#9ca3af;">{{ $i + 1 }}</td>
                                        <td>{{ $item->description }}</td>
                                        <td style="color:#6b7280;font-size:0.72rem;">
                                            {{ $item->inventoryItem?->sku ?? '—' }}
                                        </td>
                                        <td class="num">{{ rtrim(rtrim(number_format((float)$item->quantity, 2), '0'), '.') }}</td>
                                        <td style="color:#6b7280;">{{ $item->unit ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        {{-- Notes --}}
                        @if ($deliveryNote->notes)
                            <p class="doc-section-label">Notes</p>
                            <div style="font-size:0.78rem;color:#333;white-space:pre-line;border:1px solid #e5e7eb;padding:0.6rem 0.75rem;background:#f9fafb;">{{ $deliveryNote->notes }}</div>
                        @endif

                        {{-- Signature blocks --}}
                        <div class="sig-row">
                            <div class="sig-block">Dispatched by (signature &amp; date)</div>
                            <div class="sig-block">Received by (signature &amp; date)</div>
                        </div>

                    </div>
                </div>

            </main>
        </div>
    </div>
@endsection
