@extends('layouts.public')

@section('title', $company->registered_name . ' — New Quotation')
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

        /* ── Header: logo/company + quotation fields ────────────── */
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

        /* ── Quotation number / date fields (right side) ─────────── */
        .invoice-fields {
            text-align: right;
            flex-shrink: 0;
            min-width: 240px;
        }

        .inv-field-inline {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.6rem;
            margin-bottom: 0.55rem;
        }

        .inv-field-inline label {
            font-size: 0.62rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #9ca3af;
            white-space: nowrap;
        }

        .inv-field-inline input,
        .inv-field-inline select {
            border: 1px solid #ddd6fe;
            border-radius: 0;
            padding: 0.35rem 0.6rem;
            font-size: 0.82rem;
            color: #1b1b18;
            background: #faf9ff;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
            text-align: right;
        }

        .inv-field-inline input:focus,
        .inv-field-inline select:focus {
            border-color: #5e17eb;
            box-shadow: 0 0 0 3px rgba(94,23,235,0.08);
        }

        .inv-number-input {
            font-size: 1.1rem !important;
            font-weight: 800 !important;
            color: #5e17eb !important;
            width: 160px;
            letter-spacing: -0.01em;
        }

        /* ── Parties ────────────────────────────────────────────── */
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
            margin: 0 0 0.75rem;
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

        /* ── Form fields ────────────────────────────────────────── */
        .inv-field label {
            display: block;
            font-size: 0.62rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #9ca3af;
            margin-bottom: 0.25rem;
        }

        .inv-field + .inv-field { margin-top: 0.7rem; }

        .inv-field input,
        .inv-field select,
        .inv-field textarea {
            width: 100%;
            border: 1px solid #ddd6fe;
            border-radius: 0;
            padding: 0.42rem 0.7rem;
            font-size: 0.855rem;
            color: #1b1b18;
            background: #faf9ff;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
            box-sizing: border-box;
        }

        .inv-field input:focus,
        .inv-field select:focus,
        .inv-field textarea:focus {
            border-color: #5e17eb;
            box-shadow: 0 0 0 3px rgba(94,23,235,0.08);
            background: #fff;
        }

        .field-error {
            font-size: 0.7rem;
            color: #dc2626;
            margin: 0.2rem 0 0;
        }

        /* ── Line items table ───────────────────────────────────── */
        .inv-table-wrap {
            margin-bottom: 1rem;
            border: 1px solid #ede9fe;
            border-radius: 0;
                    }

        .inv-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
        }

        .inv-table thead th {
            padding: 0.6rem 0.75rem;
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

        .inv-table tbody tr { border-bottom: 1px solid #f5f3ff; }
        .inv-table tbody tr:last-child { border-bottom: none; }

        .inv-table td {
            padding: 0.5rem 0.75rem;
            color: #1b1b18;
            vertical-align: middle;
        }

        .inv-table td.r {
            text-align: right;
            font-family: 'Courier New', monospace;
            font-size: 0.78rem;
            color: #374151;
            white-space: nowrap;
        }

        .inv-table td input,
        .inv-table td select {
            width: 100%;
            border: 1px solid #ddd6fe;
            border-radius: 0;
            padding: 0.32rem 0.5rem;
            font-size: 0.8rem;
            color: #1b1b18;
            background: #faf9ff;
            outline: none;
            transition: border-color 0.15s;
            box-sizing: border-box;
        }

        .inv-table td input:focus,
        .inv-table td select:focus {
            border-color: #5e17eb;
            background: #fff;
        }

        .inv-table td input[type="number"] { text-align: right; }

        /* ── Add / remove line buttons ──────────────────────────── */
        .btn-add-line {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.78rem;
            font-weight: 700;
            color: #5e17eb;
            background: none;
            border: 1px dashed rgba(94,23,235,0.35);
            border-radius: 0;
            padding: 0.4rem 0.9rem;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s;
        }

        .btn-add-line:hover { background: #faf5ff; border-color: #5e17eb; }

        .btn-remove-line {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: none;
            border: 1px solid #e5e7eb;
            color: #aaa;
            cursor: pointer;
            font-size: 0.85rem;
            line-height: 1;
            transition: background 0.12s, color 0.12s;
        }

        .btn-remove-line:hover { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }

        /* ── Totals ─────────────────────────────────────────────── */
        .invoice-footer-row {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 2rem;
        }

        .inv-totals { min-width: 280px; }

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
        .invoice-notes-label {
            font-size: 0.62rem;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #7c3aed;
            margin-bottom: 0.5rem;
        }

        /* ── Document footer (actions) ──────────────────────────── */
        .invoice-doc-footer {
            border-top: 1px solid #f3f0ff;
            padding-top: 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .invoice-doc-footer-info {
            font-size: 0.72rem;
            color: #9ca3af;
        }

        .inv-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.5rem 1.4rem;
            border-radius: 0;
            font-size: 0.855rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
            text-decoration: none;
        }

        .inv-action-cancel {
            background: #fff;
            border: 1px solid #d1d5db;
            color: #6b7280;
        }

        .inv-action-cancel:hover { background: #f9fafb; color: #374151; }

        .inv-action-submit {
            background: #5e17eb;
            border: 1px solid transparent;
            color: #fff;
        }

        .inv-action-submit:hover { background: #4a10c4; }

        @media (max-width: 640px) {
            .invoice-doc-body { padding: 1.5rem 1.25rem; }
            .invoice-parties { grid-template-columns: 1fr; }
            .invoice-header { flex-direction: column; }
            .invoice-fields { text-align: left; min-width: 0; }
            .inv-field-inline { justify-content: flex-start; }
        }
    </style>
@endpush

@section('content')
    <div class="co-wrap">

        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">

                {{-- Management bar --}}
                <div class="inv-mgmt-bar">
</div>

                @if ($errors->any())
                    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:0.75rem 1rem;border-radius:0;font-size:0.8rem;margin-bottom:1.25rem;">
                        <strong>Please fix the following errors:</strong>
                        <ul style="margin:0.4rem 0 0 1.1rem;padding:0;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('companies.quotations.store', $company) }}" id="quotation-form">
                    @csrf

                    {{-- Quotation document --}}
                    <div class="invoice-doc">
                        <div class="invoice-doc-accent"></div>
                        <div class="invoice-doc-body">

                            {{-- Header: company + quotation number/dates --}}
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
                                            <p>{{ implode(', ', array_filter([$company->city, $company->province, $company->postal_code])) }}</p>
                                        @endif
                                        @if ($company->vat_number)
                                            <p>VAT Reg. No: {{ $company->vat_number }}</p>
                                        @endif
                                        @if ($company->registration_number)
                                            <p>Reg. No: {{ $company->registration_number }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div class="invoice-fields">
                                    <div class="inv-field-inline" style="margin-bottom:0.85rem;">
                                        <input type="text" name="quotation_number" id="quotation_number"
                                               value="{{ old('quotation_number', $nextNumber) }}"
                                               class="inv-number-input" required placeholder="QUO-0001">
                                    </div>
                                    <div class="inv-field-inline">
                                        <label for="status">Status</label>
                                        <select name="status" id="status" style="width:110px;">
                                            <option value="draft"    {{ old('status','draft') === 'draft'    ? 'selected' : '' }}>Draft</option>
                                            <option value="sent"     {{ old('status') === 'sent'     ? 'selected' : '' }}>Sent</option>
                                            <option value="accepted" {{ old('status') === 'accepted' ? 'selected' : '' }}>Accepted</option>
                                            <option value="declined" {{ old('status') === 'declined' ? 'selected' : '' }}>Declined</option>
                                        </select>
                                    </div>
                                    <div class="inv-field-inline">
                                        <label for="quotation_date">Quote Date</label>
                                        <input type="date" name="quotation_date" id="quotation_date"
                                               value="{{ old('quotation_date', now()->format('Y-m-d')) }}"
                                               style="width:150px;" required>
                                    </div>
                                    <div class="inv-field-inline">
                                        <label for="expiry_date">Expiry Date</label>
                                        <input type="date" name="expiry_date" id="expiry_date"
                                               value="{{ old('expiry_date', now()->addDays(30)->format('Y-m-d')) }}"
                                               style="width:150px;">
                                    </div>
                                    @error('quotation_number')<p class="field-error" style="text-align:right;">{{ $message }}</p>@enderror
                                    @error('quotation_date')<p class="field-error" style="text-align:right;">{{ $message }}</p>@enderror
                                    @error('expiry_date')<p class="field-error" style="text-align:right;">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            {{-- Parties: Quote For (editable) | From (read-only) --}}
                            <div class="invoice-parties">
                                <div>
                                    <p class="invoice-party-label">Quote For</p>

                                    <div class="inv-field" style="position:relative;">
                                        <label for="customer-search">Search Customer <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                                        <input type="hidden" id="customer_id" name="customer_id" value="{{ old('customer_id', request('customer_id')) }}">
                                        <input type="text" id="customer-search" autocomplete="off" placeholder="Type name, email…"
                                               value="{{ old('customer_id') ? ($customers->firstWhere('id', old('customer_id'))?->name ?? '') : (request('customer_id') ? ($customers->firstWhere('id', request('customer_id'))?->name ?? '') : '') }}">
                                        <div id="customer-dropdown" style="display:none;position:absolute;left:0;right:0;top:100%;background:#fff;border:1px solid #ddd6fe;border-top:none;z-index:50;max-height:220px;overflow-y:auto;box-shadow:0 4px 12px rgba(94,23,235,0.10);"></div>
                                        @error('customer_id')<p class="field-error">{{ $message }}</p>@enderror
                                    </div>

                                    <div class="inv-field">
                                        <label for="customer_name">Name</label>
                                        <input type="text" id="customer_name" name="customer_name"
                                               value="{{ old('customer_name') }}" required>
                                        @error('customer_name')<p class="field-error">{{ $message }}</p>@enderror
                                    </div>

                                    <div class="inv-field">
                                        <label for="customer_email">Email</label>
                                        <input type="email" id="customer_email" name="customer_email"
                                               value="{{ old('customer_email') }}" required>
                                        @error('customer_email')<p class="field-error">{{ $message }}</p>@enderror
                                    </div>

                                    <div class="inv-field">
                                        <label for="customer_address">Address <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                                        <textarea id="customer_address" name="customer_address" rows="3" style="resize:vertical;">{{ old('customer_address') }}</textarea>
                                        @error('customer_address')<p class="field-error">{{ $message }}</p>@enderror
                                    </div>
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
                                    @if ($company->registration_number)
                                        <p class="invoice-party-detail">Reg. No: {{ $company->registration_number }}</p>
                                    @endif
                                </div>
                            </div>

                            {{-- Line items --}}
                            @if ($inventoryItems->isEmpty())
                                <div style="background:#fef9c3;border:1px solid #fde68a;border-radius:0;padding:0.75rem 1rem;font-size:0.82rem;color:#854d0e;display:flex;align-items:center;gap:0.6rem;margin-bottom:2rem;">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                    No inventory items found. <a href="{{ route('companies.inventory.index', $company) }}" style="color:#854d0e;font-weight:700;">Add items to your inventory</a> first.
                                </div>
                            @else
                                <div class="inv-table-wrap">
                                    <table class="inv-table">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th style="width:180px;">Description</th>
                                                <th class="r" style="width:90px;">Qty</th>
                                                <th class="r" style="width:110px;">Unit Price</th>
                                                <th class="r" style="width:75px;">VAT %</th>
                                                <th class="r" style="width:110px;">Line Total</th>
                                                <th style="width:36px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="line-items-body">
                                            {{-- rows injected by JS --}}
                                        </tbody>
                                    </table>
                                </div>

                                <div style="margin-bottom:1.5rem;">
                                    <button type="button" class="btn-add-line" onclick="addLineItem()">
                                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24">
                                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                                        </svg>
                                        Add Line Item
                                    </button>
                                </div>

                                {{-- Totals --}}
                                <div class="invoice-footer-row">
                                    <div class="inv-totals">
                                        <div class="inv-total-line">
                                            <span class="lbl">Subtotal</span>
                                            <span class="amt" id="summary-subtotal">R&nbsp;0.00</span>
                                        </div>
                                        <div class="inv-total-line">
                                            <span class="lbl">VAT</span>
                                            <span class="amt" id="summary-tax">R&nbsp;0.00</span>
                                        </div>
                                        <div class="inv-total-line grand">
                                            <span class="lbl">Estimated Total</span>
                                            <span class="amt" id="summary-total">R&nbsp;0.00</span>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Notes --}}
                            <div style="margin-bottom:1.75rem;">
                                <p class="invoice-notes-label">Notes / Terms <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#9ca3af;">(optional)</span></p>
                                <textarea name="notes" id="notes" rows="3"
                                    style="width:100%;border:1px solid #ddd6fe;border-radius:0;padding:0.6rem 0.75rem;font-size:0.82rem;color:#4b5563;line-height:1.65;background:#faf9ff;outline:none;resize:vertical;box-sizing:border-box;transition:border-color 0.15s,box-shadow 0.15s;"
                                    onfocus="this.style.borderColor='#5e17eb';this.style.boxShadow='0 0 0 3px rgba(94,23,235,0.08)';this.style.background='#fff'"
                                    onblur="this.style.borderColor='#ddd6fe';this.style.boxShadow='none';this.style.background='#faf9ff'">{{ old('notes') }}</textarea>
                            </div>

                            {{-- Document footer / actions --}}
                            <div class="invoice-doc-footer">
                                <span class="invoice-doc-footer-info">
                                    @if ($company->vat_number)VAT Vendor &mdash; {{ $company->vat_number }}@endif
                                </span>
                                <div style="display:flex;gap:0.65rem;align-items:center;">
                                    <a href="{{ route('companies.quotations.index', $company) }}" class="inv-action-btn inv-action-cancel">
                                        Cancel
                                    </a>
                                    <button type="submit" class="inv-action-btn inv-action-submit">
                                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                                        Create Quotation
                                    </button>
                                </div>
                            </div>

                        </div>{{-- /.invoice-doc-body --}}
                    </div>{{-- /.invoice-doc --}}

                </form>

            </main>
        </div>
    </div>

    <script>
        const inventoryItems = @json($inventoryItemsForJs);
        let lineIndex = 0;
        const oldItems = @json(old('items', []));
        const searchCustomersUrl = '{{ route('companies.customers.search', $company) }}';
        const searchInventoryUrl = '{{ route('companies.inventory.search', $company) }}';

        // ── Helpers ──────────────────────────────────────────────
        function fmt(n) {
            return 'R ' + Number(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        function escHtml(s) {
            return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function debounce(fn, ms) {
            let t;
            return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
        }

        // ── Customer autocomplete ─────────────────────────────────
        (function () {
            const searchEl = document.getElementById('customer-search');
            const hiddenEl = document.getElementById('customer_id');
            const dropdown = document.getElementById('customer-dropdown');

            function showDropdown(hits) {
                if (!hits.length) { dropdown.style.display = 'none'; return; }
                dropdown.innerHTML = hits.map(h => `
                    <div class="cust-hit" data-id="${h.id}" data-name="${escHtml(h.name)}"
                         data-email="${escHtml(h.email ?? '')}" data-address="${escHtml(h.address ?? '')}"
                         style="padding:0.55rem 0.85rem;cursor:pointer;font-size:0.82rem;border-bottom:1px solid #f3f0ff;">
                        <div style="font-weight:700;color:#1b1b18;">${escHtml(h.name)}</div>
                        ${h.email ? `<div style="font-size:0.72rem;color:#6b7280;">${escHtml(h.email)}</div>` : ''}
                    </div>`).join('') +
                    `<div style="padding:0.4rem 0.85rem;font-size:0.7rem;color:#9ca3af;background:#faf9ff;"><em>or continue typing to add manually</em></div>`;
                dropdown.style.display = 'block';
                dropdown.querySelectorAll('.cust-hit').forEach(el => {
                    el.addEventListener('mouseenter', () => el.style.background = '#faf5ff');
                    el.addEventListener('mouseleave', () => el.style.background = '');
                    el.addEventListener('mousedown', e => {
                        e.preventDefault();
                        hiddenEl.value = el.dataset.id;
                        searchEl.value = el.dataset.name;
                        document.getElementById('customer_name').value    = el.dataset.name;
                        document.getElementById('customer_email').value   = el.dataset.email;
                        document.getElementById('customer_address').value = el.dataset.address;
                        dropdown.style.display = 'none';
                    });
                });
            }

            const doSearch = debounce(async (q) => {
                if (q.length < 2) { dropdown.style.display = 'none'; return; }
                try {
                    const res  = await fetch(`${searchCustomersUrl}?q=${encodeURIComponent(q)}`);
                    const data = await res.json();
                    showDropdown(data.hits || []);
                } catch { dropdown.style.display = 'none'; }
            }, 220);

            searchEl.addEventListener('input', () => { hiddenEl.value = ''; doSearch(searchEl.value.trim()); });
            searchEl.addEventListener('blur',  () => setTimeout(() => { dropdown.style.display = 'none'; }, 150));
            searchEl.addEventListener('focus', () => { if (searchEl.value.trim().length >= 2) doSearch(searchEl.value.trim()); });
        })();

        // ── Item search per line row ──────────────────────────────
        function attachItemSearch(idx, initialItem) {
            const searchEl = document.getElementById(`item-search-${idx}`);
            const hiddenEl = document.getElementById(`item-id-${idx}`);
            const dropdown = document.getElementById(`item-dd-${idx}`);
            if (!searchEl) return;

            function applyItem(item) {
                hiddenEl.value = item.id;
                searchEl.value = item.name;
                document.getElementById('desc-' + idx).textContent  = item.description || '—';
                document.getElementById('price-' + idx).textContent = fmt(item.unit_price);
                document.getElementById('tax-' + idx).textContent   = item.tax_rate != null ? item.tax_rate + '%' : '—';
                const qtyInput  = document.getElementById('qty-' + idx);
                const stockHint = document.getElementById('stock-hint-' + idx);
                if (!item.is_service) {
                    qtyInput.max = item.quantity_on_hand;
                    stockHint.innerHTML = `<div style="font-size:0.65rem;color:#6b7280;margin-top:1px;">${item.quantity_on_hand} available</div>`;
                    if (parseFloat(qtyInput.value) > item.quantity_on_hand) qtyInput.value = item.quantity_on_hand;
                } else {
                    qtyInput.removeAttribute('max');
                    stockHint.innerHTML = '';
                }
                recalc();
                dropdown.style.display = 'none';
            }

            if (initialItem) applyItem(initialItem);

            function showDropdown(hits) {
                if (!hits.length) { dropdown.style.display = 'none'; return; }
                dropdown.innerHTML = hits.map(h => `
                    <div class="item-hit"
                         data-id="${h.id}" data-name="${escHtml(h.name)}" data-desc="${escHtml(h.description ?? '')}"
                         data-price="${h.unit_price}" data-tax="${h.tax_rate ?? ''}"
                         data-service="${h.is_service ? '1' : '0'}" data-qty="${h.quantity_on_hand ?? 0}"
                         style="padding:0.5rem 0.75rem;cursor:pointer;font-size:0.8rem;border-bottom:1px solid #f3f0ff;">
                        <div style="font-weight:700;color:#1b1b18;">${escHtml(h.name)}${h.sku ? ` <span style="font-weight:400;color:#9ca3af;font-size:0.7rem;">${escHtml(h.sku)}</span>` : ''}</div>
                        <div style="font-size:0.72rem;color:#6b7280;display:flex;gap:1rem;">
                            <span>${fmt(h.unit_price)}</span>
                            ${!h.is_service ? `<span>${h.quantity_on_hand} in stock</span>` : '<span>Service</span>'}
                        </div>
                    </div>`).join('');
                dropdown.style.display = 'block';
                dropdown.querySelectorAll('.item-hit').forEach(el => {
                    el.addEventListener('mouseenter', () => el.style.background = '#faf5ff');
                    el.addEventListener('mouseleave', () => el.style.background = '');
                    el.addEventListener('mousedown', e => {
                        e.preventDefault();
                        applyItem({
                            id: el.dataset.id, name: el.dataset.name, description: el.dataset.desc,
                            unit_price: parseFloat(el.dataset.price),
                            tax_rate: el.dataset.tax === '' ? null : parseFloat(el.dataset.tax),
                            is_service: el.dataset.service === '1', quantity_on_hand: parseFloat(el.dataset.qty),
                        });
                    });
                });
            }

            const doSearch = debounce(async (q) => {
                if (q.length < 2) { dropdown.style.display = 'none'; return; }
                try {
                    const res  = await fetch(`${searchInventoryUrl}?q=${encodeURIComponent(q)}`);
                    const data = await res.json();
                    showDropdown(data.hits || []);
                } catch { dropdown.style.display = 'none'; }
            }, 220);

            searchEl.addEventListener('input', () => { hiddenEl.value = ''; doSearch(searchEl.value.trim()); });
            searchEl.addEventListener('blur',  () => setTimeout(() => { dropdown.style.display = 'none'; }, 150));
            searchEl.addEventListener('focus', () => { if (searchEl.value.trim().length >= 2) doSearch(searchEl.value.trim()); });
        }

        // ── Line items ────────────────────────────────────────────
        function addLineItem(invId = null, qty = 1) {
            const idx      = lineIndex++;
            const tbody    = document.getElementById('line-items-body');
            const selected = invId ? inventoryItems.find(i => i.id == invId) : null;

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="position:relative;min-width:160px;">
                    <input type="hidden" name="items[${idx}][inventory_item_id]" id="item-id-${idx}">
                    <input type="text" id="item-search-${idx}" autocomplete="off" placeholder="Search item…"
                           style="width:100%;border:1px solid #ddd6fe;border-radius:0;padding:0.32rem 0.5rem;font-size:0.8rem;color:#1b1b18;background:#faf9ff;outline:none;box-sizing:border-box;">
                    <div id="item-dd-${idx}" style="display:none;position:absolute;left:0;right:0;top:100%;background:#fff;border:1px solid #ddd6fe;border-top:none;z-index:50;max-height:200px;overflow-y:auto;box-shadow:0 4px 12px rgba(94,23,235,0.10);min-width:260px;"></div>
                </td>
                <td id="desc-${idx}" style="font-size:0.78rem;color:#6b7280;">—</td>
                <td>
                    <input type="number" name="items[${idx}][quantity]" id="qty-${idx}" value="${qty}"
                           min="0.01" step="0.01" oninput="recalc()" required>
                    <span id="stock-hint-${idx}"></span>
                </td>
                <td class="r" id="price-${idx}">—</td>
                <td class="r" id="tax-${idx}">—</td>
                <td class="r" id="lt-${idx}" style="font-weight:700;">${fmt(0)}</td>
                <td style="text-align:center;">
                    <button type="button" class="btn-remove-line" onclick="removeLine(this)" title="Remove">&#x2715;</button>
                </td>`;
            tbody.appendChild(tr);
            attachItemSearch(idx, selected);
        }

        function removeLine(btn) {
            if (document.querySelectorAll('#line-items-body tr').length <= 1) return;
            btn.closest('tr').remove();
            recalc();
        }

        function recalc() {
            let subtotal = 0, tax = 0;
            document.querySelectorAll('#line-items-body tr').forEach(tr => {
                const hiddenId = tr.querySelector('input[name*="[inventory_item_id]"]');
                const qty      = tr.querySelector('input[name*="[quantity]"]');
                if (!hiddenId || !qty || !hiddenId.value) return;
                const item = inventoryItems.find(i => i.id == hiddenId.value);
                if (!item) return;
                const q       = parseFloat(qty.value) || 0;
                const base    = q * item.unit_price;
                const lineTax = item.tax_rate != null ? base * (item.tax_rate / 100) : 0;
                subtotal += base;
                tax      += lineTax;
                const ltCell = tr.querySelector('[id^="lt-"]');
                if (ltCell) ltCell.textContent = fmt(base + lineTax);
            });
            document.getElementById('summary-subtotal').textContent = fmt(subtotal);
            document.getElementById('summary-tax').textContent      = fmt(tax);
            document.getElementById('summary-total').textContent    = fmt(subtotal + tax);
        }

        // Validate all line items have an item selected before submit
        document.getElementById('quotation-form').addEventListener('submit', function (e) {
            const missing = [...document.querySelectorAll('input[name*="[inventory_item_id]"]')].some(i => !i.value);
            if (missing) { e.preventDefault(); alert('Please select an item for every line.'); }
        });

        // Restore from old() on validation failure
        if (oldItems && oldItems.length > 0) {
            oldItems.forEach(item => addLineItem(item.inventory_item_id || null, item.quantity || 1));
        } else {
            addLineItem();
        }
    </script>
@endsection
