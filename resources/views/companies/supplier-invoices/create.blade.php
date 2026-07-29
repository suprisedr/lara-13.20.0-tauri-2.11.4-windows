@extends('layouts.public')

@section('title', $company->registered_name . ' — New Supplier Invoice')
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

        .supp-search-wrap { position:relative; }
        .supp-search-dropdown {
            display:none; position:absolute; left:0; right:0; top:100%;
            background:#fff; border:1px solid #ddd6fe; border-top:none; z-index:50;
            max-height:240px; overflow-y:auto; box-shadow:0 4px 12px rgba(94,23,235,0.10);
        }
        .supp-hit { padding:0.5rem 0.8rem; cursor:pointer; font-size:0.8rem; border-bottom:1px solid #f3f0ff; }
        .supp-hit:hover { background:#faf5ff; }

        .items-table { width:100%; border-collapse:collapse; margin-bottom:0.75rem; }
        .items-table th { font-size:0.6rem; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#555; padding:0 0 0.4rem; border-bottom:1.5px solid #000; text-align:left; }
        .items-table th.r { text-align:right; }
        .items-table td { padding:0.4rem 0.3rem; border-bottom:1px solid #e5e7eb; vertical-align:middle; }
        .items-table td.r { text-align:right; }
        .items-table input, .items-table select { width:100%; border:1px solid #ccc; padding:0.28rem 0.45rem; font-size:0.78rem; font-family:inherit; background:#fff; box-sizing:border-box; }
        .items-table input:focus, .items-table select:focus { outline:none; border-color:#5e17eb; }
        .items-table input[type="number"] { text-align:right; }
        .btn-remove-row { background:none; border:none; cursor:pointer; color:#dc2626; font-size:1rem; padding:0.1rem 0.3rem; line-height:1; }
        .btn-add-row { background:none; border:1px dashed #ccc; padding:0.4rem 0.8rem; font-size:0.72rem; font-weight:600; cursor:pointer; color:#555; transition:border-color 0.15s; }
        .btn-add-row:hover { border-color:#5e17eb; color:#5e17eb; }
        .btn-submit { background:#000; color:#fff; border:none; padding:0.55rem 1.5rem; font-size:0.82rem; font-weight:700; cursor:pointer; transition:background 0.15s; font-family:inherit; }
        .btn-submit:hover { background:#333; }
        .form-error { color:#dc2626; font-size:0.72rem; margin-top:0.2rem; }

        .line-total { font-size:0.78rem; font-weight:600; text-align:right; font-variant-numeric:tabular-nums; }
        .cn-totals { margin-left:auto; width:220px; font-size:0.82rem; }
        .cn-totals td { padding:0.2rem 0; }
        .cn-totals td.r { text-align:right; font-variant-numeric:tabular-nums; }
        .cn-totals tr.total-row td { font-weight:800; border-top:1.5px solid #000; padding-top:0.4rem; }

        .item-search-wrap { position:relative; }
        .item-search-dropdown {
            display:none; position:absolute; left:0; right:0; top:100%;
            background:#fff; border:1px solid #ddd6fe; border-top:none; z-index:50;
            max-height:220px; overflow-y:auto; box-shadow:0 4px 12px rgba(94,23,235,0.10);
            min-width:260px;
        }
        .item-hit { padding:0.5rem 0.8rem; cursor:pointer; font-size:0.8rem; border-bottom:1px solid #f3f0ff; }
        .item-hit:hover { background:#faf5ff; }
    </style>
@endpush

@section('content')
    <div class="co-wrap">
        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')
            <main class="co-main">

                <form method="POST" action="{{ route('companies.supplier-invoices.store', $company) }}" class="cn-form" id="sinv-form">
                    @csrf

                    @if ($errors->any())
                        <div style="background:#fee2e2;border:1px solid #fecaca;color:#b91c1c;padding:0.75rem 1.25rem;font-size:0.82rem;margin-bottom:1.25rem;">
                            <strong>Please fix the errors below:</strong>
                            <ul style="margin:0.4rem 0 0 1.1rem;padding:0;">
                                @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Invoice details --}}
                    <div class="cn-section">
                        <p class="cn-section-title">Supplier Invoice Details</p>
                        <div class="cn-grid three">
                            <div class="cn-field">
                                <label>Invoice # <span style="color:#dc2626;">*</span></label>
                                <input type="text" name="invoice_number" value="{{ old('invoice_number', $nextNumber) }}" required>
                                @error('invoice_number')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="cn-field">
                                <label>Invoice Date <span style="color:#dc2626;">*</span></label>
                                <input type="date" name="invoice_date" value="{{ old('invoice_date', now()->format('Y-m-d')) }}" required>
                                @error('invoice_date')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="cn-field">
                                <label>Due Date</label>
                                <input type="date" name="due_date" value="{{ old('due_date') }}">
                                @error('due_date')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div class="cn-grid" style="margin-top:0.75rem;">
                            <div class="cn-field">
                                <label>Status</label>
                                <select name="status">
                                    <option value="draft"    {{ old('status','draft') === 'draft'    ? 'selected' : '' }}>Draft</option>
                                    <option value="pending"  {{ old('status') === 'pending'  ? 'selected' : '' }}>Pending</option>
                                    <option value="approved" {{ old('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                                </select>
                            </div>
                            <div class="cn-field">
                                <label>Currency</label>
                                <input type="text" name="currency" value="{{ old('currency', 'ZAR') }}" maxlength="3">
                            </div>
                        </div>
                    </div>

                    {{-- Supplier --}}
                    <div class="cn-section">
                        <p class="cn-section-title">Supplier</p>
                        <div class="cn-grid">
                            <div class="cn-field">
                                <label>Select Supplier <span style="color:#dc2626;">*</span></label>
                                <select name="supplier_id" id="supplier_id" required>
                                    <option value="">-- Select Supplier --</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ (int) old('supplier_id') === $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}{{ $supplier->email ? ' (' . $supplier->email . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('supplier_id')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    {{-- Line items --}}
                    <div class="cn-section">
                        <p class="cn-section-title">Line Items</p>

                        <table class="items-table" id="items-table">
                            <thead>
                                <tr>
                                    <th style="width:22%;">Item <span style="font-weight:400;text-transform:none;">(optional)</span></th>
                                    <th style="width:20%;">Description</th>
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
                                <tr class="total-row"><td>Total</td><td class="r" id="summary-total">R 0.00</td></tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Notes --}}
                    <div class="cn-section">
                        <p class="cn-section-title">Notes</p>
                        <textarea name="notes" id="notes" rows="3" style="width:100%;max-width:600px;border:1px solid #ccc;padding:0.4rem 0.6rem;font-size:0.8rem;font-family:inherit;">{{ old('notes') }}</textarea>
                    </div>

                    <div style="display:flex;gap:1rem;align-items:center;">
                        <button type="submit" class="btn-submit">Create Supplier Invoice</button>
                        <a href="{{ route('companies.supplier-invoices.index', $company) }}" style="font-size:0.78rem;color:#6b7280;text-decoration:none;">Cancel</a>
                    </div>
                </form>

            </main>
        </div>
    </div>

    <script>
        const inventoryItems = @json($inventoryItemsForJs);
        let lineIndex = 0;
        const oldItems = @json(old('items', []));
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

        // Item search per line row
        function attachItemSearch(idx, initialItem) {
            const searchEl = document.getElementById(`item-search-${idx}`);
            const hiddenEl = document.getElementById(`item-id-${idx}`);
            const dropdown = document.getElementById(`item-dd-${idx}`);
            if (!searchEl) return;

            function applyItem(item) {
                hiddenEl.value = item.id;
                searchEl.value = item.name;
                document.getElementById('desc-' + idx).value = item.description || item.name;
                document.getElementById('price-' + idx).value = Number(item.unit_price).toFixed(2);
                document.getElementById('tax-' + idx).value = item.tax_rate != null ? Number(item.tax_rate).toFixed(2) : '15.00';
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
                        <div style="font-weight:700;color:#1b1b18;">${escHtml(h.name)}</div>
                        <div style="font-size:0.72rem;color:#6b7280;">${fmt(h.unit_price)}</div>
                    </div>`).join('');
                dropdown.style.display = 'block';
                dropdown.querySelectorAll('.item-hit').forEach(el => {
                    el.addEventListener('mousedown', e => {
                        e.preventDefault();
                        applyItem({
                            id: el.dataset.id, name: el.dataset.name, description: el.dataset.desc,
                            unit_price: parseFloat(el.dataset.price),
                            tax_rate: el.dataset.tax === '' ? null : parseFloat(el.dataset.tax),
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

        // Line items
        function addLineItem(data = null) {
            const idx   = lineIndex++;
            const tbody = document.getElementById('line-items-body');

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>
                    <div class="item-search-wrap">
                        <input type="hidden" name="items[${idx}][inventory_item_id]" id="item-id-${idx}" value="${data?.inventory_item_id || ''}">
                        <input type="text" id="item-search-${idx}" autocomplete="off" placeholder="Search item...">
                        <div class="item-search-dropdown" id="item-dd-${idx}"></div>
                    </div>
                </td>
                <td>
                    <input type="text" name="items[${idx}][description]" id="desc-${idx}" value="${escHtml(data?.description || '')}" required placeholder="Description">
                </td>
                <td>
                    <input type="number" name="items[${idx}][quantity]" id="qty-${idx}" value="${data?.quantity || 1}"
                           min="0.01" step="0.01" oninput="recalc()" required>
                </td>
                <td>
                    <input type="number" name="items[${idx}][unit_price]" id="price-${idx}" value="${data?.unit_price || '0.00'}"
                           min="0" step="0.01" oninput="recalc()" required>
                </td>
                <td>
                    <input type="number" name="items[${idx}][tax_rate]" id="tax-${idx}" value="${data?.tax_rate ?? '15.00'}"
                           min="0" max="100" step="0.01" oninput="recalc()">
                </td>
                <td class="r" id="lt-${idx}" style="font-weight:700;">${fmt(0)}</td>
                <td style="text-align:center;">
                    <button type="button" class="btn-remove-row" onclick="removeLine(this)" title="Remove">&times;</button>
                </td>`;
            tbody.appendChild(tr);

            if (data?.inventory_item_id) {
                const item = inventoryItems.find(i => i.id == data.inventory_item_id);
                if (item) attachItemSearch(idx, item);
                else attachItemSearch(idx, null);
            } else {
                attachItemSearch(idx, null);
            }

            recalc();
        }

        function removeLine(btn) {
            if (document.querySelectorAll('#line-items-body tr').length <= 1) return;
            btn.closest('tr').remove();
            recalc();
        }

        function recalc() {
            let subtotal = 0, tax = 0;
            document.querySelectorAll('#line-items-body tr').forEach(tr => {
                const qty     = tr.querySelector('input[name*="[quantity]"]');
                const price   = tr.querySelector('input[name*="[unit_price]"]');
                const taxRate = tr.querySelector('input[name*="[tax_rate]"]');
                if (!qty || !price) return;
                const q       = parseFloat(qty.value) || 0;
                const p       = parseFloat(price.value) || 0;
                const t       = parseFloat(taxRate?.value) || 0;
                const base    = q * p;
                const lineTax = base * (t / 100);
                subtotal += base;
                tax      += lineTax;
                const ltCell = tr.querySelector('[id^="lt-"]');
                if (ltCell) ltCell.textContent = fmt(base + lineTax);
            });
            document.getElementById('summary-subtotal').textContent = fmt(subtotal);
            document.getElementById('summary-tax').textContent      = fmt(tax);
            document.getElementById('summary-total').textContent    = fmt(subtotal + tax);
        }

        document.getElementById('sinv-form').addEventListener('submit', function (e) {
            const descs = [...document.querySelectorAll('input[name*="[description]"]')].some(i => !i.value.trim());
            if (descs) { e.preventDefault(); alert('Please enter a description for every line item.'); }
        });

        if (oldItems && Object.keys(oldItems).length > 0) {
            Object.values(oldItems).forEach(item => addLineItem(item));
        } else {
            addLineItem();
        }
    </script>
@endsection
