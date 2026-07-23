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
        .dn-badge-draft      { background:#f3f4f6; color:#6b5b8a; }
        .dn-badge-dispatched { background:#fef3c7; color:#92400e; }
        .dn-badge-delivered  { background:#dcfce7; color:#15803d; }
        .dn-badge-cancelled  { background:#fee2e2; color:#b91c1c; }

        .list-search-wrap { position:relative; display:flex; align-items:center; gap:3pt; }
        .list-search-input {
            height:16pt; border:1px solid #c4b5fd; padding:0 10pt 0 5pt;
            font-size:6.5pt; font-family:Helvetica, Arial, "DejaVu Sans", sans-serif; color:#4c1d95; background:#fff; width:140pt; box-sizing:border-box;
        }
        .list-search-input:focus { outline:none; border-color:#4c1d95; }
        .list-search-clear {
            position:absolute; right:3pt; background:none; border:none;
            cursor:pointer; font-size:9pt; color:#8b7aad; line-height:1; padding:0; display:none;
        }
        .list-search-clear:hover { color:#4c1d95; }
        .list-search-count { font-size:6pt; color:#6b5b8a; white-space:nowrap; }
        mark.ls-hl { background:#fef08a; border-radius:2px; padding:0 1px; }
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
                                <svg width="28" height="28" fill="none" stroke="#a78bfa" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="margin:0 auto 6pt;display:block;">
                                    <rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 4v4h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
                                </svg>
                                <p style="font-weight:700;color:#6b5b8a;margin:0 0 2pt;font-size:7pt;">No delivery notes yet</p>
                                <p style="font-size:6.5pt;margin:0 0 8pt;color:#8b7aad;">Create your first delivery note to accompany shipments.</p>
                                <a href="{{ route('companies.delivery-notes.create', $company) }}" class="reg-btn primary">
                                    Create Delivery Note
                                </a>
                            </div>
                        @else
                            <div style="overflow-x:auto;">
                                <table class="reg-table">
                                    <thead>
                                        <tr>
                                            <th>DN #</th>
                                            <th>Customer</th>
                                            <th>Delivery Date</th>
                                            <th>Invoice</th>
                                            <th>Status</th>
                                            <th>Items</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($notes as $note)
                                            <tr class="dn-row" data-search="{{ strtolower($note->delivery_note_number . ' ' . (optional($note->customer)->name ?? $note->customer_name) . ' ' . $note->status) }}">
                                                <td style="font-weight:700;">{{ $note->delivery_note_number }}</td>
                                                <td>{{ optional($note->customer)->name ?? $note->customer_name }}</td>
                                                <td class="dim">{{ $note->delivery_date->format('d M Y') }}</td>
                                                <td class="dim">
                                                    @if ($note->invoice)
                                                        <a href="{{ route('companies.invoices.show', [$company, $note->invoice]) }}" class="reg-link" style="font-size:7pt;">
                                                            {{ $note->invoice->invoice_number }}
                                                        </a>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td><span class="dn-badge dn-badge-{{ $note->status }}">{{ $note->statusLabel() }}</span></td>
                                                <td class="dim">{{ $note->items->count() }}</td>
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

        document.addEventListener('click', function(e) {
            if (e.target.closest('.reg-row-dots')) {
                var menu = e.target.closest('.reg-row-actions').querySelector('.reg-row-menu');
                document.querySelectorAll('.reg-row-menu.open').forEach(function(m) { if (m !== menu) m.classList.remove('open'); });
                menu.classList.toggle('open');
                e.stopPropagation();
                return;
            }
            document.querySelectorAll('.reg-row-menu.open').forEach(function(m) { m.classList.remove('open'); });
        });
    </script>
@endsection
