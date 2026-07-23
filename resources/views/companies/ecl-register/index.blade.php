@extends('layouts.public')

@section('title', $company->registered_name . ' — ECL Register (IFRS 9)')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .ecl-summary { display:grid;grid-template-columns:repeat(4,1fr);gap:8pt;margin-bottom:10pt; }
        .ecl-metric { border-top:1.5pt solid #4c1d95;background:#fff;padding:6pt 8pt; }
        .ecl-metric-label { font-size:5pt;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:#6b5b8a;margin-bottom:2pt; }
        .ecl-metric-value { font-size:10pt;font-weight:800;font-family:"DejaVu Sans Mono",monospace;color:#4c1d95; }
        .ecl-metric-value.surplus { color:#16a34a; }
        .ecl-metric-value.deficit { color:#dc2626; }
        .ecl-rate-form { display:grid;grid-template-columns:repeat(5,1fr);gap:6pt;align-items:end;margin-bottom:8pt;padding:6pt 0; }
        .ecl-rate-form .field label { display:block;font-size:5.5pt;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;color:#6b5b8a;margin-bottom:2pt; }
        .ecl-rate-form .input-pct { position:relative; }
        .ecl-rate-form input[type='number'] { width:100%;border:1px solid #c4b5fd;padding:3pt 12pt 3pt 4pt;font-size:7pt;font-family:"DejaVu Sans Mono",monospace;box-sizing:border-box;color:#4c1d95; }
        .ecl-rate-form input[type='number']:focus { outline:none;border-color:#4c1d95; }
        .ecl-rate-form .input-pct::after { content:'%';position:absolute;right:4pt;top:50%;transform:translateY(-50%);color:#8b7aad;font-size:6.5pt;pointer-events:none; }
        .ifrs-box { border-top:1.5pt solid #4c1d95;border-bottom:0.5pt solid #c4b5fd;padding:6pt 8pt;margin-top:8pt;font-size:7pt;line-height:1.6;background:#f8fbfe; }
        .ifrs-box h4 { font-size:6.5pt;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#7c3aed;margin:0 0 3pt; }
        .ifrs-box p { margin:0 0 4pt;color:#6b5b8a; }
        .ifrs-box ul { margin:0 0 4pt 10pt;padding:0;color:#6b5b8a; }
        .ifrs-box li { margin-bottom:1pt; }
        .snapshot-table td { padding:2pt 4pt; }
        @media (max-width:640px) {
            .ecl-summary { grid-template-columns:repeat(2,1fr); }
            .ecl-rate-form { grid-template-columns:repeat(2,1fr); }
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
                    <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:4pt 8pt;font-size:7pt;font-weight:600;margin-bottom:8pt;">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:4pt 8pt;font-size:7pt;font-weight:600;margin-bottom:8pt;">
                        {{ session('error') }}
                    </div>
                @endif

                {{-- Current ECL Calculation --}}
                <div class="reg-doc">
                    <div class="reg-doc-body">

                        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8pt;">
                            <div>
                                <div class="reg-doc-title">IFRS 9 &mdash; Expected Credit Losses</div>
                                <p class="reg-doc-subtitle">
                                    Simplified approach &middot; Provision matrix as at {{ \Carbon\Carbon::parse($asOfDate)->format('d F Y') }}
                                </p>
                            </div>
                            <div style="display:flex;gap:4pt;align-items:center;">
                                <a href="{{ route('companies.reports.registers.export', $company) }}?register=ecl&as_of_date={{ $asOfDate }}"
                                    class="reg-btn">
                                    <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    Export Excel
                                </a>
                                <a href="{{ route('companies.reports.registers.export', $company) }}?register=all&as_of_date={{ $asOfDate }}"
                                    class="reg-btn" style="opacity:0.7;">
                                    All Registers
                                </a>
                            </div>
                        </div>

                        <form method="GET" action="{{ route('companies.ecl-register.index', $company) }}" style="display:flex;gap:4pt;align-items:flex-end;margin:6pt 0 8pt;">
                            <div>
                                <label style="display:block;font-size:5.5pt;font-weight:700;text-transform:uppercase;letter-spacing:0.07em;color:#6b5b8a;margin-bottom:2pt;">As at</label>
                                <input type="date" name="as_of_date" value="{{ $asOfDate }}" style="border:1px solid #c4b5fd;padding:3pt 4pt;font-size:7pt;font-family:Helvetica,Arial,sans-serif;color:#4c1d95;">
                            </div>
                            <button type="submit" class="reg-btn">Recalculate</button>
                        </form>

                        <hr class="reg-divider">

                        {{-- Summary metrics --}}
                        <div class="ecl-summary">
                            <div class="ecl-metric">
                                <div class="ecl-metric-label">Gross Receivables</div>
                                <div class="ecl-metric-value">R {{ number_format($totals['total_outstanding'], 2) }}</div>
                            </div>
                            <div class="ecl-metric">
                                <div class="ecl-metric-label">Required Allowance (ECL)</div>
                                <div class="ecl-metric-value">R {{ number_format($totals['total_ecl'], 2) }}</div>
                            </div>
                            <div class="ecl-metric">
                                <div class="ecl-metric-label">Current GL Balance</div>
                                <div class="ecl-metric-value">R {{ number_format($allowanceBalance, 2) }}</div>
                            </div>
                            @php $movement = round($totals['total_ecl'] - $allowanceBalance, 2); @endphp
                            <div class="ecl-metric">
                                <div class="ecl-metric-label">Movement Required</div>
                                <div class="ecl-metric-value {{ $movement > 0 ? 'deficit' : ($movement < 0 ? 'surplus' : '') }}">
                                    R {{ number_format(abs($movement), 2) }}
                                    @if ($movement > 0) <span style="font-size:5pt;">&uarr; increase</span>
                                    @elseif ($movement < 0) <span style="font-size:5pt;">&darr; decrease</span>
                                    @else <span style="font-size:5pt;">&mdash; nil</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if ($provisionPosted)
                            <div style="display:inline-flex;align-items:center;gap:4pt;background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;font-size:6.5pt;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;padding:3pt 8pt;margin-bottom:8pt;">
                                <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                                Provision posted for {{ \Carbon\Carbon::parse($asOfDate)->format('d M Y') }}
                            </div>
                        @elseif (abs($movement) >= 0.01)
                            <form method="POST" action="{{ route('companies.ecl-register.post', $company) }}" style="margin-bottom:8pt;">
                                @csrf
                                <input type="hidden" name="as_of_date" value="{{ $asOfDate }}">
                                <button type="submit" class="reg-btn primary">
                                    Post ECL Provision &mdash; R {{ number_format(abs($movement), 2) }} {{ $movement > 0 ? 'Increase' : 'Decrease' }}
                                </button>
                            </form>
                        @endif

                        {{-- Provision Matrix Rates --}}
                        <div class="reg-section-header">Provision Matrix &mdash; Loss Rates</div>
                        <form method="POST" action="{{ route('companies.ecl-register.rates', $company) }}" class="ecl-rate-form">
                            @csrf
                            <input type="hidden" name="as_of_date" value="{{ $asOfDate }}">
                            <div class="field">
                                <label>Current (0&ndash;30 days)</label>
                                <div class="input-pct">
                                    <input type="number" step="0.01" min="0" max="100" name="current_rate"
                                        value="{{ number_format($rates->current_rate * 100, 2) }}">
                                </div>
                            </div>
                            <div class="field">
                                <label>31&ndash;60 days</label>
                                <div class="input-pct">
                                    <input type="number" step="0.01" min="0" max="100" name="days_31_60_rate"
                                        value="{{ number_format($rates->days_31_60_rate * 100, 2) }}">
                                </div>
                            </div>
                            <div class="field">
                                <label>61&ndash;90 days</label>
                                <div class="input-pct">
                                    <input type="number" step="0.01" min="0" max="100" name="days_61_90_rate"
                                        value="{{ number_format($rates->days_61_90_rate * 100, 2) }}">
                                </div>
                            </div>
                            <div class="field">
                                <label>91+ days</label>
                                <div class="input-pct">
                                    <input type="number" step="0.01" min="0" max="100" name="days_91_plus_rate"
                                        value="{{ number_format($rates->days_91_plus_rate * 100, 2) }}">
                                </div>
                            </div>
                            <div style="display:flex;align-items:flex-end;">
                                <button type="submit" class="reg-btn">Save Rates</button>
                            </div>
                        </form>

                        {{-- ECL by customer (top 5) --}}
                        <div style="display:flex;justify-content:space-between;align-items:baseline;">
                            <div class="reg-section-header" style="flex:1;">ECL Allowance by Customer</div>
                            @if ($analyses->count() > 5)
                                <a href="{{ route('companies.ecl-register.customers', [$company, 'as_of_date' => $asOfDate]) }}" class="reg-link" style="font-size:6.5pt;white-space:nowrap;margin-left:8pt;">
                                    View all {{ $analyses->count() }} customers &rarr;
                                </a>
                            @endif
                        </div>

                        @if ($analyses->isEmpty())
                            <p class="reg-empty" style="text-align:center;padding:8pt 0;">No outstanding receivables as at this date.</p>
                        @else
                            @php $topFive = $analyses->sortByDesc('total_ecl')->take(5); @endphp
                            <table class="reg-table">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th class="amt">Gross Balance</th>
                                        <th class="amt">Current</th>
                                        <th class="amt">31&ndash;60</th>
                                        <th class="amt">61&ndash;90</th>
                                        <th class="amt">91+</th>
                                        <th class="amt">ECL Allowance</th>
                                        <th class="amt">ECL %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($topFive as $a)
                                        <tr>
                                            <td>
                                                @if ($a->customer)
                                                    <a class="reg-link" href="{{ route('companies.customers.show', [$company, $a->customer]) }}">{{ $a->customer->name }}</a>
                                                @else
                                                    &mdash;
                                                @endif
                                            </td>
                                            <td class="amt">{{ number_format($a->total_outstanding, 2) }}</td>
                                            <td class="amt">{{ number_format($a->ecl_current, 2) }}</td>
                                            <td class="amt">{{ number_format($a->ecl_31_60, 2) }}</td>
                                            <td class="amt">{{ number_format($a->ecl_61_90, 2) }}</td>
                                            <td class="amt">{{ number_format($a->ecl_91_plus, 2) }}</td>
                                            <td class="amt" style="font-weight:700;">{{ number_format($a->total_ecl, 2) }}</td>
                                            <td class="amt">{{ $a->total_outstanding > 0 ? number_format(($a->total_ecl / $a->total_outstanding) * 100, 1) : '0.0' }}%</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>Total (all {{ $analyses->count() }} customers)</td>
                                        <td class="amt">{{ number_format($totals['total_outstanding'], 2) }}</td>
                                        <td class="amt">{{ number_format($totals['ecl_current'], 2) }}</td>
                                        <td class="amt">{{ number_format($totals['ecl_31_60'], 2) }}</td>
                                        <td class="amt">{{ number_format($totals['ecl_61_90'], 2) }}</td>
                                        <td class="amt">{{ number_format($totals['ecl_91_plus'], 2) }}</td>
                                        <td class="amt">{{ number_format($totals['total_ecl'], 2) }}</td>
                                        <td class="amt">{{ $totals['total_outstanding'] > 0 ? number_format(($totals['total_ecl'] / $totals['total_outstanding']) * 100, 1) : '0.0' }}%</td>
                                    </tr>
                                </tfoot>
                            </table>

                            @if ($analyses->count() > 5)
                                <div style="text-align:center;padding:6pt 0;">
                                    <a href="{{ route('companies.ecl-register.customers', [$company, 'as_of_date' => $asOfDate]) }}" class="reg-btn">
                                        View All Customers &rarr;
                                    </a>
                                </div>
                            @endif
                        @endif

                        {{-- IFRS 7 disclosure --}}
                        <div class="ifrs-box" style="margin-top:10pt;">
                            <h4>Credit Risk Disclosure (IFRS 7 &amp; IFRS 9)</h4>
                            <p>
                                The company applies the simplified approach under IFRS 9 to measure the loss allowance for
                                trade receivables at an amount equal to lifetime expected credit losses. A provision matrix
                                groups receivables by days past due and applies historical loss rates adjusted for
                                forward-looking factors.
                            </p>
                            <p>Loss rates applied:</p>
                            <ul>
                                <li>Current (0&ndash;30 days): {{ number_format($rates->current_rate * 100, 2) }}%</li>
                                <li>31&ndash;60 days past due: {{ number_format($rates->days_31_60_rate * 100, 2) }}%</li>
                                <li>61&ndash;90 days past due: {{ number_format($rates->days_61_90_rate * 100, 2) }}%</li>
                                <li>91+ days past due: {{ number_format($rates->days_91_plus_rate * 100, 2) }}%</li>
                            </ul>
                            <p>
                                As at {{ \Carbon\Carbon::parse($asOfDate)->format('d F Y') }}, gross trade receivables
                                amounted to R&nbsp;{{ number_format($totals['total_outstanding'], 2) }}, against which a loss
                                allowance of R&nbsp;{{ number_format($totals['total_ecl'], 2) }} has been recognised, resulting
                                in net trade receivables of R&nbsp;{{ number_format($totals['total_outstanding'] - $totals['total_ecl'], 2) }}.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Snapshot history --}}
                @if ($snapshots->isNotEmpty())
                    <div class="reg-doc">
                        <div class="reg-doc-body">
                            <div class="reg-doc-title" style="font-size:8pt;">ECL Provision History</div>
                            <hr class="reg-divider">
                            <table class="reg-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th class="amt">Customers</th>
                                        <th class="amt">Gross Receivables</th>
                                        <th class="amt">ECL Allowance</th>
                                        <th class="amt">Loss Rate</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($snapshots as $snap)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($snap->as_of_date)->format('d M Y') }}</td>
                                            <td class="amt">{{ $snap->customer_count }}</td>
                                            <td class="amt">{{ number_format($snap->total_outstanding, 2) }}</td>
                                            <td class="amt">{{ number_format($snap->total_ecl, 2) }}</td>
                                            <td class="amt">{{ $snap->total_outstanding > 0 ? number_format(($snap->total_ecl / $snap->total_outstanding) * 100, 1) : '0.0' }}%</td>
                                            <td style="text-align:right;">
                                                <a href="{{ route('companies.ecl-register.show', [$company, $snap->as_of_date->format('Y-m-d')]) }}" class="reg-link" style="font-size:6.5pt;">
                                                    View &rarr;
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

            </main>
        </div>
    </div>
@endsection
