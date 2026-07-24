@extends('layouts.public')

@section('title', $company->registered_name . ' — New Delivery Note')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .cn-form { max-width:920px; }
        .cn-section { margin-bottom:1.75rem; }
        .cn-section-title { font-size:0.65rem; font-weight:800; letter-spacing:0.1em; text-transform:uppercase; color:#5e17eb; margin:0 0 0.75rem; }
        .cn-grid { display:grid; grid-template-columns:1fr 1fr; gap:0.75rem 1.25rem; }
        .cn-grid.three { grid-template-columns:1fr 1fr 1fr; }
        .cn-field label { display:block; font-size:0.6rem; font-weight:700; letter-spacing:0.07em; text-transform:uppercase; color:#555; margin-bottom:0.22rem; }
        .cn-field input, .cn-field select, .cn-field textarea {
            width:100%; border:1px solid #ccc; padding:0.35rem 0.55rem;
            font-size:0.8rem; font-family:inherit; color:#000; background:#fff; box-sizing:border-box;
        }
        .cn-field input:focus, .cn-field select:focus, .cn-field textarea:focus { outline:none; border-color:#5e17eb; }
        .cn-field textarea { resize:vertical; min-height:70px; }

        .inv-search-wrap { position:relative; }
        .inv-search-dropdown {
            display:none; position:absolute; left:0; right:0; top:100%;
            background:#fff; border:1px solid #ddd6fe; border-top:none; z-index:50;
            max-height:240px; overflow-y:auto; box-shadow:0 4px 12px rgba(94,23,235,0.10);
        }
        .inv-hit { padding:0.5rem 0.8rem; cursor:pointer; font-size:0.8rem; border-bottom:1px solid #f3f0ff; }
        .inv-hit:hover { background:#faf5ff; }

        .items-table { width:100%; border-collapse:collapse; margin-bottom:0.75rem; }
        .items-table th { font-size:0.6rem; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#555; padding:0 0 0.4rem; border-bottom:1.5px solid #000; text-align:left; }
        .items-table th.r { text-align:right; }
        .items-table td { padding:0.4rem 0.3rem; border-bottom:1px solid #e5e7eb; vertical-align:middle; }
        .items-table input, .items-table select { width:100%; border:1px solid #ccc; padding:0.28rem 0.45rem; font-size:0.78rem; font-family:inherit; background:#fff; box-sizing:border-box; }
        .items-table input:focus, .items-table select:focus { outline:none; border-color:#5e17eb; }
        .items-table input[type="number"] { text-align:right; }
        .btn-remove-row { background:none; border:none; cursor:pointer; color:#dc2626; font-size:1rem; padding:0.1rem 0.3rem; line-height:1; }
        .btn-add-row { background:none; border:1px dashed #ccc; padding:0.4rem 0.8rem; font-size:0.72rem; font-weight:600; cursor:pointer; color:#555; transition:border-color 0.15s; }
        .btn-add-row:hover { border-color:#5e17eb; color:#5e17eb; }
        .btn-submit { background:#000; color:#fff; border:none; padding:0.55rem 1.5rem; font-size:0.82rem; font-weight:700; cursor:pointer; transition:background 0.15s; font-family:inherit; }
        .btn-submit:hover { background:#333; }
        .form-error { color:#dc2626; font-size:0.72rem; margin-top:0.2rem; }
    </style>
@endpush

@section('content')
    <div class="co-wrap">
        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')
            <main class="co-main">

                <form method="POST" action="{{ route('companies.delivery-notes.store', $company) }}" class="cn-form" id="dn-form">
                    @csrf

                    @if ($errors->any())
                        <div style="background:#fee2e2;border:1px solid #fecaca;color:#b91c1c;padding:0.75rem 1.25rem;font-size:0.82rem;margin-bottom:1.25rem;">
                            <strong>Please fix the errors below:</strong>
                            <ul style="margin:0.4rem 0 0 1.1rem;padding:0;">
                                @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Link to invoice --}}
                    <div class="cn-section">
                        <p class="cn-section-title">Link to Invoice (optional)</p>
                        <div class="cn-grid">
                            <div class="cn-field">
                                <label>Invoice</label>
                                <input type="hidden" id="invoice-id-hidden" name="invoice_id" value="{{ old('invoice_id', $invoice?->id) }}">
                                <div class="inv-search-wrap">
                                    <input type="text" id="invoice-search" autocomplete="off"
                                        placeholder="Type invoice number or customer…"
                                        value="{{ $invoice ? $invoice->invoice_number . ' — ' . (optional($invoice->customer)->name ?? $invoice->customer_name) : '' }}">
                                    <div class="inv-search-dropdown" id="invoice-dropdown"></div>
                                </div>
                                <div id="invoice-selected-badge" style="margin-top:0.3rem;font-size:0.72rem;color:#5e17eb;display:{{ $invoice ? 'flex' : 'none' }};align-items:center;gap:0.35rem;">
                                    <span id="invoice-selected-label">{{ $invoice?->invoice_number }}</span>
                                    <button type="button" onclick="clearInvoice()" style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:0.9rem;line-height:1;padding:0;" title="Clear">&times;</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Delivery note details --}}
                    <div class="cn-section">
                        <p class="cn-section-title">Delivery Note Details</p>
                        <div class="cn-grid three">
                            <div class="cn-field">
                                <label>Delivery Note # <span style="color:#dc2626;">*</span></label>
                                <input type="text" name="delivery_note_number" value="{{ old('delivery_note_number', $nextNumber) }}" required>
                                @error('delivery_note_number')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="cn-field">
                                <label>Delivery Date <span style="color:#dc2626;">*</span></label>
                                <input type="date" name="delivery_date" value="{{ old('delivery_date', now()->toDateString()) }}" required>
                                @error('delivery_date')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="cn-field">
                                <label>Expected Delivery Date</label>
                                <input type="date" name="expected_delivery_date" value="{{ old('expected_delivery_date') }}">
                            </div>
                        </div>
                        <div class="cn-grid" style="margin-top:0.75rem;">
                            <div class="cn-field">
                                <label>Status</label>
                                <select name="status">
                                    <option value="draft"      {{ old('status','draft') === 'draft'      ? 'selected' : '' }}>Draft</option>
                                    <option value="dispatched" {{ old('status') === 'dispatched' ? 'selected' : '' }}>Dispatched</option>
                                    <option value="delivered"  {{ old('status') === 'delivered'  ? 'selected' : '' }}>Delivered</option>
                                    <option value="cancelled"  {{ old('status') === 'cancelled'  ? 'selected' : '' }}>Cancelled</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Customer / deliver to --}}
                    <div class="cn-section">
                        <p class="cn-section-title">Deliver To</p>
                        <div class="cn-grid">
                            <div class="cn-field">
                                <label>Customer</label>
                                <select name="customer_id" id="customer-select" onchange="fillCustomerFields(this)">
                                    <option value="">— Select customer —</option>
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
                            <div class="cn-field">
                                <label>Name <span style="color:#dc2626;">*</span></label>
                                <input type="text" name="customer_name" id="customer-name"
                                    value="{{ old('customer_name', optional($invoice?->customer)->name ?? $invoice?->customer_name) }}" required>
                                @error('customer_name')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="cn-field">
                                <label>Email</label>
                                <input type="email" name="customer_email" id="customer-email"
                                    value="{{ old('customer_email', $invoice?->customer_email) }}">
                            </div>
                            <div class="cn-field">
                                <label>Delivery Address</label>
                                <textarea name="delivery_address" id="delivery-address" rows="2">{{ old('delivery_address', $invoice?->customer_address) }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Dispatch details --}}
                    <div class="cn-section">
                        <p class="cn-section-title">Dispatch Details</p>
                        <div class="cn-grid three">
                            <div class="cn-field">
                                <label>Dispatched By</label>
                                <input type="text" name="dispatched_by" value="{{ old('dispatched_by') }}" placeholder="Driver / courier name">
                            </div>
                            <div class="cn-field">
                                <label>Vehicle Registration</label>
                                <input type="text" name="vehicle_registration" value="{{ old('vehicle_registration') }}" placeholder="e.g. CA 123-456">
                            </div>
                            <div class="cn-field">
                                <label>Tracking Reference</label>
                                <input type="text" name="tracking_reference" value="{{ old('tracking_reference') }}" placeholder="Courier waybill / reference">
                            </div>
                        </div>
                    </div>

                    {{-- Line items --}}
                    <div class="cn-section">
                        <p class="cn-section-title">Items Dispatched</p>
                        <table class="items-table" id="items-table">
                            <thead>
                                <tr>
                                    <th style="width:34%;">Description</th>
                                    <th style="width:22%;">Inventory Item</th>
                                    <th class="r" style="width:12%;">Quantity</th>
                                    <th style="width:12%;">Unit</th>
                                    <th style="width:4%;"></th>
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
                                            <select name="items[{{ $idx }}][inventory_item_id]" onchange="fillUnit(this, {{ $idx }})">
                                                <option value="">— None —</option>
                                                @foreach ($inventoryItems as $invItem)
                                                    <option value="{{ $invItem->id }}"
                                                        data-unit="{{ $invItem->unit ?? '' }}"
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
                                            <input type="text" name="items[{{ $idx }}][unit]" id="unit-{{ $idx }}"
                                                value="{{ old("items.$idx.unit", $li['unit'] ?? '') }}"
                                                placeholder="e.g. pcs">
                                        </td>
                                        <td style="text-align:center;">
                                            <button type="button" class="btn-remove-row" onclick="removeRow(this)" title="Remove">&times;</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <button type="button" class="btn-add-row" onclick="addRow()">+ Add Item</button>
                        @error('items')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Notes --}}
                    <div class="cn-section">
                        <p class="cn-section-title">Notes</p>
                        <textarea name="notes" rows="3" style="width:100%;max-width:600px;border:1px solid #ccc;padding:0.4rem 0.6rem;font-size:0.8rem;font-family:inherit;">{{ old('notes') }}</textarea>
                    </div>

                    <div style="display:flex;gap:1rem;align-items:center;">
                        <button type="submit" class="btn-submit">Create Delivery Note</button>
                        <a href="{{ route('companies.delivery-notes.index', $company) }}" style="font-size:0.78rem;color:#6b7280;text-decoration:none;">Cancel</a>
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
                        <div style="font-weight:700;color:#1b1b18;">${esc(h.number)}</div>
                        <div style="font-size:0.7rem;color:#6b7280;">${esc(h.customer_name)}${h.date ? ' · ' + esc(h.date) : ''}</div>
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
                            `<option value="${i.id}" data-unit="${esc(i.unit)}" ${i.id == line.inventory_item_id ? 'selected' : ''}>${esc(i.name)}</option>`
                        ).join('');
                        const tr = document.createElement('tr');
                        tr.className = 'item-row';
                        tr.innerHTML = `
                            <td><input type="text" name="items[${idx}][description]" value="${esc(line.description)}" placeholder="Item description" required></td>
                            <td><select name="items[${idx}][inventory_item_id]" onchange="fillUnit(this,${idx})"><option value="">— None —</option>${optionsHtml}</select></td>
                            <td><input type="number" name="items[${idx}][quantity]" value="${line.quantity}" min="0.01" step="0.01" required></td>
                            <td><input type="text" name="items[${idx}][unit]" id="unit-${idx}" value="${esc(line.unit)}" placeholder="e.g. pcs"></td>
                            <td style="text-align:center;"><button type="button" class="btn-remove-row" onclick="removeRow(this)" title="Remove">&times;</button></td>
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
                `<option value="${i.id}" data-unit="${esc(i.unit)}">${esc(i.name)}</option>`
            ).join('');
            const tr = document.createElement('tr');
            tr.className = 'item-row';
            tr.innerHTML = `
                <td><input type="text" name="items[${idx}][description]" placeholder="Item description" required></td>
                <td><select name="items[${idx}][inventory_item_id]" onchange="fillUnit(this,${idx})"><option value="">— None —</option>${optionsHtml}</select></td>
                <td><input type="number" name="items[${idx}][quantity]" value="1" min="0.01" step="0.01" required></td>
                <td><input type="text" name="items[${idx}][unit]" id="unit-${idx}" placeholder="e.g. pcs"></td>
                <td style="text-align:center;"><button type="button" class="btn-remove-row" onclick="removeRow(this)" title="Remove">&times;</button></td>
            `;
            tbody.appendChild(tr);
        }

        function removeRow(btn) {
            const rows = document.querySelectorAll('.item-row');
            if (rows.length <= 1) return;
            btn.closest('tr').remove();
        }

        function fillUnit(select, idx) {
            const opt = select.options[select.selectedIndex];
            const unitEl = document.getElementById('unit-' + idx);
            if (unitEl && opt.dataset.unit) unitEl.value = opt.dataset.unit;
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
