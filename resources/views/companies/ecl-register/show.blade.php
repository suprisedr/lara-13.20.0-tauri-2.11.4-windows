@extends('layouts.public')

@section('title', $company->registered_name . ' — ECL Snapshot ' . \Carbon\Carbon::parse($date)->format('d M Y'))
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .cust-doc { background:#fff;border:1px solid #ddd;font-family:'DejaVu Sans',Helvetica,Arial,sans-serif;color:#000;font-size:0.78rem;line-height:1.45;margin-bottom:1.5rem; }
        .cust-doc-body { padding:2rem 2.25rem; }
        .doc-title { font-size:1.3rem;font-weight:800;letter-spacing:0.04em;margin-bottom:0.25rem; }
        .divider { border:none;border-top:2px solid #000;margin:1rem 0 1.25rem; }
        .section-header { font-weight:700;font-size:0.85rem;text-transform:uppercase;letter-spacing:0.06em;border-bottom:1.5px solid #000;padding-bottom:0.25rem;margin-bottom:0.5rem; }
        table.cust-items-table { width:100%;border-collapse:collapse; }
        table.cust-items-table thead td { font-weight:700;font-size:0.7rem;text-transform:uppercase;letter-spacing:0.04em;border-bottom:1.5px solid #000;padding-bottom:0.45rem; }
        table.cust-items-table thead td.amt { text-align:right; }
        table.cust-items-table tbody td { padding:0.55rem 0;font-size:0.78rem;border-bottom:1px solid #ddd;vertical-align:middle; }
        table.cust-items-table tbody td.amt { text-align:right;font-family:'Courier New',monospace;white-space:nowrap; }
        table.cust-items-table tbody tr:last-child td { border-bottom:none; }
        table.cust-items-table tbody tr:hover td { background:#fafafa; }
        table.cust-items-table tfoot td { padding:0.55rem 0;font-size:0.78rem;font-weight:800;border-top:1.5px solid #000; }
        table.cust-items-table tfoot td.amt { text-align:right;font-family:'Courier New',monospace; }
        .row-link { color:#000;font-weight:700;text-decoration:none;border-bottom:1px solid #000; }
        .row-link:hover { border-bottom-color:transparent; }
        .mgmt-btn { display:inline-flex;align-items:center;gap:0.4rem;background:#fff;border:1px solid #000;color:#000;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;padding:0.4rem 0.85rem;text-decoration:none;cursor:pointer;font-family:inherit;transition:background 0.15s,color 0.15s; }
        .mgmt-btn:hover { background:#000;color:#fff; }
        .ecl-summary { display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem; }
        .ecl-metric { border:1px solid #ddd;padding:1rem; }
        .ecl-metric-label { font-size:0.6rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#666;margin-bottom:0.25rem; }
        .ecl-metric-value { font-size:1.25rem;font-weight:800;font-family:'Courier New',monospace;color:#000; }
        .rate-chip { display:inline-block;font-size:0.62rem;font-weight:700;color:#555;border:1px solid #ddd;padding:0.1rem 0.4rem;margin-left:0.4rem; }
        @media (max-width:640px) {
            .cust-doc-body { padding:1.25rem 1rem; }
            .ecl-summary { grid-template-columns:repeat(2,1fr); }
        }
    </style>
@endpush

@section('content')
    <div class="co-wrap">
        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">

                <div class="cust-doc">
                    <div class="cust-doc-body">

                        <div class="doc-title">Expected Credit Loss &mdash; Provision Matrix</div>
                        <div style="font-size:0.72rem;color:#666;margin-bottom:0.25rem;">
                            As at {{ \Carbon\Carbon::parse($date)->format('d F Y') }}
                            &middot; IFRS 9 simplified approach
                        </div>

                        <hr class="divider">

                        {{-- Summary --}}
                        <div class="ecl-summary">
                            <div class="ecl-metric">
                                <div class="ecl-metric-label">Gross Receivables</div>
                                <div class="ecl-metric-value">R {{ number_format($totals['total_outstanding'], 2) }}</div>
                            </div>
                            <div class="ecl-metric">
                                <div class="ecl-metric-label">Total ECL Allowance</div>
                                <div class="ecl-metric-value">R {{ number_format($totals['total_ecl'], 2) }}</div>
                            </div>
                            <div class="ecl-metric">
                                <div class="ecl-metric-label">Net Receivables</div>
                                <div class="ecl-metric-value">R {{ number_format($totals['total_outstanding'] - $totals['total_ecl'], 2) }}</div>
                            </div>
                            <div class="ecl-metric">
                                <div class="ecl-metric-label">Blended Loss Rate</div>
                                <div class="ecl-metric-value">{{ $totals['total_outstanding'] > 0 ? number_format(($totals['total_ecl'] / $totals['total_outstanding']) * 100, 1) : '0.0' }}%</div>
                            </div>
                        </div>

                        {{-- Age analysis with ECL --}}
                        <div class="section-header">
                            Debtors Age Analysis with ECL
                        </div>

                        <div style="font-size:0.7rem;color:#666;margin-bottom:0.75rem;">
                            Rates applied:
                            Current {{ number_format($rates->current_rate * 100, 2) }}%
                            &middot; 31&ndash;60d {{ number_format($rates->days_31_60_rate * 100, 2) }}%
                            &middot; 61&ndash;90d {{ number_format($rates->days_61_90_rate * 100, 2) }}%
                            &middot; 91+d {{ number_format($rates->days_91_plus_rate * 100, 2) }}%
                        </div>

                        @if ($analyses->isEmpty())
                            <p style="padding:2rem 0;text-align:center;color:#888;">No outstanding receivables as at this date.</p>
                        @else
                            <table class="cust-items-table">
                                <thead>
                                    <tr>
                                        <td>Customer</td>
                                        <td class="amt">Current</td>
                                        <td class="amt">31&ndash;60</td>
                                        <td class="amt">61&ndash;90</td>
                                        <td class="amt">91+</td>
                                        <td class="amt">Gross Total</td>
                                        <td class="amt">ECL Allowance</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($analyses as $a)
                                        <tr>
                                            <td>
                                                @if ($a->customer)
                                                    <a class="row-link" href="{{ route('companies.customers.show', [$company, $a->customer]) }}">{{ $a->customer->name }}</a>
                                                @else
                                                    &mdash;
                                                @endif
                                            </td>
                                            <td class="amt">
                                                {{ number_format($a->current_amount, 2) }}
                                                @if ($a->ecl_current > 0) <br><span style="font-size:0.65rem;color:#dc2626;">({{ number_format($a->ecl_current, 2) }})</span> @endif
                                            </td>
                                            <td class="amt">
                                                {{ number_format($a->days_31_60, 2) }}
                                                @if ($a->ecl_31_60 > 0) <br><span style="font-size:0.65rem;color:#dc2626;">({{ number_format($a->ecl_31_60, 2) }})</span> @endif
                                            </td>
                                            <td class="amt">
                                                {{ number_format($a->days_61_90, 2) }}
                                                @if ($a->ecl_61_90 > 0) <br><span style="font-size:0.65rem;color:#dc2626;">({{ number_format($a->ecl_61_90, 2) }})</span> @endif
                                            </td>
                                            <td class="amt">
                                                {{ number_format($a->days_91_plus, 2) }}
                                                @if ($a->ecl_91_plus > 0) <br><span style="font-size:0.65rem;color:#dc2626;">({{ number_format($a->ecl_91_plus, 2) }})</span> @endif
                                            </td>
                                            <td class="amt">{{ number_format($a->total_outstanding, 2) }}</td>
                                            <td class="amt" style="font-weight:700;">{{ number_format($a->total_ecl, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>Total</td>
                                        <td class="amt">
                                            {{ number_format($totals['current_amount'], 2) }}
                                            <br><span style="font-size:0.65rem;color:#dc2626;">({{ number_format($totals['ecl_current'], 2) }})</span>
                                        </td>
                                        <td class="amt">
                                            {{ number_format($totals['days_31_60'], 2) }}
                                            <br><span style="font-size:0.65rem;color:#dc2626;">({{ number_format($totals['ecl_31_60'], 2) }})</span>
                                        </td>
                                        <td class="amt">
                                            {{ number_format($totals['days_61_90'], 2) }}
                                            <br><span style="font-size:0.65rem;color:#dc2626;">({{ number_format($totals['ecl_61_90'], 2) }})</span>
                                        </td>
                                        <td class="amt">
                                            {{ number_format($totals['days_91_plus'], 2) }}
                                            <br><span style="font-size:0.65rem;color:#dc2626;">({{ number_format($totals['ecl_91_plus'], 2) }})</span>
                                        </td>
                                        <td class="amt">{{ number_format($totals['total_outstanding'], 2) }}</td>
                                        <td class="amt">{{ number_format($totals['total_ecl'], 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        @endif

                        <div style="margin-top:1.5rem;text-align:right;">
                            <a href="{{ route('companies.ecl-register.index', [$company, 'as_of_date' => $date]) }}" class="mgmt-btn">
                                &larr; Back to ECL Register
                            </a>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>
@endsection
