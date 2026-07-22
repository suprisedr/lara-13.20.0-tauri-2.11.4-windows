@extends('layouts.public')

@section('title', $employee->full_name . ' — ' . $company->registered_name)
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        /* ── Management bar ─────────────────────────────────────── */
        .inv-mgmt-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }

        .inv-mgmt-bar a {
            font-size: 0.78rem;
            color: #6b7280;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            transition: color 0.15s;
        }

        .inv-mgmt-bar a:hover { color: #000; }

        .mgmt-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: #fff;
            border: 1px solid #000;
            color: #000;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 0.4rem 0.85rem;
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
        }

        .mgmt-btn:hover { background: #000; color: #fff; }

        .mgmt-btn.primary {
            background: #000;
            color: #fff;
        }

        .mgmt-btn.primary:hover { background: #333; }

        /* ── Profile document (PDF-style) ───────────────────────── */
        .cust-doc {
            background: #fff;
            border: 1px solid #ddd;
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
            color: #000;
            font-size: 0.78rem;
            line-height: 1.45;
        }

        .cust-doc-body {
            padding: 2rem 2.25rem;
        }

        /* ── Header ──────────────────────────────────────────────── */
        .cust-header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0.75rem;
        }

        .cust-name-fallback {
            font-size: 1.3rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .cust-address-block {
            text-align: right;
            font-size: 0.78rem;
            line-height: 1.55;
        }

        .cust-meta-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-label {
            font-weight: 700;
            font-size: 0.78rem;
            display: inline-block;
            width: 150px;
        }

        .meta-value {
            font-size: 0.78rem;
        }

        .doc-title {
            font-size: 1.3rem;
            font-weight: 800;
            text-align: right;
            margin-bottom: 0.25rem;
            letter-spacing: 0.04em;
        }

        .doc-meta-line {
            text-align: right;
            font-size: 0.78rem;
        }

        .status-box {
            display: inline-block;
            font-weight: 700;
            text-transform: uppercase;
            border: 1px solid #000;
            padding: 0.08rem 0.5rem;
            font-size: 0.62rem;
            letter-spacing: 0.08em;
            margin-top: 0.35rem;
        }

        .status-box.inactive { color: #999; border-color: #999; }

        .divider {
            border: none;
            border-top: 2px solid #000;
            margin: 1rem 0 1.25rem 0;
        }

        .divider.light {
            border-top: 1px solid #ddd;
            margin: 1.25rem 0;
        }

        /* ── Summary line ─────────────────────────────────────────── */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0.25rem;
        }

        .summary-table td {
            padding: 0 1.25rem 0 0;
            font-size: 0.78rem;
            vertical-align: top;
        }

        .summary-table .lbl {
            display: block;
            font-weight: 700;
            font-size: 0.62rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.15rem;
        }

        .summary-table .amt {
            font-size: 0.95rem;
            font-weight: 800;
        }

        /* ── Section headers ─────────────────────────────────────── */
        .section-header {
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border-bottom: 1.5px solid #000;
            padding-bottom: 0.25rem;
            margin-bottom: 0.5rem;
        }

        .info-section {
            margin-top: 1.25rem;
        }

        /* ── Tables ───────────────────────────────────────────────── */
        table.cust-items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0.25rem;
        }

        table.cust-items-table thead td {
            font-weight: 700;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1.5px solid #000;
            padding-bottom: 0.45rem;
        }

        table.cust-items-table thead td.amt { text-align: right; }

        table.cust-items-table tbody td {
            padding: 0.55rem 0;
            font-size: 0.78rem;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }

        table.cust-items-table tbody td.amt {
            text-align: right;
            font-family: 'Courier New', monospace;
            white-space: nowrap;
        }

        table.cust-items-table tbody tr:last-child td { border-bottom: none; }

        table.cust-items-table tbody tr:hover td { background: #fafafa; }

        table.cust-items-table a.row-link {
            color: #000;
            font-weight: 700;
            text-decoration: none;
            border-bottom: 1px solid #000;
        }

        table.cust-items-table a.row-link:hover { border-bottom-color: transparent; }

        .empty-row {
            padding: 1rem 0;
            text-align: center;
            color: #999;
            font-size: 0.78rem;
        }

        @media (max-width: 640px) {
            .cust-doc-body { padding: 1.25rem 1rem; }
            .cust-header-table, .cust-header-table tr, .cust-header-table td,
            .cust-meta-table, .cust-meta-table tr, .cust-meta-table td,
            .summary-table, .summary-table tr, .summary-table td {
                display: block;
                width: 100% !important;
                text-align: left !important;
            }
            .cust-address-block, .doc-title, .doc-meta-line { text-align: left !important; }
            .summary-table td { padding: 0 0 0.75rem 0; }
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
                    <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:0.75rem 1.25rem;border-radius:0;font-size:0.855rem;font-weight:600;margin-bottom:1.5rem;">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- Management bar --}}
                <div class="inv-mgmt-bar">
<div style="display:flex;align-items:center;gap:0.65rem;flex-wrap:wrap;">
                        <a href="{{ route('companies.payroll.employees.edit', [$company, $employee]) }}" class="mgmt-btn primary">
                            Edit Employee
                        </a>
                    </div>
                </div>

                {{-- Profile document --}}
                <div class="cust-doc">
                    <div class="cust-doc-body">

                        {{-- Header --}}
                        <table class="cust-header-table">
                            <tr>
                                <td style="vertical-align:top;width:55%;">
                                    <div class="cust-name-fallback">{{ $employee->full_name }}</div>
                                </td>
                                <td style="vertical-align:top;width:45%;">
                                    <div class="cust-address-block">
                                        @if ($employee->job_title)<div>{{ $employee->job_title }}</div>@endif
                                        @if ($employee->department)<div>{{ $employee->department }}</div>@endif
                                        <div>{{ ucfirst($employee->employment_type) }}</div>
                                    </div>
                                </td>
                            </tr>
                        </table>

                        {{-- Meta --}}
                        <table class="cust-meta-table">
                            <tr>
                                <td style="vertical-align:top;width:50%;">
                                    @if ($employee->id_number)
                                        <div><span class="meta-label">ID Number</span><span class="meta-value">{{ $employee->id_number }}</span></div>
                                    @endif
                                    @if ($employee->passport_number)
                                        <div><span class="meta-label">Passport Number</span><span class="meta-value">{{ $employee->passport_number }}</span></div>
                                    @endif
                                    @if ($employee->tax_reference_number)
                                        <div><span class="meta-label">Tax Reference No</span><span class="meta-value">{{ $employee->tax_reference_number }}</span></div>
                                    @endif
                                    @if ($employee->date_of_birth)
                                        <div><span class="meta-label">Date of Birth</span><span class="meta-value">{{ $employee->date_of_birth->format('Y-m-d') }}</span></div>
                                    @endif
                                    @if ($employee->gender)
                                        <div><span class="meta-label">Gender</span><span class="meta-value">{{ ucfirst($employee->gender) }}</span></div>
                                    @endif
                                    <div><span class="meta-label">Start Date</span><span class="meta-value">{{ $employee->start_date->format('Y-m-d') }}</span></div>
                                    @if ($employee->end_date)
                                        <div><span class="meta-label">End Date</span><span class="meta-value">{{ $employee->end_date->format('Y-m-d') }}</span></div>
                                    @endif
                                </td>
                                <td style="vertical-align:top;width:50%;">
                                    <div class="doc-title">EMPLOYEE PROFILE</div>
                                    <div class="doc-meta-line">Employee No: <strong>{{ $employee->employee_number }}</strong></div>
                                    <div class="doc-meta-line">Vendor: <strong>{{ $company->registered_name }}</strong></div>
                                    <div style="text-align:right;">
                                        <span class="status-box {{ $employee->is_active ? '' : 'inactive' }}">
                                            {{ $employee->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </table>

                        <hr class="divider">

                        {{-- Remuneration & banking summary --}}
                        <table class="summary-table">
                            <tr>
                                <td>
                                    <span class="lbl">Pay Frequency</span>
                                    <span class="amt">{{ ucfirst($employee->pay_frequency) }}</span>
                                </td>
                                <td>
                                    <span class="lbl">Pay Day</span>
                                    @php
                                        $d = $employee->pay_day_of_month;
                                        $ord = $d ? $d . match(true) { $d===1||$d===21||$d===31=>'st', $d===2||$d===22=>'nd', $d===3||$d===23=>'rd', default=>'th' } . ' of month' : '—';
                                    @endphp
                                    <span class="amt">{{ $ord }}</span>
                                </td>
                                <td>
                                    <span class="lbl">{{ $employee->isHourly() ? 'Hourly Rate' : 'Basic Salary' }}</span>
                                    <span class="amt">R {{ number_format($employee->isHourly() ? $employee->hourly_rate : $employee->basic_salary, 2) }}</span>
                                </td>
                                @if ($employee->retirement_fund_contribution)
                                    <td>
                                        <span class="lbl">Retirement Fund</span>
                                        <span class="amt">R {{ number_format($employee->retirement_fund_contribution, 2) }}</span>
                                    </td>
                                @endif
                                @if ($employee->medical_aid_employee_contribution)
                                    <td>
                                        <span class="lbl">Medical Aid ({{ $employee->medical_aid_members }})</span>
                                        <span class="amt">R {{ number_format($employee->medical_aid_employee_contribution, 2) }}</span>
                                    </td>
                                @endif
                            </tr>
                        </table>

                        {{-- Banking details --}}
                        @if ($employee->bank_name || $employee->bank_account_number)
                            <div class="info-section">
                                <div class="section-header">Banking Details</div>
                                <table class="cust-meta-table">
                                    <tr>
                                        <td style="vertical-align:top;width:50%;">
                                            @if ($employee->bank_name)
                                                <div><span class="meta-label">Bank</span><span class="meta-value">{{ $employee->bank_name }}</span></div>
                                            @endif
                                            @if ($employee->bank_account_number)
                                                <div><span class="meta-label">Account Number</span><span class="meta-value">{{ $employee->bank_account_number }}</span></div>
                                            @endif
                                        </td>
                                        <td style="vertical-align:top;width:50%;">
                                            @if ($employee->bank_account_type)
                                                <div><span class="meta-label">Account Type</span><span class="meta-value">{{ ucfirst($employee->bank_account_type) }}</span></div>
                                            @endif
                                            @if ($employee->bank_branch_code)
                                                <div><span class="meta-label">Branch Code</span><span class="meta-value">{{ $employee->bank_branch_code }}</span></div>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        @endif

                        {{-- IAS 19 Employee Benefits --}}
                        <div class="info-section">
                            <div class="section-header">IAS 19 — Employee Benefits</div>
                            <table class="cust-meta-table">
                                <tr>
                                    <td style="vertical-align:top;width:50%;">
                                        <div>
                                            <span class="meta-label">Retirement Fund Type</span>
                                            <span class="meta-value">{{ $employee->retirement_fund_type === 'defined_benefit' ? 'Defined Benefit (DB)' : 'Defined Contribution (DC)' }}</span>
                                        </div>
                                        <div>
                                            <span class="meta-label">Employer Retirement</span>
                                            <span class="meta-value">R {{ number_format($employee->employer_retirement_contribution, 2) }} / month</span>
                                        </div>
                                        <div>
                                            <span class="meta-label">Employer Medical Aid</span>
                                            <span class="meta-value">R {{ number_format($employee->medical_aid_employer_contribution, 2) }} / month</span>
                                        </div>
                                    </td>
                                    <td style="vertical-align:top;width:50%;">
                                        <div>
                                            <span class="meta-label">Annual Leave</span>
                                            <span class="meta-value">{{ rtrim(rtrim(number_format((float) $employee->leave_days_per_year, 1), '0'), '.') }} days / year</span>
                                        </div>
                                        <div>
                                            <span class="meta-label">Leave Balance</span>
                                            <span class="meta-value">{{ number_format($employee->leave_balance_days, 2) }} days</span>
                                        </div>
                                        <div>
                                            <span class="meta-label">Bonus Months</span>
                                            <span class="meta-value">
                                                @if ((float) $employee->bonus_months > 0)
                                                    {{ rtrim(rtrim(number_format((float) $employee->bonus_months, 2), '0'), '.') }} month(s) per year
                                                @else
                                                    None
                                                @endif
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        {{-- Payslip history --}}
                        <div class="info-section">
                            <div class="section-header">Payslip History ({{ $payslips->count() }})</div>
                            <table class="cust-items-table">
                                <thead>
                                    <tr>
                                        <td style="width:24%;">Period</td>
                                        <td style="width:16%;">Pay Date</td>
                                        <td style="width:14%;">Status</td>
                                        <td class="amt" style="width:15%;">Gross</td>
                                        <td class="amt" style="width:13%;">PAYE</td>
                                        <td class="amt" style="width:9%;">UIF</td>
                                        <td class="amt" style="width:15%;">Net Pay</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($payslips as $payslip)
                                        <tr>
                                            <td>
                                                <a class="row-link" href="{{ route('companies.payroll.payslip.pdf', [$company, $payslip->payrollRun, $payslip]) }}" target="_blank">
                                                    {{ $payslip->payrollRun->period_label }}
                                                </a>
                                                @if ($payslip->is_adjusted)
                                                    <span class="status-box" style="margin-top:0;margin-left:0.35rem;">Adj</span>
                                                @endif
                                            </td>
                                            <td>{{ $payslip->pay_date ? $payslip->pay_date->format('d M Y') : '—' }}</td>
                                            <td>
                                                <span class="status-box">
                                                    {{ $payslip->payrollRun->isPosted() ? 'Posted' : 'Draft' }}
                                                </span>
                                            </td>
                                            <td class="amt">{{ number_format($payslip->gross_earnings, 2) }}</td>
                                            <td class="amt">{{ number_format($payslip->paye, 2) }}</td>
                                            <td class="amt">{{ number_format($payslip->uif_employee, 2) }}</td>
                                            <td class="amt">{{ number_format($payslip->net_pay, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="empty-row">No payslips yet for this employee.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                    </div>{{-- /.cust-doc-body --}}
                </div>{{-- /.cust-doc --}}

            </main>
        </div>
    </div>
@endsection
