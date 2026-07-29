@extends('layouts.public')

@section('title', $employee->full_name . ' — ' . $company->registered_name)
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .emp-detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6pt;
        }

        .emp-detail-item {
            font-size: 7pt;
            margin-bottom: 2pt;
        }

        .emp-detail-item .lbl {
            font-size: 5.5pt;
            font-weight: 700;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: #8b7aad;
            margin-bottom: 1pt;
        }

        .emp-detail-item .val {
            color: #23282d;
            font-weight: 600;
        }

        .emp-summary-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 6pt;
            margin-bottom: 8pt;
        }

        .emp-summary-chip {
            border: 0.5pt solid #c4b5fd;
            padding: 4pt 6pt;
        }

        .emp-summary-chip .lbl {
            font-size: 5.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #8b7aad;
            margin-bottom: 1pt;
        }

        .emp-summary-chip .val {
            font-size: 8pt;
            font-weight: 800;
            color: #23282d;
        }

        @media (max-width: 640px) {
            .emp-detail-grid { grid-template-columns: 1fr; }
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

                {{-- Management bar --}}
                <div class="reg-mgmt-bar">
                    <div style="display:flex;align-items:center;gap:4pt;">
                        <a href="{{ route('companies.payroll.employees.edit', [$company, $employee]) }}" class="reg-btn primary">Edit Employee</a>
                    </div>
                </div>

                {{-- Profile document --}}
                <div class="reg-doc">
                    <div class="reg-doc-body">

                        {{-- Letterhead --}}
                        <div class="afs-letterhead">
                            <div>
                                <div class="afs-letterhead-name">{{ $employee->full_name }}</div>
                                <div class="afs-letterhead-meta">
                                    @if ($employee->job_title){{ $employee->job_title }}<br>@endif
                                    @if ($employee->department){{ $employee->department }}<br>@endif
                                    {{ ucfirst($employee->employment_type) }}
                                </div>
                            </div>
                            <div class="afs-letterhead-meta afs-right">
                                <div style="font-size:9pt;font-weight:800;color:#4c1d95;margin-bottom:3pt;">{{ $employee->employee_number }}</div>
                                <div style="margin-bottom:2pt;">
                                    <span class="reg-status {{ $employee->is_active ? '' : 'disposed' }}">
                                        {{ $employee->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                                <div style="font-size:6.5pt;color:#6b5b8a;">
                                    {{ $company->registered_name }}
                                </div>
                            </div>
                        </div>

                        {{-- Remuneration summary chips --}}
                        <div class="emp-summary-chips" style="margin-top:8pt;">
                            <div class="emp-summary-chip">
                                <div class="lbl">Pay Frequency</div>
                                <div class="val">{{ ucfirst($employee->pay_frequency) }}</div>
                            </div>
                            <div class="emp-summary-chip">
                                <div class="lbl">Pay Day</div>
                                @php
                                    $d = $employee->pay_day_of_month;
                                    $ord = $d ? $d . match(true) { $d===1||$d===21||$d===31=>'st', $d===2||$d===22=>'nd', $d===3||$d===23=>'rd', default=>'th' } . ' of month' : '—';
                                @endphp
                                <div class="val">{{ $ord }}</div>
                            </div>
                            <div class="emp-summary-chip">
                                <div class="lbl">{{ $employee->isHourly() ? 'Hourly Rate' : 'Basic Salary' }}</div>
                                <div class="val">R {{ number_format($employee->isHourly() ? $employee->hourly_rate : $employee->basic_salary, 2) }}</div>
                            </div>
                            @if ($employee->retirement_fund_contribution)
                                <div class="emp-summary-chip">
                                    <div class="lbl">Retirement Fund</div>
                                    <div class="val">R {{ number_format($employee->retirement_fund_contribution, 2) }}</div>
                                </div>
                            @endif
                            @if ($employee->medical_aid_employee_contribution)
                                <div class="emp-summary-chip">
                                    <div class="lbl">Medical Aid ({{ $employee->medical_aid_members }})</div>
                                    <div class="val">R {{ number_format($employee->medical_aid_employee_contribution, 2) }}</div>
                                </div>
                            @endif
                        </div>

                        {{-- Personal details --}}
                        <div class="reg-section-header">Personal Details</div>
                        <div class="emp-detail-grid">
                            <div>
                                @if ($employee->id_number)
                                    <div class="emp-detail-item"><div class="lbl">ID Number</div><div class="val">{{ $employee->id_number }}</div></div>
                                @endif
                                @if ($employee->passport_number)
                                    <div class="emp-detail-item"><div class="lbl">Passport Number</div><div class="val">{{ $employee->passport_number }}</div></div>
                                @endif
                                @if ($employee->tax_reference_number)
                                    <div class="emp-detail-item"><div class="lbl">Tax Reference No</div><div class="val">{{ $employee->tax_reference_number }}</div></div>
                                @endif
                            </div>
                            <div>
                                @if ($employee->date_of_birth)
                                    <div class="emp-detail-item"><div class="lbl">Date of Birth</div><div class="val">{{ $employee->date_of_birth->format('d M Y') }}</div></div>
                                @endif
                                @if ($employee->gender)
                                    <div class="emp-detail-item"><div class="lbl">Gender</div><div class="val">{{ ucfirst($employee->gender) }}</div></div>
                                @endif
                                <div class="emp-detail-item"><div class="lbl">Start Date</div><div class="val">{{ $employee->start_date->format('d M Y') }}</div></div>
                                @if ($employee->end_date)
                                    <div class="emp-detail-item"><div class="lbl">End Date</div><div class="val">{{ $employee->end_date->format('d M Y') }}</div></div>
                                @endif
                            </div>
                        </div>

                        {{-- Banking details --}}
                        @if ($employee->bank_name || $employee->bank_account_number)
                            <div class="reg-section-header" style="margin-top:8pt;">Banking Details</div>
                            <div class="emp-detail-grid">
                                <div>
                                    @if ($employee->bank_name)
                                        <div class="emp-detail-item"><div class="lbl">Bank</div><div class="val">{{ $employee->bank_name }}</div></div>
                                    @endif
                                    @if ($employee->bank_account_number)
                                        <div class="emp-detail-item"><div class="lbl">Account Number</div><div class="val" style="font-family:'DejaVu Sans Mono',monospace;">{{ $employee->bank_account_number }}</div></div>
                                    @endif
                                </div>
                                <div>
                                    @if ($employee->bank_account_type)
                                        <div class="emp-detail-item"><div class="lbl">Account Type</div><div class="val">{{ ucfirst($employee->bank_account_type) }}</div></div>
                                    @endif
                                    @if ($employee->bank_branch_code)
                                        <div class="emp-detail-item"><div class="lbl">Branch Code</div><div class="val" style="font-family:'DejaVu Sans Mono',monospace;">{{ $employee->bank_branch_code }}</div></div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- IAS 19 Employee Benefits --}}
                        <div class="reg-section-header" style="margin-top:8pt;">IAS 19 — Employee Benefits</div>
                        <div class="emp-detail-grid">
                            <div>
                                <div class="emp-detail-item"><div class="lbl">Retirement Fund Type</div><div class="val">{{ $employee->retirement_fund_type === 'defined_benefit' ? 'Defined Benefit (DB)' : 'Defined Contribution (DC)' }}</div></div>
                                <div class="emp-detail-item"><div class="lbl">Employer Retirement</div><div class="val">R {{ number_format($employee->employer_retirement_contribution, 2) }} / month</div></div>
                                <div class="emp-detail-item"><div class="lbl">Employer Medical Aid</div><div class="val">R {{ number_format($employee->medical_aid_employer_contribution, 2) }} / month</div></div>
                            </div>
                            <div>
                                <div class="emp-detail-item"><div class="lbl">Annual Leave</div><div class="val">{{ rtrim(rtrim(number_format((float) $employee->leave_days_per_year, 1), '0'), '.') }} days / year</div></div>
                                <div class="emp-detail-item"><div class="lbl">Leave Balance</div><div class="val">{{ number_format($employee->leave_balance_days, 2) }} days</div></div>
                                <div class="emp-detail-item"><div class="lbl">Bonus Months</div><div class="val">
                                    @if ((float) $employee->bonus_months > 0)
                                        {{ rtrim(rtrim(number_format((float) $employee->bonus_months, 2), '0'), '.') }} month(s) per year
                                    @else
                                        None
                                    @endif
                                </div></div>
                            </div>
                        </div>

                        {{-- Payslip history --}}
                        <div class="reg-section-header" style="margin-top:8pt;">Payslip History ({{ $payslips->count() }})</div>
                        <table class="reg-table">
                            <thead>
                                <tr>
                                    <th style="width:24%;">Period</th>
                                    <th style="width:16%;">Pay Date</th>
                                    <th style="width:14%;">Status</th>
                                    <th class="amt" style="width:15%;">Gross</th>
                                    <th class="amt" style="width:13%;">PAYE</th>
                                    <th class="amt" style="width:9%;">UIF</th>
                                    <th class="amt" style="width:15%;">Net Pay</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($payslips as $payslip)
                                    <tr>
                                        <td>
                                            <a class="reg-link" href="{{ route('companies.payroll.payslip.pdf', [$company, $payslip->payrollRun, $payslip]) }}" target="_blank">
                                                {{ $payslip->payrollRun->period_label }}
                                            </a>
                                            @if ($payslip->is_adjusted)
                                                <span class="reg-status" style="margin-left:3pt;">Adj</span>
                                            @endif
                                        </td>
                                        <td class="dim">{{ $payslip->pay_date ? $payslip->pay_date->format('d M Y') : '—' }}</td>
                                        <td>
                                            <span class="reg-status {{ $payslip->payrollRun->isPosted() ? '' : 'draft' }}">
                                                {{ $payslip->payrollRun->isPosted() ? 'Posted' : 'Draft' }}
                                            </span>
                                        </td>
                                        <td class="amt">{{ number_format($payslip->gross_earnings, 2) }}</td>
                                        <td class="amt">{{ number_format($payslip->paye, 2) }}</td>
                                        <td class="amt">{{ number_format($payslip->uif_employee, 2) }}</td>
                                        <td class="amt">{{ number_format($payslip->net_pay, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" style="text-align:center;color:#8b7aad;padding:12pt;">No payslips yet for this employee.</td></tr>
                                @endforelse
                            </tbody>
                        </table>

                    </div>
                </div>

            </main>
        </div>
    </div>
@endsection
