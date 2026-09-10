@extends('layouts.public')

@section('title', $company->registered_name . ' — ' . $deliveryNote->delivery_note_number)
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        /* Status badge variants for delivery notes */
        .reg-status.draft      { color: #5a7186; border-color: #5a7186; }
        .reg-status.dispatched { color: #92400e; border-color: #92400e; }
        .reg-status.delivered  { color: #15803d; border-color: #15803d; }
        .reg-status.cancelled  { color: #dc2626; border-color: #dc2626; }

        /* Danger button (not in _styles) */
        .reg-btn.danger { border-color: #dc2626; color: #dc2626; }
        .reg-btn.danger:hover { background: #dc2626; color: #fff; }

        /* Success flash */
        .reg-flash-success {
            background: #f0fdf4;
            border: 0.5pt solid #15803d;
            color: #15803d;
            padding: 4pt 8pt;
            font-size: 7pt;
            font-weight: 600;
            margin-bottom: 8pt;
        }

        /* Signature row */
        .sig-row { display: flex; gap: 20pt; margin-top: 16pt; }
        .sig-block {
            flex: 1;
            border-top: 1pt solid #9ec1f5;
            padding-top: 3pt;
            font-size: 6.5pt;
            color: #6f869b;
        }

        /* Notes box */
        .reg-notes-box {
            font-size: 7pt;
            color: #191919;
            white-space: pre-line;
            border: 0.5pt solid #9ec1f5;
            padding: 4pt 6pt;
            background: #f4fafc;
        }
    </style>
@endpush

@section('content')
    <div class="co-wrap">
        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">

                @if (session('success'))
                    <div class="reg-flash-success">{{ session('success') }}</div>
                @endif

                {{-- Management bar --}}
                <div class="reg-mgmt-bar">
                    <div style="display:flex;align-items:center;gap:6pt;">
                        <span class="reg-status {{ $deliveryNote->status }}">{{ $deliveryNote->statusLabel() }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:5pt;flex-wrap:wrap;">
                        {{-- Status change --}}
                        @if ($deliveryNote->status !== 'delivered' && $deliveryNote->status !== 'cancelled')
                            @if ($deliveryNote->status === 'draft')
                                <form method="POST" action="{{ route('companies.delivery-notes.update-status', [$company, $deliveryNote]) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="dispatched">
                                    <button type="submit" class="reg-btn primary">Mark Dispatched</button>
                                </form>
                            @endif
                            @if ($deliveryNote->status === 'dispatched')
                                <form method="POST" action="{{ route('companies.delivery-notes.update-status', [$company, $deliveryNote]) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="status" value="delivered">
                                    <button type="submit" class="reg-btn primary">Mark Delivered</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('companies.delivery-notes.update-status', [$company, $deliveryNote]) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="cancelled">
                                <button type="submit" class="reg-btn danger" onclick="return confirm('Cancel this delivery note?')">Cancel</button>
                            </form>
                        @endif

                        <a href="{{ route('companies.delivery-notes.pdf', [$company, $deliveryNote]) }}" target="_blank" class="reg-btn">
                            <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            Download PDF
                        </a>

                        @if ($deliveryNote->invoice)
                            <a href="{{ route('companies.invoices.show', [$company, $deliveryNote->invoice]) }}" class="reg-btn">
                                View Invoice {{ $deliveryNote->invoice->invoice_number }}
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Document preview --}}
                <div class="reg-doc">
                    <div class="reg-doc-body">

                        {{-- Letterhead --}}
                        <div class="afs-letterhead">
                            <div>
                                @if ($company->logo_path && Storage::disk('public')->exists($company->logo_path))
                                    <img src="{{ Storage::url($company->logo_path) }}" style="max-height:36pt;max-width:120pt;object-fit:contain;" alt="">
                                @else
                                    <div class="afs-letterhead-name">{{ $company->registered_name }}</div>
                                @endif
                                <div class="afs-letterhead-meta">
                                    @if ($company->address_line_1)<div>{{ $company->address_line_1 }}</div>@endif
                                    @if ($company->address_line_2)<div>{{ $company->address_line_2 }}</div>@endif
                                    @if ($company->city)<div>{{ $company->city }}@if ($company->postal_code), {{ $company->postal_code }}@endif</div>@endif
                                    @if ($company->registration_number)<div>Reg. No: {{ $company->registration_number }}</div>@endif
                                </div>
                            </div>
                            <div style="text-align:right;">
                                <div class="reg-doc-title">DELIVERY NOTE</div>
                                <div class="afs-letterhead-meta afs-right">
                                    <div><strong style="color:#1a345b;">DN No:</strong> {{ $deliveryNote->delivery_note_number }}</div>
                                    <div><strong style="color:#1a345b;">Date:</strong> {{ $deliveryNote->delivery_date->format('d M Y') }}</div>
                                    @if ($deliveryNote->expected_delivery_date)
                                        <div><strong style="color:#1a345b;">Expected:</strong> {{ $deliveryNote->expected_delivery_date->format('d M Y') }}</div>
                                    @endif
                                    @if ($deliveryNote->invoice)
                                        <div><strong style="color:#1a345b;">Invoice:</strong> {{ $deliveryNote->invoice->invoice_number }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <hr class="reg-divider">

                        {{-- Deliver to / Dispatch info --}}
                        <div style="display:flex;gap:20pt;">
                            <div>
                                <p class="reg-section-header">Deliver To</p>
                                <div style="font-size:7pt;line-height:1.6;">
                                    <strong style="color:#1a345b;">{{ $deliveryNote->customer_name }}</strong><br>
                                    @if ($deliveryNote->customer_email)<div style="color:#5a7186;">{{ $deliveryNote->customer_email }}</div>@endif
                                    @if ($deliveryNote->delivery_address)
                                        <div style="white-space:pre-line;color:#5a7186;">{{ $deliveryNote->delivery_address }}</div>
                                    @endif
                                </div>
                            </div>
                            @if ($deliveryNote->dispatched_by || $deliveryNote->vehicle_registration || $deliveryNote->tracking_reference)
                                <div>
                                    <p class="reg-section-header">Dispatch Info</p>
                                    <div style="font-size:7pt;line-height:1.6;color:#5a7186;">
                                        @if ($deliveryNote->dispatched_by)<div><strong style="color:#1a345b;">Dispatched By:</strong> {{ $deliveryNote->dispatched_by }}</div>@endif
                                        @if ($deliveryNote->vehicle_registration)<div><strong style="color:#1a345b;">Vehicle:</strong> {{ $deliveryNote->vehicle_registration }}</div>@endif
                                        @if ($deliveryNote->tracking_reference)<div><strong style="color:#1a345b;">Tracking:</strong> {{ $deliveryNote->tracking_reference }}</div>@endif
                                    </div>
                                </div>
                            @endif
                        </div>

                        <hr class="reg-divider">

                        {{-- Items --}}
                        <table class="reg-table">
                            <thead>
                                <tr>
                                    <th style="width:5%;">#</th>
                                    <th style="width:55%;">Description</th>
                                    <th style="width:20%;">Item Code / SKU</th>
                                    <th class="amt" style="width:10%;">Qty</th>
                                    <th style="width:10%;">Unit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($deliveryNote->items as $i => $item)
                                    <tr>
                                        <td class="dim">{{ $i + 1 }}</td>
                                        <td>{{ $item->description }}</td>
                                        <td class="dim" style="font-size:6.5pt;">
                                            {{ $item->inventoryItem?->sku ?? '—' }}
                                        </td>
                                        <td class="amt">{{ rtrim(rtrim(number_format((float)$item->quantity, 2), '0'), '.') }}</td>
                                        <td class="dim">{{ $item->unit ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        {{-- Notes --}}
                        @if ($deliveryNote->notes)
                            <p class="reg-section-header">Notes</p>
                            <div class="reg-notes-box">{{ $deliveryNote->notes }}</div>
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
