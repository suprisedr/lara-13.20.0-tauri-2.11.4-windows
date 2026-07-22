@extends('layouts.public')

@section('title', $company->registered_name . ' — Suppliers')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        /* ── Page-specific: supplier register ── */
        .supplier-status-badge {
            display: inline-block;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 5.5pt;
            letter-spacing: 0.06em;
            padding: 1pt 4pt;
            border: 0.5pt solid #16355c;
            color: #16355c;
        }
        .supplier-status-badge.off {
            color: #dc2626;
            border-color: #dc2626;
        }

        .supplier-unconfirmed {
            font-size: 5.5pt;
            font-weight: 700;
            background: #fef3c7;
            color: #92400e;
            padding: 1pt 4pt;
            margin-left: 4pt;
            vertical-align: middle;
        }

        .supplier-address-cell {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ── Supplier actions modal ── */
        .supplier-modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(22,53,92,0.45);
            z-index: 100;
            align-items: center;
            justify-content: center;
            padding: 10pt;
        }
        .supplier-modal-backdrop.open { display: flex; }

        .supplier-modal {
            background: #fff;
            width: 100%;
            max-width: 200pt;
            box-shadow: 0 8pt 24pt rgba(22,53,92,0.16);
            overflow: hidden;
            border-top: 2pt solid #16355c;
        }

        .supplier-modal-head {
            padding: 6pt 8pt 5pt;
            border-bottom: 1pt solid #c9dff0;
        }
        .supplier-modal-head p.label {
            font-size: 5.5pt;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #0079c8;
            margin: 0 0 1pt;
        }
        .supplier-modal-head h3 {
            font-size: 7.5pt;
            font-weight: 800;
            color: #16355c;
            margin: 0;
        }

        .supplier-modal-body {
            padding: 3pt;
            display: flex;
            flex-direction: column;
            gap: 1pt;
        }

        .supplier-modal-item {
            display: flex;
            align-items: center;
            gap: 5pt;
            padding: 3pt 6pt;
            font-size: 6.5pt;
            font-weight: 700;
            color: #16355c;
            text-decoration: none;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
            font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
            transition: background 0.12s, color 0.12s;
        }
        .supplier-modal-item svg { flex-shrink: 0; color: #7a90a5; transition: color 0.12s; }
        .supplier-modal-item:hover { background: #eef6fc; color: #16355c; }
        .supplier-modal-item:hover svg { color: #16355c; }

        .supplier-modal-foot {
            padding: 3pt 8pt 6pt;
        }
        .supplier-modal-cancel {
            display: block;
            width: 100%;
            text-align: center;
            padding: 3pt;
            border: 1px solid #c9dff0;
            background: #fff;
            color: #4a5f78;
            font-size: 6pt;
            font-weight: 700;
            cursor: pointer;
            font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
            transition: background 0.12s;
        }
        .supplier-modal-cancel:hover { background: #eef6fc; }
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
                                <div class="reg-doc-title">Suppliers</div>
                                <div class="reg-doc-subtitle">Purchases</div>
                            </div>
                            <a href="{{ route('companies.suppliers.create', $company) }}" class="reg-btn primary">
                                <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <line x1="12" y1="5" x2="12" y2="19" />
                                    <line x1="5" y1="12" x2="19" y2="12" />
                                </svg>
                                New Supplier
                            </a>
                        </div>

                        <hr class="reg-divider">

                        @if ($suppliers->isEmpty())
                            <div style="padding:20pt 10pt;text-align:center;">
                                <svg width="24" height="24" fill="none" stroke="#9cc3e0" stroke-width="1.5"
                                    stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"
                                    style="margin:0 auto 5pt;display:block;">
                                    <rect x="1" y="3" width="15" height="13" />
                                    <polygon points="16 8 20 8 23 11 23 16 16 16 16 8" />
                                    <circle cx="5.5" cy="18.5" r="2.5" />
                                    <circle cx="18.5" cy="18.5" r="2.5" />
                                </svg>
                                <p style="font-weight:700;color:#16355c;margin:0 0 2pt;font-size:7pt;">No suppliers yet</p>
                                <p style="font-size:6.5pt;margin:0 0 8pt;color:#7a90a5;">Add suppliers to track purchases and accounts payable.</p>
                                <a href="{{ route('companies.suppliers.create', $company) }}" class="reg-btn primary">
                                    Add Supplier
                                </a>
                            </div>
                        @else
                            <div style="overflow-x:auto;">
                                <table class="reg-table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Contact</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Address</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($suppliers as $supplier)
                                            <tr>
                                                <td style="font-weight:700;">
                                                    <a href="{{ route('companies.suppliers.show', [$company, $supplier]) }}" class="reg-link">{{ $supplier->name }}</a>
                                                    @if (!$supplier->is_confirmed)
                                                        <span class="supplier-unconfirmed">UNCONFIRMED</span>
                                                    @endif
                                                </td>
                                                <td>{{ $supplier->contact_name ?? '—' }}</td>
                                                <td>{{ $supplier->email ?? '—' }}</td>
                                                <td>{{ $supplier->phone ?? '—' }}</td>
                                                <td class="supplier-address-cell">{{ $supplier->address ?? '—' }}</td>
                                                <td>
                                                    <span class="supplier-status-badge{{ $supplier->is_active ? '' : ' off' }}">
                                                        {{ $supplier->is_active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </td>
                                                <td class="reg-row-actions">
                                                    <button type="button" class="reg-row-dots" aria-label="Actions for {{ $supplier->name }}"
                                                        onclick="openSupplierActions({{ $supplier->id }})">
                                                        &#x2026;
                                                    </button>
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

    {{-- Supplier actions modal --}}
    <div class="supplier-modal-backdrop" id="supplier-actions-backdrop" onclick="if (event.target === this) closeSupplierActions()">
        <div class="supplier-modal" role="dialog" aria-modal="true" aria-labelledby="supplier-actions-title">
            <div class="supplier-modal-head">
                <p class="label">Supplier</p>
                <h3 id="supplier-actions-title"></h3>
            </div>
            <div class="supplier-modal-body">
                <a class="supplier-modal-item" id="supplier-actions-profile" href="#">
                    <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="12" cy="7" r="4" />
                    </svg>
                    View Profile
                </a>
                <a class="supplier-modal-item" id="supplier-actions-edit" href="#">
                    <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                    </svg>
                    Edit Supplier
                </a>
                <a class="supplier-modal-item" id="supplier-actions-statement" href="#">
                    <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                        <line x1="9" y1="9" x2="15" y2="9" />
                        <line x1="9" y1="13" x2="15" y2="13" />
                    </svg>
                    Statement
                </a>
            </div>
            <div class="supplier-modal-foot">
                <button type="button" class="supplier-modal-cancel" onclick="closeSupplierActions()">Cancel</button>
            </div>
        </div>
    </div>

    @php
        $supplierActionsData = $suppliers->mapWithKeys(function ($supplier) use ($company) {
            return [
                $supplier->id => [
                    'name' => $supplier->name,
                    'profile' => route('companies.suppliers.show', [$company, $supplier]),
                    'edit' => route('companies.suppliers.edit', [$company, $supplier]),
                    'statement' => route('companies.suppliers.statement', [$company, $supplier]),
                ],
            ];
        });
    @endphp

    <script>
        const supplierActionsData = @json($supplierActionsData);

        function openSupplierActions(id) {
            const data = supplierActionsData[id];
            if (!data) return;

            document.getElementById('supplier-actions-title').textContent = data.name;
            document.getElementById('supplier-actions-profile').href = data.profile;
            document.getElementById('supplier-actions-edit').href = data.edit;
            document.getElementById('supplier-actions-statement').href = data.statement;

            document.getElementById('supplier-actions-backdrop').classList.add('open');
        }

        function closeSupplierActions() {
            document.getElementById('supplier-actions-backdrop').classList.remove('open');
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeSupplierActions();
        });
    </script>
@endsection
