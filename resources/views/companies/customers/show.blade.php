@extends('layouts.public')

@section('title', $company->registered_name . ' — ' . $customer->name)
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        /* ── Management bar ─────────────────────────────────────── */
        .inv-mgmt-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8pt;
            flex-wrap: wrap;
            margin-bottom: 12pt;
        }

        .inv-mgmt-bar a {
            font-size: 7pt;
            color: #7a90a5;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 3pt;
            transition: color 0.15s;
        }

        .inv-mgmt-bar a:hover { color: #16355c; }

        .mgmt-btn {
            display: inline-flex;
            align-items: center;
            gap: 3pt;
            background: #fff;
            border: 1px solid #c9dff0;
            color: #16355c;
            font-size: 6pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 3pt 7pt;
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
        }

        .mgmt-btn:hover { background: #eef6fc; }

        .mgmt-btn.primary {
            background: #0079c8;
            color: #fff;
            border-color: #0079c8;
        }

        .mgmt-btn.primary:hover { background: #005f9e; }

        /* ── Profile document (PDF-style) ───────────────────────── */
        .cust-doc {
            background: #fff;
            border: 1px solid #c9dff0;
            font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
            color: #16355c;
            font-size: 7pt;
            line-height: 1.45;
        }

        .cust-doc-body {
            padding: 16pt 18pt;
        }

        /* ── Header ──────────────────────────────────────────────── */
        .cust-header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6pt;
        }

        .cust-name-fallback {
            font-size: 11pt;
            font-weight: 800;
            letter-spacing: -0.01em;
            color: #16355c;
        }

        .cust-address-block {
            text-align: right;
            font-size: 7pt;
            line-height: 1.55;
            color: #4a5f78;
        }

        .cust-meta-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-label {
            font-weight: 700;
            font-size: 7pt;
            display: inline-block;
            width: 80pt;
            color: #16355c;
        }

        .meta-value {
            font-size: 7pt;
            color: #4a5f78;
        }

        .doc-title {
            font-size: 9pt;
            font-weight: 800;
            text-align: right;
            margin-bottom: 2pt;
            letter-spacing: 0.04em;
            color: #16355c;
        }

        .doc-meta-line {
            text-align: right;
            font-size: 7pt;
            color: #4a5f78;
        }

        .status-box {
            display: inline-block;
            font-weight: 700;
            text-transform: uppercase;
            border: 1px solid #16355c;
            padding: 1pt 4pt;
            font-size: 5pt;
            letter-spacing: 0.08em;
            margin-top: 3pt;
            color: #16355c;
        }

        .status-box.inactive { color: #7a90a5; border-color: #a9bccd; }

        .divider {
            border: none;
            border-top: 1.5pt solid #16355c;
            margin: 8pt 0 10pt 0;
        }

        .divider.light {
            border-top: 0.4pt solid #ddebf5;
            margin: 10pt 0;
        }

        /* ── Summary line ─────────────────────────────────────────── */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2pt;
        }

        .summary-table td {
            padding: 0 10pt 0 0;
            font-size: 7pt;
            vertical-align: top;
        }

        .summary-table .lbl {
            display: block;
            font-weight: 700;
            font-size: 5pt;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 1.5pt;
            color: #7a90a5;
        }

        .summary-table .amt {
            font-size: 8pt;
            font-weight: 800;
            font-family: "DejaVu Sans Mono", monospace;
            color: #16355c;
        }

        /* ── Section headers ─────────────────────────────────────── */
        .section-header {
            font-weight: 700;
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border-bottom: 1.5pt solid #16355c;
            padding-bottom: 2pt;
            margin-bottom: 4pt;
            color: #16355c;
        }

        .info-section {
            margin-top: 10pt;
        }

        /* ── Tables ───────────────────────────────────────────────── */
        table.cust-items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2pt;
        }

        table.cust-items-table thead td {
            font-weight: 700;
            font-size: 5.5pt;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1.5pt solid #16355c;
            padding-bottom: 4pt;
            color: #7a90a5;
        }

        table.cust-items-table thead td.amt { text-align: right; }

        table.cust-items-table tbody td {
            padding: 5pt 0;
            font-size: 7pt;
            border-bottom: 0.4pt solid #ddebf5;
            vertical-align: top;
            color: #16355c;
        }

        table.cust-items-table tbody td.amt {
            text-align: right;
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 6.5pt;
            white-space: nowrap;
        }

        table.cust-items-table tbody tr:last-child td { border-bottom: none; }

        table.cust-items-table tbody tr:hover td { background: #eef6fc; }

        table.cust-items-table a.row-link {
            color: #16355c;
            font-weight: 700;
            text-decoration: none;
            border-bottom: 0.4pt solid #c9dff0;
        }

        table.cust-items-table a.row-link:hover { border-bottom-color: #0079c8; color: #0079c8; }

        .empty-row {
            padding: 8pt 0;
            text-align: center;
            color: #7a90a5;
            font-size: 7pt;
        }

        @media (max-width: 640px) {
            .cust-doc-body { padding: 10pt 8pt; }
            .cust-header-table, .cust-header-table tr, .cust-header-table td,
            .cust-meta-table, .cust-meta-table tr, .cust-meta-table td,
            .summary-table, .summary-table tr, .summary-table td {
                display: block;
                width: 100% !important;
                text-align: left !important;
            }
            .cust-address-block, .doc-title, .doc-meta-line { text-align: left !important; }
            .summary-table td { padding: 0 0 6pt 0; }
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
                        style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:6pt 10pt;font-size:7pt;font-weight:600;margin-bottom:12pt;font-family:Helvetica,Arial,'DejaVu Sans',sans-serif;">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- Management bar --}}
                <div class="inv-mgmt-bar">
<div style="display:flex;align-items:center;gap:5pt;flex-wrap:wrap;">
                        <a href="{{ route('companies.invoices.create', $company) . '?customer_id=' . $customer->id }}" class="mgmt-btn">
                            + New Invoice
                        </a>
                        <a href="{{ route('companies.quotations.create', $company) . '?customer_id=' . $customer->id }}" class="mgmt-btn">
                            + New Quotation
                        </a>
                        <a href="{{ route('companies.customers.statement', [$company, $customer]) }}" class="mgmt-btn">
                            Statement
                        </a>
                        <a href="{{ route('companies.customers.edit', [$company, $customer]) }}" class="mgmt-btn primary">
                            Edit Customer
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
                                    <div class="cust-name-fallback">{{ $customer->name }}</div>
                                </td>
                                <td style="vertical-align:top;width:45%;">
                                    <div class="cust-address-block">
                                        @if ($customer->contact_name)<div>Attn: {{ $customer->contact_name }}</div>@endif
                                        @if ($customer->email)<div>{{ $customer->email }}</div>@endif
                                        @if ($customer->phone)<div>{{ $customer->phone }}</div>@endif
                                    </div>
                                </td>
                            </tr>
                        </table>

                        {{-- Meta --}}
                        <table class="cust-meta-table">
                            <tr>
                                <td style="vertical-align:top;width:50%;">
                                    @if ($customer->address)
                                        <div><span class="meta-label">Address</span><span class="meta-value" style="white-space:pre-line;">{{ $customer->address }}</span></div>
                                    @endif
                                    <div><span class="meta-label">Customer Since</span><span class="meta-value">{{ $customer->created_at->format('Y-m-d') }}</span></div>
                                </td>
                                <td style="vertical-align:top;width:50%;">
                                    <div class="doc-title">CUSTOMER PROFILE</div>
                                    <div class="doc-meta-line">Vendor: <strong>{{ $company->registered_name }}</strong></div>
                                    @if ($company->vat_number)
                                        <div class="doc-meta-line">VAT No: <strong>{{ $company->vat_number }}</strong></div>
                                    @endif
                                    @if ($company->registration_number)
                                        <div class="doc-meta-line">Reg. No: <strong>{{ $company->registration_number }}</strong></div>
                                    @endif
                                    <div style="text-align:right;">
                                        <span class="status-box {{ $customer->is_active ? '' : 'inactive' }}">
                                            {{ $customer->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <hr class="divider">

                        {{-- Summary --}}
                        @php
                            $totalInvoiced = $invoices->sum(fn ($invoice) => $invoice->total());
                            $totalPaid = $invoices->sum(fn ($invoice) => $invoice->amountPaid());
                            $outstanding = $invoices->sum(fn ($invoice) => $invoice->balanceDue());
                            $totalQuoted = $quotations->sum(fn ($quotation) => $quotation->total());
                        @endphp
                        <table class="summary-table">
                            <tr>
                                <td>
                                    <span class="lbl">Total Invoiced</span>
                                    <span class="amt">R {{ number_format($totalInvoiced, 2) }}</span>
                                </td>
                                <td>
                                    <span class="lbl">Total Paid</span>
                                    <span class="amt">R {{ number_format($totalPaid, 2) }}</span>
                                </td>
                                <td>
                                    <span class="lbl">Outstanding</span>
                                    <span class="amt">R {{ number_format($outstanding, 2) }}</span>
                                </td>
                                <td>
                                    <span class="lbl">Total Quoted</span>
                                    <span class="amt">R {{ number_format($totalQuoted, 2) }}</span>
                                </td>
                            </tr>
                        </table>

                        {{-- Invoices --}}
                        <div class="info-section">
                            <div class="section-header">Invoices ({{ $invoices->count() }})</div>
                            <table class="cust-items-table">
                                <thead>
                                    <tr>
                                        <td style="width:24%;">Invoice No</td>
                                        <td style="width:18%;">Date</td>
                                        <td style="width:18%;">Due Date</td>
                                        <td style="width:14%;">Status</td>
                                        <td class="amt" style="width:13%;">Total</td>
                                        <td class="amt" style="width:13%;">Balance Due</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($invoices as $invoice)
                                        <tr>
                                            <td>
                                                <a class="row-link" href="{{ route('companies.invoices.show', [$company, $invoice]) }}">
                                                    {{ $invoice->invoice_number }}
                                                </a>
                                            </td>
                                            <td>{{ $invoice->invoice_date->format('Y-m-d') }}</td>
                                            <td>{{ $invoice->due_date?->format('Y-m-d') ?? '—' }}</td>
                                            <td>
                                                <span class="status-box">
                                                    {{ \App\Enums\InvoiceStatus::from($invoice->status)->label() }}
                                                </span>
                                            </td>
                                            <td class="amt">{{ number_format($invoice->total(), 2) }}</td>
                                            <td class="amt">{{ number_format($invoice->balanceDue(), 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="empty-row">No invoices for this customer yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Quotations --}}
                        <div class="info-section">
                            <div class="section-header">Quotations ({{ $quotations->count() }})</div>
                            <table class="cust-items-table">
                                <thead>
                                    <tr>
                                        <td style="width:24%;">Quote No</td>
                                        <td style="width:18%;">Date</td>
                                        <td style="width:18%;">Expiry Date</td>
                                        <td style="width:14%;">Status</td>
                                        <td class="amt" style="width:26%;">Total</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($quotations as $quotation)
                                        <tr>
                                            <td>
                                                <a class="row-link" href="{{ route('companies.quotations.show', [$company, $quotation]) }}">
                                                    {{ $quotation->quotation_number }}
                                                </a>
                                            </td>
                                            <td>{{ $quotation->quotation_date->format('Y-m-d') }}</td>
                                            <td>{{ $quotation->expiry_date?->format('Y-m-d') ?? '—' }}</td>
                                            <td>
                                                <span class="status-box">
                                                    {{ \App\Enums\QuotationStatus::from($quotation->status)->label() }}
                                                </span>
                                            </td>
                                            <td class="amt">{{ number_format($quotation->total(), 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="empty-row">No quotations for this customer yet.</td></tr>
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
