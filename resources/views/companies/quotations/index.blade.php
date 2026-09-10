@extends('layouts.public')

@section('title', $company->registered_name . ' — Quotations')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .quo-badge {
            display: inline-block;
            padding: 1pt 4pt;
            border-radius: 0;
            font-size: 5.5pt;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .quo-badge-draft {
            background: #fef9c3;
            color: #854d0e;
        }

        .quo-badge-sent {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .quo-badge-accepted {
            background: #dcfce7;
            color: #15803d;
        }

        .quo-badge-declined {
            background: #fee2e2;
            color: #b91c1c;
        }

        .quo-badge-expired {
            background: #f4fafc;
            color: #6f869b;
        }

        .list-search-wrap { position:relative; display:flex; align-items:center; gap:3pt; }
        .list-search-input {
            height:16pt; border:1px solid #9ec1f5; border-radius:0;
            padding:0 12pt 0 5pt; font-size:6.5pt; font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;
            color:#1a345b; background:#fff; width:150pt; box-sizing:border-box;
        }
        .list-search-input:focus { outline:none; border-color:#1a345b; }
        .list-search-clear {
            position:absolute; right:3pt; background:none; border:none;
            cursor:pointer; font-size:9pt; color:#6f869b; line-height:1; padding:0; display:none;
        }
        .list-search-clear:hover { color:#1a345b; }
        .list-search-count { font-size:6pt; color:#5a7186; white-space:nowrap; }
        mark.ls-hl { background:#fef08a; border-radius:2px; padding:0 1px; font-weight:inherit; }

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
                    <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:4pt 8pt;border-radius:0;font-size:7pt;font-weight:600;margin-bottom:12pt;">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="reg-doc">
                    <div class="reg-doc-body">
                        <div class="reg-mgmt-bar">
                            <div>
                                <div class="reg-doc-title">Quotations</div>
                                <div class="reg-doc-subtitle">Sales</div>
                            </div>
                            <div style="display:flex;align-items:center;gap:6pt;">
                                <div class="list-search-wrap">
                                    <input type="text" class="list-search-input" id="ls-input" placeholder="Search quotations…" autocomplete="off" oninput="listSearch(this,'quot-row')">
                                    <button class="list-search-clear" id="ls-clear" onclick="listSearch(null,'quot-row',true)" title="Clear">&times;</button>
                                </div>
                                <span class="list-search-count" id="ls-count"></span>
                                <a href="{{ route('companies.quotations.create', $company) }}" class="reg-btn primary">
                                    <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                        <line x1="12" y1="5" x2="12" y2="19" />
                                        <line x1="5" y1="12" x2="19" y2="12" />
                                    </svg>
                                    New Quotation
                                </a>
                            </div>
                        </div>

                        @if ($quotations->isEmpty())
                            <div class="reg-empty-state">
                                <svg width="28" height="28" fill="none" stroke="#2674f2" stroke-width="1.5"
                                    stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"
                                    style="display:block;margin:0 auto 0.75rem;">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                    <polyline points="14 2 14 8 20 8" />
                                    <path d="M9 15l2 2 4-4" />
                                </svg>
                                <p class="reg-empty-title">No quotations yet</p>
                                <p>Create your first quotation to get started.</p>
                                <a href="{{ route('companies.quotations.create', $company) }}" class="reg-btn primary">
                                    Create Quotation
                                </a>
                            </div>
                        @else
                            <div style="overflow-x:auto;">
                                <table class="reg-table">
                                    <thead>
                                        <tr>
                                            <th data-sortable data-sort-type="text">Quotation # <span class="sort-arrow">&#8597;</span></th>
                                            <th data-sortable data-sort-type="text">Customer <span class="sort-arrow">&#8597;</span></th>
                                            <th data-sortable data-sort-type="date">Date <span class="sort-arrow">&#8597;</span></th>
                                            <th data-sortable data-sort-type="date">Expiry <span class="sort-arrow">&#8597;</span></th>
                                            <th data-sortable data-sort-type="text">Status <span class="sort-arrow">&#8597;</span></th>
                                            <th class="amt" data-sortable data-sort-type="number">Total <span class="sort-arrow">&#8597;</span></th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($quotations as $quotation)
                                            <tr class="quot-row" data-search="{{ strtolower($quotation->quotation_number . ' ' . (optional($quotation->customer)->name ?? $quotation->customer_name ?? '') . ' ' . $quotation->status) }}">
                                                <td style="font-weight:700;" data-sort="{{ strtolower($quotation->quotation_number) }}">{{ $quotation->quotation_number }}</td>
                                                <td data-sort="{{ strtolower(optional($quotation->customer)->name ?? $quotation->customer_name ?? '') }}">{{ optional($quotation->customer)->name ?? $quotation->customer_name }}</td>
                                                <td class="dim" data-sort="{{ $quotation->quotation_date->format('Y-m-d') }}">{{ $quotation->quotation_date->format('d M Y') }}</td>
                                                <td class="dim" data-sort="{{ $quotation->expiry_date ? $quotation->expiry_date->format('Y-m-d') : '' }}">
                                                    {{ $quotation->expiry_date ? $quotation->expiry_date->format('d M Y') : '—' }}
                                                </td>
                                                <td data-sort="{{ $quotation->status }}">
                                                    <span class="quo-badge quo-badge-{{ $quotation->status }}">
                                                        {{ $quotation->status }}
                                                    </span>
                                                    @if ($quotation->converted_invoice_id)
                                                        <span style="font-size:5.5pt;color:#1a345b;font-weight:700;display:block;margin-top:1pt;">→ Invoiced</span>
                                                    @endif
                                                </td>
                                                <td class="amt" data-sort="{{ $quotation->total() }}">
                                                    R {{ number_format($quotation->total(), 2) }}
                                                </td>
                                                <td>
                                                    <div class="reg-row-actions">
                                                        <button class="reg-row-dots" title="Actions">&#x2026;</button>
                                                        <div class="reg-row-menu">
                                                            <a href="{{ route('companies.quotations.show', [$company, $quotation]) }}">View</a>
                                                            <a href="{{ route('companies.quotations.pdf', [$company, $quotation]) }}" target="_blank">PDF</a>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

            </main>
        </div>
    </div>

    @include('companies._row-actions')

    <script>
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
                    const rows = Array.from(tbody.querySelectorAll('tr.quot-row'));
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
