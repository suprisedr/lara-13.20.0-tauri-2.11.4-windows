@extends('layouts.public')

@section('title', $company->registered_name . ' — General Ledger')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .co-topbar {
            padding: 1rem 2rem;
        }

        .co-main {
            padding: 1.25rem 1.5rem;
        }

        .co-card {
            margin-bottom: 0;
        }

        .co-card-head {
            padding: 0.875rem 1.25rem;
        }

        .gl-account-card {
            border: none;
            border-top: 1.5px solid #4c1d95;
            margin-bottom: 16pt;
            background: #fff;
        }

        .gl-account-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 3pt 6pt;
            background: #ede9fe;
            border-bottom: 1px solid #c4b5fd;
            flex-wrap: wrap;
            gap: 4pt;
        }

        .gl-account-code {
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 7.5pt;
            font-weight: bold;
            color: #4c1d95;
            margin-right: 8px;
        }

        .gl-account-name {
            font-size: 7.5pt;
            font-weight: bold;
            letter-spacing: 0.01em;
            color: #4c1d95;
        }

        .gl-account-type {
            font-size: 7pt;
            font-weight: normal;
            text-transform: uppercase;
            color: #7c3aed;
        }

        .gl-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7pt;
            font-family: Helvetica, Arial, "DejaVu Sans", sans-serif;
        }

        .gl-table thead th {
            font-size: 7pt;
            padding: 3pt 4pt;
            border-top: 1px solid #4c1d95;
            border-bottom: 1px solid #a78bfa;
            background: #f5f3ff;
            font-weight: bold;
            color: #4c1d95;
            text-align: left;
        }

        .gl-table thead th.right {
            text-align: right;
        }

        .gl-table tbody tr {
            border-bottom: 0.4pt solid #ddd6fe;
        }

        .gl-table tbody tr:hover {
            background: #faf5ff;
        }

        .gl-table td {
            padding: 2pt 4pt;
            font-size: 7pt;
            color: #23282d;
            vertical-align: middle;
        }

        .gl-table td.right {
            text-align: right;
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 7pt;
            white-space: nowrap;
        }

        .gl-table td.dim {
            color: #c4b5fd;
        }

        .gl-table tr.gl-ob-row td,
        .gl-table tr.gl-cb-row td {
            font-size: 7pt;
            font-weight: bold;
            padding: 3pt 4pt;
            background: #f5f3ff;
            color: #7c3aed;
        }

        .gl-table tr.gl-ob-row td {
            border-top: 1px solid #c4b5fd;
            border-bottom: 0.4pt solid #ddd6fe;
        }

        .gl-table tr.gl-cb-row td {
            border-top: 1px solid #a78bfa;
            border-bottom: 2px solid #4c1d95;
        }
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
                            <div class="co-section-heading"><p class="co-section-label">Reports</p><h2>General Ledger</h2></div>
                            <p class="is-period-label">
                                {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}
                                &ndash;
                                {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                            </p>
                        </div>
                        <a href="{{ route('companies.reports.general-ledger.pdf', [$company, 'start_date' => $startDate, 'end_date' => $endDate, 'rounding' => $rounding]) }}"
                            target="_blank" rel="noopener"
                            style="display:inline-flex;align-items:center;gap:0.35rem;background:#5e17eb;color:#fff;border-radius:0;padding:0.28rem 0.75rem;font-size:0.72rem;font-weight:700;text-decoration:none;transition:background 0.15s;white-space:nowrap;"
                            onmouseover="this.style.background='#4a10c4'" onmouseout="this.style.background='#5e17eb'">
                            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                <polyline points="7 10 12 15 17 10" />
                                <line x1="12" y1="15" x2="12" y2="3" />
                            </svg>
                            Download PDF
                        </a>
                    </div>

                    <form method="GET" action="{{ route('companies.reports.general-ledger', $company) }}"
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

                    @php
                        $roundingLabel = match ($rounding) {
                            1000 => "R'000",
                            1000000 => "R'm",
                            default => 'R',
                        };
                        $roundingDecimals = match ($rounding) {
                            1000 => 0,
                            1000000 => 2,
                            default => 2,
                        };
                    @endphp

                    <div style="padding: 1.25rem 1rem 0.5rem;">
                        @forelse ($ledger as $entry)
                            <div class="gl-account-card">
                                <div class="gl-account-head">
                                    <div>
                                        <span class="gl-account-code">{{ $entry->account->account_code }}</span>
                                        <span class="gl-account-name">{{ $entry->account->account_name }}</span>
                                    </div>
                                    <span class="gl-account-type">{{ ucfirst($entry->account->account_type) }}</span>
                                </div>
                                <div style="overflow-x:auto;">
                                    <table class="gl-table">
                                        <thead>
                                            <tr>
                                                <th style="width:55px;">Date</th>
                                                <th style="width:120px;">Reference</th>
                                                <th>Description</th>
                                                <th class="right" style="width:70px;">Debit ({{ $roundingLabel }})</th>
                                                <th class="right" style="width:70px;">Credit ({{ $roundingLabel }})</th>
                                                <th class="right" style="width:80px;">Balance ({{ $roundingLabel }})</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="gl-ob-row">
                                                <td colspan="5">Opening Balance</td>
                                                <td class="right">{{ number_format($entry->opening_balance / $rounding, $roundingDecimals) }}</td>
                                            </tr>
                                            @forelse ($entry->lines as $line)
                                                <tr>
                                                    <td style="white-space:nowrap;">
                                                        {{ \Carbon\Carbon::parse($line->date)->format('d/m/Y') }}
                                                    </td>
                                                    <td>
                                                        {{ $line->reference ?? '—' }}
                                                    </td>
                                                    <td>{{ $line->description }}</td>
                                                    <td class="{{ $line->debit ? 'right' : 'right dim' }}">
                                                        {{ $line->debit ? number_format($line->debit / $rounding, $roundingDecimals) : '—' }}
                                                    </td>
                                                    <td class="{{ $line->credit ? 'right' : 'right dim' }}">
                                                        {{ $line->credit ? number_format($line->credit / $rounding, $roundingDecimals) : '—' }}
                                                    </td>
                                                    <td class="right">
                                                        {{ number_format($line->running_balance / $rounding, $roundingDecimals) }}
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" style="font-style:italic;color:#aaa;padding:4px 6px;">No posted transactions in this period.</td>
                                                </tr>
                                            @endforelse
                                            <tr class="gl-cb-row">
                                                <td colspan="3">
                                                    Closing Balance —
                                                    Debits: {{ number_format($entry->period_debits / $rounding, $roundingDecimals) }} /
                                                    Credits: {{ number_format($entry->period_credits / $rounding, $roundingDecimals) }}
                                                </td>
                                                <td colspan="3" class="right">{{ number_format($entry->closing_balance / $rounding, $roundingDecimals) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">
                                <p style="font-weight:700;color:#555;margin:0 0 0.3rem;">No transactions found</p>
                                <p style="font-size:0.8rem;margin:0;">No posted transactions exist in the selected period.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </main>
        </div>
    </div>
@endsection
