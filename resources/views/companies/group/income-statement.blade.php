@extends('layouts.public')

@section('title', $company->registered_name . ' — Consolidated Statement of Profit or Loss')
@section('meta-robots', 'noindex, nofollow')

@php
    $fmt = fn($v) => ($v < 0 ? '(' : '') . 'R ' . number_format(abs($v) / $rounding, $rounding === 1 ? 2 : 0) . ($v < 0 ? ')' : '');
@endphp

@push('styles')
    @include('companies._styles')
    <style>
        .co-topbar { padding: 1rem 2rem; }
        .co-main { padding: 1.25rem 1.5rem; }
        .cfs-table { width: 100%; border-collapse: collapse; }
        .cfs-table td { padding: 0.4rem 0.75rem; font-size: 0.85rem; }
        .cfs-table td.amt { text-align: right; font-family: monospace; white-space: nowrap; }
        .cfs-total td { font-weight: 800; border-top: 1.5px solid #191919; border-bottom: 1px solid #191919; }
        .cfs-grand td { font-weight: 900; border-top: 2px solid #005bf0; border-bottom: 3px double #005bf0; }
        .cfs-attrib td { font-size: 0.8rem; color: #191919; padding: 0.35rem 0.75rem; }
        .ws-table { width: 100%; border-collapse: collapse; margin-top: 0.5rem; }
        .ws-table th, .ws-table td { padding: 0.4rem 0.6rem; font-size: 0.76rem; border-bottom: 1px solid #f4fafc; }
        .ws-table th { text-align: right; font-size: 0.7rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #6f869b; }
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
                            <div class="co-section-heading"><p class="co-section-label">Consolidated Annual Financial Statements</p><h2>Consolidated Statement of Profit or Loss</h2></div>
                            <p class="is-period-label">For the period {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} – {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('companies.group.income-statement', $company) }}" class="is-filter-bar">
                        <div>
                            <label for="start_date">From</label>
                            <input type="date" id="start_date" name="start_date" value="{{ $startDate }}">
                        </div>
                        <div>
                            <label for="end_date">To</label>
                            <input type="date" id="end_date" name="end_date" value="{{ $endDate }}">
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
                            <tr><td>Revenue</td><td class="amt">{{ $fmt($revenue) }}</td></tr>
                            <tr><td>Operating &amp; other expenses</td><td class="amt">{{ $fmt(-$expenses) }}</td></tr>
                            <tr class="cfs-total"><td>Operating profit</td><td class="amt">{{ $fmt($operatingProfit) }}</td></tr>
                            @if (abs($remeasurementGain) > 0.005)
                                <tr><td>Gain on step acquisition (remeasurement to fair value)</td><td class="amt">{{ $fmt($remeasurementGain) }}</td></tr>
                            @endif
                            @if (abs($disposalGain) > 0.005)
                                <tr><td>Gain / (loss) on disposal of subsidiary</td><td class="amt">{{ $fmt($disposalGain) }}</td></tr>
                            @endif
                            <tr class="cfs-grand"><td>Profit for the period</td><td class="amt">{{ $fmt($profit) }}</td></tr>
                        </table>

                        <table class="cfs-table" style="margin-top:1rem;">
                            <tr class="cfs-attrib"><td colspan="2" style="font-weight:800;color:#005bf0;font-size:0.7rem;letter-spacing:0.12em;text-transform:uppercase;">Profit attributable to</td></tr>
                            <tr class="cfs-attrib"><td>Owners of the parent</td><td class="amt">{{ $fmt($ownersProfit) }}</td></tr>
                            <tr class="cfs-attrib"><td>Non-controlling interests</td><td class="amt">{{ $fmt($nciProfit) }}</td></tr>
                            <tr class="cfs-total"><td>Profit for the period</td><td class="amt">{{ $fmt($profit) }}</td></tr>
                        </table>
                    </div>
                </div>

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
                                    <th>Revenue</th>
                                    <th>Expenses</th>
                                    <th>Profit</th>
                                    <th>NCI share</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($worksheet as $row)
                                    <tr>
                                        <td>{{ $row['company']->registered_name }} @if($row['is_parent'])<span style="font-size:0.7rem;color:#005bf0;font-weight:800;">(PARENT)</span>@endif</td>
                                        <td class="amt">{{ $fmt($row['revenue']) }}</td>
                                        <td class="amt">{{ $fmt($row['expenses']) }}</td>
                                        <td class="amt">{{ $fmt($row['profit']) }}</td>
                                        <td class="amt">{{ $row['is_parent'] ? '—' : $fmt($row['nci_share']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <p style="font-size:0.72rem;color:#6f869b;margin:0.8rem 0 0;">
                            Intragroup trading is removed via
                            <a href="{{ route('companies.group.eliminations', $company) }}" style="color:#005bf0;">Eliminations</a>.
                            NCI share of profit = (1 − holding %) × each subsidiary's profit for the period.
                        </p>
                    </div>
                </div>
            </main>
        </div>
    </div>
@endsection
