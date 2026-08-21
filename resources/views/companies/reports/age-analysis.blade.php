@extends('layouts.public')

@section('title', $company->registered_name . ' — Debtors Age Analysis')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .co-topbar { padding: 1rem 2rem; }
        .co-main { padding: 1.25rem 1.5rem; }
        .co-card { margin-bottom: 1.5rem; }
        .co-card-head { padding: 0.875rem 1.25rem; }

        .aa-table { width: 100%; border-collapse: collapse; font-size: 10.5pt; }

        .aa-table thead th {
            background: #005bf0;
            border: none;
            padding: 0.6rem 1rem;
            font-size: 10.5pt;
            font-weight: 700;
            text-transform: none;
            letter-spacing: 0;
            color: rgba(255,255,255,0.85);
            text-align: left;
            white-space: nowrap;
        }

        .aa-table thead th.amt { text-align: right; }

        .aa-table tbody td {
            padding: 0.6rem 1rem;
            border-bottom: 1px solid #d3e2f5;
            vertical-align: top;
            color: #1a345b;
        }

        .aa-table tbody td.amt {
            text-align: right;
            font-family: inherit;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .aa-table tbody tr:hover td { background: #f7fbfd; }

        .aa-table tfoot td {
            padding: 0.65rem 1rem;
            border-top: 0.5pt solid #000000;
            font-weight: 800;
            font-size: 10.5pt;
            background: #eaf8fb;
            color: #1a345b;
        }

        .aa-table tfoot td.amt {
            text-align: right;
            font-family: inherit;
            font-variant-numeric: tabular-nums;
        }

        .aa-table a.row-link {
            color: #005bf0;
            font-weight: 700;
            text-decoration: none;
        }

        .aa-table a.row-link:hover { text-decoration: underline; }


        .ifrs-note h3 {
            font-size: 11.5pt;
            font-weight: 800;
            color: #191919;
            margin: 0 0 0.5rem;
        }

        .ifrs-note p { margin: 0 0 0.75rem; }

        .ifrs-note ul { margin: 0 0 0.75rem 1.25rem; padding: 0; }

        .ifrs-note li { margin-bottom: 0.25rem; }

        .empty-state { padding: 3rem 2rem; text-align: center; color: #888; }
    </style>
@endpush

@section('content')
    <div class="co-wrap">
        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')
            <main class="co-main">

                @if (session('success'))
                    <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:0.75rem 1.25rem;border-radius:0;font-size:0.855rem;font-weight:600;margin-bottom:1.5rem;">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- Debtors age analysis --}}
                <div class="co-card">
                    <div class="co-card-head">
                        <div>
                            <div class="co-section-heading"><p class="co-section-label">Receivables</p><h2>Debtors Age Analysis</h2></div>
                            <p class="is-period-label">As at {{ \Carbon\Carbon::parse($asOfDate)->format('d F Y') }}</p>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('companies.reports.age-analysis', $company) }}" class="is-filter-bar">
                        <div>
                            <label for="as_of_date">As at</label>
                            <input type="date" id="as_of_date" name="as_of_date" value="{{ $asOfDate }}">
                        </div>
                        <div style="display:flex;align-items:flex-end;">
                            <button type="submit" style="background:#005bf0;color:#fff;border:none;border-radius:0;padding:0.45rem 1rem;font-size:0.8rem;font-weight:700;cursor:pointer;">
                                Update
                            </button>
                        </div>
                    </form>

                    @if ($analyses->isEmpty())
                        <div class="empty-state">
                            <p style="font-weight:700;color:#5a7186;margin:0 0 0.3rem;">No outstanding receivables</p>
                            <p style="font-size:0.8rem;margin:0;">There are no open invoices (pending, partially paid or overdue) as at this date.</p>
                        </div>
                    @else
                        <div style="overflow-x:auto;">
                            <table class="aa-table">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th class="amt">Current</th>
                                        <th class="amt">31&ndash;60 Days</th>
                                        <th class="amt">61&ndash;90 Days</th>
                                        <th class="amt">91+ Days</th>
                                        <th class="amt">Total Outstanding</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($analyses as $analysis)
                                        <tr>
                                            <td>
                                                @if ($analysis->customer)
                                                    <a class="row-link" href="{{ route('companies.customers.show', [$company, $analysis->customer]) }}">
                                                        {{ $analysis->customer->name }}
                                                    </a>
                                                @else
                                                    &mdash;
                                                @endif
                                            </td>
                                            <td class="amt">{{ number_format($analysis->current_amount, 2) }}</td>
                                            <td class="amt">{{ number_format($analysis->days_31_60, 2) }}</td>
                                            <td class="amt">{{ number_format($analysis->days_61_90, 2) }}</td>
                                            <td class="amt">{{ number_format($analysis->days_91_plus, 2) }}</td>
                                            <td class="amt">{{ number_format($analysis->total_outstanding, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>Total</td>
                                        <td class="amt">{{ number_format($totals['current_amount'], 2) }}</td>
                                        <td class="amt">{{ number_format($totals['days_31_60'], 2) }}</td>
                                        <td class="amt">{{ number_format($totals['days_61_90'], 2) }}</td>
                                        <td class="amt">{{ number_format($totals['days_91_plus'], 2) }}</td>
                                        <td class="amt">{{ number_format($totals['total_outstanding'], 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="co-card" style="padding:1rem 1.25rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;">
                    <p style="margin:0;font-size:0.82rem;color:#5a7186;">
                        For ECL provision matrix, loss allowance calculation, and IFRS 7 credit risk disclosure,
                        see the <strong>ECL Register</strong>.
                    </p>
                    <a href="{{ route('companies.ecl-register.index', $company) }}"
                        style="white-space:nowrap;background:#005bf0;color:#fff;border:none;padding:0.45rem 1rem;font-size:0.78rem;font-weight:700;text-decoration:none;display:inline-block;">
                        Open ECL Register &rarr;
                    </a>
                </div>

            </main>
        </div>
    </div>
@endsection
