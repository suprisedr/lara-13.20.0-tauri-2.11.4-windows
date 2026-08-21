@extends('layouts.public')

@section('title', $company->registered_name . ' — New Quotation')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .cn-form { max-width:920px; }
        .cn-section { margin-bottom:1.75rem; }
        .cn-section-title { font-size:0.65rem; font-weight:800; letter-spacing:0.1em; text-transform:uppercase; color:#005bf0; margin:0 0 0.75rem; }
        .cn-grid { display:grid; grid-template-columns:1fr 1fr; gap:0.75rem 1.25rem; }
        .cn-grid.three { grid-template-columns:1fr 1fr 1fr; }
        .cn-field label { display:block; font-size:0.7rem; font-weight:700; letter-spacing:0.07em; text-transform:uppercase; color:#5a7186; margin-bottom:0.22rem; }
        .cn-field input, .cn-field select, .cn-field textarea {
            width:100%; border:1px solid #d3e2f5; padding:0.35rem 0.55rem;
            font-size:0.8rem; font-family:inherit; color:#000; background:#fff; box-sizing:border-box;
        }
        .cn-field input:focus, .cn-field select:focus, .cn-field textarea:focus { outline:none; border-color:#005bf0; }
        .cn-field textarea { resize:vertical; min-height:70px; }

        .cust-search-wrap { position:relative; }
        .cust-search-dropdown {
            display:none; position:absolute; left:0; right:0; top:100%;
            background:#fff; border:1px solid #d3e2f5; border-top:none; z-index:50;
            max-height:240px; overflow-y:auto; box-shadow:0 4px 12px rgba(0, 91, 240,0.10);
        }
        .cust-hit { padding:0.5rem 0.8rem; cursor:pointer; font-size:0.8rem; border-bottom:1px solid #f4fafc; }
        .cust-hit:hover { background:#f4fafc; }

        .items-table { width:100%; border-collapse:collapse; margin-bottom:0.75rem; }
        .items-table th { font-size:0.7rem; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#5a7186; padding:0 0 0.4rem; border-bottom:1.5px solid #000; text-align:left; }
        .items-table th.r { text-align:right; }
        .items-table td { padding:0.4rem 0.3rem; border-bottom:1px solid #d3e2f5; vertical-align:middle; }
        .items-table td.r { text-align:right; }
        .items-table input, .items-table select { width:100%; border:1px solid #d3e2f5; padding:0.28rem 0.45rem; font-size:0.78rem; font-family:inherit; background:#fff; box-sizing:border-box; }
        .items-table input:focus, .items-table select:focus { outline:none; border-color:#005bf0; }
        .items-table input[type="number"] { text-align:right; }
        .btn-remove-row { background:none; border:none; cursor:pointer; color:#dc2626; font-size:1rem; padding:0.1rem 0.3rem; line-height:1; }
        .btn-add-row { background:none; border:1px dashed #d3e2f5; padding:0.4rem 0.8rem; font-size:0.72rem; font-weight:600; cursor:pointer; color:#5a7186; transition:border-color 0.15s; }
        .btn-add-row:hover { border-color:#005bf0; color:#005bf0; }
        .btn-submit { background:#000; color:#fff; border:none; padding:0.55rem 1.5rem; font-size:0.82rem; font-weight:700; cursor:pointer; transition:background 0.15s; font-family:inherit; }
        .btn-submit:hover { background:#1a345b; }
        .form-error { color:#dc2626; font-size:0.72rem; margin-top:0.2rem; }

        .line-total { font-size:0.78rem; font-weight:600; text-align:right; font-variant-numeric:tabular-nums; }
        .cn-totals { margin-left:auto; width:220px; font-size:0.82rem; }
        .cn-totals td { padding:0.2rem 0; }
        .cn-totals td.r { text-align:right; font-variant-numeric:tabular-nums; }
        .cn-totals tr.total-row td { font-weight:800; border-top:1.5px solid #000; padding-top:0.4rem; }

        .item-search-wrap { position:relative; }
        .item-search-dropdown {
            display:none; position:absolute; left:0; right:0; top:100%;
            background:#fff; border:1px solid #d3e2f5; border-top:none; z-index:50;
            max-height:220px; overflow-y:auto; box-shadow:0 4px 12px rgba(0, 91, 240,0.10);
            min-width:260px;
        }
        .item-hit { padding:0.5rem 0.8rem; cursor:pointer; font-size:0.8rem; border-bottom:1px solid #f4fafc; }
        .item-hit:hover { background:#f4fafc; }

        .stock-hint { font-size:0.72rem; color:#5a7186; margin-top:1px; }
    </style>
@endpush

@section('content')
    <div class="co-wrap">
        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')
            <main class="co-main">

                <form method="POST" action="{{ route('companies.quotations.store', $company) }}" class="cn-form" id="quotation-form">
                    @csrf

                    @if ($errors->any())
                        <div style="background:#fee2e2;border:1px solid #fecaca;color:#b91c1c;padding:0.75rem 1.25rem;font-size:0.82rem;margin-bottom:1.25rem;">
                            <strong>Please fix the errors below:</strong>
                            <ul style="margin:0.4rem 0 0 1.1rem;padding:0;">
                                @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Quotation details --}}
                    <div class="cn-section">
                        <p class="cn-section-title">Quotation Details</p>
                        <div class="cn-grid three">
                            <div class="cn-field">
                                <label>Quotation # <span style="color:#dc2626;">*</span></label>
                                <input type="text" name="quotation_number" value="{{ old('quotation_number', $nextNumber) }}" required>
                                @error('quotation_number')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="cn-field">
                                <label>Quote Date <span style="color:#dc2626;">*</span></label>
                                <input type="date" name="quotation_date" value="{{ old('quotation_date', now()->format('Y-m-d')) }}" required>
                                @error('quotation_date')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="cn-field">
                                <label>Expiry Date</label>
                                <input type="date" name="expiry_date" value="{{ old('expiry_date', now()->addDays(30)->format('Y-m-d')) }}">
                                @error('expiry_date')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div class="cn-grid" style="margin-top:0.75rem;">
                            <div class="cn-field">
                                <label>Status</label>
                                <select name="status">
                                    <option value="draft"    {{ old('status','draft') === 'draft'    ? 'selected' : '' }}>Draft</option>
                                    <option value="sent"     {{ old('status') === 'sent'     ? 'selected' : '' }}>Sent</option>
                                    <option value="accepted" {{ old('status') === 'accepted' ? 'selected' : '' }}>Accepted</option>
                                    <option value="declined" {{ old('status') === 'declined' ? 'selected' : '' }}>Declined</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Customer --}}
                    <div class="cn-section">
                        <p class="cn-section-title">Quote For</p>
                        <div class="cn-grid">
                            <div class="cn-field">
                                <label>Search Customer <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                                <input type="hidden" id="customer_id" name="customer_id" value="{{ old('customer_id', request('customer_id')) }}">
                                <div class="cust-search-wrap">
                                    <input type="text" id="customer-search" autocomplete="off" placeholder="Type name, email…"
                                        value="{{ old('customer_id') ? ($customers->firstWhere('id', old('customer_id'))?->name ?? '') : (request('customer_id') ? ($customers->firstWhere('id', request('customer_id'))?->name ?? '') : '') }}">
                                    <div class="cust-search-dropdown" id="customer-dropdown"></div>
                                </div>
                                @error('customer_id')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="cn-field">
                                <label>Name <span style="color:#dc2626;">*</span></label>
                                <input type="text" id="customer_name" name="customer_name" value="{{ old('customer_name') }}" required>
                                @error('customer_name')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="cn-field">
                                <label>Email <span style="color:#dc2626;">*</span></label>
                                <input type="email" id="customer_email" name="customer_email" value="{{ old('customer_email') }}" required>
                                @error('customer_email')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="cn-field">
                                <label>Address <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                                <textarea id="customer_address" name="customer_address" rows="2">{{ old('customer_address') }}</textarea>
                                @error('customer_address')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    {{-- Line items --}}
                    <div class="cn-section">
                        <p class="cn-section-title">Line Items</p>

                        @if ($inventoryItems->isEmpty())
                            <div style="background:#fef9c3;border:1px solid #fde68a;padding:0.75rem 1rem;font-size:0.82rem;color:#854d0e;display:flex;align-items:center;gap:0.6rem;margin-bottom:1rem;">
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                No inventory items found. <a href="{{ route('companies.inventory.index', $company) }}" style="color:#854d0e;font-weight:700;">Add items to your inventory</a> first.
                            </div>
                        @else
                            <table class="items-table" id="items-table">
                                <thead>
                                    <tr>
                                        <th style="width:24%;">Item</th>
                                        <th style="width:18%;">Description</th>
                                        <th class="r" style="width:10%;">Qty</th>
                                        <th class="r" style="width:12%;">Unit Price</th>
                                        <th class="r" style="width:8%;">VAT %</th>
                                        <th class="r" style="width:12%;">Line Total</th>
                                        <th style="width:4%;"></th>
                                    </tr>
                                </thead>
                                <tbody id="line-items-body">
                                    {{-- rows injected by JS --}}
                                </tbody>
                            </table>
                            <button type="button" class="btn-add-row" onclick="addLineItem()">+ Add Line Item</button>

                            <table class="cn-totals" style="margin-top:1rem;">
                                <tbody>
                                    <tr><td>Subtotal</td><td class="r" id="summary-subtotal">R 0.00</td></tr>
                                    <tr><td>VAT</td><td class="r" id="summary-tax">R 0.00</td></tr>
                                    <tr class="total-row"><td>Estimated Total</td><td class="r" id="summary-total">R 0.00</td></tr>
                                </tbody>
                            </table>
                        @endif
                    </div>

                    {{-- Notes --}}
                    <div class="cn-section">
                        <p class="cn-section-title">Notes / Terms</p>
                        <textarea name="notes" id="notes" rows="3" style="width:100%;max-width:600px;border:1px solid #d3e2f5;padding:0.4rem 0.6rem;font-size:0.8rem;font-family:inherit;">{{ old('notes') }}</textarea>
                    </div>

                    <div style="display:flex;gap:1rem;align-items:center;">
                        <button type="submit" class="btn-submit">Create Quotation</button>
                        <a href="{{ route('companies.quotations.index', $company) }}" style="font-size:0.78rem;color:#5a7186;text-decoration:none;">Cancel</a>
                    </div>
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
                         data-email="${escHtml(h.email ?? '')}" data-address="${escHtml(h.address ?? '')}">
                        <div style="font-weight:700;color:#191919;">${escHtml(h.name)}</div>
                        ${h.email ? `<div style="font-size:0.72rem;color:#5a7186;">${escHtml(h.email)}</div>` : ''}
                    </div>`).join('') +
                    `<div style="padding:0.4rem 0.85rem;font-size:0.7rem;color:#6f869b;background:#f7fbfd;"><em>or continue typing to add manually</em></div>`;
                dropdown.style.display = 'block';
                dropdown.querySelectorAll('.cust-hit').forEach(el => {
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
                    stockHint.innerHTML = `<div class="stock-hint">${item.quantity_on_hand} available</div>`;
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
                         data-service="${h.is_service ? '1' : '0'}" data-qty="${h.quantity_on_hand ?? 0}">
                        <div style="font-weight:700;color:#191919;">${escHtml(h.name)}${h.sku ? ` <span style="font-weight:400;color:#6f869b;font-size:0.7rem;">${escHtml(h.sku)}</span>` : ''}</div>
                        <div style="font-size:0.72rem;color:#5a7186;display:flex;gap:1rem;">
                            <span>${fmt(h.unit_price)}</span>
                            ${!h.is_service ? `<span>${h.quantity_on_hand} in stock</span>` : '<span>Service</span>'}
                        </div>
                    </div>`).join('');
                dropdown.style.display = 'block';
                dropdown.querySelectorAll('.item-hit').forEach(el => {
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
                <td>
                    <div class="item-search-wrap">
                        <input type="hidden" name="items[${idx}][inventory_item_id]" id="item-id-${idx}">
                        <input type="text" id="item-search-${idx}" autocomplete="off" placeholder="Search item…">
                        <div class="item-search-dropdown" id="item-dd-${idx}"></div>
                    </div>
                </td>
                <td id="desc-${idx}" style="font-size:0.78rem;color:#5a7186;">—</td>
                <td>
                    <input type="number" name="items[${idx}][quantity]" id="qty-${idx}" value="${qty}"
                           min="0.01" step="0.01" oninput="recalc()" required>
                    <span id="stock-hint-${idx}"></span>
                </td>
                <td class="r" id="price-${idx}">—</td>
                <td class="r" id="tax-${idx}">—</td>
                <td class="r" id="lt-${idx}" style="font-weight:700;">${fmt(0)}</td>
                <td style="text-align:center;">
                    <button type="button" class="btn-remove-row" onclick="removeLine(this)" title="Remove">&times;</button>
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

        document.getElementById('quotation-form').addEventListener('submit', function (e) {
            const missing = [...document.querySelectorAll('input[name*="[inventory_item_id]"]')].some(i => !i.value);
            if (missing) { e.preventDefault(); alert('Please select an item for every line.'); }
        });

        if (oldItems && oldItems.length > 0) {
            oldItems.forEach(item => addLineItem(item.inventory_item_id || null, item.quantity || 1));
        } else {
            addLineItem();
        }
    </script>
@endsection
