@extends('layouts.public')

@section('title', $company->registered_name . ' — Cash Flow Mapping')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .cfm-table { width:100%; border-collapse:collapse; font-size:0.82rem; }
        .cfm-table th { text-align:left; font-size:0.66rem; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#6b7280; padding:0.5rem 0.6rem; border-bottom:1.5px solid #e5e7eb; }
        .cfm-table td { padding:0.4rem 0.6rem; border-bottom:0.5px solid #f0eee6; }
        .cfm-group { font-size:0.72rem; font-weight:800; text-transform:uppercase; letter-spacing:0.08em; color:#5e17eb; background:#f5f3fb; padding:0.45rem 0.6rem; }
        .cfm-code { color:#9ca3af; font-family:monospace; white-space:nowrap; }
        .cfm-table select { font-size:0.78rem; font-family:inherit; border:1.5px solid #e5e7eb; border-radius:0; padding:0.25rem 0.45rem; background:#fff; }
        .cfm-child td:first-child { padding-left:1.6rem; }
    </style>
@endpush

@section('content')
    <div class="co-wrap">
        <div class="co-body">
            @include('companies._sidebar')
            <main class="co-main" style="padding:1.25rem 1.5rem;">
                <div class="co-card">
                    <div class="co-card-head">
                        <div>
                            <div class="co-section-heading"><p class="co-section-label">Reports</p><h2>Cash Flow Mapping</h2></div>
                            <p style="font-size:0.8rem;color:#6b7280;margin:0;max-width:60ch;">
                                Tie each account to the cash flow activity it belongs to. This drives the direct-method
                                Statement of Cash Flows. Accounts left on <strong>Auto</strong> are classified by a sensible
                                default (income &amp; expenses → operating, non-current assets → investing, borrowings &amp; equity → financing).
                            </p>
                        </div>
                        <a href="{{ route('companies.reports.cash-flow', $company) }}"
                            style="display:inline-flex;align-items:center;gap:0.35rem;background:#eee;color:#1b1b18;border-radius:0;padding:0.4rem 0.85rem;font-size:0.74rem;font-weight:700;text-decoration:none;white-space:nowrap;">
                            View Cash Flow
                        </a>
                    </div>

                    @if (session('success'))
                        <div style="margin:1rem 1.25rem 0;padding:0.6rem 0.9rem;background:#dcfce7;color:#15803d;border-radius:0;font-size:0.8rem;font-weight:600;">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('companies.cash-flow-mapping.update', $company) }}" style="padding:1rem 1.25rem 1.5rem;">
                        @csrf
                        @method('PATCH')

                        @php
                            $order = ['income' => 'Income', 'expenses' => 'Expenses', 'assets' => 'Assets', 'liabilities' => 'Liabilities', 'equity' => 'Equity'];
                        @endphp

                        <table class="cfm-table">
                            <thead>
                                <tr>
                                    <th style="width:55%;">Account</th>
                                    <th>Cash flow activity</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order as $type => $label)
                                    @if (($accounts[$type] ?? collect())->isNotEmpty())
                                        <tr><td class="cfm-group" colspan="2">{{ $label }}</td></tr>
                                        @foreach ($accounts[$type] as $account)
                                            <tr class="{{ $account->parent_id ? 'cfm-child' : '' }}">
                                                <td>
                                                    <span class="cfm-code">{{ $account->account_code }}</span>
                                                    &nbsp;{{ $account->account_name }}
                                                </td>
                                                <td>
                                                    <select name="categories[{{ $account->id }}]">
                                                        <option value="">— Auto —</option>
                                                        <option value="operating" @selected($account->cash_flow_category === 'operating')>Operating</option>
                                                        <option value="investing" @selected($account->cash_flow_category === 'investing')>Investing</option>
                                                        <option value="financing" @selected($account->cash_flow_category === 'financing')>Financing</option>
                                                    </select>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                @endforeach
                            </tbody>
                        </table>

                        <div style="margin-top:1.25rem;position:sticky;bottom:0;background:#fff;padding-top:0.5rem;">
                            <button type="submit"
                                style="background:#5e17eb;color:#fff;border:none;border-radius:0;padding:0.5rem 1.4rem;font-size:0.82rem;font-weight:700;cursor:pointer;font-family:inherit;">Save Mapping</button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
@endsection
