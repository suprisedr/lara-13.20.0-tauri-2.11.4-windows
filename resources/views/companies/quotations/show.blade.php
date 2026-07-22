@extends('layouts.public')

@section('title', $company->registered_name . ' — Quotation ' . $quotation->quotation_number)
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

        .inv-convert-btn {
            background: #15803d;
            color: #fff;
            border: none;
            border-radius: 0;
            padding: 0.38rem 0.85rem;
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .inv-convert-btn:hover { background: #116932; }

        /* ── Quotation document ─────────────────────────────────── */
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

        /* ── Header row: logo/company + quotation number ────────── */
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

        .inv-status-pill.draft    { background: #fef9c3; color: #854d0e; }
        .inv-status-pill.sent     { background: #dbeafe; color: #1d4ed8; }
        .inv-status-pill.accepted { background: #dcfce7; color: #15803d; }
        .inv-status-pill.declined { background: #fee2e2; color: #b91c1c; }
        .inv-status-pill.expired  { background: #f3f4f6; color: #6b7280; }

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

        /* ── Conversion banner ──────────────────────────────────── */
        .convert-banner {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 0;
            padding: 0.85rem 1.25rem;
            font-size: 0.82rem;
            color: #15803d;
            margin-bottom: 1.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .convert-banner a {
            color: #15803d;
            font-weight: 700;
            text-decoration: underline;
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

                {{-- Management bar --}}
                <div class="inv-mgmt-bar">
<div style="display:flex;align-items:center;gap:0.65rem;flex-wrap:wrap;">
                        @if ($quotation->status === \App\Enums\QuotationStatus::Draft->value)
                            <a href="{{ route('companies.quotations.edit', [$company, $quotation]) }}"
                               style="display:inline-flex;align-items:center;gap:0.4rem;background:#fff;border:1px solid #ddd6fe;color:#5e17eb;font-size:0.75rem;font-weight:700;padding:0.38rem 0.85rem;border-radius:0;text-decoration:none;transition:background 0.15s,border-color 0.15s;"
                               onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background='#fff'">
                                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                                Edit
                            </a>
                        @endif
                        <a href="{{ route('companies.quotations.pdf', [$company, $quotation]) }}"
                           style="display:inline-flex;align-items:center;gap:0.4rem;background:#fff;border:1px solid #ddd6fe;color:#5e17eb;font-size:0.75rem;font-weight:700;padding:0.38rem 0.85rem;border-radius:0;text-decoration:none;transition:background 0.15s,border-color 0.15s;"
                           onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background='#fff'">
                            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="7 10 12 15 17 10"/>
                                <line x1="12" y1="15" x2="12" y2="3"/>
                            </svg>
                            Download PDF
                        </a>

                        @if (! $quotation->converted_invoice_id)
                            <form method="POST" action="{{ route('companies.quotations.convert', [$company, $quotation]) }}" onsubmit="return false"
                                data-confirm-label="Quotations" data-confirm-title="Convert to Invoice" data-confirm-body="Convert this quotation to an invoice? This action cannot be undone." data-confirm-text="Convert">
                                @csrf
                                <button type="submit" class="inv-convert-btn">
                                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                        <polyline points="14 2 14 8 20 8" />
                                        <path d="M9 15l2 2 4-4" />
                                    </svg>
                                    Convert to Invoice
                                </button>
                            </form>
                        @endif

                        @php
                            $currentQuotationStatus = \App\Enums\QuotationStatus::from($quotation->status);
                            $quotationStatusOptions = [$currentQuotationStatus, ...$currentQuotationStatus->allowedTransitions()];
                        @endphp
                        <form method="POST" action="{{ route('companies.quotations.update-status', [$company, $quotation]) }}" class="inv-status-form">
                            @csrf
                            @method('PATCH')
                            <select name="status" class="inv-status-select">
                                @foreach ($quotationStatusOptions as $statusOption)
                                    <option value="{{ $statusOption->value }}" {{ $quotation->status === $statusOption->value ? 'selected' : '' }}>{{ $statusOption->label() }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="inv-status-btn">Update Status</button>
                        </form>
                    </div>
                </div>

                @if ($quotation->converted_invoice_id && $quotation->convertedInvoice)
                    <div class="convert-banner">
                        <span>
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="vertical-align:-2px;margin-right:0.3rem;"><polyline points="20 6 9 17 4 12"/></svg>
                            This quotation has been converted to invoice <strong>{{ $quotation->convertedInvoice->invoice_number }}</strong>.
                        </span>
                        <a href="{{ route('companies.invoices.show', [$company, $quotation->convertedInvoice]) }}">View Invoice &rarr;</a>
                    </div>
                @endif

                {{-- Quotation document --}}
                <div class="invoice-doc">
                    <div class="invoice-doc-accent"></div>
                    <div class="invoice-doc-body">

                        {{-- Header: company + quotation number --}}
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
                                <div class="inv-number">{{ $quotation->quotation_number }}</div>
                                <div>
                                    <span class="inv-status-pill {{ $quotation->status }}">{{ $quotation->status }}</span>
                                </div>
                                <div class="inv-date-row">
                                    Quote Date:
                                    <strong>{{ $quotation->quotation_date->format('d M Y') }}</strong>
                                </div>
                                @if ($quotation->expiry_date)
                                    <div class="inv-date-row">
                                        Valid Until:
                                        <strong>{{ $quotation->expiry_date->format('d M Y') }}</strong>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Parties --}}
                        <div class="invoice-parties">
                            <div>
                                <p class="invoice-party-label">Quote For</p>
                                <p class="invoice-party-name">
                                    {{ optional($quotation->customer)->name ?? $quotation->customer_name }}
                                </p>
                                @if ($quotation->customer_email)
                                    <p class="invoice-party-detail">{{ $quotation->customer_email }}</p>
                                @endif
                                @if ($quotation->customer_address)
                                    <p class="invoice-party-detail" style="white-space:pre-line;">{{ $quotation->customer_address }}</p>
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
                                    @foreach ($quotation->items as $item)
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
                                    <span class="amt">R&nbsp;{{ number_format($quotation->subtotal(), 2) }}</span>
                                </div>
                                <div class="inv-total-line">
                                    <span class="lbl">VAT</span>
                                    <span class="amt">R&nbsp;{{ number_format($quotation->taxTotal(), 2) }}</span>
                                </div>
                                <div class="inv-total-line grand">
                                    <span class="lbl">Estimated Total</span>
                                    <span class="amt">R&nbsp;{{ number_format($quotation->total(), 2) }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Notes --}}
                        @if ($quotation->notes)
                            <div class="invoice-notes-label">Notes / Terms</div>
                            <div class="invoice-notes">{{ $quotation->notes }}</div>
                        @endif

                        {{-- Document footer --}}
                        <div class="invoice-doc-footer">
                            <span>This quotation is valid until the expiry date shown above.</span>
                            @if ($company->vat_number)
                                <span>VAT Vendor &mdash; {{ $company->vat_number }}</span>
                            @endif
                            <span>{{ $quotation->quotation_number }} &middot; {{ now()->format('d M Y') }}</span>
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
        draft: 'Draft', sent: 'Sent', accepted: 'Accepted',
        declined: 'Declined', expired: 'Expired',
    };

    function showToast(msg, color) {
        const t = document.createElement('div');
        t.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;padding:0.75rem 1.25rem;border-radius:6px;font-size:0.8rem;font-weight:600;color:#fff;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.15);transition:opacity 0.4s;background:' + color;
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }, 4000);
    }

    const statusForm = document.querySelector('.inv-status-form');
    if (statusForm) {
        statusForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = statusForm.querySelector('[type=submit]');
            const newStatus = statusForm.querySelector('[name=status]').value;
            if (btn) { btn.disabled = true; btn.textContent = 'Updating…'; }
            try {
                const res = await fetch(statusForm.action, {
                    method: 'POST', body: new FormData(statusForm),
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
})();
</script>
@endpush
@endsection
