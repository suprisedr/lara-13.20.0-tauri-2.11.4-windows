@extends('layouts.public')

@section('title', $company->registered_name . ' — New Delivery Note')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
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

        .dn-section {
            margin-bottom: 2.25rem;
        }

        .dn-section-title {
            font-size: 0.62rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #7c3aed;
            margin: 0 0 0.75rem;
        }

        .dn-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.7rem 1.25rem;
        }

        .dn-grid.three { grid-template-columns: 1fr 1fr 1fr; }

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

        .inv-table tbody tr { border-bottom: 1px solid #f5f3ff; }
        .inv-table tbody tr:last-child { border-bottom: none; }

        .inv-table td {
            padding: 0.5rem 0.75rem;
            color: #1b1b18;
            vertical-align: middle;
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

        .inv-search-dropdown {
            display: none;
            position: absolute;
            left: 0;
            right: 0;
            top: 100%;
            background: #fff;
            border: 1px solid #ddd6fe;
            border-top: none;
            z-index: 50;
            max-height: 220px;
            overflow-y: auto;
            box-shadow: 0 4px 12px rgba(94,23,235,0.10);
        }

        .inv-hit {
            padding: 0.55rem 0.85rem;
            cursor: pointer;
            font-size: 0.82rem;
            border-bottom: 1px solid #f3f0ff;
        }

        .inv-hit:hover { background: #faf5ff; }

        .inv-hit-number { font-weight: 700; color: #1b1b18; }
        .inv-hit-sub { font-size: 0.72rem; color: #6b7280; margin-top: 1px; }

        .dn-dispatch-info {
            background: #faf9ff;
            border: 1px solid #ede9fe;
            border-radius: 0;
            padding: 1rem 1.25rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.7rem 1.25rem;
        }

        .dn-dispatch-info .inv-field { margin-top: 0; }

        @media (max-width: 640px) {
            .invoice-doc-body { padding: 1.5rem 1.25rem; }
            .invoice-parties { grid-template-columns: 1fr; }
            .invoice-header { flex-direction: column; }
            .invoice-fields { text-align: left; min-width: 0; }
            .inv-field-inline { justify-content: flex-start; }
            .dn-grid.three { grid-template-columns: 1fr; }
            .dn-grid { grid-template-columns: 1fr; }
        }
    </style>
@endpush

@section('content')
    <div class="co-wrap">
        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">

                <div class="inv-mgmt-bar"></div>

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

                <form method="POST" action="{{ route('companies.delivery-notes.store', $company) }}" id="dn-form">
                    @csrf

                    <div class="invoice-doc">
                        <div class="invoice-doc-accent"></div>
                        <div class="invoice-doc-body">

                            {{-- Header: company + delivery note number/dates --}}
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
                                        <input type="text" name="delivery_note_number" id="delivery_note_number"
                                               value="{{ old('delivery_note_number', $nextNumber) }}"
                                               class="inv-number-input" required placeholder="DN-0001">
                                    </div>
                                    <div class="inv-field-inline">
                                        <label for="status">Status</label>
                                        <select name="status" id="status" style="width:120px;">
                                            <option value="draft"      {{ old('status','draft') === 'draft'      ? 'selected' : '' }}>Draft</option>
                                            <option value="dispatched" {{ old('status') === 'dispatched' ? 'selected' : '' }}>Dispatched</option>
                                            <option value="delivered"  {{ old('status') === 'delivered'  ? 'selected' : '' }}>Delivered</option>
                                            <option value="cancelled"  {{ old('status') === 'cancelled'  ? 'selected' : '' }}>Cancelled</option>
                                        </select>
                                    </div>
                                    <div class="inv-field-inline">
                                        <label for="delivery_date">Delivery Date</label>
                                        <input type="date" name="delivery_date" id="delivery_date"
                                               value="{{ old('delivery_date', now()->toDateString()) }}"
                                               style="width:150px;" required>
                                    </div>
                                    <div class="inv-field-inline">
                                        <label for="expected_delivery_date">Expected Date</label>
                                        <input type="date" name="expected_delivery_date" id="expected_delivery_date"
                                               value="{{ old('expected_delivery_date') }}"
                                               style="width:150px;">
                                    </div>
                                    @error('delivery_note_number')<p class="field-error" style="text-align:right;">{{ $message }}</p>@enderror
                                    @error('delivery_date')<p class="field-error" style="text-align:right;">{{ $message }}</p>@enderror
                                </div>
                            </div>

                            {{-- Link to Invoice --}}
                            <div class="dn-section">
                                <p class="dn-section-title">Link to Invoice</p>
                                <div style="position:relative;max-width:400px;">
                                    <input type="hidden" id="invoice-id-hidden" name="invoice_id"
                                        value="{{ old('invoice_id', $invoice?->id) }}">
                                    <div class="inv-field">
                                        <label>Search Invoice <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                                        <input type="text" id="invoice-search" autocomplete="off"
                                            placeholder="Type invoice number or customer..."
                                            value="{{ $invoice ? $invoice->invoice_number . ' — ' . (optional($invoice->customer)->name ?? $invoice->customer_name) : '' }}">
                                        <div class="inv-search-dropdown" id="invoice-dropdown"></div>
                                    </div>
                                    <div id="invoice-selected-badge" style="margin-top:0.3rem;font-size:0.72rem;color:#5e17eb;display:{{ $invoice ? 'flex' : 'none' }};align-items:center;gap:0.35rem;">
                                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                                        <span id="invoice-selected-label">{{ $invoice?->invoice_number }}</span>
                                        <button type="button" onclick="clearInvoice()" style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:0.9rem;line-height:1;padding:0;" title="Clear">&times;</button>
                                    </div>
                                </div>
                            </div>

                            {{-- Parties: Deliver To + Dispatch Details --}}
                            <div class="invoice-parties">
                                <div>
                                    <p class="invoice-party-label">Deliver To</p>

                                    <div class="inv-field" style="position:relative;">
                                        <label for="customer-select">Customer</label>
                                        <select name="customer_id" id="customer-select" onchange="fillCustomerFields(this)">
                                            <option value="">-- Select customer --</option>
                                            @foreach ($customers as $cust)
                                                <option value="{{ $cust->id }}"
                                                    data-name="{{ $cust->name }}"
                                                    data-email="{{ $cust->email }}"
                                                    data-address="{{ $cust->address }}"
                                                    {{ old('customer_id', $invoice?->customer_id) == $cust->id ? 'selected' : '' }}>
                                                    {{ $cust->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="inv-field">
                                        <label for="customer-name">Name</label>
                                        <input type="text" id="customer-name" name="customer_name"
                                               value="{{ old('customer_name', optional($invoice?->customer)->name ?? $invoice?->customer_name) }}" required>
                                        @error('customer_name')<p class="field-error">{{ $message }}</p>@enderror
                                    </div>

                                    <div class="inv-field">
                                        <label for="customer-email">Email</label>
                                        <input type="email" id="customer-email" name="customer_email"
                                               value="{{ old('customer_email', $invoice?->customer_email) }}">
                                    </div>

                                    <div class="inv-field">
                                        <label for="delivery-address">Delivery Address</label>
                                        <textarea id="delivery-address" name="delivery_address" rows="3" style="resize:vertical;">{{ old('delivery_address', $invoice?->customer_address) }}</textarea>
                                    </div>
                                </div>

                                <div>
                                    <p class="invoice-party-label">Dispatch Details</p>
                                    <div class="dn-dispatch-info">
                                        <div class="inv-field" style="flex:1;min-width:180px;">
                                            <label>Dispatched By</label>
                                            <input type="text" name="dispatched_by" value="{{ old('dispatched_by') }}" placeholder="Driver / courier name">
                                        </div>
                                        <div class="inv-field" style="flex:1;min-width:180px;">
                                            <label>Vehicle Registration</label>
                                            <input type="text" name="vehicle_registration" value="{{ old('vehicle_registration') }}" placeholder="e.g. CA 123-456">
                                        </div>
                                        <div class="inv-field" style="flex:1;min-width:180px;">
                                            <label>Tracking Reference</label>
                                            <input type="text" name="tracking_reference" value="{{ old('tracking_reference') }}" placeholder="Courier waybill / reference">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Line items --}}
                            <div class="dn-section">
                                <p class="dn-section-title">Items Dispatched</p>

                                <div class="inv-table-wrap">
                                    <table class="inv-table">
                                        <thead>
                                            <tr>
                                                <th style="width:40%;">Description</th>
                                                <th style="width:22%;">Inventory Item</th>
                                                <th style="width:12%;">Quantity</th>
                                                <th style="width:12%;">Unit</th>
                                                <th style="width:36px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="items-body">
                                            @php
                                                $prefillItems = $invoice
                                                    ? $invoice->items->map(fn($i) => [
                                                        'inventory_item_id' => $i->inventory_item_id,
                                                        'description'       => $i->description,
                                                        'quantity'          => $i->quantity,
                                                        'unit'              => $i->inventoryItem?->unit ?? '',
                                                      ])->values()->all()
                                                    : (old('items') ?? [['inventory_item_id'=>'','description'=>'','quantity'=>'1','unit'=>'']]);
                                            @endphp
                                            @foreach ($prefillItems as $idx => $li)
                                                <tr class="item-row">
                                                    <td>
                                                        <input type="text" name="items[{{ $idx }}][description]"
                                                            value="{{ old("items.$idx.description", $li['description'] ?? '') }}"
                                                            placeholder="Item description" required>
                                                    </td>
                                                    <td>
                                                        <select name="items[{{ $idx }}][inventory_item_id]">
                                                            <option value="">-- None --</option>
                                                            @foreach ($inventoryItems as $invItem)
                                                                <option value="{{ $invItem->id }}"
                                                                    {{ old("items.$idx.inventory_item_id", $li['inventory_item_id'] ?? '') == $invItem->id ? 'selected' : '' }}>
                                                                    {{ $invItem->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="number" name="items[{{ $idx }}][quantity]"
                                                            value="{{ old("items.$idx.quantity", $li['quantity'] ?? 1) }}"
                                                            min="0.01" step="0.01" required>
                                                    </td>
                                                    <td>
                                                        <input type="text" name="items[{{ $idx }}][unit]"
                                                            value="{{ old("items.$idx.unit", $li['unit'] ?? '') }}"
                                                            placeholder="e.g. pcs">
                                                    </td>
                                                    <td style="text-align:center;">
                                                        <button type="button" class="btn-remove-line" onclick="removeRow(this)" title="Remove">&#x2715;</button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <button type="button" class="btn-add-line" onclick="addRow()">
                                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24">
                                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                                    </svg>
                                    Add Item
                                </button>
                                @error('items')<p class="field-error">{{ $message }}</p>@enderror
                            </div>

                            {{-- Notes --}}
                            <div style="margin-bottom:1.75rem;">
                                <p class="dn-section-title">Notes <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#9ca3af;">(optional)</span></p>
                                <textarea name="notes" id="notes" rows="3"
                                    style="width:100%;border:1px solid #ddd6fe;border-radius:0;padding:0.6rem 0.75rem;font-size:0.82rem;color:#4b5563;line-height:1.65;background:#faf9ff;outline:none;resize:vertical;box-sizing:border-box;transition:border-color 0.15s,box-shadow 0.15s;"
                                    onfocus="this.style.borderColor='#5e17eb';this.style.boxShadow='0 0 0 3px rgba(94,23,235,0.08)';this.style.background='#fff'"
                                    onblur="this.style.borderColor='#ddd6fe';this.style.boxShadow='none';this.style.background='#faf9ff'">{{ old('notes') }}</textarea>
                            </div>

                            {{-- Document footer / actions --}}
                            <div class="invoice-doc-footer">
                                <span class="invoice-doc-footer-info">
                                    {{ $company->registered_name }}
                                </span>
                                <div style="display:flex;gap:0.65rem;align-items:center;">
                                    <a href="{{ route('companies.delivery-notes.index', $company) }}" class="inv-action-btn inv-action-cancel">
                                        Cancel
                                    </a>
                                    <button type="submit" class="inv-action-btn inv-action-submit">
                                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                                        Create Delivery Note
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>

                </form>

            </main>
        </div>
    </div>

    @php
        $inventoryItemsJs = $inventoryItems->map(fn($i) => [
            'id'   => $i->id,
            'name' => $i->name,
            'unit' => $i->unit ?? '',
        ])->values()->all();

        $invoicesJs = $invoices->map(fn($inv) => [
            'id'               => $inv->id,
            'number'           => $inv->invoice_number,
            'customer_id'      => $inv->customer_id,
            'customer_name'    => optional($inv->customer)->name ?? $inv->customer_name,
            'customer_email'   => $inv->customer_email,
            'customer_address' => $inv->customer_address,
            'date'             => optional($inv->invoice_date)->format('d M Y'),
            'items'            => $inv->items->map(fn($item) => [
                'inventory_item_id' => $item->inventory_item_id,
                'description'       => $item->description,
                'quantity'          => (float) $item->quantity,
                'unit'              => $item->inventoryItem?->unit ?? '',
            ])->values()->all(),
        ])->values()->all();
    @endphp
    <script>
        const inventoryItems = @json($inventoryItemsJs);
        const invoicesData   = @json($invoicesJs);

        let rowIndex = {{ count($prefillItems) }};

        function esc(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

        // ── Invoice search ────────────────────────────────────────
        (function () {
            const searchEl   = document.getElementById('invoice-search');
            const hiddenEl   = document.getElementById('invoice-id-hidden');
            const dropdown   = document.getElementById('invoice-dropdown');
            const badge      = document.getElementById('invoice-selected-badge');
            const badgeLabel = document.getElementById('invoice-selected-label');

            function renderHits(hits) {
                if (!hits.length) { dropdown.style.display = 'none'; return; }
                dropdown.innerHTML = hits.slice(0, 12).map(h => `
                    <div class="inv-hit" data-id="${h.id}">
                        <div class="inv-hit-number">${esc(h.number)}</div>
                        <div class="inv-hit-sub">${esc(h.customer_name)}${h.date ? ' · ' + esc(h.date) : ''}</div>
                    </div>`).join('');
                dropdown.style.display = 'block';
                dropdown.querySelectorAll('.inv-hit').forEach(el => {
                    el.addEventListener('mousedown', e => {
                        e.preventDefault();
                        const inv = invoicesData.find(i => i.id == el.dataset.id);
                        if (inv) selectInvoice(inv);
                    });
                });
            }

            function selectInvoice(inv) {
                hiddenEl.value         = inv.id;
                searchEl.value         = inv.number + ' — ' + inv.customer_name;
                badgeLabel.textContent = inv.number;
                badge.style.display    = 'flex';
                dropdown.style.display = 'none';

                const custSel = document.getElementById('customer-select');
                if (custSel) {
                    Array.from(custSel.options).forEach(o => { if (o.value == inv.customer_id) custSel.value = o.value; });
                }
                document.getElementById('customer-name').value    = inv.customer_name    || '';
                document.getElementById('customer-email').value   = inv.customer_email   || '';
                document.getElementById('delivery-address').value = inv.customer_address || '';

                if (inv.items && inv.items.length) {
                    const tbody = document.getElementById('items-body');
                    tbody.innerHTML = '';
                    rowIndex = 0;
                    inv.items.forEach(line => {
                        const idx = rowIndex++;
                        const optionsHtml = inventoryItems.map(i =>
                            `<option value="${i.id}" ${i.id == line.inventory_item_id ? 'selected' : ''}>${esc(i.name)}</option>`
                        ).join('');
                        const tr = document.createElement('tr');
                        tr.className = 'item-row';
                        tr.innerHTML = `
                            <td><input type="text" name="items[${idx}][description]" value="${esc(line.description)}" placeholder="Item description" required></td>
                            <td>
                                <select name="items[${idx}][inventory_item_id]">
                                    <option value="">-- None --</option>
                                    ${optionsHtml}
                                </select>
                            </td>
                            <td><input type="number" name="items[${idx}][quantity]" value="${line.quantity}" min="0.01" step="0.01" required></td>
                            <td><input type="text" name="items[${idx}][unit]" id="unit-${idx}" value="${esc(line.unit)}" placeholder="e.g. pcs"></td>
                            <td style="text-align:center;"><button type="button" class="btn-remove-line" onclick="removeRow(this)" title="Remove">&#x2715;</button></td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
            }

            searchEl.addEventListener('input', function () {
                hiddenEl.value = '';
                badge.style.display = 'none';
                const q = this.value.trim().toLowerCase();
                if (q.length < 1) { dropdown.style.display = 'none'; return; }
                const hits = invoicesData.filter(i =>
                    i.number.toLowerCase().includes(q) ||
                    i.customer_name.toLowerCase().includes(q)
                );
                renderHits(hits);
            });

            searchEl.addEventListener('blur', () => setTimeout(() => { dropdown.style.display = 'none'; }, 160));
            searchEl.addEventListener('focus', function () {
                if (!hiddenEl.value && this.value.trim().length >= 1) this.dispatchEvent(new Event('input'));
            });

            window.clearInvoice = function () {
                hiddenEl.value = '';
                searchEl.value = '';
                badge.style.display = 'none';
                dropdown.style.display = 'none';
            };

            if (hiddenEl.value) {
                const inv = invoicesData.find(i => i.id == hiddenEl.value);
                if (inv) {
                    badge.style.display = 'flex';
                    badgeLabel.textContent = inv.number;
                }
            }
        })();

        // ── Row add/remove ────────────────────────────────────────
        function addRow() {
            const tbody = document.getElementById('items-body');
            const idx = rowIndex++;
            const optionsHtml = inventoryItems.map(i =>
                `<option value="${i.id}">${esc(i.name)}</option>`
            ).join('');
            const tr = document.createElement('tr');
            tr.className = 'item-row';
            tr.innerHTML = `
                <td><input type="text" name="items[${idx}][description]" placeholder="Item description" required></td>
                <td>
                    <select name="items[${idx}][inventory_item_id]" onchange="fillUnit(this,${idx})">
                        <option value="">-- None --</option>
                        ${optionsHtml}
                    </select>
                </td>
                <td><input type="number" name="items[${idx}][quantity]" value="1" min="0.01" step="0.01" required></td>
                <td><input type="text" name="items[${idx}][unit]" id="unit-${idx}" placeholder="e.g. pcs"></td>
                <td style="text-align:center;"><button type="button" class="btn-remove-line" onclick="removeRow(this)" title="Remove">&#x2715;</button></td>
            `;
            tbody.appendChild(tr);
        }

        function removeRow(btn) {
            const rows = document.querySelectorAll('.item-row');
            if (rows.length <= 1) return;
            btn.closest('tr').remove();
        }

        function fillUnit(select, idx) {
            const item = inventoryItems.find(i => i.id == select.value);
            const unitEl = document.getElementById('unit-' + idx);
            if (unitEl && item) unitEl.value = item.unit || '';
        }

        function fillCustomerFields(select) {
            const opt = select.options[select.selectedIndex];
            document.getElementById('customer-name').value    = opt.dataset.name    || '';
            document.getElementById('customer-email').value   = opt.dataset.email   || '';
            document.getElementById('delivery-address').value = opt.dataset.address || '';
        }

        document.addEventListener('DOMContentLoaded', function () {
            const custSel = document.getElementById('customer-select');
            if (custSel && custSel.value) fillCustomerFields(custSel);
        });
    </script>
@endsection
