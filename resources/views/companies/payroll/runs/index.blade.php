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

                @if (session('success'))
                    <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:4pt 8pt;font-size:7pt;font-weight:600;margin-bottom:10pt;">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="reg-doc">
                    <div class="reg-doc-body">

                        <div class="reg-mgmt-bar">
                            <div>
                                <div class="reg-doc-title">Payroll Runs</div>
                                <div class="reg-doc-subtitle">Payroll</div>
                            </div>
                            <a href="{{ route('companies.payroll.runs.create', $company) }}" class="reg-btn primary">
                                <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24">
                                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                                New Payroll Run
                            </a>
                        </div>

                        <hr class="reg-divider">

                        @if ($runs->isEmpty())
                            <div class="reg-empty-state">
                                <p class="reg-empty-title">No payroll runs yet</p>
                                <p><a href="{{ route('companies.payroll.runs.create', $company) }}" class="reg-link">Create your first run</a></p>
                            </div>
                        @else
                            <div style="overflow-x:auto;">
                                <table class="reg-table">
                                    <thead>
                                        <tr>
                                            <th>Period</th>
                                            <th>Type</th>
                                            <th class="amt">Gross</th>
                                            <th class="amt">PAYE</th>
                                            <th class="amt">Net Pay</th>
                                            <th>Status</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($runs as $run)
                                            <tr>
                                                <td>{{ $run->period_start->format('d M Y') }} – {{ $run->period_end->format('d M Y') }}</td>
                                                <td>
                                                    @if ($run->employee_type === 'salary')
                                                        <span class="reg-status" style="color:#15803d;border-color:#15803d;background:#f0fdf4;">Salary</span>
                                                    @elseif ($run->employee_type === 'hourly')
                                                        <span class="reg-status" style="color:#854d0e;border-color:#854d0e;background:#fef9c3;">Hourly</span>
                                                    @else
                                                        <span style="font-size:6pt;color:#8b7aad;">—</span>
                                                    @endif
                                                </td>
                                                <td class="amt">R&nbsp;{{ number_format($run->total_gross_earnings, 2) }}</td>
                                                <td class="amt" style="color:#dc2626;">R&nbsp;{{ number_format($run->total_paye, 2) }}</td>
                                                <td class="amt" style="font-weight:700;color:#15803d;">R&nbsp;{{ number_format($run->total_net_pay, 2) }}</td>
                                                <td>
                                                    @if ($run->status === 'posted')
                                                        <span class="reg-status" style="color:#15803d;border-color:#15803d;background:#dcfce7;">Posted</span>
                                                    @else
                                                        <span class="reg-status draft">Draft</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="reg-row-actions">
                                                        <button class="reg-row-dots" title="Actions">&#x2026;</button>
                                                        <div class="reg-row-menu">
                                                            <a href="{{ route('companies.payroll.runs.show', [$company, $run]) }}">View</a>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div style="margin-top:8pt;">
                                {{ $runs->links() }}
                            </div>
                        @endif

                    </div>
                </div>

            </main>
        </div>
    </div>

    <script>
        document.addEventListener('click', function(e) {
            if (e.target.closest('.reg-row-dots')) {
                e.stopPropagation();
                var menu = e.target.closest('.reg-row-actions').querySelector('.reg-row-menu');
                var open = menu.classList.contains('open');
                document.querySelectorAll('.reg-row-menu.open').forEach(function(m) { m.classList.remove('open'); });
                if (!open) menu.classList.add('open');
                return;
            }
            document.querySelectorAll('.reg-row-menu.open').forEach(function(m) { m.classList.remove('open'); });
        });
    </script>
@endsection
