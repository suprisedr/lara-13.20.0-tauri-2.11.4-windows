@extends('layouts.public')

@section('title', 'Payroll Runs — ' . $company->registered_name)
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
@endpush

@section('content')
    <div class="co-wrap">
        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">

                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.5rem;flex-wrap:wrap;gap:0.75rem;">
                    <div>
                        <h2 style="font-size:1.1rem;font-weight:800;color:#1b1b18;margin:0 0 0.25rem;">Payroll Runs</h2>
                        <p style="font-size:0.82rem;color:#888;margin:0;">History of all payroll batches</p>
                    </div>
                    <a href="{{ route('companies.payroll.runs.create', $company) }}"
                        style="display:inline-flex;align-items:center;gap:0.4rem;background:#5e17eb;color:#fff;padding:0.5rem 1rem;border-radius:0;font-size:0.82rem;font-weight:700;text-decoration:none;">
                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24">
                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        New Payroll Run
                    </a>
                </div>

                @if (session('success'))
                    <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:0.75rem 1.25rem;border-radius:0;font-size:0.855rem;font-weight:600;margin-bottom:1.5rem;">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($runs->isEmpty())
                    <div style="text-align:center;padding:3rem 2rem;color:#888;">
                        <p>No payroll runs yet. <a href="{{ route('companies.payroll.runs.create', $company) }}" style="color:#5e17eb;">Create your first run</a>.</p>
                    </div>
                @else
                    <div class="co-card" style="overflow:hidden;">
                        <table class="co-table">
                            <thead>
                                <tr>
                                    <th>Period</th>
                                    <th>Type</th>
                                    <th style="text-align:right;">Gross</th>
                                    <th style="text-align:right;">PAYE</th>
                                    <th style="text-align:right;">Net Pay</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($runs as $run)
                                    <tr>
                                        <td style="font-size:0.82rem;">{{ $run->period_start->format('d M Y') }} – {{ $run->period_end->format('d M Y') }}</td>
                                        <td>
                                            @if ($run->employee_type === 'salary')
                                                <span style="font-size:0.68rem;font-weight:700;background:#f0fdf4;color:#15803d;padding:0.12rem 0.45rem;border:1px solid #bbf7d0;">Salary</span>
                                            @elseif ($run->employee_type === 'hourly')
                                                <span style="font-size:0.68rem;font-weight:700;background:#fef9c3;color:#854d0e;padding:0.12rem 0.45rem;border:1px solid #fde68a;">Hourly</span>
                                            @else
                                                <span style="font-size:0.68rem;color:#aaa;">—</span>
                                            @endif
                                        </td>
                                        <td style="text-align:right;font-weight:600;">R&nbsp;{{ number_format($run->total_gross_earnings, 2) }}</td>
                                        <td style="text-align:right;color:#dc2626;">R&nbsp;{{ number_format($run->total_paye, 2) }}</td>
                                        <td style="text-align:right;font-weight:700;color:#15803d;">R&nbsp;{{ number_format($run->total_net_pay, 2) }}</td>
                                        <td>
                                            @if ($run->status === 'posted')
                                                <span style="font-size:0.7rem;font-weight:700;background:#dcfce7;color:#15803d;padding:0.15rem 0.5rem;border-radius:0;">Posted</span>
                                            @else
                                                <span style="font-size:0.7rem;font-weight:700;background:#f3f4f6;color:#555;padding:0.15rem 0.5rem;border-radius:0;">Draft</span>
                                            @endif
                                        </td>
                                        <td style="text-align:right;">
                                            <a href="{{ route('companies.payroll.runs.show', [$company, $run]) }}"
                                                style="font-size:0.78rem;color:#5e17eb;text-decoration:none;font-weight:600;">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top:1rem;">
                        {{ $runs->links() }}
                    </div>
                @endif

            </main>
        </div>
    </div>
@endsection
