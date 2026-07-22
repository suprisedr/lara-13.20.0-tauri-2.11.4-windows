@extends('layouts.public')

@section('title', $company->registered_name . ' — Invoice ' . $invoice->invoice_number)
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        /* ── Management bar ─────────────────────────────────────── */
        .inv-mgmt-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }

        .inv-mgmt-bar a {
            font-size: 0.78rem;
            color: #6b7280;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            transition: color 0.15s;
        }

        .inv-mgmt-bar a:hover { color: #5e17eb; }

        .inv-status-form {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .inv-status-select {
            border: 1px solid #ddd6fe;
            border-radius: 0;
            padding: 0.35rem 0.6rem;
            font-size: 0.78rem;
            color: #1b1b18;
            background: #fff;
            outline: none;
            cursor: pointer;
        }

        .inv-status-btn {
            background: #5e17eb;
            color: #fff;
            border: none;
            border-radius: 0;
            padding: 0.38rem 0.8rem;
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s;
            white-space: nowrap;
        }

        .inv-status-btn:hover { background: #4a10c4; }

        /* ── Invoice document ───────────────────────────────────── */
        .invoice-doc {
            background: #fff;
            border: 1px solid #ede9fe;
            border-radius: 0;
            overflow: hidden;
        }

        .invoice-doc-accent {
            height: 5px;
            background: linear-gradient(90deg, #5e17eb 0%, #7c3aed 100%);
        }

        .invoice-doc-body {
            padding: 2.5rem 2.75rem;
        }

        /* ── Header row: logo/company + invoice number ──────────── */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 2rem;
            margin-bottom: 2.25rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #ede9fe;
            flex-wrap: wrap;
        }

        .invoice-from {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .invoice-logo {
            width: 72px;
            height: 72px;
            object-fit: contain;
            border-radius: 0;
            border: 1px solid #f3f0ff;
            flex-shrink: 0;
        }

        .invoice-logo-placeholder {
            width: 72px;
            height: 72px;
            border-radius: 0;
            background: linear-gradient(135deg, #5e17eb, #7c3aed);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1.5rem;
            font-weight: 900;
            color: #fff;
            letter-spacing: -0.02em;
        }

        .invoice-company-block h1 {
            font-size: 1.15rem;
            font-weight: 900;
            color: #1b1b18;
            margin: 0 0 0.3rem;
            letter-spacing: -0.01em;
        }

        .invoice-company-block p {
            font-size: 0.78rem;
            color: #6b7280;
            margin: 0 0 0.18rem;
            line-height: 1.5;
        }

        .invoice-meta {
            text-align: right;
            flex-shrink: 0;
        }

        .invoice-meta .inv-number {
            font-size: 1.4rem;
            font-weight: 900;
            color: #5e17eb;
            letter-spacing: -0.02em;
            margin-bottom: 0.4rem;
        }

        .inv-status-pill {
            display: inline-block;
            padding: 0.2rem 0.65rem;
            border-radius:0;
            font-size: 0.65rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 0.85rem;
        }

        .inv-status-pill.draft  { background: #fef9c3; color: #854d0e; }
        .inv-status-pill.pending { background: #dbeafe; color: #1d4ed8; }
        .inv-status-pill.partially_paid { background: #fef3c7; color: #b45309; }
        .inv-status-pill.paid   { background: #dcfce7; color: #15803d; }
        .inv-status-pill.overdue { background: #fee2e2; color: #b91c1c; }
        .inv-status-pill.voided { background: #f3f4f6; color: #6b7280; }
        .inv-status-pill.write_off { background: #ede9fe; color: #5e17eb; }

        /* ── Payments ───────────────────────────────────────────── */
        .inv-payments-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
            margin-bottom: 1rem;
        }

        .inv-payments-table th {
            text-align: left;
            font-size: 0.62rem;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #7c3aed;
            padding: 0.5rem 0.6rem;
            border-bottom: 2px solid #ede9fe;
        }

        .inv-payments-table th.r, .inv-payments-table td.r { text-align: right; }

        .inv-payments-table td {
            padding: 0.45rem 0.6rem;
            border-bottom: 1px solid #f5f3ff;
            color: #1b1b18;
        }

        .inv-payment-form {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            align-items: flex-end;
            background: #faf9ff;
            border: 1px solid #ede9fe;
            border-radius: 0;
            padding: 1rem 1.25rem;
        }

        .inv-payment-form .inv-field {
            margin: 0;
            min-width: 140px;
        }

        .inv-payment-form .inv-field label {
            display: block;
            font-size: 0.62rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #9ca3af;
            margin-bottom: 0.25rem;
        }

        .inv-payment-form .inv-field input,
        .inv-payment-form .inv-field select {
            width: 100%;
            border: 1px solid #ddd6fe;
            border-radius: 0;
            padding: 0.42rem 0.7rem;
            font-size: 0.82rem;
            color: #1b1b18;
            background: #fff;
            outline: none;
            box-sizing: border-box;
        }

        .inv-date-row {
            display: flex;
            justify-content: flex-end;
            gap: 2rem;
            font-size: 0.78rem;
            color: #6b7280;
            margin-bottom: 0.3rem;
        }

        .inv-date-row strong {
            color: #1b1b18;
            font-weight: 600;
            margin-left: 0.4rem;
        }

        /* ── Bill To / From grid ────────────────────────────────── */
        .invoice-parties {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2.25rem;
        }

        .invoice-party-label {
            font-size: 0.62rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #7c3aed;
            margin: 0 0 0.55rem;
        }

        .invoice-party-name {
            font-size: 0.92rem;
            font-weight: 700;
            color: #1b1b18;
            margin: 0 0 0.2rem;
        }

        .invoice-party-detail {
            font-size: 0.78rem;
            color: #6b7280;
            margin: 0 0 0.15rem;
            line-height: 1.55;
        }

        /* ── Line items table ───────────────────────────────────── */
        .inv-table-wrap {
            margin-bottom: 1.5rem;
            border: 1px solid #ede9fe;
            border-radius: 0;
            overflow: hidden;
        }

        .inv-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
        }

        .inv-table thead th {
            padding: 0.6rem 1rem;
            font-size: 0.62rem;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #7c3aed;
            background: #faf5ff;
            border-bottom: 2px solid #ede9fe;
            text-align: left;
            white-space: nowrap;
        }

        .inv-table thead th.r { text-align: right; }

        .inv-table tbody tr {
            border-bottom: 1px solid #f5f3ff;
        }

        .inv-table tbody tr:last-child {
            border-bottom: none;
        }

        .inv-table td {
            padding: 0.75rem 1rem;
            color: #1b1b18;
            vertical-align: top;
        }

        .inv-table td.r {
            text-align: right;
            font-family: 'Courier New', monospace;
            font-size: 0.78rem;
            white-space: nowrap;
            color: #374151;
        }

        /* ── Totals ─────────────────────────────────────────────── */
        .invoice-footer-row {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 2rem;
        }

        .inv-totals {
            min-width: 280px;
        }

        .inv-total-line {
            display: flex;
            justify-content: space-between;
            gap: 3rem;
            padding: 0.45rem 0;
            font-size: 0.855rem;
            border-bottom: 1px solid #f3f0ff;
        }

        .inv-total-line:last-child { border-bottom: none; }

        .inv-total-line .lbl { color: #6b7280; }
        .inv-total-line .amt { font-family: 'Courier New', monospace; color: #1b1b18; }

        .inv-total-line.grand {
            border-top: 2px solid #5e17eb;
            border-bottom: 2px solid #5e17eb;
            margin-top: 0.35rem;
            padding: 0.7rem 0;
            font-size: 1.05rem;
            font-weight: 800;
        }

        .inv-total-line.grand .lbl { color: #1b1b18; }
        .inv-total-line.grand .amt { color: #5e17eb; }

        /* ── Notes ──────────────────────────────────────────────── */
        .invoice-notes {
            background: #faf9ff;
            border: 1px solid #ede9fe;
            border-radius: 0;
            padding: 1rem 1.25rem;
            font-size: 0.82rem;
            color: #4b5563;
            line-height: 1.65;
            white-space: pre-line;
            margin-bottom: 2rem;
        }

        .invoice-notes-label {
            font-size: 0.62rem;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #7c3aed;
            margin-bottom: 0.5rem;
        }

        /* ── Document footer ────────────────────────────────────── */
        .invoice-doc-footer {
            border-top: 1px solid #f3f0ff;
            padding-top: 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            font-size: 0.72rem;
            color: #9ca3af;
        }

        @media (max-width: 640px) {
            .invoice-doc-body { padding: 1.5rem 1.25rem; }
            .invoice-parties { grid-template-columns: 1fr; }
            .invoice-header { flex-direction: column; }
            .invoice-meta { text-align: left; }
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
                    <div
                        style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:0.75rem 1.25rem;border-radius:0;font-size:0.855rem;font-weight:600;margin-bottom:1.5rem;">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div
                        style="background:#fee2e2;border:1px solid #fecaca;color:#b91c1c;padding:0.75rem 1.25rem;border-radius:0;font-size:0.855rem;font-weight:600;margin-bottom:1.5rem;">
                        {{ session('error') }}
                    </div>
                @endif

                {{-- Management bar --}}
                <div class="inv-mgmt-bar">
<div style="display:flex;align-items:center;gap:0.65rem;flex-wrap:wrap;">
                        @if ($invoice->status === \App\Enums\InvoiceStatus::Draft->value)
                            <a href="{{ route('companies.invoices.edit', [$company, $invoice]) }}"
                               style="display:inline-flex;align-items:center;gap:0.4rem;background:#fff;border:1px solid #ddd6fe;color:#5e17eb;font-size:0.75rem;font-weight:700;padding:0.38rem 0.85rem;border-radius:0;text-decoration:none;transition:background 0.15s,border-color 0.15s;"
                               onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background='#fff'">
                                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                                Edit
                            </a>
                        @endif
                        <a href="{{ route('companies.invoices.pdf', [$company, $invoice]) }}"
                           style="display:inline-flex;align-items:center;gap:0.4rem;background:#fff;border:1px solid #ddd6fe;color:#5e17eb;font-size:0.75rem;font-weight:700;padding:0.38rem 0.85rem;border-radius:0;text-decoration:none;transition:background 0.15s,border-color 0.15s;"
                           onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background='#fff'">
                            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7 10 12 15 17 10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            Download PDF
                        </a>
                        {{-- Credit Note button --}}
                        @php $existingCn = $company->creditNotes()->where('invoice_id', $invoice->id)->first(); @endphp
                        @if ($existingCn)
                            <a href="{{ route('companies.credit-notes.show', [$company, $existingCn]) }}"
                                style="display:inline-flex;align-items:center;gap:0.35rem;border:1px solid #fecaca;background:#fff;color:#b91c1c;font-size:0.75rem;font-weight:700;padding:0.38rem 0.85rem;text-decoration:none;transition:all 0.15s;"
                                onmouseover="this.style.background='#dc2626';this.style.color='#fff'" onmouseout="this.style.background='#fff';this.style.color='#b91c1c'">
                                CN: {{ $existingCn->credit_note_number }}
                            </a>
                        @else
                            <a href="{{ route('companies.credit-notes.create', $company) }}?invoice_id={{ $invoice->id }}"
                                style="display:inline-flex;align-items:center;gap:0.35rem;border:1px solid #fecaca;background:#fff;color:#b91c1c;font-size:0.75rem;font-weight:700;padding:0.38rem 0.85rem;text-decoration:none;transition:all 0.15s;"
                                onmouseover="this.style.background='#dc2626';this.style.color='#fff'" onmouseout="this.style.background='#fff';this.style.color='#b91c1c'">
                                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="11" y2="17"/></svg>
                                Credit Note
                            </a>
                        @endif

                        @php $existingDn = $company->deliveryNotes()->where('invoice_id', $invoice->id)->first(); @endphp
                        @if ($existingDn)
                            <a href="{{ route('companies.delivery-notes.show', [$company, $existingDn]) }}"
                                style="display:inline-flex;align-items:center;gap:0.35rem;border:1px solid #ccc;background:#fff;color:#1b1b18;font-size:0.75rem;font-weight:700;padding:0.38rem 0.85rem;text-decoration:none;transition:all 0.15s;"
                                onmouseover="this.style.background='#000';this.style.color='#fff'" onmouseout="this.style.background='#fff';this.style.color='#1b1b18'">
                                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 4v4h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                                {{ $existingDn->delivery_note_number }}
                            </a>
                        @else
                            <a href="{{ route('companies.delivery-notes.create', $company) }}?invoice_id={{ $invoice->id }}"
                                style="display:inline-flex;align-items:center;gap:0.35rem;border:1px solid #ccc;background:#fff;color:#1b1b18;font-size:0.75rem;font-weight:700;padding:0.38rem 0.85rem;text-decoration:none;transition:all 0.15s;"
                                onmouseover="this.style.background='#000';this.style.color='#fff'" onmouseout="this.style.background='#fff';this.style.color='#1b1b18'">
                                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 4v4h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                                Delivery Note
                            </a>
                        @endif
                    @php
                        $currentInvoiceStatus = \App\Enums\InvoiceStatus::from($invoice->status);
                        $invoiceStatusOptions = [$currentInvoiceStatus, ...$currentInvoiceStatus->allowedTransitions()];
                    @endphp
                    <form method="POST" action="{{ route('companies.invoices.update-status', [$company, $invoice]) }}" class="inv-status-form">
                        @csrf
                        @method('PATCH')
                        <select name="status" class="inv-status-select">
                            @foreach ($invoiceStatusOptions as $statusOption)
                                <option value="{{ $statusOption->value }}" {{ $invoice->status === $statusOption->value ? 'selected' : '' }}>{{ $statusOption->label() }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="inv-status-btn">Update Status</button>
                    </form>
                    @if ($invoice->posting_transaction_id)
                        <a href="{{ route('companies.transactions', [$company, 'amount' => number_format($invoice->total(), 2, '.', ''), 'start_date' => $invoice->invoice_date->format('Y-m-d'), 'end_date' => $invoice->invoice_date->format('Y-m-d')]) }}"
                            style="display:inline-flex;align-items:center;gap:0.4rem;background:#f5f3ff;border:1px solid #ddd6fe;color:#5e17eb;font-size:0.75rem;font-weight:700;padding:0.38rem 0.85rem;border-radius:0;text-decoration:none;">
                            View Journal Entry
                        </a>
                    @endif
                    </div>
                </div>

                {{-- Invoice document --}}
                <div class="invoice-doc">
                    <div class="invoice-doc-accent"></div>
                    <div class="invoice-doc-body">

                        {{-- Header: company + invoice number --}}
                        <div class="invoice-header">
                            <div class="invoice-from">
                                @if ($company->logo_path)
                                    <img src="{{ asset('storage/' . $company->logo_path) }}"
                                         alt="{{ $company->registered_name }}"
                                         class="invoice-logo">
                                @else
                                    <div class="invoice-logo-placeholder">
                                        {{ strtoupper(substr($company->registered_name, 0, 1)) }}
                                    </div>
                                @endif
                                <div class="invoice-company-block">
                                    <h1>{{ $company->registered_name }}</h1>
                                    @if ($company->address_line_1)
                                        <p>{{ $company->address_line_1 }}</p>
                                    @endif
                                    @if ($company->address_line_2)
                                        <p>{{ $company->address_line_2 }}</p>
                                    @endif
                                    @if ($company->city || $company->postal_code)
                                        <p>
                                            {{ implode(', ', array_filter([$company->city, $company->province, $company->postal_code])) }}
                                        </p>
                                    @endif
                                    @if ($company->vat_number)
                                        <p>VAT Reg. No: {{ $company->vat_number }}</p>
                                    @endif
                                    @if ($company->registration_number)
                                        <p>Reg. No: {{ $company->registration_number }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="invoice-meta">
                                <div class="inv-number">{{ $invoice->invoice_number }}</div>
                                <div>
                                    <span class="inv-status-pill {{ $invoice->status }}">{{ $currentInvoiceStatus->label() }}</span>
                                </div>
                                <div class="inv-date-row">
                                    Invoice Date:
                                    <strong>{{ $invoice->invoice_date->format('d M Y') }}</strong>
                                </div>
                                @if ($invoice->due_date)
                                    <div class="inv-date-row">
                                        Due Date:
                                        <strong>{{ $invoice->due_date->format('d M Y') }}</strong>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Parties --}}
                        <div class="invoice-parties">
                            <div>
                                <p class="invoice-party-label">Bill To</p>
                                <p class="invoice-party-name">
                                    {{ optional($invoice->customer)->name ?? $invoice->customer_name }}
                                </p>
                                @if ($invoice->customer_email)
                                    <p class="invoice-party-detail">{{ $invoice->customer_email }}</p>
                                @endif
                                @if ($invoice->customer_address)
                                    <p class="invoice-party-detail" style="white-space:pre-line;">{{ $invoice->customer_address }}</p>
                                @endif
                            </div>
                            <div>
                                <p class="invoice-party-label">From</p>
                                <p class="invoice-party-name">{{ $company->registered_name }}</p>
                                @if ($company->address_line_1)
                                    <p class="invoice-party-detail">{{ $company->address_line_1 }}</p>
                                @endif
                                @if ($company->address_line_2)
                                    <p class="invoice-party-detail">{{ $company->address_line_2 }}</p>
                                @endif
                                @if ($company->city)
                                    <p class="invoice-party-detail">
                                        {{ implode(', ', array_filter([$company->city, $company->province, $company->postal_code])) }}
                                    </p>
                                @endif
                                @if ($company->vat_number)
                                    <p class="invoice-party-detail">VAT No: {{ $company->vat_number }}</p>
                                @endif
                            </div>
                        </div>

                        {{-- Line items --}}
                        <div class="inv-table-wrap">
                            <table class="inv-table">
                                <thead>
                                    <tr>
                                        <th>Description</th>
                                        <th class="r" style="width:80px;">Qty</th>
                                        <th class="r" style="width:120px;">Unit Price</th>
                                        <th class="r" style="width:70px;">VAT %</th>
                                        <th class="r" style="width:90px;">Tax</th>
                                        <th class="r" style="width:110px;">Line Total</th>
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
                                                <span style="font-weight:600;">{{ $item->description }}</span>
                                                @if ($item->inventoryItem && $item->inventoryItem->sku)
                                                    <br>
                                                    <span style="font-size:0.7rem;color:#9ca3af;">SKU: {{ $item->inventoryItem->sku }}</span>
                                                @endif
                                            </td>
                                            <td class="r">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                                            <td class="r">R&nbsp;{{ number_format((float) $item->unit_price, 2) }}</td>
                                            <td class="r">{{ $item->tax_rate !== null ? number_format((float) $item->tax_rate, 2) . '%' : '—' }}</td>
                                            <td class="r">R&nbsp;{{ number_format($itemTax, 2) }}</td>
                                            <td class="r" style="font-weight:700;">R&nbsp;{{ number_format($base + $itemTax, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Totals --}}
                        <div class="invoice-footer-row">
                            <div class="inv-totals">
                                <div class="inv-total-line">
                                    <span class="lbl">Subtotal</span>
                                    <span class="amt">R&nbsp;{{ number_format($invoice->subtotal(), 2) }}</span>
                                </div>
                                <div class="inv-total-line">
                                    <span class="lbl">VAT</span>
                                    <span class="amt">R&nbsp;{{ number_format($invoice->taxTotal(), 2) }}</span>
                                </div>
                                <div class="inv-total-line grand">
                                    <span class="lbl">Total Due</span>
                                    <span class="amt">R&nbsp;{{ number_format($invoice->total(), 2) }}</span>
                                </div>
                                @if ($invoice->payments->isNotEmpty())
                                    <div class="inv-total-line">
                                        <span class="lbl">Amount Paid</span>
                                        <span class="amt">R&nbsp;{{ number_format($invoice->amountPaid(), 2) }}</span>
                                    </div>
                                    <div class="inv-total-line">
                                        <span class="lbl">Balance Due</span>
                                        <span class="amt">R&nbsp;{{ number_format($invoice->balanceDue(), 2) }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Payments --}}
                        @if ($invoice->payments->isNotEmpty() || ($currentInvoiceStatus->isOpen() && $invoice->posting_transaction_id))
                            <div style="margin-bottom:1.75rem;">
                                <p class="invoice-notes-label">Payments</p>

                                @if ($invoice->payments->isNotEmpty())
                                    <table class="inv-payments-table">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Method</th>
                                                <th>Recorded By</th>
                                                <th>Notes</th>
                                                <th class="r">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($invoice->payments as $payment)
                                                <tr>
                                                    <td>{{ $payment->payment_date->format('d M Y') }}</td>
                                                    <td>{{ $payment->method ?: '—' }}</td>
                                                    <td>{{ $payment->user->name ?? '—' }}</td>
                                                    <td>{{ $payment->notes ?: '—' }}</td>
                                                    <td class="r">R&nbsp;{{ number_format($payment->amount, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif

                                @if ($currentInvoiceStatus->isOpen() && $invoice->posting_transaction_id)
                                    <form method="POST" action="{{ route('companies.invoices.payments.store', [$company, $invoice]) }}" class="inv-payment-form">
                                        @csrf
                                        <div class="inv-field">
                                            <label for="payment_date">Date</label>
                                            <input type="date" name="payment_date" id="payment_date" value="{{ now()->format('Y-m-d') }}" required>
                                        </div>
                                        <div class="inv-field">
                                            <label for="amount">Amount</label>
                                            <input type="number" name="amount" id="amount" min="0.01" step="0.01" max="{{ $invoice->balanceDue() }}" value="{{ $invoice->balanceDue() }}" required>
                                        </div>
                                        <div class="inv-field">
                                            <label for="deposit_account_id">Deposited To</label>
                                            <select name="deposit_account_id" id="deposit_account_id" required>
                                                <option value="">— Select account —</option>
                                                @foreach ($depositAccounts as $account)
                                                    <option value="{{ $account->id }}">{{ $account->account_code }} — {{ $account->account_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="inv-field">
                                            <label for="method">Method</label>
                                            <input type="text" name="method" id="method" placeholder="EFT, Cash, ...">
                                        </div>
                                        <div class="inv-field" style="flex:1;">
                                            <label for="notes">Notes</label>
                                            <input type="text" name="notes" id="notes" placeholder="Optional">
                                        </div>
                                        <button type="submit" class="inv-status-btn">Record Payment</button>
                                    </form>
                                    @error('amount')<p class="field-error">{{ $message }}</p>@enderror
                                    @error('deposit_account_id')<p class="field-error">{{ $message }}</p>@enderror
                                @endif
                            </div>
                        @endif

                        {{-- Payment / banking details --}}
                        @if ($company->bank_name || $company->bank_account_number)
                            <div style="margin-bottom:1.75rem;">
                                <p style="font-size:0.62rem;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;color:#7c3aed;margin:0 0 0.75rem;">
                                    Payment Details
                                </p>
                                <div style="background:#faf9ff;border:1px solid #ede9fe;border-radius:0;padding:1rem 1.25rem;display:flex;flex-wrap:wrap;gap:1.25rem 2.5rem;">
                                    @if ($company->bank_name)
                                        <div>
                                            <p style="font-size:0.62rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#9ca3af;margin:0 0 0.2rem;">Bank</p>
                                            <p style="font-size:0.855rem;font-weight:600;color:#1b1b18;margin:0;">{{ $company->bank_name }}</p>
                                        </div>
                                    @endif
                                    @if ($company->bank_account_number)
                                        <div>
                                            <p style="font-size:0.62rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#9ca3af;margin:0 0 0.2rem;">Account Number</p>
                                            <p style="font-size:0.855rem;font-weight:600;color:#1b1b18;margin:0;font-family:'Courier New',monospace;">{{ $company->bank_account_number }}</p>
                                        </div>
                                    @endif
                                    @if ($company->bank_account_type)
                                        <div>
                                            <p style="font-size:0.62rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#9ca3af;margin:0 0 0.2rem;">Account Type</p>
                                            <p style="font-size:0.855rem;font-weight:600;color:#1b1b18;margin:0;">{{ ucfirst($company->bank_account_type) }}</p>
                                        </div>
                                    @endif
                                    @if ($company->bank_branch_code)
                                        <div>
                                            <p style="font-size:0.62rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#9ca3af;margin:0 0 0.2rem;">Branch Code</p>
                                            <p style="font-size:0.855rem;font-weight:600;color:#1b1b18;margin:0;font-family:'Courier New',monospace;">{{ $company->bank_branch_code }}</p>
                                        </div>
                                    @endif
                                    <div>
                                        <p style="font-size:0.62rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#9ca3af;margin:0 0 0.2rem;">Reference</p>
                                        <p style="font-size:0.855rem;font-weight:600;color:#5e17eb;margin:0;font-family:'Courier New',monospace;">{{ $invoice->invoice_number }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Notes --}}
                        @if ($invoice->notes)
                            <div class="invoice-notes-label">Notes</div>
                            <div class="invoice-notes">{{ $invoice->notes }}</div>
                        @endif

                        {{-- Document footer --}}
                        <div class="invoice-doc-footer">
                            <span>Thank you for your business.</span>
                            @if ($company->vat_number)
                                <span>VAT Vendor &mdash; {{ $company->vat_number }}</span>
                            @endif
                            <span>{{ $invoice->invoice_number }} &middot; {{ now()->format('d M Y') }}</span>
                        </div>

                    </div>
                </div>

            </main>
        </div>
    </div>

@push('scripts')
<script>
(function () {
    const statusLabels = {
        draft: 'Draft', pending: 'Pending', paid: 'Paid',
        partially_paid: 'Partially Paid', overdue: 'Overdue',
        voided: 'Voided', write_off: 'Write Off',
    };

    function showToast(msg, color) {
        const t = document.createElement('div');
        t.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;padding:0.75rem 1.25rem;border-radius:6px;font-size:0.8rem;font-weight:600;color:#fff;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.15);transition:opacity 0.4s;background:' + color;
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }, 4000);
    }

    // Status update form — update pill in place
    const statusForm = document.querySelector('.inv-status-form');
    if (statusForm) {
        statusForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = statusForm.querySelector('[type=submit]');
            const newStatus = statusForm.querySelector('[name=status]').value;
            if (btn) { btn.disabled = true; btn.textContent = 'Updating…'; }
            try {
                const fd = new FormData(statusForm);
                const res = await fetch(statusForm.action, {
                    method: 'POST', body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    redirect: 'follow',
                });
                if (res.ok || res.redirected) {
                    const pill = document.querySelector('.inv-status-pill');
                    if (pill) {
                        pill.className = 'inv-status-pill ' + newStatus;
                        pill.textContent = statusLabels[newStatus] ?? newStatus;
                    }
                    showToast('Status updated', '#065f46');
                } else {
                    showToast('Could not update status', '#b91c1c');
                }
            } catch { showToast('Network error', '#b91c1c'); }
            finally { if (btn) { btn.disabled = false; btn.textContent = 'Update Status'; } }
        });
    }

    // Payment form — reload payments section after recording
    const paymentForm = document.querySelector('.inv-payment-form');
    if (paymentForm) {
        paymentForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = paymentForm.querySelector('[type=submit]');
            if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }
            try {
                const res = await fetch(paymentForm.action, {
                    method: 'POST', body: new FormData(paymentForm),
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    redirect: 'follow',
                });
                if (res.ok || res.redirected) {
                    showToast('Payment recorded', '#065f46');
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    showToast('Could not record payment', '#b91c1c');
                    if (btn) { btn.disabled = false; btn.textContent = 'Record Payment'; }
                }
            } catch {
                showToast('Network error', '#b91c1c');
                if (btn) { btn.disabled = false; btn.textContent = 'Record Payment'; }
            }
        });
    }
})();
</script>
@endpush
@endsection
