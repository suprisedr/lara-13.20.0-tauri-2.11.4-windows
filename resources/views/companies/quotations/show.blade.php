@extends('layouts.public')

@section('title', $company->registered_name . ' — Quotation ' . $quotation->quotation_number)
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        /* ── Quotation-specific overrides (corporate palette) ── */
        .qt-status-form {
            display: flex;
            align-items: center;
            gap: 4pt;
        }

        .qt-status-select {
            border: 1px solid #9ec1f5;
            padding: 2pt 5pt;
            font-size: 6.5pt;
            font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;
            color: #191919;
            background: #fff;
            outline: none;
            cursor: pointer;
            border-radius: 0;
        }

        .qt-convert-btn {
            display: inline-flex;
            align-items: center;
            gap: 4pt;
            background: #15803d;
            border: 1px solid #15803d;
            color: #fff;
            font-size: 6.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 3pt 8pt;
            cursor: pointer;
            font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;
            transition: background 0.15s;
            border-radius: 0;
            white-space: nowrap;
        }
        .qt-convert-btn:hover { background: #116932; }

        /* ── Letterhead ── */
        .qt-letterhead {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1.5pt solid #1a345b;
            padding: 0 0 6pt;
            margin: 0 0 8pt;
        }

        .qt-letterhead-left {
            display: flex;
            align-items: flex-start;
            gap: 6pt;
        }

        .qt-logo {
            width: 36pt;
            height: 36pt;
            object-fit: contain;
            border: 0.5pt solid #9ec1f5;
            flex-shrink: 0;
        }

        .qt-logo-placeholder {
            width: 36pt;
            height: 36pt;
            background: #1a345b;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 11pt;
            font-weight: 900;
            color: #fff;
        }

        .qt-company-name {
            font-size: 11pt;
            font-weight: bold;
            color: #1a345b;
            letter-spacing: 0.01em;
            margin: 0 0 1pt;
            font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;
        }

        .qt-company-detail {
            font-size: 7pt;
            color: #5a7186;
            margin: 0;
            line-height: 1.6;
        }

        .qt-meta-right {
            text-align: right;
            flex-shrink: 0;
        }

        .qt-doc-number {
            font-size: 9pt;
            font-weight: 800;
            color: #1a345b;
            letter-spacing: 0.02em;
            margin-bottom: 2pt;
        }

        .qt-date-row {
            font-size: 7pt;
            color: #5a7186;
            margin-bottom: 1pt;
            text-align: right;
        }

        .qt-date-row strong {
            color: #191919;
            font-weight: 700;
            margin-left: 3pt;
        }

        /* ── Status badges (quotation-specific colours) ── */
        .reg-status.sent     { color: #1d4ed8; border-color: #1d4ed8; }
        .reg-status.accepted { color: #15803d; border-color: #15803d; }
        .reg-status.declined { color: #b91c1c; border-color: #b91c1c; }
        .reg-status.expired  { color: #5a7186; border-color: #5a7186; }

        /* ── Parties grid ── */
        .qt-parties {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8pt;
            margin-bottom: 8pt;
        }

        .qt-party-label {
            font-weight: 700;
            font-size: 5.5pt;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #005bf0;
            margin: 0 0 3pt;
        }

        .qt-party-name {
            font-size: 7pt;
            font-weight: 700;
            color: #191919;
            margin: 0 0 1pt;
        }

        .qt-party-detail {
            font-size: 7pt;
            color: #5a7186;
            margin: 0 0 1pt;
            line-height: 1.5;
        }

        /* ── Totals table ── */
        .qt-totals-wrap {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 8pt;
        }

        table.qt-totals {
            min-width: 160pt;
            border-collapse: collapse;
            font-size: 7pt;
            font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;
        }

        table.qt-totals td {
            padding: 2pt 4pt;
            border-bottom: 0.4pt solid #d3e2f5;
        }

        table.qt-totals td.lbl {
            color: #5a7186;
        }

        table.qt-totals td.amt {
            text-align: right;
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 7pt;
            white-space: nowrap;
            color: #191919;
        }

        table.qt-totals tr.grand td {
            padding: 3pt 4pt;
            font-weight: 800;
            color: #1a345b;
            background: #eaf8fb;
            border-top: 1.5pt solid #1a345b;
            border-bottom: 2pt solid #1a345b;
        }

        table.qt-totals tr.grand td.amt {
            color: #1a345b;
            font-weight: 800;
        }

        /* ── Notes ── */
        .qt-notes {
            background: #f4fafc;
            border: 1px solid #9ec1f5;
            padding: 5pt 8pt;
            font-size: 7pt;
            color: #191919;
            line-height: 1.55;
            white-space: pre-line;
            margin-bottom: 8pt;
        }

        /* ── Conversion banner ── */
        .qt-convert-banner {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            padding: 4pt 8pt;
            font-size: 7pt;
            color: #15803d;
            margin-bottom: 10pt;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 6pt;
            flex-wrap: wrap;
        }

        .qt-convert-banner a {
            color: #15803d;
            font-weight: 700;
            text-decoration: none;
            border-bottom: 0.5pt solid #15803d;
        }
        .qt-convert-banner a:hover { border-bottom-color: transparent; }

        /* ── Document footer ── */
        .qt-doc-footer {
            border-top: 1pt solid #9ec1f5;
            padding-top: 4pt;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 6pt;
            flex-wrap: wrap;
            font-size: 6pt;
            color: #6f869b;
        }

        /* ── Success alert ── */
        .qt-alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #15803d;
            padding: 4pt 8pt;
            font-size: 7pt;
            font-weight: 600;
            margin-bottom: 10pt;
        }

        @media (max-width: 640px) {
            .qt-letterhead { flex-direction: column; gap: 4pt; }
            .qt-meta-right { text-align: left; }
            .qt-parties { grid-template-columns: 1fr; }
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
                    <div class="qt-alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- Management bar --}}
                <div class="reg-mgmt-bar">
                    <div style="display:flex;align-items:center;gap:4pt;flex-wrap:wrap;">
                        @if ($quotation->status === \App\Enums\QuotationStatus::Draft->value)
                            <a href="{{ route('companies.quotations.edit', [$company, $quotation]) }}" class="reg-btn">
                                <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                                Edit
                            </a>
                        @endif
                        <a href="{{ route('companies.quotations.pdf', [$company, $quotation]) }}" class="reg-btn">
                            <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
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
                                <button type="submit" class="qt-convert-btn">
                                    <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
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
                        <form method="POST" action="{{ route('companies.quotations.update-status', [$company, $quotation]) }}" class="qt-status-form">
                            @csrf
                            @method('PATCH')
                            <select name="status" class="qt-status-select">
                                @foreach ($quotationStatusOptions as $statusOption)
                                    <option value="{{ $statusOption->value }}" {{ $quotation->status === $statusOption->value ? 'selected' : '' }}>{{ $statusOption->label() }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="reg-btn primary">Update Status</button>
                        </form>
                    </div>
                </div>

                @if ($quotation->converted_invoice_id && $quotation->convertedInvoice)
                    <div class="qt-convert-banner">
                        <span>
                            <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="vertical-align:-1pt;margin-right:2pt;"><polyline points="20 6 9 17 4 12"/></svg>
                            This quotation has been converted to invoice <strong>{{ $quotation->convertedInvoice->invoice_number }}</strong>.
                        </span>
                        <a href="{{ route('companies.invoices.show', [$company, $quotation->convertedInvoice]) }}">View Invoice &rarr;</a>
                    </div>
                @endif

                {{-- Quotation document --}}
                <div class="reg-doc">
                    <div class="reg-doc-body">

                        {{-- Letterhead: company + quotation number --}}
                        <div class="qt-letterhead">
                            <div class="qt-letterhead-left">
                                @if ($company->logo_path)
                                    <img src="{{ asset('storage/' . $company->logo_path) }}"
                                         alt="{{ $company->registered_name }}"
                                         class="qt-logo">
                                @else
                                    <div class="qt-logo-placeholder">
                                        {{ strtoupper(substr($company->registered_name, 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <p class="qt-company-name">{{ $company->registered_name }}</p>
                                    @if ($company->address_line_1)
                                        <p class="qt-company-detail">{{ $company->address_line_1 }}</p>
                                    @endif
                                    @if ($company->address_line_2)
                                        <p class="qt-company-detail">{{ $company->address_line_2 }}</p>
                                    @endif
                                    @if ($company->city || $company->postal_code)
                                        <p class="qt-company-detail">
                                            {{ implode(', ', array_filter([$company->city, $company->province, $company->postal_code])) }}
                                        </p>
                                    @endif
                                    @if ($company->vat_number)
                                        <p class="qt-company-detail">VAT Reg. No: {{ $company->vat_number }}</p>
                                    @endif
                                    @if ($company->registration_number)
                                        <p class="qt-company-detail">Reg. No: {{ $company->registration_number }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="qt-meta-right">
                                <div class="qt-doc-number">{{ $quotation->quotation_number }}</div>
                                <div style="margin-bottom:3pt;">
                                    <span class="reg-status {{ $quotation->status }}">{{ $quotation->status }}</span>
                                </div>
                                <div class="qt-date-row">
                                    Quote Date:<strong>{{ $quotation->quotation_date->format('d M Y') }}</strong>
                                </div>
                                @if ($quotation->expiry_date)
                                    <div class="qt-date-row">
                                        Valid Until:<strong>{{ $quotation->expiry_date->format('d M Y') }}</strong>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Parties --}}
                        <div class="qt-parties">
                            <div>
                                <p class="qt-party-label">Quote For</p>
                                <p class="qt-party-name">
                                    {{ optional($quotation->customer)->name ?? $quotation->customer_name }}
                                </p>
                                @if ($quotation->customer_email)
                                    <p class="qt-party-detail">{{ $quotation->customer_email }}</p>
                                @endif
                                @if ($quotation->customer_address)
                                    <p class="qt-party-detail" style="white-space:pre-line;">{{ $quotation->customer_address }}</p>
                                @endif
                            </div>
                            <div>
                                <p class="qt-party-label">From</p>
                                <p class="qt-party-name">{{ $company->registered_name }}</p>
                                @if ($company->address_line_1)
                                    <p class="qt-party-detail">{{ $company->address_line_1 }}</p>
                                @endif
                                @if ($company->address_line_2)
                                    <p class="qt-party-detail">{{ $company->address_line_2 }}</p>
                                @endif
                                @if ($company->city)
                                    <p class="qt-party-detail">
                                        {{ implode(', ', array_filter([$company->city, $company->province, $company->postal_code])) }}
                                    </p>
                                @endif
                                @if ($company->vat_number)
                                    <p class="qt-party-detail">VAT No: {{ $company->vat_number }}</p>
                                @endif
                            </div>
                        </div>

                        {{-- Line items --}}
                        <table class="reg-table">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th class="amt" style="width:50pt;">Qty</th>
                                    <th class="amt" style="width:70pt;">Unit Price</th>
                                    <th class="amt" style="width:45pt;">VAT %</th>
                                    <th class="amt" style="width:55pt;">Tax</th>
                                    <th class="amt" style="width:70pt;">Line Total</th>
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
                                                <span style="font-size:6pt;color:#6f869b;">SKU: {{ $item->inventoryItem->sku }}</span>
                                            @endif
                                        </td>
                                        <td class="amt">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                                        <td class="amt">R&nbsp;{{ number_format((float) $item->unit_price, 2) }}</td>
                                        <td class="amt">{{ $item->tax_rate !== null ? number_format((float) $item->tax_rate, 2) . '%' : '—' }}</td>
                                        <td class="amt">R&nbsp;{{ number_format($itemTax, 2) }}</td>
                                        <td class="amt" style="font-weight:700;">R&nbsp;{{ number_format($base + $itemTax, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        {{-- Totals --}}
                        <div class="qt-totals-wrap">
                            <table class="qt-totals">
                                <tr>
                                    <td class="lbl">Subtotal</td>
                                    <td class="amt">R&nbsp;{{ number_format($quotation->subtotal(), 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="lbl">VAT</td>
                                    <td class="amt">R&nbsp;{{ number_format($quotation->taxTotal(), 2) }}</td>
                                </tr>
                                <tr class="grand">
                                    <td class="lbl">Estimated Total</td>
                                    <td class="amt">R&nbsp;{{ number_format($quotation->total(), 2) }}</td>
                                </tr>
                            </table>
                        </div>

                        {{-- Notes --}}
                        @if ($quotation->notes)
                            <div class="reg-section-header">Notes / Terms</div>
                            <div class="qt-notes">{{ $quotation->notes }}</div>
                        @endif

                        {{-- Document footer --}}
                        <div class="qt-doc-footer">
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
        t.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;padding:4pt 8pt;font-size:7pt;font-weight:600;color:#fff;z-index:9999;box-shadow:0 2px 8px rgba(0,0,0,0.15);transition:opacity 0.4s;background:' + color;
        t.textContent = msg;
        document.body.appendChild(t);
        setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }, 4000);
    }

    const statusForm = document.querySelector('.qt-status-form');
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
                    const pill = document.querySelector('.reg-status');
                    if (pill) {
                        pill.className = 'reg-status ' + newStatus;
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
