@extends('layouts.public')

@section('title', $company->registered_name . ' — Opening Balances')
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

        .co-table thead th {
            padding: 0.3rem 0.7rem;
            font-size: 0.58rem;
        }

        .co-table td {
            padding: 0.15rem 0.7rem;
            font-size: 0.74rem;
            line-height: 1.25;
        }

        /* Dense section header rows */
        .co-table tr.is-section-label-lg td {
            padding: 0.22rem 0.7rem;
            font-size: 0.6rem;
        }

        /* Compact filter row (mirrors the reports views) */
        .ob-filter {
            display: flex;
            align-items: flex-end;
            gap: 0.75rem;
            flex-wrap: wrap;
            background: #fff;
            border: 1px solid #f0f0f0;
            border-radius: 0;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
        }

        .ob-filter label {
            display: block;
            font-size: 0.68rem;
            font-weight: 700;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 0.2rem;
        }

        .ob-filter select {
            border: 1.5px solid #e5e7eb;
            border-radius: 0;
            padding: 0.35rem 0.6rem;
            font-size: 0.8rem;
            font-family: inherit;
            color: #1b1b18;
            background: #fff;
            min-width: 230px;
        }

        .btn-xs {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            border-radius: 0;
            padding: 0.38rem 0.85rem;
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            font-family: inherit;
            transition: background 0.15s, border-color 0.15s, color 0.15s;
        }

        .btn-xs-primary {
            background: #5e17eb;
            color: #fff;
            border: none;
        }

        .btn-xs-primary:hover {
            background: #4a10c4;
        }

        .btn-xs-ghost {
            background: #ede9fe;
            color: #6d28d9;
            border: 1px solid #c4b5fd;
        }

        .btn-xs-ghost:hover {
            background: #ddd6fe;
        }

        .ob-input {
            width: 100px;
            text-align: right;
            font-family: monospace;
            font-size: 0.73rem;
            padding: 0.1rem 0.35rem;
            border: 1px solid rgba(94, 23, 235, 0.25);
            border-radius: 0;
            color: #1b1b18;
            background: #faf5ff;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .ob-input:focus {
            border-color: #5e17eb;
            box-shadow: 0 0 0 2px rgba(94, 23, 235, 0.12);
        }
    </style>
@endpush

