@extends('layouts.public')

@section('title', 'Add Employee — ' . $company->registered_name)
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .ef-row {
            display: flex;
            flex-wrap: wrap;
            gap: 4pt 6pt;
            align-items: flex-end;
            margin-bottom: 6pt;
        }
        .ef-field { flex: 1; min-width: 110pt; }
        .ef-field.wide { flex: 2; min-width: 160pt; }
        .ef-field.narrow { flex: 0 0 80pt; }

        .ef-field label {
            display: block;
            font-size: 6pt;
            font-weight: 700;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: #6f869b;
            margin-bottom: 2pt;
        }
        .ef-field input,
        .ef-field select {
            width: 100%;
            border: 1px solid #9ec1f5;
            border-radius: 0;
            padding: 3pt 4pt;
            font-size: 7pt;
            line-height: 1.4;
            font-family: "Century Gothic", "URW Gothic", "Avant Garde", Futura, "Avenir Next", Avenir, "Trebuchet MS", Helvetica, Arial, "DejaVu Sans", sans-serif;
            color: #1a345b;
            background: #fff;
            box-sizing: border-box;
        }
        .ef-field select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            padding-right: 14pt;
            background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='5' viewBox='0 0 8 5'%3E%3Cpath d='M0 0l4 5 4-5z' fill='%238b7aad'/%3E%3C/svg%3E") no-repeat right 4pt center;
        }
        .ef-field input[type="number"] {
            text-align: right;
            font-family: "DejaVu Sans Mono", monospace;
        }
        .ef-field input:focus,
        .ef-field select:focus { outline: none; border-color: #1a345b; }
        .ef-field input:disabled { background: #f4fafc; color: #6f869b; }

        .ef-note {
            font-size: 6.5pt;
            color: #5a7186;
            margin: -2pt 0 4pt;
        }

        .comp-row {
            display: flex;
            align-items: center;
            gap: 6pt;
            padding: 3pt 0;
            border-bottom: 0.4pt solid #d3e2f5;
        }
        .comp-row:last-child { border-bottom: none; }
        .comp-badge {
            display: inline-block;
            font-size: 5.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border: 1px solid #9ec1f5;
            padding: 0 3pt;
            color: #1a345b;
            margin-left: 3pt;
        }

        @media (max-width: 640px) {
            .ef-field, .ef-field.wide { flex: 1 1 100%; min-width: 0; }
        }
    </style>
@endpush

@section('content')
    <div class="co-wrap">
        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">

                @if ($errors->any())
                    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:4pt 8pt;font-size:7pt;font-weight:600;margin-bottom:10pt;">
                        <strong>Please fix the following errors:</strong>
                        <ul style="margin:2pt 0 0 10pt;padding:0;">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('companies.payroll.employees.store', $company) }}">
                    @csrf

                    <div class="reg-doc">
                        <div class="reg-doc-body">
                            <div class="reg-mgmt-bar">
                                <div>
                                    <div class="reg-doc-title">New Employee</div>
                                    <div class="reg-doc-subtitle">All fields marked * are required</div>
                                </div>
                                <div style="display:flex;align-items:center;gap:4pt;">
                                    <a href="{{ route('companies.payroll.employees.index', $company) }}" class="reg-btn">Cancel</a>
                                    <button type="submit" class="reg-btn primary">
                                        <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                        Save Employee
                                    </button>
                                </div>
                            </div>

                            <hr class="reg-divider">

                            {{-- ── Personal Details ────────────────────── --}}
                            <div class="reg-section-header">Personal Details</div>
                            <div class="ef-row">
                                <div class="ef-field narrow">
                                    <label>Employee # *</label>
                                    <input type="text" name="employee_number" value="{{ old('employee_number', $nextNumber) }}" required maxlength="20">
                                </div>
                                <div class="ef-field wide">
                                    <label>First Name *</label>
                                    <input type="text" name="first_name" value="{{ old('first_name') }}" required>
                                </div>
                                <div class="ef-field wide">
                                    <label>Last Name *</label>
                                    <input type="text" name="last_name" value="{{ old('last_name') }}" required>
                                </div>
                            </div>
                            <div class="ef-row">
                                <div class="ef-field">
                                    <label>SA ID Number</label>
                                    <input type="text" name="id_number" value="{{ old('id_number') }}" maxlength="13" placeholder="0000000000000">
                                </div>
                                <div class="ef-field">
                                    <label>Passport Number</label>
                                    <input type="text" name="passport_number" value="{{ old('passport_number') }}">
                                </div>
                                <div class="ef-field">
                                    <label>SARS Tax Ref No.</label>
                                    <input type="text" name="tax_reference_number" value="{{ old('tax_reference_number') }}" placeholder="1234567890">
                                </div>
                                <div class="ef-field">
                                    <label>Date of Birth</label>
                                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}">
                                </div>
                                <div class="ef-field">
                                    <label>Gender</label>
                                    <select name="gender">
                                        <option value="">— Select —</option>
                                        <option value="male" @selected(old('gender') === 'male')>Male</option>
                                        <option value="female" @selected(old('gender') === 'female')>Female</option>
                                        <option value="other" @selected(old('gender') === 'other')>Other</option>
                                        <option value="prefer_not_to_say" @selected(old('gender') === 'prefer_not_to_say')>Prefer not to say</option>
                                    </select>
                                </div>
                            </div>

                            <hr class="reg-divider">

                            {{-- ── Employment ──────────────────────────── --}}
                            <div class="reg-section-header">Employment</div>
                            <div class="ef-row" x-data="{ payType: '{{ old('pay_type', 'salary') }}', payFreq: '{{ old('pay_frequency', 'monthly') }}' }">
                                <div class="ef-field">
                                    <label>Employment Type *</label>
                                    <select name="employment_type" required>
                                        @foreach (\App\Models\Employee::employmentTypes() as $val => $label)
                                            <option value="{{ $val }}" @selected(old('employment_type', 'permanent') === $val)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="ef-field wide">
                                    <label>Job Title</label>
                                    <input type="text" name="job_title" value="{{ old('job_title') }}">
                                </div>
                                <div class="ef-field">
                                    <label>Department</label>
                                    <input type="text" name="department" value="{{ old('department') }}">
                                </div>
                                <div class="ef-field">
                                    <label>Start Date *</label>
                                    <input type="date" name="start_date" value="{{ old('start_date') }}" required>
                                </div>
                                <div class="ef-field">
                                    <label>End Date (fixed-term)</label>
                                    <input type="date" name="end_date" value="{{ old('end_date') }}">
                                </div>
                                <div class="ef-field">
                                    <label>Pay Frequency *</label>
                                    <select name="pay_frequency" x-model="payFreq" required>
                                        @foreach (\App\Models\Employee::payFrequencies() as $val => $label)
                                            <option value="{{ $val }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="ef-field" x-show="payFreq === 'monthly'" x-cloak>
                                    <label>Pay Day of Month</label>
                                    <select name="pay_day_of_month">
                                        <option value="">— Not set —</option>
                                        @foreach (range(1, 31) as $d)
                                            <option value="{{ $d }}" @selected(old('pay_day_of_month') == $d)>
                                                {{ $d }}{{ match(true) { $d === 1 || $d === 21 || $d === 31 => 'st', $d === 2 || $d === 22 => 'nd', $d === 3 || $d === 23 => 'rd', default => 'th' } }} of every month
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="ef-field" x-show="payFreq !== 'monthly'" x-cloak>
                                    <label>First / Reference Pay Date *</label>
                                    <input type="date" name="pay_cycle_anchor" value="{{ old('pay_cycle_anchor') }}">
                                </div>
                                <div class="ef-field">
                                    <label>Pay Type *</label>
                                    <select name="pay_type" x-model="payType" required>
                                        @foreach (\App\Models\Employee::payTypes() as $val => $label)
                                            <option value="{{ $val }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="ef-field" x-show="payType === 'salary'" x-cloak>
                                    <label>Basic Salary (R/mth)</label>
                                    <input type="number" name="basic_salary" value="{{ old('basic_salary', '0.00') }}" step="0.01" min="0">
                                </div>
                                <div class="ef-field" x-show="payType === 'hourly'" x-cloak>
                                    <label>Hourly Rate (R/hr)</label>
                                    <input type="number" name="hourly_rate" value="{{ old('hourly_rate', '0.00') }}" step="0.01" min="0">
                                </div>
                            </div>

                            <hr class="reg-divider">

                            {{-- ── Tax & Benefits ──────────────────────── --}}
                            <div class="reg-section-header">Tax &amp; Benefits</div>
                            <p class="ef-note">Used to calculate PAYE using the 2025/2026 SARS tax tables (medical aid credits, section 11F retirement deduction).</p>
                            <div class="ef-row">
                                <div class="ef-field">
                                    <label>Med. Aid Members</label>
                                    <input type="number" name="medical_aid_members" value="{{ old('medical_aid_members', '1') }}" min="1" max="20">
                                </div>
                                <div class="ef-field">
                                    <label>Med. Aid Employee (R/mth)</label>
                                    <input type="number" name="medical_aid_employee_contribution" value="{{ old('medical_aid_employee_contribution', '0.00') }}" step="0.01" min="0">
                                </div>
                                <div class="ef-field">
                                    <label>Retirement / RA (R/mth)</label>
                                    <input type="number" name="retirement_fund_contribution" value="{{ old('retirement_fund_contribution', '0.00') }}" step="0.01" min="0">
                                </div>
                            </div>

                            <hr class="reg-divider">

                            {{-- ── IAS 19 ──────────────────────────────── --}}
                            <div class="reg-section-header">IAS 19 — Employee Benefits</div>
                            <p class="ef-note">Employer contributions, leave entitlement, and bonus provisions required by IAS 19 to correctly accrue employee benefit liabilities each payroll period.</p>
                            <div class="ef-row">
                                <div class="ef-field">
                                    <label>Retirement Fund Type</label>
                                    <select name="retirement_fund_type">
                                        <option value="defined_contribution" @selected(old('retirement_fund_type', 'defined_contribution') === 'defined_contribution')>Defined Contribution (DC)</option>
                                        <option value="defined_benefit" @selected(old('retirement_fund_type') === 'defined_benefit')>Defined Benefit (DB)</option>
                                    </select>
                                </div>
                                <div class="ef-field">
                                    <label>Employer Retirement (R/mth)</label>
                                    <input type="number" name="employer_retirement_contribution" value="{{ old('employer_retirement_contribution', '0.00') }}" step="0.01" min="0">
                                </div>
                                <div class="ef-field">
                                    <label>Med. Aid Employer (R/mth)</label>
                                    <input type="number" name="medical_aid_employer_contribution" value="{{ old('medical_aid_employer_contribution', '0.00') }}" step="0.01" min="0">
                                </div>
                                <div class="ef-field">
                                    <label>Leave Entitlement (days/yr)</label>
                                    <input type="number" name="leave_days_per_year" value="{{ old('leave_days_per_year', '15') }}" step="0.5" min="0" max="365">
                                </div>
                                <div class="ef-field">
                                    <label>Leave Balance (days)</label>
                                    <input type="number" name="leave_balance_days" value="{{ old('leave_balance_days', '0') }}" step="0.5" min="0">
                                </div>
                                <div class="ef-field">
                                    <label>Bonus Months (13th cheque = 1)</label>
                                    <input type="number" name="bonus_months" value="{{ old('bonus_months', '0') }}" step="0.5" min="0" max="12">
                                </div>
                            </div>

                            <hr class="reg-divider">

                            {{-- ── Banking ─────────────────────────────── --}}
                            <div class="reg-section-header">Banking Details</div>
                            <div class="ef-row">
                                <div class="ef-field wide">
                                    <label>Bank Name</label>
                                    <input type="text" name="bank_name" value="{{ old('bank_name') }}" placeholder="FNB, Standard Bank…">
                                </div>
                                <div class="ef-field">
                                    <label>Account Number</label>
                                    <input type="text" name="bank_account_number" value="{{ old('bank_account_number') }}">
                                </div>
                                <div class="ef-field">
                                    <label>Account Type</label>
                                    <select name="bank_account_type">
                                        <option value="">— Select —</option>
                                        <option value="current" @selected(old('bank_account_type') === 'current')>Current / Cheque</option>
                                        <option value="savings" @selected(old('bank_account_type') === 'savings')>Savings</option>
                                    </select>
                                </div>
                                <div class="ef-field narrow">
                                    <label>Branch Code</label>
                                    <input type="text" name="bank_branch_code" value="{{ old('bank_branch_code') }}" maxlength="10">
                                </div>
                            </div>

                            <hr class="reg-divider">

                            {{-- ── Address ─────────────────────────────── --}}
                            <div class="reg-section-header">Address</div>
                            <div class="ef-row">
                                <div class="ef-field wide">
                                    <label>Address Line 1</label>
                                    <input type="text" name="address_line_1" value="{{ old('address_line_1') }}" placeholder="12 Main Street">
                                </div>
                                <div class="ef-field wide">
                                    <label>Address Line 2</label>
                                    <input type="text" name="address_line_2" value="{{ old('address_line_2') }}" placeholder="Unit 4, Block B">
                                </div>
                            </div>
                            <div class="ef-row">
                                <div class="ef-field">
                                    <label>City / Town</label>
                                    <input type="text" name="city" value="{{ old('city') }}">
                                </div>
                                <div class="ef-field">
                                    <label>Province</label>
                                    <input type="text" name="province" value="{{ old('province') }}">
                                </div>
                                <div class="ef-field narrow">
                                    <label>Postal Code</label>
                                    <input type="text" name="postal_code" value="{{ old('postal_code') }}" maxlength="10">
                                </div>
                            </div>

                            @if ($components->count())
                                <hr class="reg-divider">

                                {{-- ── Pay Components ──────────────────── --}}
                                <div class="reg-section-header">Additional Pay Components</div>
                                <p class="ef-note">Set monthly amounts for allowances and deductions beyond basic salary. Leave at R 0.00 to exclude.</p>
                                @foreach ($components as $i => $component)
                                    <div class="comp-row">
                                        <input type="hidden" name="components[{{ $i }}][id]" value="{{ $component->id }}">
                                        <div style="flex:1;">
                                            <span style="font-size:7pt;font-weight:700;">{{ $component->name }}</span>
                                            <span class="comp-badge">{{ ucfirst(str_replace('_', ' ', $component->type)) }}</span>
                                        </div>
                                        <div class="ef-field" style="flex:0 0 100pt;margin:0;">
                                            <label>Amount (R/mth)</label>
                                            <input type="number" name="components[{{ $i }}][amount]" value="{{ old("components.{$i}.amount", '0.00') }}" step="0.01" min="0" placeholder="0.00">
                                        </div>
                                    </div>
                                @endforeach
                            @endif

                            <hr class="reg-divider" style="margin-top:8pt;">

                            <div style="display:flex;gap:4pt;align-items:center;">
                                <button type="submit" class="reg-btn primary">
                                    <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                    Save Employee
                                </button>
                                <a href="{{ route('companies.payroll.employees.index', $company) }}" class="reg-btn">Cancel</a>
                            </div>

                        </div>
                    </div>

                </form>

            </main>
        </div>
    </div>
@endsection
