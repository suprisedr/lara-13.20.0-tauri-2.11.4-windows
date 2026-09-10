@extends('layouts.public')

@section('title', $company->registered_name . ' — Statement of Financial Position')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .co-topbar { padding: 1rem 2rem; }
        .co-main { padding: 1.25rem 1.5rem; }
        .co-card { margin-bottom: 0; }
        .co-card-head { padding: 0.875rem 1.25rem; }
    </style>
@endpush

@section('content')
    <div class="co-wrap">
        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')
            <main class="co-main">
                <div class="co-card">
                    @php
                        $hasData =
                            $currentAssets->isNotEmpty() ||
                            $nonCurrentAssets->isNotEmpty() ||
                            $currentLiabilities->isNotEmpty() ||
                            $nonCurrentLiabilities->isNotEmpty() ||
                            $equityAccounts->isNotEmpty();
                        $isBalanced = abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01;
                    @endphp

                    <div class="co-card-head">
                        <div>
                            <div class="co-section-heading"><p class="co-section-label">Annual Financial Statements</p><h2>Statement of Financial Position</h2></div>
                            <p class="is-period-label">
                                As at {{ \Carbon\Carbon::parse($asOfDate)->format('d F Y') }}
                                @if ($compare && $priorAsOfDate)
                                    &middot; Compared with {{ \Carbon\Carbon::parse($priorAsOfDate)->format('d F Y') }}
                                @endif
                            </p>
                        </div>
                        <div style="display:flex;align-items:center;gap:0.6rem;">
                            @if ($hasData)
                                <span class="is-balanced-badge {{ $isBalanced ? 'ok' : 'off' }}">
                                    @if ($isBalanced)
                                        <svg width="11" height="11" fill="none" stroke="currentColor"
                                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                                            viewBox="0 0 24 24">
                                            <polyline points="20 6 9 17 4 12" />
                                        </svg>
                                        Balanced
                                    @else
                                        <svg width="11" height="11" fill="none" stroke="currentColor"
                                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                                            viewBox="0 0 24 24">
                                            <line x1="12" y1="8" x2="12" y2="12" />
                                            <line x1="12" y1="16" x2="12.01" y2="16" />
                                            <path
                                                d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                                        </svg>
                                        Out of Balance
                                    @endif
                                </span>
                                <a href="{{ route('companies.reports.balance-sheet.pdf', [$company, 'as_of_date' => $asOfDate, 'rounding' => $rounding, 'compare' => $compare ? 1 : 0, 'compare_date' => $priorAsOfDate]) }}"
                                    target="_blank" rel="noopener"
                                    style="display:inline-flex;align-items:center;gap:0.35rem;background:#005bf0;color:#fff;border-radius:0;padding:0.28rem 0.75rem;font-size:0.72rem;font-weight:700;text-decoration:none;transition:background 0.15s;white-space:nowrap;"
                                    onmouseover="this.style.background='#0047c4'"
                                    onmouseout="this.style.background='#005bf0'">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                        <polyline points="7 10 12 15 17 10" />
                                        <line x1="12" y1="15" x2="12" y2="3" />
                                    </svg>
                                    Download PDF
                                </a>
                            @endif
                        </div>
                    </div>

                    <form method="GET" action="{{ route('companies.reports.balance-sheet', $company) }}"
                        class="is-filter-bar">
                        <div>
                            <label for="as_of_date">As at</label>
                            <input type="date" id="as_of_date" name="as_of_date" value="{{ $asOfDate }}">
                        </div>
                        <div>
                            <label for="compare">Comparative</label>
                            <label style="display:inline-flex;align-items:center;gap:0.35rem;font-size:0.8rem;">
                                <input type="checkbox" id="compare" name="compare" value="1"
                                    @checked($compare)>
                                Compare to
                            </label>
                        </div>
                        <div>
                            <label for="compare_date">Compare date</label>
                            <input type="date" id="compare_date" name="compare_date"
                                value="{{ $priorAsOfDate ?? \Illuminate\Support\Carbon::parse($asOfDate)->subYear()->format('Y-m-d') }}">
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

                    @if (!$hasData)
                        <div class="empty-state">
                            <p style="font-weight:700;color:#5a7186;margin:0 0 0.3rem;">No accounts found</p>
                            <p style="font-size:0.8rem;margin:0;">Set up your chart of accounts first.</p>
                        </div>
                    @else
                        <div style="padding:1rem 1.25rem 1.5rem;">
                            <div class="afs-letterhead">
                                <div>
                                    <div class="afs-letterhead-name">{{ $company->registered_name }}</div>
                                    <div class="afs-letterhead-meta">
                                        {{ $company->company_type_label ?? '' }}
                                        @if ($company->registration_number)
                                            &nbsp;&nbsp;Registration number: {{ $company->registration_number }}
                                        @endif
                                        @if ($company->income_tax_number)
                                            &nbsp;&nbsp;Income tax number: {{ $company->income_tax_number }}
                                        @endif
                                    </div>
                                </div>
                                <div class="afs-letterhead-meta afs-right">
                                    <div>Date: {{ now()->format('d F Y') }}</div>
                                </div>
                            </div>

                            <div class="afs-doc-header">
                                <div class="afs-doc-title">Statement of Financial Position</div>
                                <div class="afs-doc-subtitle">
                                    As at {{ \Carbon\Carbon::parse($asOfDate)->format('d F Y') }}
                                </div>
                            </div>

                            <div style="overflow-x:auto;">
                                @include('companies.reports._partials.sofp')
                            </div>
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>
@endsection