@section('content')
    <div class="co-wrap">

        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">
                @if (session('error'))
                    <div style="background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;padding:0.55rem 0.9rem;border-radius:0;font-size:0.78rem;margin-bottom:0.85rem;">
                        {{ session('error') }}
                    </div>
                @endif

                {{-- ── Financial period selector ── --}}
                <div class="ob-filter">
                    <div>
                        <label for="ob-period">Financial period</label>
                        <select id="ob-period"
                            onchange="window.location.href='{{ route('companies.opening-balances.edit', $company) }}?period=' + this.value">
                            @foreach ($periods as $p)
                                <option value="{{ $p->id }}" @selected($p->id === $period->id)>
                                    {{ $p->label }}{{ $p->id === $periods->first()->id ? ' (opening period)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" form="open-next-period-form" class="btn-xs btn-xs-ghost"
                        style="align-self:flex-end;"
                        onclick="event.preventDefault(); document.getElementById('open-next-period-form').dispatchEvent(new Event('submit', {bubbles:true,cancelable:true}));">
                        + Open next period
                    </button>

                    <span style="font-size:0.7rem;color:#888;align-self:flex-end;padding-bottom:0.45rem;margin-left:auto;">
                        {{ \Carbon\Carbon::parse($period->start_date)->format('d M Y') }}
                        &ndash; {{ \Carbon\Carbon::parse($period->end_date)->format('d M Y') }}
                    </span>
                </div>

                <form method="POST" action="{{ route('companies.opening-balances.update', $company) }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="period_id" value="{{ $period->id }}">

                    <div class="co-card">
                        <div class="co-card-head">
                            <div>
                                <div class="co-section-heading"><p class="co-section-label">Chart of Accounts</p><h2>Opening Balances</h2></div>
                                <p style="font-size:0.72rem;color:#888;margin:0.15rem 0 0;">
                                    Balance each account carries at the start of {{ $period->label }}. Use negative values
                                    for credit balances on debit-normal accounts (and vice versa).
                                </p>
                            </div>
                            <div style="display:flex;align-items:center;gap:0.6rem;flex-shrink:0;">
                                <a href="{{ route('companies.chart-of-accounts', $company) }}" class="btn-xs"
                                    style="color:#888;border:1px solid #e5e7eb;background:#fff;">Cancel</a>
                                <button type="submit" class="btn-xs btn-xs-primary">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                                        <polyline points="17 21 17 13 7 13 7 21" />
                                        <polyline points="7 3 7 8 15 8" />
                                    </svg>
                                    Save
                                </button>
                            </div>
                        </div>

                        @php
                            $typeOrder = ['assets', 'liabilities', 'equity', 'income', 'expenses'];
                            $typeLabels = [
                                'assets' => 'Assets',
                                'liabilities' => 'Liabilities',
                                'equity' => 'Equity',
                                'income' => 'Income',
                                'expenses' => 'Expenses',
                            ];
                        @endphp

                        <div style="overflow-x:auto;">
                            <table class="co-table">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Account Name</th>
                                        <th class="hide-mobile">Type</th>
                                        <th style="text-align:right;">Opening Balance (R)</th>
                                        <th style="text-align:right;">Closing Balance (R)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($typeOrder as $type)
                                        @if ($accounts->has($type))
                                            <tr class="is-section-label-lg">
                                                <td colspan="5">{{ $typeLabels[$type] ?? $type }}</td>
                                            </tr>
                                            @foreach ($accounts[$type] as $account)
                                                <tr>
                                                    <td style="font-family:monospace;font-weight:700;color:#5e17eb;white-space:nowrap;">
                                                        {{ $account->account_code }}</td>
                                                    <td style="{{ $account->is_parent ? 'font-weight:700;' : 'padding-left:1.4rem;' }}">
                                                        {{ $account->account_name }}
                                                        @if ($account->is_parent)
                                                            <span class="muted" style="font-size:0.6rem;text-transform:uppercase;letter-spacing:0.05em;">Group</span>
                                                        @endif
                                                    </td>
                                                    <td class="muted hide-mobile">
                                                        {{ $typeLabels[$account->account_type] ?? $account->account_type }}
                                                    </td>
                                                    <td style="text-align:right;">
                                                        @if ($account->is_parent)
                                                            <span style="font-family:monospace;font-size:0.73rem;color:#888;"
                                                                title="Rolled up from sub-accounts (not editable)">
                                                                {{ number_format((float) $account->period_opening, 2) }}
                                                            </span>
                                                        @else
                                                            <input type="number" name="balances[{{ $account->id }}]"
                                                                value="{{ number_format((float) $account->period_opening, 2, '.', '') }}"
                                                                step="0.01" class="ob-input" />
                                                        @endif
                                                    </td>
                                                    <td style="text-align:right;">
                                                        @php $cb = (float) $account->period_closing; @endphp
                                                        <span style="font-family:monospace;font-size:0.73rem;color:{{ $cb < 0 ? '#b91c1c' : ($cb == 0 ? '#ccc' : '#1b1b18') }};">
                                                            {{ $cb != 0 ? number_format(abs($cb), 2) . ($cb < 0 ? ' Cr' : '') : '—' }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>

                <form id="open-next-period-form" method="POST"
                    action="{{ route('companies.financial-periods.next', $company) }}" style="display:none;"
                    data-confirm-label="Financial Periods"
                    data-confirm-title="Open Next Financial Year"
                    data-confirm-body="Closing balances from {{ $periods->last()->label }} will be brought forward as opening balances."
                    data-confirm-text="Open Period">
                    @csrf
                </form>
            </main>
        </div>
    </div>
@endsection
