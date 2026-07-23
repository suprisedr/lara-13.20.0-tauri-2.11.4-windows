@extends('layouts.public')

@section('title', $company->registered_name . ' — Credit Notes')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .cn-badge { display:inline-block; padding:1pt 4pt; font-size:6pt; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; }
        .cn-badge-draft   { background:#f3f4f6; color:#6b5b8a; }
        .cn-badge-issued  { background:#fef3c7; color:#92400e; }
        .cn-badge-applied { background:#dcfce7; color:#15803d; }
        .cn-badge-voided  { background:#fee2e2; color:#b91c1c; }

        .list-search-wrap { position:relative; display:flex; align-items:center; gap:3pt; }
        .list-search-input { height:16pt; border:1px solid #c4b5fd; padding:0 10pt 0 5pt; font-size:6.5pt; font-family:inherit; color:#4c1d95; background:#fff; width:140pt; box-sizing:border-box; }
        .list-search-input:focus { outline:none; border-color:#4c1d95; }
        .list-search-clear { position:absolute; right:3pt; background:none; border:none; cursor:pointer; font-size:9pt; color:#8b7aad; line-height:1; padding:0; display:none; }
        .list-search-clear:hover { color:#4c1d95; }
        .list-search-count { font-size:5.5pt; color:#6b5b8a; white-space:nowrap; }
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
                                <div class="reg-doc-title">Credit Notes</div>
                                <div class="reg-doc-subtitle">Sales</div>
                            </div>
                            <div style="display:flex;align-items:center;gap:6pt;">
                                <div class="list-search-wrap">
                                    <input type="text" class="list-search-input" id="ls-input" placeholder="Search..." autocomplete="off" oninput="listSearch(this,'cn-row')">
                                    <button class="list-search-clear" id="ls-clear" onclick="listSearch(null,'cn-row',true)" title="Clear">&times;</button>
                                </div>
                                <span class="list-search-count" id="ls-count"></span>
                                <a href="{{ route('companies.credit-notes.create', $company) }}" class="reg-btn primary">
                                    <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    New Credit Note
                                </a>
                            </div>
                        </div>

                        <hr class="reg-divider">

                        @if ($creditNotes->isEmpty())
                            <div style="padding:24pt 14pt;text-align:center;color:#8b7aad;font-size:7pt;">
                                <svg width="28" height="28" fill="none" stroke="#a78bfa" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="margin:0 auto 6pt;display:block;">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="11" y2="17"/>
                                </svg>
                                <p style="font-weight:700;color:#6b5b8a;margin:0 0 2pt;">No credit notes yet</p>
                                <p style="font-size:6.5pt;margin:0 0 8pt;">Issue a credit note when goods are returned or a billing correction is needed.</p>
                                <a href="{{ route('companies.credit-notes.create', $company) }}" class="reg-btn primary">
                                    Create Credit Note
                                </a>
                            </div>
                        @else
                            <div style="overflow-x:auto;">
                                <table class="reg-table">
                                    <thead>
                                        <tr>
                                            <th>CN #</th>
                                            <th>Customer</th>
                                            <th>Date</th>
                                            <th>Invoice</th>
                                            <th>Status</th>
                                            <th>Posted</th>
                                            <th class="amt">Total</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($creditNotes as $cn)
                                            <tr class="cn-row" data-search="{{ strtolower($cn->credit_note_number . ' ' . (optional($cn->customer)->name ?? $cn->customer_name) . ' ' . $cn->status) }}">
                                                <td style="font-weight:700;">{{ $cn->credit_note_number }}</td>
                                                <td>{{ optional($cn->customer)->name ?? $cn->customer_name }}</td>
                                                <td class="dim">{{ $cn->credit_note_date->format('d M Y') }}</td>
                                                <td class="dim">
                                                    @if ($cn->invoice)
                                                        <a href="{{ route('companies.invoices.show', [$company, $cn->invoice]) }}" class="reg-link" style="font-size:7pt;">
                                                            {{ $cn->invoice->invoice_number }}
                                                        </a>
                                                    @else —
                                                    @endif
                                                </td>
                                                <td><span class="cn-badge cn-badge-{{ $cn->status }}">{{ $cn->statusLabel() }}</span></td>
                                                <td>
                                                    @if ($cn->posting_transaction_id)
                                                        <span style="color:#16a34a;font-size:6pt;font-weight:700;">✓ Posted</span>
                                                    @else
                                                        <span style="color:#8b7aad;font-size:6pt;">—</span>
                                                    @endif
                                                </td>
                                                <td class="amt">
                                                    R {{ number_format($cn->items->sum(fn($i) => $i->quantity * $i->unit_price * (1 + ($i->tax_rate ?? 0) / 100)), 2) }}
                                                </td>
                                                <td>
                                                    <div class="reg-row-actions">
                                                        <button class="reg-row-dots" onclick="toggleRowMenu(this)" title="Actions">&#x22EE;</button>
                                                        <div class="reg-row-menu">
                                                            <a href="{{ route('companies.credit-notes.show', [$company, $cn]) }}">View</a>
                                                            <a href="{{ route('companies.credit-notes.pdf', [$company, $cn]) }}" target="_blank">PDF</a>
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

        function toggleRowMenu(btn) {
            const menu = btn.nextElementSibling;
            document.querySelectorAll('.reg-row-menu.open').forEach(m => { if (m !== menu) m.classList.remove('open'); });
            menu.classList.toggle('open');
        }
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.reg-row-actions')) {
                document.querySelectorAll('.reg-row-menu.open').forEach(m => m.classList.remove('open'));
            }
        });
    </script>
@endsection
