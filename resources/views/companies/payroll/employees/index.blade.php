@extends('layouts.public')

@section('title', 'Employees — ' . $company->registered_name)
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .status-box {
            display: inline-block;
            font-weight: 700;
            text-transform: uppercase;
            border: 0.5pt solid #1a345b;
            color: #1a345b;
            padding: 1pt 4pt;
            font-size: 5.5pt;
            letter-spacing: 0.06em;
        }
        .status-box.inactive { color: #dc2626; border-color: #dc2626; }

        .emp-tag {
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 6.5pt;
            font-weight: 700;
            color: #1a345b;
        }

        .freq-badge {
            display: inline-block;
            font-size: 5.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border: 0.5pt solid #9ec1f5;
            color: #1a345b;
            padding: 1pt 4pt;
        }
    </style>
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
                @if (session('error'))
                    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:4pt 8pt;font-size:7pt;font-weight:600;margin-bottom:10pt;">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="reg-doc">
                    <div class="reg-doc-body">

                        <div class="reg-mgmt-bar">
                            <div>
                                <div class="reg-doc-title">Employee Register</div>
                                <div class="reg-doc-subtitle">
                                    @php
                                        $active   = $employees->where('is_active', true)->count();
                                        $inactive = $employees->where('is_active', false)->count();
                                    @endphp
                                    {{ $active }} active{{ $inactive > 0 ? ', ' . $inactive . ' inactive' : '' }} &mdash;
                                    IAS 19 benefit data feeds directly into payroll journal entries.
                                </div>
                            </div>
                            <a href="{{ route('companies.payroll.employees.create', $company) }}" class="reg-btn primary">
                                <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24">
                                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                                Add Employee
                            </a>
                        </div>

                        <hr class="reg-divider">

                        @if ($employees->isEmpty())
                            <div class="reg-empty-state">
                                <p class="reg-empty-title">No employees yet</p>
                                <p>
                                    <a href="{{ route('companies.payroll.employees.create', $company) }}" class="reg-link">Add your first employee</a>
                                </p>
                            </div>
                        @else
                            @php
                                $totalSalary = $employees->where('is_active', true)->sum('basic_salary');
                                $totalEmployerRetirement = $employees->where('is_active', true)->sum('employer_retirement_contribution');
                                $totalEmployerMedical    = $employees->where('is_active', true)->sum('medical_aid_employer_contribution');
                            @endphp
                            <div style="overflow-x:auto;">
                                <table class="reg-table">
                                    <thead>
                                        <tr>
                                            <th style="width:8%;">Emp #</th>
                                            <th style="width:19%;">Name</th>
                                            <th style="width:13%;">Title / Dept</th>
                                            <th style="width:9%;">Type</th>
                                            <th class="amt" style="width:11%;">Basic Salary</th>
                                            <th class="amt" style="width:11%;">Employer Ret.</th>
                                            <th class="amt" style="width:8%;">Leave Days</th>
                                            <th class="amt" style="width:7%;">Bonus Mo.</th>
                                            <th style="width:7%;text-align:center;">Status</th>
                                            <th style="width:7%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($employees as $employee)
                                            <tr>
                                                <td>
                                                    <span class="emp-tag">{{ $employee->employee_number }}</span>
                                                </td>
                                                <td>
                                                    <a class="reg-link" href="{{ route('companies.payroll.employees.show', [$company, $employee]) }}">
                                                        {{ $employee->full_name }}
                                                    </a>
                                                    <br>
                                                    <span class="freq-badge">{{ ucfirst($employee->pay_frequency) }}</span>
                                                    @if ($employee->pay_type === 'hourly')
                                                        <span style="font-size:6pt;color:#6f869b;margin-left:2pt;">@ R{{ number_format($employee->hourly_rate, 2) }}/hr</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span style="font-weight:600;color:#191919;">{{ $employee->job_title ?? '—' }}</span>
                                                    @if ($employee->department)
                                                        <br><span style="font-size:6pt;color:#6f869b;">{{ $employee->department }}</span>
                                                    @endif
                                                </td>
                                                <td class="dim">
                                                    {{ \App\Models\Employee::employmentTypes()[$employee->employment_type] ?? $employee->employment_type }}
                                                </td>
                                                <td class="amt">
                                                    @if ($employee->pay_type === 'salary')
                                                        R {{ number_format($employee->basic_salary, 2) }}
                                                    @else
                                                        <span style="color:#6f869b;">hourly</span>
                                                    @endif
                                                </td>
                                                <td class="amt">
                                                    @if ((float) $employee->employer_retirement_contribution > 0)
                                                        R {{ number_format($employee->employer_retirement_contribution, 2) }}
                                                        <br><span style="font-size:5.5pt;color:#6f869b;">{{ $employee->retirement_fund_type === 'defined_benefit' ? 'DB' : 'DC' }}</span>
                                                    @else
                                                        <span style="color:#6f869b;">—</span>
                                                    @endif
                                                </td>
                                                <td class="amt">
                                                    {{ rtrim(rtrim(number_format((float) $employee->leave_days_per_year, 1), '0'), '.') }} days
                                                    @if ((float) $employee->leave_balance_days > 0)
                                                        <br><span style="font-size:5.5pt;color:#6f869b;">{{ number_format($employee->leave_balance_days, 1) }} bal.</span>
                                                    @endif
                                                </td>
                                                <td class="amt">
                                                    @if ((float) $employee->bonus_months > 0)
                                                        {{ rtrim(rtrim(number_format((float) $employee->bonus_months, 2), '0'), '.') }}
                                                    @else
                                                        <span style="color:#6f869b;">—</span>
                                                    @endif
                                                </td>
                                                <td style="text-align:center;">
                                                    <span class="status-box {{ $employee->is_active ? '' : 'inactive' }}">
                                                        {{ $employee->is_active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </td>
                                                <td style="text-align:right;">
                                                    <div class="reg-row-actions">
                                                        <button class="reg-row-dots" title="Actions">&#x2026;</button>
                                                        <div class="reg-row-menu">
                                                            <a href="{{ route('companies.payroll.employees.show', [$company, $employee]) }}">View</a>
                                                            <a href="{{ route('companies.payroll.employees.edit', [$company, $employee]) }}">Edit</a>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    @if ($employees->where('is_active', true)->count() > 0)
                                        <tfoot>
                                            <tr>
                                                <td colspan="4">Total (active employees)</td>
                                                <td class="amt">R {{ number_format($totalSalary, 2) }}</td>
                                                <td class="amt">R {{ number_format($totalEmployerRetirement, 2) }}</td>
                                                <td colspan="4"></td>
                                            </tr>
                                        </tfoot>
                                    @endif
                                </table>
                            </div>
                        @endif

                    </div>
                </div>

                {{-- IAS 19 summary callout --}}
                @if ($employees->isNotEmpty())
                    @php
                        $hasIas19 = $employees->contains(fn($e) =>
                            (float) $e->employer_retirement_contribution > 0
                            || (float) $e->medical_aid_employer_contribution > 0
                            || (float) $e->bonus_months > 0
                        );
                    @endphp
                    @if ($hasIas19)
                        <div class="afs-warning">
                            <strong>IAS 19 benefit accruals are active.</strong>
                            Employer retirement (R {{ number_format($totalEmployerRetirement, 2) }}/mo),
                            medical aid subsidy (R {{ number_format($totalEmployerMedical, 2) }}/mo),
                            and any bonus provisions will be posted as additional journal lines when each payroll run is posted to accounting.
                        </div>
                    @endif
                @endif

            </main>
        </div>
    </div>

    @include('companies._row-actions')
@endsection
