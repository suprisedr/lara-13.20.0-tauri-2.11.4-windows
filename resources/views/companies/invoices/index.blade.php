@extends('layouts.public')

@section('title', $company->registered_name . ' — Invoices')
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

        .inv-badge-draft {
            background: #fef9c3;
            color: #854d0e;
        }

        .inv-badge-pending {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .inv-badge-paid {
            background: #dcfce7;
            color: #15803d;
        }

        .inv-badge-partially_paid {
            background: #fef3c7;
            color: #b45309;
        }

        .inv-badge-overdue {
            background: #fee2e2;
            color: #b91c1c;
        }

        .inv-badge-voided {
            background: #f3f4f6;
            color: #6b5b8a;
        }

        .inv-badge-write_off {
            background: #ede9fe;
            color: #4c1d95;
        }

        .list-search-wrap { position:relative; display:flex; align-items:center; gap:3pt; }
        .list-search-input {
            height:14pt; border:1px solid #c4b5fd; border-radius:0;
            padding:0 10pt 0 5pt; font-size:6.5pt; font-family:Helvetica, Arial, "DejaVu Sans", sans-serif;
            color:#4c1d95; background:#fff; width:140pt; box-sizing:border-box;
        }
        .list-search-input:focus { outline:none; border-color:#4c1d95; }
        .list-search-clear {
            position:absolute; right:3pt; background:none; border:none;
            cursor:pointer; font-size:9pt; color:#8b7aad; line-height:1; padding:0; display:none;
        }
        .list-search-clear:hover { color:#4c1d95; }
        .list-search-count { font-size:5.5pt; color:#6b5b8a; white-space:nowrap; }
        mark.ls-hl { background:#fef08a; border-radius:0; padding:0 1px; font-weight:inherit; }
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
                                <div class="reg-doc-title">Invoices</div>
                                <div class="reg-doc-subtitle">Sales</div>
                            </div>
                            <div style="display:flex;align-items:center;gap:6pt;">
                                <div class="list-search-wrap">
                                    <input type="text" class="list-search-input" id="ls-input" placeholder="Search invoices…" autocomplete="off" oninput="listSearch(this,'inv-row')">
                                    <button class="list-search-clear" id="ls-clear" onclick="listSearch(null,'inv-row',true)" title="Clear">&times;</button>
                                </div>
                                <span class="list-search-count" id="ls-count"></span>
                                <a href="{{ route('companies.invoices.create', $company) }}" class="reg-btn primary">
                                    <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                        <line x1="12" y1="5" x2="12" y2="19" />
                                        <line x1="5" y1="12" x2="19" y2="12" />
                                    </svg>
                                    New Invoice
                                </a>
                            </div>
                        </div>

                        <hr class="reg-divider">

                        @if ($invoices->isEmpty())
                            <div style="padding:20pt 10pt;text-align:center;color:#8b7aad;font-size:7pt;">
                                <svg width="28" height="28" fill="none" stroke="#a78bfa" stroke-width="1.5"
                                    stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"
                                    style="margin:0 auto 6pt;display:block;">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                    <polyline points="14 2 14 8 20 8" />
                                    <line x1="16" y1="13" x2="8" y2="13" />
                                    <line x1="16" y1="17" x2="8" y2="17" />
                                    <line x1="10" y1="9" x2="8" y2="9" />
                                </svg>
                                <p style="font-weight:700;color:#6b5b8a;margin:0 0 2pt;">No invoices yet</p>
                                <p style="font-size:6.5pt;margin:0 0 6pt;">Create your first invoice to get started.</p>
                                <a href="{{ route('companies.invoices.create', $company) }}" class="reg-btn primary">
                                    Create Invoice
                                </a>
                            </div>
                        @else
                            <div style="overflow-x:auto;">
                                <table class="reg-table">
                                    <thead>
                                        <tr>
                                            <th>Invoice #</th>
                                            <th>Customer</th>
                                            <th>Date</th>
                                            <th>Due Date</th>
                                            <th>Status</th>
                                            <th class="amt">Total</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($invoices as $invoice)
                                            <tr class="inv-row" data-search="{{ strtolower($invoice->invoice_number . ' ' . (optional($invoice->customer)->name ?? $invoice->customer_name ?? '') . ' ' . \App\Enums\InvoiceStatus::from($invoice->status)->label()) }}">
                                                <td style="font-weight:700;">{{ $invoice->invoice_number }}</td>
                                                <td>{{ optional($invoice->customer)->name ?? $invoice->customer_name }}</td>
                                                <td class="dim">{{ $invoice->invoice_date->format('d M Y') }}</td>
                                                <td class="dim">
                                                    {{ $invoice->due_date ? $invoice->due_date->format('d M Y') : '—' }}
                                                </td>
                                                <td>
                                                    <span class="inv-badge inv-badge-{{ $invoice->status }}">
                                                        {{ \App\Enums\InvoiceStatus::from($invoice->status)->label() }}
                                                    </span>
                                                </td>
                                                <td class="amt">
                                                    R {{ number_format($invoice->total(), 2) }}
                                                </td>
                                                <td>
                                                    <div class="reg-row-actions">
                                                        <button class="reg-row-dots" title="Actions">&#x2026;</button>
                                                        <div class="reg-row-menu">
                                                            <a href="{{ route('companies.invoices.show', [$company, $invoice]) }}">View</a>
                                                            <a href="{{ route('companies.invoices.pdf', [$company, $invoice]) }}" target="_blank">PDF</a>
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
        /* ── Row-action three-dot menus ── */
        document.addEventListener('click', function(e) {
            if (e.target.closest('.reg-row-dots')) {
                e.stopPropagation();
                var menu = e.target.closest('.reg-row-actions').querySelector('.reg-row-menu');
                var open = menu.classList.contains('open');
                document.querySelectorAll('.reg-row-menu.open').forEach(function(m) { m.classList.remove('open'); });
                if (!open) menu.classList.add('open');
                return;
            }
            document.querySelectorAll('.reg-row-menu.open').forEach(function(m) { m.classList.remove('open'); });
        });

        /* ── Client-side list search ── */
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
    </script>
@endsection
