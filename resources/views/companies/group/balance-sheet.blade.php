@extends('layouts.public')

@section('title', $company->registered_name . ' — Consolidated Statement of Financial Position')
@section('meta-robots', 'noindex, nofollow')

@php
    $fmt = fn($v) => ($v < 0 ? '(' : '') . 'R ' . number_format(abs($v) / $rounding, $rounding === 1 ? 2 : 0) . ($v < 0 ? ')' : '');
    $isBalanced = abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01;
@endphp

@push('styles')
    @include('companies._styles')
    <style>
        .co-topbar { padding: 1rem 2rem; }
        .co-main { padding: 1.25rem 1.5rem; }
        .cfs-table { width: 100%; border-collapse: collapse; }
        .cfs-table td { padding: 0.4rem 0.75rem; font-size: 0.85rem; }
        .cfs-table td.amt { text-align: right; font-family: monospace; white-space: nowrap; }
        .cfs-section td { font-size: 0.62rem; font-weight: 900; letter-spacing: 0.16em; text-transform: uppercase; color: #5e17eb; padding-top: 0.9rem; }
        .cfs-total td { font-weight: 800; border-top: 1.5px solid #1b1b18; border-bottom: 1px solid #1b1b18; }
        .cfs-grand td { font-weight: 900; border-top: 2px solid #5e17eb; border-bottom: 3px double #5e17eb; }
        .ws-table { width: 100%; border-collapse: collapse; margin-top: 0.5rem; }
        .ws-table th, .ws-table td { padding: 0.4rem 0.6rem; font-size: 0.76rem; border-bottom: 1px solid #f3f4f6; }
        .ws-table th { text-align: right; font-size: 0.6rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #9ca3af; }
        .ws-table th:first-child, .ws-table td:first-child { text-align: left; }
        .ws-table td.amt { text-align: right; font-family: monospace; white-space: nowrap; }
    </style>
@endpush

@section('content')
    <div class="co-wrap">
        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')
            <main class="co-main">
                <div class="co-card">
                    <div class="co-card-head">
                        <div>
                            <div class="co-section-heading"><p class="co-section-label">Consolidated Annual Financial Statements</p><h2>Consolidated Statement of Financial Position</h2></div>
                            <p class="is-period-label">As at {{ \Carbon\Carbon::parse($asOfDate)->format('d F Y') }}</p>
                        </div>
                        <span class="is-balanced-badge {{ $isBalanced ? 'ok' : 'off' }}">{{ $isBalanced ? 'Balanced' : 'Out of Balance' }}</span>
                    </div>

                    <form method="GET" action="{{ route('companies.group.balance-sheet', $company) }}" class="is-filter-bar">
                        <div>
                            <label for="as_of_date">As at</label>
                            <input type="date" id="as_of_date" name="as_of_date" value="{{ $asOfDate }}">
                        </div>
                        <div>
                            <label for="rounding">Rounding</label>
                            <select id="rounding" name="rounding">
                                <option value="1" @selected($rounding === 1)>Exact (R)</option>
                                <option value="1000" @selected($rounding === 1000)>Thousands (R&rsquo;000)</option>
                                <option value="1000000" @selected($rounding === 1000000)>Millions (R&rsquo;m)</option>
                            </select>
                        </div>
                        <button type="submit" class="is-filter-btn">Apply</button>
                    </form>

                    <div style="padding:1rem 1.25rem 1.5rem;">
                        <table class="cfs-table">
                            <tr class="cfs-section"><td colspan="2">Assets</td></tr>
                            <tr><td>Non-current assets</td><td class="amt">{{ $fmt($nonCurrentAssets) }}</td></tr>
                            <tr><td>Goodwill on consolidation</td><td class="amt">{{ $fmt($goodwill) }}</td></tr>
                            <tr><td>Current assets</td><td class="amt">{{ $fmt($currentAssets) }}</td></tr>
                            <tr class="cfs-total"><td>Total assets</td><td class="amt">{{ $fmt($totalAssets) }}</td></tr>

                            <tr class="cfs-section"><td colspan="2">Equity</td></tr>
                            <tr><td>Equity attributable to owners of the parent</td><td class="amt">{{ $fmt($ownersEquity) }}</td></tr>
                            <tr><td>Non-controlling interests</td><td class="amt">{{ $fmt($nci) }}</td></tr>
                            <tr class="cfs-total"><td>Total equity</td><td class="amt">{{ $fmt($totalEquity) }}</td></tr>

                            <tr class="cfs-section"><td colspan="2">Liabilities</td></tr>
                            <tr><td>Non-current liabilities</td><td class="amt">{{ $fmt($nonCurrentLiabilities) }}</td></tr>
                            <tr><td>Current liabilities</td><td class="amt">{{ $fmt($currentLiabilities) }}</td></tr>
                            <tr class="cfs-total"><td>Total liabilities</td><td class="amt">{{ $fmt($totalLiabilities) }}</td></tr>

                            <tr class="cfs-grand"><td>Total equity and liabilities</td><td class="amt">{{ $fmt($totalEquity + $totalLiabilities) }}</td></tr>
                        </table>
                    </div>
                </div>

                {{-- Consolidation worksheet --}}
                <div class="co-card" style="margin-top:1.25rem;">
                    <div class="co-card-head">
                        <div>
                            <div class="co-section-heading"><p class="co-section-label">Workings</p><h2>Consolidation Worksheet</h2></div>
                        </div>
                    </div>
                    <div style="padding:0.5rem 1.25rem 1.25rem;overflow-x:auto;">
                        <table class="ws-table">
                            <thead>
                                <tr>
                                    <th>Entity</th>
                                    <th>Non-current assets</th>
                                    <th>Current assets</th>
                                    <th>Non-current liab.</th>
                                    <th>Current liab.</th>
                                    <th>Equity</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($worksheet as $row)
                                    <tr>
                                        <td>{{ $row['company']->registered_name }} @if($row['is_parent'])<span style="font-size:0.6rem;color:#7c3aed;font-weight:800;">(PARENT)</span>@endif</td>
                                        <td class="amt">{{ $fmt($row['non_current_assets']) }}</td>
                                        <td class="amt">{{ $fmt($row['current_assets']) }}</td>
                                        <td class="amt">{{ $fmt($row['non_current_liabilities']) }}</td>
                                        <td class="amt">{{ $fmt($row['current_liabilities']) }}</td>
                                        <td class="amt">{{ $fmt($row['equity']) }}</td>
                                    </tr>
                                @endforeach
                                <tr style="font-weight:800;border-top:1.5px solid #ddd;">
                                    <td>Combined</td>
                                    <td class="amt">{{ $fmt($worksheet->sum('non_current_assets')) }}</td>
                                    <td class="amt">{{ $fmt($worksheet->sum('current_assets')) }}</td>
                                    <td class="amt">{{ $fmt($worksheet->sum('non_current_liabilities')) }}</td>
                                    <td class="amt">{{ $fmt($worksheet->sum('current_liabilities')) }}</td>
                                    <td class="amt">{{ $fmt($worksheet->sum('equity')) }}</td>
                                </tr>
                            </tbody>
                        </table>

                        @if (!empty($subDetails))
                            <h3 style="font-size:0.78rem;font-weight:800;margin:1.25rem 0 0.4rem;color:#374151;">Goodwill &amp; non-controlling interest</h3>
                            <table class="ws-table">
                                <thead>
                                    <tr>
                                        <th>Subsidiary</th>
                                        <th>Holding now</th>
                                        <th>Investment cost</th>
                                        <th>Goodwill (frozen)</th>
                                        <th>Equity now</th>
                                        <th>NCI</th>
                                        <th>Ownership reserve</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($subDetails as $d)
                                        <tr>
                                            <td>{{ $d['company']->registered_name }}</td>
                                            <td class="amt">{{ number_format($d['ownership'] * 100, 2) }}%</td>
                                            <td class="amt">{{ $fmt($d['investment_cost']) }}</td>
                                            <td class="amt">{{ $fmt($d['goodwill']) }}</td>
                                            <td class="amt">{{ $fmt($d['equity_current']) }}</td>
                                            <td class="amt">{{ $fmt($d['nci']) }}</td>
                                            <td class="amt">{{ $fmt($d['equity_reserve']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <p style="font-size:0.72rem;color:#9ca3af;margin:0.6rem 0 0;">
                                Goodwill is measured at the date control was obtained and frozen thereafter (IFRS 3, partial-goodwill method).
                                NCI = (1 − holding %) × subsidiary equity at the reporting date.
                                The ownership reserve is the cumulative effect of buying/selling interest while retaining control
                                (an equity transaction under IFRS 10.B96), included within equity attributable to owners.
                            </p>
                        @endif

                        <p style="font-size:0.72rem;color:#9ca3af;margin:0.8rem 0 0;">
                            Parent's investment in subsidiaries eliminated: {{ $fmt($eliminatedInvestment) }}.
                            Intragroup eliminations are managed under
                            <a href="{{ route('companies.group.eliminations', $company) }}" style="color:#5e17eb;">Eliminations</a>.
                        </p>
                    </div>
                </div>
            </main>
        </div>
    </div>
@endsection
