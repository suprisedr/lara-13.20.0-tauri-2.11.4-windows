@extends('layouts.public')

@section('title', $company->registered_name . ' — Delivery Notes')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .dn-badge {
            display: inline-block;
            padding: 1pt 4pt;
            font-size: 5.5pt;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }
        .dn-badge-draft      { background:#f4fafc; color:#5a7186; }
        .dn-badge-dispatched { background:#fef3c7; color:#92400e; }
        .dn-badge-delivered  { background:#dcfce7; color:#15803d; }
        .dn-badge-cancelled  { background:#fee2e2; color:#b91c1c; }

        .list-search-wrap { position:relative; display:flex; align-items:center; gap:3pt; }
        .list-search-input {
            height:16pt; border:1px solid #9ec1f5; padding:0 10pt 0 5pt;
            font-size:6.5pt; font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif; color:#1a345b; background:#fff; width:140pt; box-sizing:border-box;
        }
        .list-search-input:focus { outline:none; border-color:#1a345b; }
        .list-search-clear {
            position:absolute; right:3pt; background:none; border:none;
            cursor:pointer; font-size:9pt; color:#6f869b; line-height:1; padding:0; display:none;
        }
        .list-search-clear:hover { color:#1a345b; }
        .list-search-count { font-size:6pt; color:#5a7186; white-space:nowrap; }
        mark.ls-hl { background:#fef08a; border-radius:2px; padding:0 1px; }

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
                    <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:4pt 8pt;font-size:7pt;font-weight:600;margin-bottom:10pt;">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="reg-doc">
                    <div class="reg-doc-body">
                        <div class="reg-mgmt-bar">
                            <div>
                                <div class="reg-doc-title">Delivery Notes</div>
                                <div class="reg-doc-subtitle">Sales</div>
                            </div>
                            <div style="display:flex;align-items:center;gap:6pt;">
                                <div class="list-search-wrap">
                                    <input type="text" class="list-search-input" id="ls-input" placeholder="Search…" autocomplete="off" oninput="listSearch(this,'dn-row')">
                                    <button class="list-search-clear" id="ls-clear" onclick="listSearch(null,'dn-row',true)" title="Clear">&times;</button>
                                </div>
                                <span class="list-search-count" id="ls-count"></span>
                                <a href="{{ route('companies.delivery-notes.create', $company) }}" class="reg-btn primary">
                                    <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    New Delivery Note
                                </a>
                            </div>
                        </div>

                        <hr class="reg-divider">

                        @if ($notes->isEmpty())
                            <div style="padding:24pt 14pt;text-align:center;">
                                <svg width="28" height="28" fill="none" stroke="#2674f2" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="margin:0 auto 6pt;display:block;">
                                    <rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 4v4h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
                                </svg>
                                <p style="font-weight:700;color:#5a7186;margin:0 0 2pt;font-size:7pt;">No delivery notes yet</p>
                                <p style="font-size:6.5pt;margin:0 0 8pt;color:#6f869b;">Create your first delivery note to accompany shipments.</p>
                                <a href="{{ route('companies.delivery-notes.create', $company) }}" class="reg-btn primary">
                                    Create Delivery Note
                                </a>
                            </div>
                        @else
                            <div style="overflow-x:auto;">
                                <table class="reg-table">
                                    <thead>
                                        <tr>
                                            <th data-sortable data-sort-type="text">DN # <span class="sort-arrow">&#8597;</span></th>
                                            <th data-sortable data-sort-type="text">Customer <span class="sort-arrow">&#8597;</span></th>
                                            <th data-sortable data-sort-type="date">Delivery Date <span class="sort-arrow">&#8597;</span></th>
                                            <th data-sortable data-sort-type="text">Invoice <span class="sort-arrow">&#8597;</span></th>
                                            <th data-sortable data-sort-type="text">Status <span class="sort-arrow">&#8597;</span></th>
                                            <th data-sortable data-sort-type="number">Items <span class="sort-arrow">&#8597;</span></th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($notes as $note)
                                            <tr class="dn-row" data-search="{{ strtolower($note->delivery_note_number . ' ' . (optional($note->customer)->name ?? $note->customer_name) . ' ' . $note->status) }}">
                                                <td style="font-weight:700;" data-sort="{{ strtolower($note->delivery_note_number) }}">{{ $note->delivery_note_number }}</td>
                                                <td data-sort="{{ strtolower(optional($note->customer)->name ?? $note->customer_name ?? '') }}">{{ optional($note->customer)->name ?? $note->customer_name }}</td>
                                                <td class="dim" data-sort="{{ $note->delivery_date->format('Y-m-d') }}">{{ $note->delivery_date->format('d M Y') }}</td>
                                                <td class="dim" data-sort="{{ strtolower(optional($note->invoice)->invoice_number ?? '') }}">
                                                    @if ($note->invoice)
                                                        <a href="{{ route('companies.invoices.show', [$company, $note->invoice]) }}" class="reg-link" style="font-size:7pt;">
                                                            {{ $note->invoice->invoice_number }}
                                                        </a>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td data-sort="{{ $note->status }}"><span class="dn-badge dn-badge-{{ $note->status }}">{{ $note->statusLabel() }}</span></td>
                                                <td class="dim" data-sort="{{ $note->items->count() }}">{{ $note->items->count() }}</td>
                                                <td>
                                                    <div class="reg-row-actions">
                                                        <button class="reg-row-dots" title="Actions">&#8943;</button>
                                                        <div class="reg-row-menu">
                                                            <a href="{{ route('companies.delivery-notes.show', [$company, $note]) }}">View</a>
                                                            <a href="{{ route('companies.delivery-notes.pdf', [$company, $note]) }}" target="_blank">PDF</a>
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
            const rows = document.querySelectorAll('tr.' + rowClass);
            let matches = 0;
            rows.forEach(row => {
                if (!q) { row.style.display = ''; return; }
                if (!(row.dataset.search || '').includes(q)) { row.style.display = 'none'; return; }
                row.style.display = ''; matches++;
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
                    const rows = Array.from(tbody.querySelectorAll('tr.dn-row'));
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
