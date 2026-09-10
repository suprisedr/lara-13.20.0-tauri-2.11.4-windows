@extends('layouts.public')

@section('title', $company->registered_name . ' — ' . $supplier->name)
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        /* ── Management bar ─────────────────────────────────────── */
        .inv-mgmt-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }

        .inv-mgmt-bar a {
            font-size: 0.78rem;
            color: #5a7186;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            transition: color 0.15s;
        }

        .inv-mgmt-bar a:hover { color: #000; }

        .mgmt-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: #fff;
            border: 1px solid #000;
            color: #000;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 0.4rem 0.85rem;
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
        }

        .mgmt-btn:hover { background: #000; color: #fff; }

        .mgmt-btn.primary {
            background: #000;
            color: #fff;
        }

        .mgmt-btn.primary:hover { background: #1a345b; }

        /* ── Profile document (PDF-style) ───────────────────────── */
        .cust-doc {
            background: #fff;
            border: 1px solid #d3e2f5;
            font-family:'Century Gothic','URW Gothic','Avant Garde',Futura,'Avenir Next',Avenir,'Trebuchet MS',Helvetica,Arial,'DejaVu Sans',sans-serif;
            color: #000;
            font-size: 0.78rem;
            line-height: 1.45;
        }

        .cust-doc-body {
            padding: 2rem 2.25rem;
        }

        /* ── Header ──────────────────────────────────────────────── */
        .cust-header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0.75rem;
        }

        .cust-name-fallback {
            font-size: 1.3rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .cust-address-block {
            text-align: right;
            font-size: 0.78rem;
            line-height: 1.55;
        }

        .cust-meta-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-label {
            font-weight: 700;
            font-size: 0.78rem;
            display: inline-block;
            width: 110px;
        }

        .meta-value {
            font-size: 0.78rem;
        }

        .doc-title {
            font-size: 1.3rem;
            font-weight: 800;
            text-align: right;
            margin-bottom: 0.25rem;
            letter-spacing: 0.04em;
        }

        .doc-meta-line {
            text-align: right;
            font-size: 0.78rem;
        }

        .status-box {
            display: inline-block;
            font-weight: 700;
            text-transform: uppercase;
            border: 1px solid #000;
            padding: 0.08rem 0.5rem;
            font-size: 0.72rem;
            letter-spacing: 0.08em;
            margin-top: 0.35rem;
        }

        .status-box.inactive { color: #6f869b; border-color: #bfbfbf; }

        .divider {
            border: none;
            border-top: 2px solid #000;
            margin: 1rem 0 1.25rem 0;
        }

        .divider.light {
            border-top: 1px solid #d3e2f5;
            margin: 1.25rem 0;
        }

        /* ── Summary line ─────────────────────────────────────────── */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0.25rem;
        }

        .summary-table td {
            padding: 0 1.25rem 0 0;
            font-size: 0.78rem;
            vertical-align: top;
        }

        .summary-table .lbl {
            display: block;
            font-weight: 700;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.15rem;
        }

        .summary-table .amt {
            font-size: 0.95rem;
            font-weight: 800;
        }

        .summary-table .amt.credit { color: #15803d; }
        .summary-table .amt.debit { color: #b91c1c; }

        /* ── Section headers ─────────────────────────────────────── */
        .section-header {
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border-bottom: 1.5px solid #000;
            padding-bottom: 0.25rem;
            margin-bottom: 0.5rem;
        }

        .info-section {
            margin-top: 1.25rem;
        }

        /* ── Tables ───────────────────────────────────────────────── */
        table.cust-items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0.25rem;
        }

        table.cust-items-table thead td {
            font-weight: 700;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1.5px solid #000;
            padding-bottom: 0.45rem;
        }

        table.cust-items-table thead td.amt { text-align: right; }

        table.cust-items-table tbody td {
            padding: 0.55rem 0;
            font-size: 0.78rem;
            border-bottom: 1px solid #d3e2f5;
            vertical-align: top;
        }

        table.cust-items-table tbody td.amt {
            text-align: right;
            font-family: 'Courier New', monospace;
            white-space: nowrap;
        }

        table.cust-items-table tbody tr:last-child td { border-bottom: none; }

        table.cust-items-table tbody tr:hover td { background: #f7fbfd; }

        .empty-row {
            padding: 1rem 0;
            text-align: center;
            color: #6f869b;
            font-size: 0.78rem;
        }

        @media (max-width: 640px) {
            .cust-doc-body { padding: 1.25rem 1rem; }
            .cust-header-table, .cust-header-table tr, .cust-header-table td,
            .cust-meta-table, .cust-meta-table tr, .cust-meta-table td,
            .summary-table, .summary-table tr, .summary-table td {
                display: block;
                width: 100% !important;
                text-align: left !important;
            }
            .cust-address-block, .doc-title, .doc-meta-line { text-align: left !important; }
            .summary-table td { padding: 0 0 0.75rem 0; }
        }
    </style>
@endpush

@section('content')
    <div class="co-wrap">

        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">

                @if (session('success'))
                    <div
                        style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:0.75rem 1.25rem;border-radius:0;font-size:0.855rem;font-weight:600;margin-bottom:1.5rem;">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- Management bar --}}
                <div class="inv-mgmt-bar">
<div style="display:flex;align-items:center;gap:0.65rem;flex-wrap:wrap;">
                        <a href="{{ route('companies.suppliers.statement', [$company, $supplier]) }}" class="mgmt-btn">
                            Statement
                        </a>
                        <a href="{{ route('companies.suppliers.edit', [$company, $supplier]) }}" class="mgmt-btn primary">
                            Edit Supplier
                        </a>
                    </div>
                </div>

                {{-- Profile document --}}
                <div class="cust-doc">
                    <div class="cust-doc-body">

                        {{-- Header --}}
                        <table class="cust-header-table">
                            <tr>
                                <td style="vertical-align:top;width:55%;">
                                    <div class="cust-name-fallback">{{ $supplier->name }}</div>
                                </td>
                                <td style="vertical-align:top;width:45%;">
                                    <div class="cust-address-block">
                                        @if ($supplier->contact_name)<div>Attn: {{ $supplier->contact_name }}</div>@endif
                                        @if ($supplier->email)<div>{{ $supplier->email }}</div>@endif
                                        @if ($supplier->phone)<div>{{ $supplier->phone }}</div>@endif
                                    </div>
                                </td>
                            </tr>
                        </table>

                        {{-- Meta --}}
                        <table class="cust-meta-table">
                            <tr>
                                <td style="vertical-align:top;width:50%;">
                                    @if ($supplier->address)
                                        <div><span class="meta-label">Address</span><span class="meta-value" style="white-space:pre-line;">{{ $supplier->address }}</span></div>
                                    @endif
                                    <div><span class="meta-label">Supplier Since</span><span class="meta-value">{{ $supplier->created_at->format('Y-m-d') }}</span></div>
                                </td>
                                <td style="vertical-align:top;width:50%;">
                                    <div class="doc-title">SUPPLIER PROFILE</div>
                                    <div class="doc-meta-line">Customer: <strong>{{ $company->registered_name }}</strong></div>
                                    @if ($company->vat_number)
                                        <div class="doc-meta-line">VAT No: <strong>{{ $company->vat_number }}</strong></div>
                                    @endif
                                    @if ($company->registration_number)
                                        <div class="doc-meta-line">Reg. No: <strong>{{ $company->registration_number }}</strong></div>
                                    @endif
                                    <div style="text-align:right;">
                                        <span class="status-box {{ $supplier->is_active ? '' : 'inactive' }}">
                                            {{ $supplier->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <hr class="divider">

                        {{-- Summary --}}
                        @php
                            $totalPurchased = $journalLines->where('type', 'credit')->sum(fn ($line) => (float) $line->amount);
                            $totalPaid = $journalLines->where('type', 'debit')->sum(fn ($line) => (float) $line->amount);
                            $balanceOwed = $totalPurchased - $totalPaid;
                        @endphp
                        <table class="summary-table">
                            <tr>
                                <td>
                                    <span class="lbl">Total Purchases</span>
                                    <span class="amt credit">R {{ number_format($totalPurchased, 2) }}</span>
                                </td>
                                <td>
                                    <span class="lbl">Total Paid</span>
                                    <span class="amt debit">R {{ number_format($totalPaid, 2) }}</span>
                                </td>
                                <td>
                                    <span class="lbl">Balance Owed</span>
                                    <span class="amt">R {{ number_format($balanceOwed, 2) }}</span>
                                </td>
                            </tr>
                        </table>

                        {{-- Ledger --}}
                        <div class="info-section">
                            <div class="section-header">Transactions ({{ $journalLines->count() }})</div>
                            <table class="cust-items-table">
                                <thead>
                                    <tr>
                                        <td style="width:14%;">Date</td>
                                        <td style="width:14%;">Reference</td>
                                        <td style="width:24%;">Account</td>
                                        <td style="width:26%;">Description</td>
                                        <td class="amt" style="width:11%;">Debit</td>
                                        <td class="amt" style="width:11%;">Credit</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($journalLines as $line)
                                        <tr>
                                            <td>{{ $line->transaction->transaction_date->format('Y-m-d') }}</td>
                                            <td>{{ $line->transaction->reference ?? '—' }}</td>
                                            <td>{{ $line->account->account_name ?? '—' }}</td>
                                            <td>{{ $line->description ?? $line->transaction->description }}</td>
                                            <td class="amt">{{ $line->type === 'debit' ? number_format($line->amount, 2) : '—' }}</td>
                                            <td class="amt">{{ $line->type === 'credit' ? number_format($line->amount, 2) : '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="empty-row">No transactions linked to this supplier yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                    </div>{{-- /.cust-doc-body --}}
                </div>{{-- /.cust-doc --}}

            </main>
        </div>
    </div>
@endsection
