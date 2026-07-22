@extends('layouts.public')

@section('title', 'Employees — ' . $company->registered_name)
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
            cursor: pointer;
            font-family: inherit;
            transition: background 0.15s, color 0.15s;
        }

        .mgmt-btn:hover { background: #000; color: #fff; }
        .mgmt-btn.primary { background: #000; color: #fff; }
        .mgmt-btn.primary:hover { background: #333; }

        /* ── Document shell ─────────────────────────────────────── */
        .cust-doc {
            background: #fff;
            border: 1px solid #ddd;
            font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
            color: #000;
            font-size: 0.78rem;
            line-height: 1.45;
            margin-bottom: 1.5rem;
        }

        .cust-doc-body { padding: 2rem 2.25rem; }

        .doc-title {
            font-size: 1.3rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            margin-bottom: 0.25rem;
        }

        .divider {
            border: none;
            border-top: 2px solid #000;
            margin: 1rem 0 1.25rem;
        }

        /* ── Tables ─────────────────────────────────────────────── */
        table.cust-items-table {
            width: 100%;
            border-collapse: collapse;
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
            vertical-align: middle;
        }

        table.cust-items-table tbody td.amt {
            text-align: right;
            font-family: 'Courier New', monospace;
            white-space: nowrap;
        }

        table.cust-items-table tbody tr:last-child td { border-bottom: none; }
        table.cust-items-table tbody tr:hover td { background: #fafafa; }

        table.cust-items-table tfoot td {
            padding: 0.55rem 0;
            font-size: 0.78rem;
            font-weight: 800;
            border-top: 1.5px solid #000;
        }

        table.cust-items-table tfoot td.amt {
            text-align: right;
            font-family: 'Courier New', monospace;
        }

        .status-box {
            display: inline-block;
            font-weight: 700;
            text-transform: uppercase;
            border: 1px solid #000;
            padding: 0.08rem 0.5rem;
            font-size: 0.6rem;
            letter-spacing: 0.08em;
        }

        .status-box.inactive { color: #dc2626; border-color: #dc2626; }

        .row-link {
            color: #000;
            font-weight: 700;
            text-decoration: none;
            border-bottom: 1px solid #000;
        }

        .row-link:hover { border-bottom-color: transparent; }

        .emp-tag {
            font-family: 'Courier New', monospace;
            font-size: 0.7rem;
            font-weight: 700;
            color: #5e17eb;
        }

        .freq-badge {
            display: inline-block;
            font-size: 0.6rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border: 1px solid #c4b5fd;
            color: #5e17eb;
            padding: 0.07rem 0.45rem;
        }

        .ias-hint {
            font-size: 0.65rem;
            color: #6b7280;
        }

        @media (max-width: 640px) {
            .cust-doc-body { padding: 1.25rem 1rem; }
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
                    <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:0.75rem 1.25rem;font-size:0.855rem;font-weight:600;margin-bottom:1.25rem;">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:0.75rem 1.25rem;font-size:0.855rem;font-weight:600;margin-bottom:1.25rem;">
                        {{ session('error') }}
                    </div>
                @endif

                {{-- Management bar --}}
                <div class="inv-mgmt-bar">
                    <span style="font-size:0.78rem;color:#6b7280;">
                        @php
                            $active   = $employees->where('is_active', true)->count();
                            $inactive = $employees->where('is_active', false)->count();
                        @endphp
                        {{ $active }} active{{ $inactive > 0 ? ', ' . $inactive . ' inactive' : '' }}
                    </span>
                    <a href="{{ route('companies.payroll.employees.create', $company) }}" class="mgmt-btn primary">
                        <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" viewBox="0 0 24 24">
                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        Add Employee
                    </a>
                </div>

                <div class="cust-doc">
                    <div class="cust-doc-body">
                        <div class="doc-title">Employee Register</div>
                        <p style="font-size:0.78rem;color:#555;margin:0.2rem 0 0.75rem;">
                            IAS 19 benefit data (employer contributions, leave entitlement, bonus months) feeds directly into payroll journal entries.
                        </p>

                        <hr class="divider">

                        @if ($employees->isEmpty())
                            <p style="color:#999;font-style:italic;font-size:0.78rem;margin-bottom:1rem;">
                                No employees yet.
                                <a href="{{ route('companies.payroll.employees.create', $company) }}" style="color:#000;font-weight:700;border-bottom:1px solid #000;">Add your first employee</a>.
                            </p>
                        @else
                            @php
                                $totalSalary = $employees->where('is_active', true)->sum('basic_salary');
                                $totalEmployerRetirement = $employees->where('is_active', true)->sum('employer_retirement_contribution');
                                $totalEmployerMedical    = $employees->where('is_active', true)->sum('medical_aid_employer_contribution');
                            @endphp
                            <div style="overflow-x:auto;">
                                <table class="cust-items-table">
                                    <thead>
                                        <tr>
                                            <td style="width:8%;">Emp #</td>
                                            <td style="width:19%;">Name</td>
                                            <td style="width:13%;">Title / Dept</td>
                                            <td style="width:9%;">Type</td>
                                            <td class="amt" style="width:11%;">Basic Salary</td>
                                            <td class="amt" style="width:11%;">Employer Ret.</td>
                                            <td class="amt" style="width:8%;">Leave Days</td>
                                            <td class="amt" style="width:7%;">Bonus Mo.</td>
                                            <td style="width:7%;text-align:center;">Status</td>
                                            <td style="width:7%;"></td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($employees as $employee)
                                            <tr>
                                                <td>
                                                    <span class="emp-tag">{{ $employee->employee_number }}</span>
                                                </td>
                                                <td>
                                                    <a class="row-link" href="{{ route('companies.payroll.employees.show', [$company, $employee]) }}">
                                                        {{ $employee->full_name }}
                                                    </a>
                                                    <br>
                                                    <span class="freq-badge">{{ ucfirst($employee->pay_frequency) }}</span>
                                                    @if ($employee->pay_type === 'hourly')
                                                        <span class="ias-hint" style="margin-left:0.25rem;">@ R{{ number_format($employee->hourly_rate, 2) }}/hr</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span style="font-weight:600;color:#111;">{{ $employee->job_title ?? '—' }}</span>
                                                    @if ($employee->department)
                                                        <br><span class="ias-hint">{{ $employee->department }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span style="font-size:0.7rem;color:#374151;">
                                                        {{ \App\Models\Employee::employmentTypes()[$employee->employment_type] ?? $employee->employment_type }}
                                                    </span>
                                                </td>
                                                <td class="amt">
                                                    @if ($employee->pay_type === 'salary')
                                                        R {{ number_format($employee->basic_salary, 2) }}
                                                    @else
                                                        <span class="ias-hint">hourly</span>
                                                    @endif
                                                </td>
                                                <td class="amt">
                                                    @if ((float) $employee->employer_retirement_contribution > 0)
                                                        R {{ number_format($employee->employer_retirement_contribution, 2) }}
                                                        <br><span class="ias-hint">{{ $employee->retirement_fund_type === 'defined_benefit' ? 'DB' : 'DC' }}</span>
                                                    @else
                                                        <span class="ias-hint">—</span>
                                                    @endif
                                                </td>
                                                <td class="amt">
                                                    {{ rtrim(rtrim(number_format((float) $employee->leave_days_per_year, 1), '0'), '.') }} days
                                                    @if ((float) $employee->leave_balance_days > 0)
                                                        <br><span class="ias-hint">{{ number_format($employee->leave_balance_days, 1) }} bal.</span>
                                                    @endif
                                                </td>
                                                <td class="amt">
                                                    @if ((float) $employee->bonus_months > 0)
                                                        {{ rtrim(rtrim(number_format((float) $employee->bonus_months, 2), '0'), '.') }}
                                                    @else
                                                        <span class="ias-hint">—</span>
                                                    @endif
                                                </td>
                                                <td style="text-align:center;">
                                                    <span class="status-box {{ $employee->is_active ? '' : 'inactive' }}">
                                                        {{ $employee->is_active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </td>
                                                <td style="text-align:right;white-space:nowrap;">
                                                    <a href="{{ route('companies.payroll.employees.show', [$company, $employee]) }}"
                                                        style="font-size:0.72rem;font-weight:700;color:#000;text-decoration:none;border-bottom:1px solid #ccc;margin-right:0.6rem;">
                                                        View
                                                    </a>
                                                    <a href="{{ route('companies.payroll.employees.edit', [$company, $employee]) }}"
                                                        style="font-size:0.72rem;font-weight:700;color:#000;text-decoration:none;border-bottom:1px solid #ccc;">
                                                        Edit
                                                    </a>
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

                {{-- IAS 19 summary callout (only if there are employees with benefits) --}}
                @if ($employees->isNotEmpty())
                    @php
                        $hasIas19 = $employees->contains(fn($e) =>
                            (float) $e->employer_retirement_contribution > 0
                            || (float) $e->medical_aid_employer_contribution > 0
                            || (float) $e->bonus_months > 0
                        );
                    @endphp
                    @if ($hasIas19)
                        <div style="border:1px solid #ddd6fe;background:#faf5ff;padding:1rem 1.25rem;font-size:0.78rem;color:#4c1d95;margin-bottom:1.5rem;">
                            <strong>IAS 19 benefit accruals are active.</strong>
                            Employer retirement (R {{ number_format($totalEmployerRetirement, 2) }}/mo),
                            medical aid subsidy (R {{ number_format($totalEmployerMedical, 2) }}/mo),
                            and any bonus provisions will be posted as additional journal lines when each payroll run is posted to accounting.
                            Ensure the corresponding GL accounts (Retirement Fund Payable, Medical Aid Payable, Accrued Leave Payable, Bonus Provision Payable) are mapped on each payroll run.
                        </div>
                    @endif
                @endif

            </main>
        </div>
    </div>
@endsection
