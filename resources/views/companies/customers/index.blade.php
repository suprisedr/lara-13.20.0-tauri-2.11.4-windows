@extends('layouts.public')

@section('title', $company->registered_name . ' — Customers')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        /* ── Page-specific: compact table tweaks ──────────────── */
        table.reg-table.compact-table { table-layout: fixed; }
        table.reg-table.compact-table thead th { white-space: nowrap; }
        table.reg-table.compact-table tbody td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        table.reg-table.compact-table td.address { max-width: 240px; }

        /* ── Page-specific: kebab button ──────────────────────── */
        .kebab-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 0;
            border: 1px solid transparent;
            background: transparent;
            color: #6f869b;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s, color 0.15s;
        }
        .kebab-btn:hover,
        .kebab-btn.active {
            background: #f4fafc;
            border-color: rgba(26, 52, 91,0.18);
            color: #1a345b;
        }

        /* ── Page-specific: actions modal ─────────────────────── */
        .actions-modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(26, 52, 91,0.45);
            z-index: 100;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .actions-modal-backdrop.open { display: flex; }

        .actions-modal {
            background: #fff;
            border-radius: 0;
            width: 100%;
            max-width: 320px;
            box-shadow: 0 12px 32px rgba(26, 52, 91,0.18);
            overflow: hidden;
            border-top: 2pt solid #1a345b;
        }

        .actions-modal-head {
            padding: 1rem 1.25rem 0.85rem;
            border-bottom: 1pt solid #9ec1f5;
        }
        .actions-modal-head p.label {
            font-size: 6pt;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #1a345b;
            margin: 0 0 0.25rem;
        }
        .actions-modal-head h3 {
            font-size: 9pt;
            font-weight: 800;
            color: #1a345b;
            margin: 0;
        }

        .actions-modal-body {
            padding: 0.6rem;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .actions-modal-item {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.65rem 0.75rem;
            border-radius: 0;
            font-size: 7.5pt;
            font-weight: 700;
            color: #1a345b;
            text-decoration: none;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
            font-family: inherit;
            transition: background 0.12s, color 0.12s;
        }
        .actions-modal-item svg { flex-shrink: 0; color: #6f869b; transition: color 0.12s; }
        .actions-modal-item:hover { background: #f4fafc; color: #1a345b; }
        .actions-modal-item:hover svg { color: #1a345b; }

        .actions-modal-foot { padding: 0.6rem 1.25rem 1rem; }
        .actions-modal-cancel {
            display: block;
            width: 100%;
            text-align: center;
            padding: 0.55rem;
            border-radius: 0;
            border: 1px solid #9ec1f5;
            background: #fff;
            color: #5a7186;
            font-size: 7pt;
            font-weight: 700;
            cursor: pointer;
            font-family: inherit;
            transition: background 0.12s;
        }
        .actions-modal-cancel:hover { background: #f4fafc; }

        /* ── Page-specific: search ────────────────────────────── */
        .list-search-wrap { position:relative; display:flex; align-items:center; gap:0.4rem; }
        .list-search-input {
            height:2rem; border:1px solid #9ec1f5; border-radius:0;
            padding:0 1.6rem 0 0.6rem; font-size:6.5pt; font-family:inherit;
            color:#1a345b; background:#fff; width:200px; box-sizing:border-box;
        }
        .list-search-input:focus { outline:none; border-color:#1a345b; }
        .list-search-clear {
            position:absolute; right:0.35rem; background:none; border:none;
            cursor:pointer; font-size:0.9rem; color:#6f869b; line-height:1; padding:0; display:none;
        }
        .list-search-clear:hover { color:#1a345b; }
        .list-search-count { font-size:6pt; color:#5a7186; white-space:nowrap; }
        mark.ls-hl { background:#fef08a; border-radius:2px; padding:0 1px; font-weight:inherit; }
    </style>
@endpush

@section('content')
    <div class="co-wrap">

        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">

                @if (session('success'))
                    <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:4pt 8pt;border-radius:0;font-size:7pt;font-weight:600;margin-bottom:1.5rem;">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="co-card">
                    <div class="co-card-head">
                        <div>
                            <div class="co-section-heading"><p class="co-section-label">Sales</p><h2>Customers</h2></div>
                        </div>
                        <div style="display:flex;align-items:center;gap:0.75rem;">
                        <div class="list-search-wrap">
                            <input type="text" class="list-search-input" id="ls-input" placeholder="Search customers…" autocomplete="off" oninput="listSearch(this,'cust-row')">
                            <button class="list-search-clear" id="ls-clear" onclick="listSearch(null,'cust-row',true)" title="Clear">&times;</button>
                        </div>
                        <span class="list-search-count" id="ls-count"></span>
                        <a href="{{ route('companies.customers.create', $company) }}"
                            class="reg-btn primary"
                            style="display:inline-flex;align-items:center;gap:0.45rem;font-size:7pt;text-decoration:none;">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <line x1="12" y1="5" x2="12" y2="19" />
                                <line x1="5" y1="12" x2="19" y2="12" />
                            </svg>
                            New Customer
                        </a>
                        </div>
                    </div>

                    @if ($customers->isEmpty())
                        <div class="reg-empty-state">
                            <svg width="36" height="36" fill="none" stroke="#6f869b" stroke-width="1.5"
                                stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"
                                style="display:block;margin:0 auto 0.75rem;">
                                <path d="M12 2a7 7 0 0 1 7 7v3a7 7 0 0 1-14 0V9a7 7 0 0 1 7-7z" />
                                <path d="M5 22c0-3 2.5-5.5 7-5.5S19 19 19 22" />
                            </svg>
                            <p class="reg-empty-title">No customers yet</p>
                            <p>Create customers and attach them to invoices.</p>
                            <a href="{{ route('companies.customers.create', $company) }}" class="reg-btn primary">
                                Add Customer
                            </a>
                        </div>
                    @else
                        <div style="overflow-x:auto;">
                            <table class="reg-table compact-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Contact</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th class="address">Address</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($customers as $customer)
                                        <tr class="cust-row" data-search="{{ strtolower($customer->name . ' ' . ($customer->contact_name ?? '') . ' ' . ($customer->email ?? '') . ' ' . ($customer->phone ?? '')) }}">
                                            <td style="font-weight:700;">
                                                <a href="{{ route('companies.customers.show', [$company, $customer]) }}" class="reg-link">{{ $customer->name }}</a>
                                            </td>
                                            <td>{{ $customer->contact_name ?? '—' }}</td>
                                            <td>{{ $customer->email ?? '—' }}</td>
                                            <td>{{ $customer->phone ?? '—' }}</td>
                                            <td class="address">{{ $customer->address ?? '—' }}</td>
                                            <td>
                                                <span class="reg-status{{ $customer->is_active ? '' : ' disposed' }}">
                                                    {{ $customer->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td class="actions">
                                                <button type="button" class="kebab-btn" aria-label="Actions for {{ $customer->name }}"
                                                    onclick="openCustomerActions({{ $customer->id }})">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                                        <circle cx="12" cy="5" r="2" />
                                                        <circle cx="12" cy="12" r="2" />
                                                        <circle cx="12" cy="19" r="2" />
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

            </main>
        </div>
    </div>

    {{-- Customer actions modal --}}
    <div class="actions-modal-backdrop" id="customer-actions-backdrop" onclick="if (event.target === this) closeCustomerActions()">
        <div class="actions-modal" role="dialog" aria-modal="true" aria-labelledby="customer-actions-title">
            <div class="actions-modal-head">
                <p class="label">Customer</p>
                <h3 id="customer-actions-title"></h3>
            </div>
            <div class="actions-modal-body">
                <a class="actions-modal-item" id="customer-actions-profile" href="#">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                    View Profile
                </a>
                <a class="actions-modal-item" id="customer-actions-edit" href="#">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                    </svg>
                    Edit Customer
                </a>
                <a class="actions-modal-item" id="customer-actions-invoice" href="#">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                        <polyline points="14 2 14 8 20 8" />
                        <line x1="12" y1="18" x2="12" y2="12" />
                        <line x1="9" y1="15" x2="15" y2="15" />
                    </svg>
                    New Invoice
                </a>
                <a class="actions-modal-item" id="customer-actions-quotation" href="#">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                        <polyline points="14 2 14 8 20 8" />
                        <line x1="9" y1="13" x2="15" y2="13" />
                        <line x1="9" y1="17" x2="15" y2="17" />
                    </svg>
                    New Quotation
                </a>
                <a class="actions-modal-item" id="customer-actions-statement" href="#">
                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                        <line x1="9" y1="9" x2="15" y2="9" />
                        <line x1="9" y1="13" x2="15" y2="13" />
                    </svg>
                    Statement
                </a>
            </div>
            <div class="actions-modal-foot">
                <button type="button" class="actions-modal-cancel" onclick="closeCustomerActions()">Cancel</button>
            </div>
        </div>
    </div>

    @php
        $customerActionsData = $customers->mapWithKeys(function ($customer) use ($company) {
            return [
                $customer->id => [
                    'name' => $customer->name,
                    'profile' => route('companies.customers.show', [$company, $customer]),
                    'edit' => route('companies.customers.edit', [$company, $customer]),
                    'invoice' => route('companies.invoices.create', $company) . '?customer_id=' . $customer->id,
                    'quotation' => route('companies.quotations.create', $company) . '?customer_id=' . $customer->id,
                    'statement' => route('companies.customers.statement', [$company, $customer]),
                ],
            ];
        });
    @endphp

    <script>
        const customerActionsData = @json($customerActionsData);

        function openCustomerActions(id) {
            const data = customerActionsData[id];
            if (!data) return;

            document.getElementById('customer-actions-title').textContent = data.name;
            document.getElementById('customer-actions-profile').href = data.profile;
            document.getElementById('customer-actions-edit').href = data.edit;
            document.getElementById('customer-actions-invoice').href = data.invoice;
            document.getElementById('customer-actions-quotation').href = data.quotation;
            document.getElementById('customer-actions-statement').href = data.statement;

            document.getElementById('customer-actions-backdrop').classList.add('open');
        }

        function closeCustomerActions() {
            document.getElementById('customer-actions-backdrop').classList.remove('open');
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeCustomerActions();
        });

        function listSearch(inputEl, rowClass, clear) {
            const input = inputEl ?? document.getElementById('ls-input');
            if (clear) { input.value = ''; }
            const q = input.value.trim().toLowerCase();
            const clearBtn   = document.getElementById('ls-clear');
            const countLabel = document.getElementById('ls-count');
            clearBtn.style.display = q ? 'inline' : 'none';

            const re = q ? new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi') : null;
            const rows = document.querySelectorAll('tr.' + rowClass);
            let matches = 0;

            rows.forEach(row => {
                row.querySelectorAll('.ls-hl').forEach(m => { m.outerHTML = m.textContent; });
                if (!q) { row.style.display = ''; return; }

                const haystack = row.dataset.search || '';
                if (!haystack.includes(q)) { row.style.display = 'none'; return; }

                row.style.display = '';
                matches++;

                // Highlight first two data cells (number/name columns)
                Array.from(row.querySelectorAll('td')).slice(0, 2).forEach(td => {
                    re.lastIndex = 0;
                    const walker = document.createTreeWalker(td, NodeFilter.SHOW_TEXT);
                    const nodes = [];
                    let n;
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

            countLabel.textContent = q ? (matches + ' match' + (matches !== 1 ? 'es' : '')) : '';
        }
    </script>
@endsection
