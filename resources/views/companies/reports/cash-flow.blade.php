@extends('layouts.public')

@section('title', $company->registered_name . ' — Statement of Cash Flows')
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
                    <div class="co-card-head">
                        <div>
                            <div class="co-section-heading"><p class="co-section-label">Annual Financial Statements</p><h2>Statement of Cash Flows</h2></div>
                            <p class="is-period-label">
                                For the period {{ \Carbon\Carbon::parse($startDate)->format('d F Y') }}
                                &ndash;
                                {{ \Carbon\Carbon::parse($endDate)->format('d F Y') }}
                                @if ($compare)
                                    &middot; Prior year comparative
                                @endif
                            </p>
                        </div>
                        @if (session('success'))
                            <div style="background:#dcfce7;color:#15803d;border-radius:0;padding:0.35rem 0.8rem;font-size:0.78rem;font-weight:600;">
                                {{ session('success') }}
                            </div>
                        @endif
                        <div style="display:flex;gap:0.5rem;align-items:center;">
                            <a href="{{ route('companies.cash-flow-manual.edit', [$company, 'start_date' => $startDate, 'end_date' => $endDate]) }}"
                                style="display:inline-flex;align-items:center;gap:0.35rem;background:#f4fafc;color:#005bf0;border:1px solid #d6caf7;border-radius:0;padding:0.28rem 0.75rem;font-size:0.72rem;font-weight:700;text-decoration:none;white-space:nowrap;">
                                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                                </svg>
                                Edit entries
                            </a>
                            <a href="{{ route('companies.reports.cash-flow.pdf', [$company, 'start_date' => $startDate, 'end_date' => $endDate, 'rounding' => $rounding, 'compare' => $compare ? 1 : 0]) }}"
                                target="_blank" rel="noopener"
                                style="display:inline-flex;align-items:center;gap:0.35rem;background:#005bf0;color:#fff;border-radius:0;padding:0.28rem 0.75rem;font-size:0.72rem;font-weight:700;text-decoration:none;transition:background 0.15s;white-space:nowrap;"
                                onmouseover="this.style.background='#0047c4'" onmouseout="this.style.background='#005bf0'">
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

                    <form method="GET" action="{{ route('companies.reports.cash-flow', $company) }}"
                        class="is-filter-bar">
                        <div>
                            <label for="start_date">From</label>
                            <input type="date" id="start_date" name="start_date" value="{{ $startDate }}">
                        </div>
                        <div>
                            <label for="end_date">To</label>
                            <input type="date" id="end_date" name="end_date" value="{{ $endDate }}">
                        </div>
                        <div>
                            <label for="compare">Comparative</label>
                            <label style="display:inline-flex;align-items:center;gap:0.35rem;font-size:0.8rem;">
                                <input type="checkbox" id="compare" name="compare" value="1"
                                    @checked($compare ?? false)>
                                Prior year
                            </label>
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

                    @if (false)
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
                                <div class="afs-doc-title">Statement of Cash Flows</div>
                                <div class="afs-doc-subtitle">
                                    For the year ended {{ \Carbon\Carbon::parse($endDate)->format('d F Y') }}
                                </div>
                            </div>

                            <div style="overflow-x:auto;">
                                @include('companies.reports._partials.socf')
                            </div>
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>
@endsection
