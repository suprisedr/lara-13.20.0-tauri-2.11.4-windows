@extends('layouts.public')

@section('title', $company->registered_name . ' — Statement of Changes in Equity')
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
                            <div class="co-section-heading"><p class="co-section-label">Annual Financial Statements</p><h2>Statement of Changes in Equity</h2></div>
                            <p class="is-period-label">
                                For the period {{ \Carbon\Carbon::parse($startDate)->format('d F Y') }} to
                                {{ \Carbon\Carbon::parse($endDate)->format('d F Y') }}
                            </p>
                        </div>
                        <div style="display:flex;align-items:center;gap:0.6rem;">
                            <a href="{{ route('companies.reports.changes-in-equity.pdf', [$company, 'start_date' => $startDate, 'end_date' => $endDate, 'rounding' => $rounding]) }}"
                                style="display:inline-flex;align-items:center;gap:0.35rem;background:#5e17eb;color:#fff;border-radius:0;padding:0.28rem 0.75rem;font-size:0.72rem;font-weight:700;text-decoration:none;transition:background 0.15s;white-space:nowrap;"
                                onmouseover="this.style.background='#4a10c4'"
                                onmouseout="this.style.background='#5e17eb'">
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

                    <form method="GET" action="{{ route('companies.reports.changes-in-equity', $company) }}"
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
                            <div class="afs-doc-title">Statement of Changes in Equity</div>
                            <div class="afs-doc-subtitle">
                                For the period {{ \Carbon\Carbon::parse($startDate)->format('d F Y') }} to
                                {{ \Carbon\Carbon::parse($endDate)->format('d F Y') }}
                            </div>
                        </div>

                        <div style="overflow-x:auto;">
                            @include('companies.reports._partials.soce')
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
@endsection
