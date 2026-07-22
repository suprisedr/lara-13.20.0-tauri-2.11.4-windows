@extends('layouts.public')

@section('title', $company->registered_name . ' — ' . $supplier->name . ' Statement')
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
            color: #6b7280;
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

        .mgmt-btn.primary:hover { background: #333; }

        /* ── Filter bar ──────────────────────────────────────────── */
        .stmt-filter-bar {
            display: flex;
            align-items: flex-end;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }

        .stmt-filter-bar .field label {
            display: block;
            font-size: 0.65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #7c3aed;
            margin-bottom: 0.35rem;
        }

        .stmt-filter-bar input[type='date'] {
            border: 1px solid rgba(94, 23, 235, 0.2);
            border-radius: 0;
            padding: 0.45rem 0.65rem;
            font-size: 0.85rem;
        }

        .stmt-filter-bar input[type='date']:focus {
            outline: none;
            border-color: #7c3aed;
        }

        /* ── Statement document (PDF-style) ─────────────────────── */
        .cust-doc {
            background: #fff;
            border: 1px solid #ddd;
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
            color: #000;
            font-size: 0.78rem;
            line-height: 1.45;
        }

        .cust-doc-body {
            padding: 2rem 2.25rem;
        }

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

        .divider {
            border: none;
            border-top: 2px solid #000;
            margin: 1rem 0 1.25rem 0;
        }

        /* ── Summary line ─────────────────────────────────────────── */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.25rem;
        }

        .summary-table td {
            padding: 0 1.25rem 0 0;
            font-size: 0.78rem;
            vertical-align: top;
        }

        .summary-table .lbl {
            display: block;
            font-weight: 700;
            font-size: 0.62rem;
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

        /* ── Statement table ─────────────────────────────────────── */
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
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }

        table.cust-items-table tbody td.amt {
            text-align: right;
            font-family: 'Courier New', monospace;
            white-space: nowrap;
        }

        table.cust-items-table tbody tr:last-child td { border-bottom: none; }

        table.cust-items-table tbody tr:hover td { background: #fafafa; }

        table.cust-items-table tfoot td {
            padding-top: 0.65rem;
            border-top: 2px solid #000;
            font-weight: 800;
            font-size: 0.82rem;
        }

        table.cust-items-table tfoot td.amt {
            text-align: right;
            font-family: 'Courier New', monospace;
        }

        .empty-row {
            padding: 1rem 0;
            text-align: center;
            color: #999;
            font-size: 0.78rem;
        }

        @media (max-width: 640px) {
            .cust-doc-body { padding: 1.25rem 1rem; }
            .cust-header-table, .cust-header-table tr, .cust-header-table td,
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

                {{-- Management bar --}}
                <div class="inv-mgmt-bar">
<div style="display:flex;align-items:center;gap:0.65rem;flex-wrap:wrap;">
                        <a href="{{ route('companies.suppliers.statement.pdf', [$company, $supplier, 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="mgmt-btn primary" target="_blank">
                            Download PDF
                        </a>
                    </div>
                </div>

                {{-- Date range filter --}}
                <form method="GET" action="{{ route('companies.suppliers.statement', [$company, $supplier]) }}" class="stmt-filter-bar">
                    <div class="field">
                        <label for="start_date">From</label>
                        <input type="date" id="start_date" name="start_date" value="{{ $startDate }}">
                    </div>
                    <div class="field">
                        <label for="end_date">To</label>
                        <input type="date" id="end_date" name="end_date" value="{{ $endDate }}">
                    </div>
                    <div class="field">
                        <button type="submit" style="background:#5e17eb;color:#fff;border:none;border-radius:0;padding:0.5rem 1.1rem;font-size:0.8rem;font-weight:700;cursor:pointer;">
                            Update
                        </button>
                    </div>
                </form>

                {{-- Statement document --}}
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

                        <table class="cust-header-table">
                            <tr>
                                <td style="vertical-align:top;width:55%;">
                                    @if ($supplier->address)
                                        <div style="white-space:pre-line;">{{ $supplier->address }}</div>
                                    @endif
                                </td>
                                <td style="vertical-align:top;width:45%;">
                                    <div class="doc-title">STATEMENT OF ACCOUNT</div>
                                    <div class="doc-meta-line">Customer: <strong>{{ $company->registered_name }}</strong></div>
                                    <div class="doc-meta-line">
                                        Period: <strong>{{ \Illuminate\Support\Carbon::parse($startDate)->format('d M Y') }} &ndash; {{ \Illuminate\Support\Carbon::parse($endDate)->format('d M Y') }}</strong>
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <hr class="divider">

                        {{-- Summary --}}
                        <table class="summary-table">
                            <tr>
                                <td>
                                    <span class="lbl">Opening Balance</span>
                                    <span class="amt">R {{ number_format($openingBalance, 2) }}</span>
                                </td>
                                <td>
                                    <span class="lbl">Total Purchases</span>
                                    <span class="amt credit">R {{ number_format($lines->sum('credit'), 2) }}</span>
                                </td>
                                <td>
                                    <span class="lbl">Total Paid</span>
                                    <span class="amt debit">R {{ number_format($lines->sum('debit'), 2) }}</span>
                                </td>
                                <td>
                                    <span class="lbl">Closing Balance</span>
                                    <span class="amt">R {{ number_format($closingBalance, 2) }}</span>
                                </td>
                            </tr>
                        </table>

                        {{-- Transactions --}}
                        <table class="cust-items-table">
                            <thead>
                                <tr>
                                    <td style="width:14%;">Date</td>
                                    <td style="width:14%;">Type</td>
                                    <td style="width:38%;">Description</td>
                                    <td class="amt" style="width:12%;">Debit</td>
                                    <td class="amt" style="width:12%;">Credit</td>
                                    <td class="amt" style="width:10%;">Balance</td>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ \Illuminate\Support\Carbon::parse($startDate)->format('Y-m-d') }}</td>
                                    <td>&mdash;</td>
                                    <td>Balance brought forward</td>
                                    <td class="amt">&mdash;</td>
                                    <td class="amt">&mdash;</td>
                                    <td class="amt">{{ number_format($openingBalance, 2) }}</td>
                                </tr>
                                @forelse ($lines as $line)
                                    <tr>
                                        <td>{{ $line['date']->format('Y-m-d') }}</td>
                                        <td>{{ $line['type'] }}</td>
                                        <td>{{ $line['description'] }}</td>
                                        <td class="amt">{{ $line['debit'] > 0 ? number_format($line['debit'], 2) : '—' }}</td>
                                        <td class="amt">{{ $line['credit'] > 0 ? number_format($line['credit'], 2) : '—' }}</td>
                                        <td class="amt">{{ number_format($line['balance'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="empty-row">No transactions in this period.</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5">Closing Balance</td>
                                    <td class="amt">{{ number_format($closingBalance, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>

                    </div>{{-- /.cust-doc-body --}}
                </div>{{-- /.cust-doc --}}

            </main>
        </div>
    </div>
@endsection
