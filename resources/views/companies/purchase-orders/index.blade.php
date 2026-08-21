@extends('layouts.public')

@section('title', $company->registered_name . ' — Purchase Orders')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .inv-badge {
            display: inline-block;
            padding: 1pt 4pt;
            border-radius: 0;
            font-size: 6pt;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .inv-badge-draft { background: #fef9c3; color: #854d0e; }
        .inv-badge-sent { background: #dbeafe; color: #1d4ed8; }
        .inv-badge-acknowledged { background: #f4fafc; color: #4338ca; }
        .inv-badge-partially_received { background: #fef3c7; color: #b45309; }
        .inv-badge-received { background: #dcfce7; color: #15803d; }
        .inv-badge-cancelled { background: #fee2e2; color: #b91c1c; }

        .list-search-wrap { position:relative; display:flex; align-items:center; gap:3pt; }
        .list-search-input {
            height:14pt; border:1px solid #9ec1f5; border-radius:0;
            padding:0 10pt 0 5pt; font-size:6.5pt; font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;
            color:#1a345b; background:#fff; width:140pt; box-sizing:border-box;
        }
        .list-search-input:focus { outline:none; border-color:#1a345b; }
        .list-search-clear {
            position:absolute; right:3pt; background:none; border:none;
            cursor:pointer; font-size:9pt; color:#6f869b; line-height:1; padding:0; display:none;
        }
        .list-search-clear:hover { color:#1a345b; }
        .list-search-count { font-size:5.5pt; color:#5a7186; white-space:nowrap; }
        mark.ls-hl { background:#fef08a; border-radius:0; padding:0 1px; font-weight:inherit; }

        .status-filter { display:flex; gap:3pt; flex-wrap:wrap; margin-bottom:8pt; }
        .status-filter a {
            font-size:6pt; padding:2pt 6pt; border:1px solid #9ec1f5; color:#5a7186;
            text-decoration:none; font-weight:600; text-transform:uppercase; letter-spacing:0.05em;
        }
        .status-filter a:hover { background:#f4fafc; }
        .status-filter a.active { background:#005bf0; color:#fff; border-color:#005bf0; }

        .reg-table th[data-sortable] { cursor: pointer; user-select: none; white-space: nowrap; }
        .reg-table th[data-sortable]:hover { color: #1a345b; }
        .sort-arrow { font-size: 5pt; margin-left: 2pt; color: #6f869b; }
        .sort-arrow.active { color: #1a345b; }
    </style>
@endpush

@section('content')
    <div class="co-wrap">

        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">

                @if (session('success'))
                    <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:4pt 8pt;border-radius:0;font-size:7pt;font-weight:600;margin-bottom:10pt;">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="reg-doc">
                    <div class="reg-doc-body">
                        <div class="reg-mgmt-bar">
                            <div>
                                <div class="reg-doc-title">Purchase Orders</div>
                                <div class="reg-doc-subtitle">Purchases</div>
                            </div>
                            <div style="display:flex;align-items:center;gap:6pt;">
                                <div class="list-search-wrap">
                                    <input type="text" class="list-search-input" id="ls-input" placeholder="Search POs..." autocomplete="off" oninput="listSearch(this,'po-row')">
                                    <button class="list-search-clear" id="ls-clear" onclick="listSearch(null,'po-row',true)" title="Clear">&times;</button>
                                </div>
                                <span class="list-search-count" id="ls-count"></span>
                                <a href="{{ route('companies.purchase-orders.create', $company) }}" class="reg-btn primary">
                                    <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                        <line x1="12" y1="5" x2="12" y2="19" />
                                        <line x1="5" y1="12" x2="19" y2="12" />
                                    </svg>
                                    New Purchase Order
                                </a>
                            </div>
                        </div>

                        <div class="status-filter">
                            <a href="{{ route('companies.purchase-orders.index', $company) }}" class="{{ !request('status') ? 'active' : '' }}">All</a>
                            @foreach (\App\Enums\PurchaseOrderStatus::cases() as $s)
                                <a href="{{ route('companies.purchase-orders.index', [$company, 'status' => $s->value]) }}" class="{{ request('status') === $s->value ? 'active' : '' }}">{{ $s->label() }}</a>
                            @endforeach
                        </div>

                        <hr class="reg-divider">

                        @if ($purchaseOrders->isEmpty())
                            <div style="padding:20pt 10pt;text-align:center;color:#6f869b;font-size:7pt;">
                                <svg width="28" height="28" fill="none" stroke="#2674f2" stroke-width="1.5"
                                    stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"
                                    style="margin:0 auto 6pt;display:block;">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                    <polyline points="14 2 14 8 20 8" />
                                    <line x1="16" y1="13" x2="8" y2="13" />
                                    <line x1="16" y1="17" x2="8" y2="17" />
                                    <line x1="10" y1="9" x2="8" y2="9" />
                                </svg>
                                <p style="font-weight:700;color:#5a7186;margin:0 0 2pt;">No purchase orders yet</p>
                                <p style="font-size:6.5pt;margin:0 0 6pt;">Create your first purchase order to get started.</p>
                                <a href="{{ route('companies.purchase-orders.create', $company) }}" class="reg-btn primary">
                                    Create Purchase Order
                                </a>
                            </div>
                        @else
                            <div style="overflow-x:auto;">
                                <table class="reg-table">
                                    <thead>
                                        <tr>
                                            <th data-sortable data-sort-type="text">PO # <span class="sort-arrow">&#8597;</span></th>
                                            <th data-sortable data-sort-type="text">Supplier <span class="sort-arrow">&#8597;</span></th>
                                            <th data-sortable data-sort-type="date">Order Date <span class="sort-arrow">&#8597;</span></th>
                                            <th data-sortable data-sort-type="date">Expected Delivery <span class="sort-arrow">&#8597;</span></th>
                                            <th data-sortable data-sort-type="text">Status <span class="sort-arrow">&#8597;</span></th>
                                            <th class="amt" data-sortable data-sort-type="number">Total <span class="sort-arrow">&#8597;</span></th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($purchaseOrders as $po)
                                            <tr class="po-row" data-search="{{ strtolower($po->po_number . ' ' . (optional($po->supplier)->name ?? '') . ' ' . $po->status->label()) }}">
                                                <td style="font-weight:700;" data-sort="{{ strtolower($po->po_number) }}">{{ $po->po_number }}</td>
                                                <td data-sort="{{ strtolower(optional($po->supplier)->name ?? '') }}">{{ optional($po->supplier)->name ?? '—' }}</td>
                                                <td class="dim" data-sort="{{ $po->order_date->format('Y-m-d') }}">{{ $po->order_date->format('d M Y') }}</td>
                                                <td class="dim" data-sort="{{ $po->expected_delivery_date ? $po->expected_delivery_date->format('Y-m-d') : '' }}">
                                                    {{ $po->expected_delivery_date ? $po->expected_delivery_date->format('d M Y') : '—' }}
                                                </td>
                                                <td data-sort="{{ $po->status->value }}">
                                                    <span class="inv-badge inv-badge-{{ $po->status->value }}">
                                                        {{ $po->status->label() }}
                                                    </span>
                                                </td>
                                                <td class="amt" data-sort="{{ (float) $po->total }}">
                                                    R {{ number_format((float) $po->total, 2) }}
                                                </td>
                                                <td>
                                                    <div class="reg-row-actions">
                                                        <button class="reg-row-dots" title="Actions">&#x2026;</button>
                                                        <div class="reg-row-menu">
                                                            <a href="{{ route('companies.purchase-orders.show', [$company, $po]) }}">View</a>
                                                            <a href="{{ route('companies.purchase-orders.pdf', [$company, $po]) }}" target="_blank">PDF</a>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div style="margin-top:8pt;">
                                {{ $purchaseOrders->withQueryString()->links() }}
                            </div>
                        @endif
                    </div>
                </div>

            </main>
        </div>
    </div>

    @include('companies._row-actions')

    <script>
        /* Client-side list search */
        function listSearch(inputEl, rowClass, clear) {
            const input = inputEl ?? document.getElementById('ls-input');
            if (clear) { input.value = ''; }
            const q = input.value.trim().toLowerCase();
            document.getElementById('ls-clear').style.display = q ? 'inline' : 'none';
            const re = q ? new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi') : null;
            const rows = document.querySelectorAll('tr.' + rowClass);
            let matches = 0;
            rows.forEach(row => {
                row.querySelectorAll('.ls-hl').forEach(m => { m.outerHTML = m.textContent; });
                if (!q) { row.style.display = ''; return; }
                if (!(row.dataset.search || '').includes(q)) { row.style.display = 'none'; return; }
                row.style.display = ''; matches++;
                Array.from(row.querySelectorAll('td')).slice(0, 2).forEach(td => {
                    re.lastIndex = 0;
                    const walker = document.createTreeWalker(td, NodeFilter.SHOW_TEXT);
                    const nodes = []; let n;
                    while ((n = walker.nextNode())) nodes.push(n);
                    nodes.forEach(tn => {
                        re.lastIndex = 0;
                        if (!re.test(tn.textContent)) return;
                        re.lastIndex = 0;
                        const span = document.createElement('span');
                        span.innerHTML = tn.textContent.replace(re, '<mark class="ls-hl">$1</mark>');
                        tn.parentNode.replaceChild(span, tn);
                    });
                    re.lastIndex = 0;
                });
            });
            document.getElementById('ls-count').textContent = q ? (matches + ' match' + (matches !== 1 ? 'es' : '')) : '';
        }

        /* ── Column sorting ── */
        (function() {
            let sortCol = -1, sortDir = 'asc';
            document.querySelectorAll('th[data-sortable]').forEach(th => {
                th.addEventListener('click', function() {
                    const colIdx = Array.from(th.parentNode.children).indexOf(th);
                    if (sortCol === colIdx) {
                        sortDir = sortDir === 'asc' ? 'desc' : 'asc';
                    } else {
                        sortCol = colIdx;
                        sortDir = 'asc';
                    }
                    document.querySelectorAll('.sort-arrow').forEach(a => { a.classList.remove('active'); a.innerHTML = '&#8597;'; });
                    const arrow = th.querySelector('.sort-arrow');
                    if (arrow) { arrow.classList.add('active'); arrow.innerHTML = sortDir === 'asc' ? '&#8593;' : '&#8595;'; }

                    const type = th.dataset.sortType || 'text';
                    const tbody = document.querySelector('.reg-table tbody');
                    const rows = Array.from(tbody.querySelectorAll('tr.po-row'));
                    rows.sort((a, b) => {
                        const aVal = a.children[colIdx]?.dataset.sort || '';
                        const bVal = b.children[colIdx]?.dataset.sort || '';
                        let cmp = 0;
                        if (type === 'number') {
                            cmp = (parseFloat(aVal) || 0) - (parseFloat(bVal) || 0);
                        } else {
                            cmp = aVal.localeCompare(bVal);
                        }
                        return sortDir === 'asc' ? cmp : -cmp;
                    });
                    rows.forEach(r => tbody.appendChild(r));
                });
            });
        })();
    </script>
@endsection
