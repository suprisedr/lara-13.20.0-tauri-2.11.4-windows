@extends('layouts.public')

@section('title', $company->registered_name . ' — VAT Return (VAT201)')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .co-topbar { padding: 1rem 2rem; }
        .co-main   { padding: 1.25rem 1.5rem; }
        .co-card   { margin-bottom: 0; }
        .co-card-head { padding: 0.875rem 1.25rem; }

        /* ── VAT201 document ─────────────────────── */
        .vat-doc {
            background: #fff;
            font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
            font-size: 7pt;
            color: #4c1d95;
        }

        /* Header band */
        .vat-doc-header {
            background: #4c1d95;
            color: #fff;
            padding: 6pt 10pt;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12pt;
        }
        .vat-doc-header-left .vat-form-no {
            font-size: 5pt;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.5);
            margin: 0 0 2pt;
        }
        .vat-doc-header-left h1 {
            font-size: 11pt;
            font-weight: 900;
            margin: 0 0 1pt;
            letter-spacing: -0.01em;
        }
        .vat-doc-header-left p.vat-subtitle {
            font-size: 6.5pt;
            color: rgba(255,255,255,0.6);
            margin: 0;
        }
        .vat-doc-header-right {
            text-align: right;
            flex-shrink: 0;
        }
        .vat-doc-header-right .vat-reg-label {
            font-size: 5pt;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.45);
            margin: 0 0 2pt;
        }
        .vat-doc-header-right .vat-reg-no {
            font-size: 10pt;
            font-weight: 800;
            letter-spacing: 0.08em;
            color: #fff;
            font-family: "DejaVu Sans Mono", monospace;
        }
        .vat-doc-header-right .vat-period {
            font-size: 6pt;
            color: rgba(255,255,255,0.5);
            margin: 3pt 0 0;
        }

        /* Vendor info bar */
        .vat-vendor-bar {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            border-bottom: 1.5pt solid #4c1d95;
        }
        .vat-vendor-cell {
            padding: 4pt 6pt;
            border-right: 0.5pt solid #d5e6f2;
        }
        .vat-vendor-cell:last-child { border-right: none; }
        .vat-vendor-cell .vc-label {
            font-size: 5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6b5b8a;
            margin: 0 0 1.5pt;
        }
        .vat-vendor-cell .vc-value {
            font-size: 7.5pt;
            font-weight: 700;
            color: #4c1d95;
        }

        /* Section heading rows */
        .vat-section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #4c1d95;
            color: #fff;
            padding: 3pt 6pt;
        }
        .vat-section-head span {
            font-size: 6pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .vat-section-head .vat-section-total {
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 7.5pt;
            font-weight: 700;
            letter-spacing: 0;
            color: #fff;
        }

        /* VAT201 field rows */
        .vat-field-row {
            display: grid;
            grid-template-columns: 18pt 1fr auto;
            align-items: stretch;
            border-bottom: 0.5pt solid #e4f0f9;
        }
        .vat-field-row:last-child { border-bottom: none; }
        .vat-field-no {
            background: #f2f8fd;
            border-right: 0.5pt solid #d5e6f2;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 6pt;
            font-weight: 800;
            color: #6b5b8a;
            padding: 4pt 2pt;
        }
        .vat-field-desc {
            padding: 4pt 6pt;
            font-size: 7pt;
            color: #6b5b8a;
        }
        .vat-field-desc strong {
            display: block;
            font-weight: 700;
            color: #4c1d95;
            font-size: 7pt;
        }
        .vat-field-desc span {
            font-size: 5.5pt;
            color: #6b5b8a;
        }
        .vat-field-amount {
            min-width: 80pt;
            padding: 4pt 6pt;
            text-align: right;
            font-family: "DejaVu Sans Mono", monospace;
            font-weight: 700;
            font-size: 8pt;
            color: #4c1d95;
            border-left: 0.5pt solid #d5e6f2;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            white-space: nowrap;
        }

        /* Net payable row */
        .vat-net-row {
            display: grid;
            grid-template-columns: 18pt 1fr auto;
            align-items: stretch;
            background: #ede9fe;
            border-top: 1.5pt solid #4c1d95;
        }
        .vat-net-row .vat-field-no {
            background: #d8ecf9;
            border-right: 0.5pt solid #c4b5fd;
            font-size: 7pt;
            font-weight: 900;
        }
        .vat-net-row .vat-field-desc strong {
            font-size: 8pt;
        }
        .vat-net-row .vat-field-amount {
            font-size: 10pt;
            border-left: 0.5pt solid #c4b5fd;
            min-width: 90pt;
        }

        /* Detail transaction table */
        .vat-detail-head {
            background: #f2f8fd;
            border-top: 1.5pt solid #4c1d95;
            border-bottom: 0.5pt solid #d5e6f2;
            padding: 3pt 6pt;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .vat-detail-head span {
            font-size: 5.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6b5b8a;
        }

        .vat-tx-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7pt;
        }
        .vat-tx-table thead th {
            background: #4c1d95;
            color: rgba(255,255,255,0.85);
            font-size: 5.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 3pt 4pt;
            text-align: left;
            border: none;
            white-space: nowrap;
        }
        .vat-tx-table thead th.r { text-align: right; }
        .vat-tx-table tbody tr { border-bottom: 0.5pt solid #e4f0f9; }
        .vat-tx-table tbody tr:hover { background: #faf5ff; }
        .vat-tx-table td {
            padding: 2pt 4pt;
            color: #4c1d95;
            font-size: 7pt;
        }
        .vat-tx-table td.r {
            text-align: right;
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 7pt;
            white-space: nowrap;
        }
        .vat-tx-table td.dim { color: #6b5b8a; font-size: 6.5pt; }
        .vat-tx-table tfoot td {
            padding: 3pt 4pt;
            font-weight: 800;
            background: #ede9fe;
            border-top: 1.5pt solid #4c1d95;
            font-size: 7pt;
            color: #4c1d95;
        }
        .vat-tx-table tfoot td.r {
            text-align: right;
            font-family: "DejaVu Sans Mono", monospace;
            white-space: nowrap;
        }

        .vat-empty {
            text-align: center;
            padding: 8pt 6pt;
            color: #8b7aad;
            font-size: 6.5pt;
            background: #f8fbfe;
        }

        .vat-footer {
            padding: 4pt 6pt;
            background: #f2f8fd;
            border-top: 0.5pt solid #d5e6f2;
            font-size: 5pt;
            color: #6b5b8a;
            line-height: 1.5;
        }

        @media (max-width: 640px) {
            .vat-vendor-bar { grid-template-columns: 1fr; }
            .vat-doc-header { flex-direction: column; gap: 4pt; }
            .vat-doc-header-right { text-align: left; }
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
                    <div style="display:flex;align-items:center;gap:0.6rem;background:#dcfce7;border:1px solid #bbf7d0;padding:0.65rem 1rem;margin-bottom:1rem;font-size:0.82rem;font-weight:600;color:#15803d;">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="co-card">
                    <div class="co-card-head">
                        <div>
                            <div class="co-section-heading"><p class="co-section-label">Reports</p><h2>VAT Return (VAT201)</h2></div>
                            <p class="is-period-label">
                                {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} &ndash;
                                {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                            </p>
                        </div>
                        <div style="display:flex;align-items:center;gap:0.6rem;">
                            <a href="{{ route('companies.reports.vat-return.pdf', [$company, 'start_date' => $startDate, 'end_date' => $endDate]) }}"
                                target="_blank" rel="noopener"
                                style="display:inline-flex;align-items:center;gap:0.35rem;background:#4c1d95;color:#fff;border-radius:0;padding:0.28rem 0.75rem;font-size:0.72rem;font-weight:700;text-decoration:none;transition:background 0.15s;white-space:nowrap;"
                                onmouseover="this.style.background='#3b0764'" onmouseout="this.style.background='#4c1d95'">
                                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                    <polyline points="7 10 12 15 17 10" />
                                    <line x1="12" y1="15" x2="12" y2="3" />
                                </svg>
                                Download PDF
                            </a>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('companies.reports.vat-return', $company) }}"
                        class="is-filter-bar">
                        <div>
                            <label for="vf-start">Tax period from</label>
                            <input type="date" id="vf-start" name="start_date" value="{{ $startDate }}">
                        </div>
                        <div>
                            <label for="vf-end">To</label>
                            <input type="date" id="vf-end" name="end_date" value="{{ $endDate }}">
                        </div>
                        <button type="submit" class="is-filter-btn">Apply</button>
                    </form>

                    <div style="padding: 0 1rem 0.5rem;">

                        @php
                            $netPositive = $netVatPayable > 0;
                            $netNegative = $netVatPayable < 0;
                            $netAmtClass = $netPositive ? 'payable' : ($netNegative ? 'refund' : 'zero');
                            $netLabel    = $netPositive ? 'VAT Payable to SARS' : ($netNegative ? 'VAT Refund Due from SARS' : 'No VAT Due / Refund');
                            $fieldLabel  = $netPositive ? '35' : '36';
                        @endphp
                        <div class="vat-doc">

                            {{-- Header --}}
                            <div class="vat-doc-header">
                                <div class="vat-doc-header-left">
                                    <p class="vat-form-no">VAT201 &mdash; Value-Added Tax Return</p>
                                    <h1>{{ $company->registered_name }}</h1>
                                    <p class="vat-subtitle">{{ $company->company_type_label ?? '' }}</p>
                                </div>
                                <div class="vat-doc-header-right">
                                    <p class="vat-reg-label">VAT Registration No.</p>
                                    <p class="vat-reg-no">{{ $company->vat_number ?? 'Not registered' }}</p>
                                    <p class="vat-period">
                                        {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}
                                        &ndash;
                                        {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                                    </p>
                                </div>
                            </div>

                            {{-- Vendor info --}}
                            <div class="vat-vendor-bar">
                                <div class="vat-vendor-cell">
                                    <p class="vc-label">Tax Period</p>
                                    <p class="vc-value">
                                        {{ \Carbon\Carbon::parse($startDate)->format('M Y') }}
                                        &ndash;
                                        {{ \Carbon\Carbon::parse($endDate)->format('M Y') }}
                                    </p>
                                </div>
                                <div class="vat-vendor-cell">
                                    <p class="vc-label">Income Tax Number</p>
                                    <p class="vc-value">{{ $company->income_tax_number ?? '—' }}</p>
                                </div>
                                <div class="vat-vendor-cell">
                                    <p class="vc-label">Return Status</p>
                                    <p class="vc-value" style="color:#92400e;">Draft &mdash; Not submitted</p>
                                </div>
                            </div>

                            {{-- Section A: Output VAT --}}
                            <div class="vat-section-head">
                                <span>Section A &mdash; Output Tax</span>
                                <span class="vat-section-total">R {{ number_format($totalOutputVat, 2) }}</span>
                            </div>

                            <div class="vat-field-row">
                                <div class="vat-field-no">1</div>
                                <div class="vat-field-desc">
                                    <strong>Standard-rated supplies (incl. VAT)</strong>
                                    <span>Sales, services and other taxable supplies at 15%</span>
                                </div>
                                <div class="vat-field-amount">
                                    R {{ number_format($totalOutputVat / 0.15 * 1.15, 2) }}
                                </div>
                            </div>
                            <div class="vat-field-row">
                                <div class="vat-field-no">4</div>
                                <div class="vat-field-desc">
                                    <strong>Output tax (VAT on standard-rated supplies)</strong>
                                    <span>15% &times; standard-rated supplies (excl. VAT)</span>
                                </div>
                                <div class="vat-field-amount">
                                    R {{ number_format($totalOutputVat, 2) }}
                                </div>
                            </div>

                            {{-- Section B: Input VAT --}}
                            <div class="vat-section-head">
                                <span>Section B &mdash; Input Tax</span>
                                <span class="vat-section-total">R {{ number_format($totalInputVat, 2) }}</span>
                            </div>

                            <div class="vat-field-row">
                                <div class="vat-field-no">14</div>
                                <div class="vat-field-desc">
                                    <strong>Input tax deductible (purchases and expenses)</strong>
                                    <span>VAT paid on qualifying business expenses at 15%</span>
                                </div>
                                <div class="vat-field-amount">
                                    R {{ number_format($totalInputVat, 2) }}
                                </div>
                            </div>

                            {{-- Net payable / refund --}}
                            <div class="vat-net-row">
                                <div class="vat-field-no">{{ $fieldLabel }}</div>
                                <div class="vat-field-desc">
                                    <strong>{{ $netLabel }}</strong>
                                    <span>Field 4 (Output tax) minus Field 14 (Input tax)</span>
                                </div>
                                <div class="vat-field-amount">
                                    R {{ number_format(abs($netVatPayable), 2) }}
                                </div>
                            </div>

                            {{-- Output detail --}}
                            <div class="vat-detail-head">
                                <span>Supporting detail &mdash; Output VAT transactions</span>
                                <span>{{ $outputVatLines->count() }} entr{{ $outputVatLines->count() === 1 ? 'y' : 'ies' }}</span>
                            </div>
                            @if ($outputVatLines->isEmpty())
                                <div class="vat-empty">No output VAT entries for this period.</div>
                            @else
                                <div style="overflow-x:auto;">
                                    <table class="vat-tx-table">
                                        <thead>
                                            <tr>
                                                <th style="width:60pt;">Date</th>
                                                <th>Description</th>
                                                <th class="hide-mobile">Reference</th>
                                                <th class="hide-mobile">Account</th>
                                                <th class="r hide-mobile" style="width:35pt;">Rate</th>
                                                <th class="r" style="width:70pt;">VAT Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($outputVatLines as $line)
                                                <tr>
                                                    <td class="dim">
                                                        {{ \Carbon\Carbon::parse($line['date'])->format('d M Y') }}
                                                    </td>
                                                    <td>{{ $line['description'] }}</td>
                                                    <td class="hide-mobile dim">{{ $line['reference'] ?? '—' }}</td>
                                                    <td class="hide-mobile dim">{{ $line['account'] }}</td>
                                                    <td class="r hide-mobile dim">
                                                        {{ $line['vat_rate'] !== null ? number_format($line['vat_rate'], 0) . '%' : '—' }}
                                                    </td>
                                                    <td class="r" style="font-weight:700;">
                                                        R {{ number_format($line['amount'], 2) }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="5">Total Output VAT (Field 4)</td>
                                                <td class="r">R {{ number_format($totalOutputVat, 2) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @endif

                            {{-- Input detail --}}
                            <div class="vat-detail-head">
                                <span>Supporting detail &mdash; Input VAT transactions</span>
                                <span>{{ $inputVatLines->count() }} entr{{ $inputVatLines->count() === 1 ? 'y' : 'ies' }}</span>
                            </div>
                            @if ($inputVatLines->isEmpty())
                                <div class="vat-empty">No input VAT entries for this period.</div>
                            @else
                                <div style="overflow-x:auto;">
                                    <table class="vat-tx-table">
                                        <thead>
                                            <tr>
                                                <th style="width:60pt;">Date</th>
                                                <th>Description</th>
                                                <th class="hide-mobile">Reference</th>
                                                <th class="hide-mobile">Account</th>
                                                <th class="r hide-mobile" style="width:35pt;">Rate</th>
                                                <th class="r" style="width:70pt;">VAT Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($inputVatLines as $line)
                                                <tr>
                                                    <td class="dim">
                                                        {{ \Carbon\Carbon::parse($line['date'])->format('d M Y') }}
                                                    </td>
                                                    <td>{{ $line['description'] }}</td>
                                                    <td class="hide-mobile dim">{{ $line['reference'] ?? '—' }}</td>
                                                    <td class="hide-mobile dim">{{ $line['account'] }}</td>
                                                    <td class="r hide-mobile dim">
                                                        {{ $line['vat_rate'] !== null ? number_format($line['vat_rate'], 0) . '%' : '—' }}
                                                    </td>
                                                    <td class="r" style="font-weight:700;color:#7c3aed;">
                                                        R {{ number_format($line['amount'], 2) }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <td colspan="5">Total Input VAT (Field 14)</td>
                                                <td class="r">R {{ number_format($totalInputVat, 2) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @endif

                            {{-- Footer disclaimer --}}
                            <div class="vat-footer">
                                This VAT201 summary is generated from the company's journal entries for the selected tax period.
                                It is a <strong>draft</strong> and must be reviewed and submitted via the SARS eFiling portal.
                                Ensure all journal lines are correctly classified before submission.
                            </div>

                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>
@endsection
