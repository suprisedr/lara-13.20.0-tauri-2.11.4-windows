@extends('layouts.public')

@section('title', 'Add Employee — ' . $company->registered_name)
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    @include('companies._styles')
    <style>
        .inv-mgmt-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
        }

        .inv-mgmt-bar a:not(.reg-btn) {
            font-size: 0.78rem;
            color: #6b7280;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            transition: color 0.15s;
        }

        .inv-mgmt-bar a:not(.reg-btn):hover { color: #4c1d95; }

        .divider {
            border: none;
            border-top: 1.5px solid #4c1d95;
            margin: 1rem 0 1.25rem;
        }

        .divider.light {
            border-top: 1px solid #ddd6fe;
            margin: 1.25rem 0;
        }

        .section-header {
            font-weight: 800;
            font-size: 0.62rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #4c1d95;
            border-bottom: 1.5px solid #4c1d95;
            padding-bottom: 0.25rem;
            margin: 1.25rem 0 0.75rem;
        }

        .section-header:first-of-type { margin-top: 0; }

        .section-note {
            font-size: 0.73rem;
            color: #6b5b8a;
            margin: -0.5rem 0 0.75rem;
        }

        .af-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem 0.85rem;
            align-items: flex-end;
            margin-bottom: 0.65rem;
        }

        .af-field { flex: 1; min-width: 165px; }
        .af-field.wide { flex: 2; min-width: 240px; }
        .af-field.narrow { flex: 0 0 120px; }

        .af-field label {
            display: block;
            font-size: 0.58rem;
            font-weight: 700;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: #6b5b8a;
            margin-bottom: 0.2rem;
        }

        .af-field input,
        .af-field select {
            width: 100%;
            border: 1px solid #c4b5fd;
            padding: 0.38rem 0.55rem;
            font-size: 0.82rem;
            font-family: inherit;
            color: #4c1d95;
            box-sizing: border-box;
            background: #fff;
        }

        .af-field input:focus,
        .af-field select:focus { outline: none; border-color: #4c1d95; }

        .af-field input:disabled { background: #f9f9f9; color: #999; }

        .comp-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid #ddd6fe;
        }

        .comp-row:last-child { border-bottom: none; }

        .comp-badge {
            display: inline-block;
            font-size: 0.58rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border: 1px solid #c4b5fd;
            padding: 0.05rem 0.4rem;
            color: #4c1d95;
            margin-left: 0.4rem;
        }

        @media (max-width: 640px) {
            .af-field, .af-field.wide { flex: 1 1 100%; min-width: 0; }
        }
    </style>
@endpush

@section('content')
    <div class="co-wrap">
        @include('companies._topbar')

        <div class="co-body">
            @include('companies._sidebar')

            <main class="co-main">

                <div class="inv-mgmt-bar">
</div>

                @if ($errors->any())
                    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:0.75rem 1rem;font-size:0.8rem;margin-bottom:1.25rem;">
                        <strong>Please fix the following errors:</strong>
                        <ul style="margin:0.4rem 0 0 1.1rem;padding:0;">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('companies.payroll.employees.store', $company) }}">
                    @csrf

                    <div class="reg-doc">
                        <div class="reg-doc-body">
                            <h3 style="font-size:0.85rem;font-weight:800;color:#1b1b18;margin:0 0 0.25rem;">New Employee</h3>
                            <p style="font-size:0.78rem;color:#6b5b8a;margin:0.2rem 0 0.75rem;">All fields marked * are required.</p>
                            <hr class="divider">

                            {{-- Personal Details --}}
                            <div class="section-header">Personal Details</div>
                            <div class="af-row">
                                <div class="af-field narrow">
                                    <label>Employee # *</label>
                                    <input type="text" name="employee_number" value="{{ old('employee_number', $nextNumber) }}" required maxlength="20">
                                </div>
                                <div class="af-field wide">
                                    <label>First Name *</label>
                                    <input type="text" name="first_name" value="{{ old('first_name') }}" required>
                                </div>
                                <div class="af-field wide">
                                    <label>Last Name *</label>
                                    <input type="text" name="last_name" value="{{ old('last_name') }}" required>
                                </div>
                            </div>
                            <div class="af-row">
                                <div class="af-field">
                                    <label>SA ID Number</label>
                                    <input type="text" name="id_number" value="{{ old('id_number') }}" maxlength="13" placeholder="13 digits">
                                </div>
                                <div class="af-field">
                                    <label>Passport Number</label>
                                    <input type="text" name="passport_number" value="{{ old('passport_number') }}">
                                </div>
                                <div class="af-field">
                                    <label>SARS Tax Ref No.</label>
                                    <input type="text" name="tax_reference_number" value="{{ old('tax_reference_number') }}" placeholder="e.g. 1234567890">
                                </div>
                                <div class="af-field">
                                    <label>Date of Birth</label>
                                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}">
                                </div>
                                <div class="af-field">
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

                            <hr class="divider light">

                            {{-- Employment --}}
                            <div class="section-header">Employment</div>
                            <div class="af-row" x-data="{ payType: '{{ old('pay_type', 'salary') }}', payFreq: '{{ old('pay_frequency', 'monthly') }}' }">
                                <div class="af-field">
                                    <label>Employment Type *</label>
                                    <select name="employment_type" required>
                                        @foreach (\App\Models\Employee::employmentTypes() as $val => $label)
                                            <option value="{{ $val }}" @selected(old('employment_type', 'permanent') === $val)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="af-field wide">
                                    <label>Job Title</label>
                                    <input type="text" name="job_title" value="{{ old('job_title') }}">
                                </div>
                                <div class="af-field">
                                    <label>Department</label>
                                    <input type="text" name="department" value="{{ old('department') }}">
                                </div>
                                <div class="af-field">
                                    <label>Start Date *</label>
                                    <input type="date" name="start_date" value="{{ old('start_date') }}" required>
                                </div>
                                <div class="af-field">
                                    <label>End Date (fixed-term)</label>
                                    <input type="date" name="end_date" value="{{ old('end_date') }}">
                                </div>
                                <div class="af-field">
                                    <label>Pay Frequency *</label>
                                    <select name="pay_frequency" x-model="payFreq" required>
                                        @foreach (\App\Models\Employee::payFrequencies() as $val => $label)
                                            <option value="{{ $val }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="af-field" x-show="payFreq === 'monthly'" x-cloak>
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
                                <div class="af-field" x-show="payFreq !== 'monthly'" x-cloak>
                                    <label>First / Reference Pay Date *</label>
                                    <input type="date" name="pay_cycle_anchor" value="{{ old('pay_cycle_anchor') }}">
                                </div>
                                <div class="af-field">
                                    <label>Pay Type *</label>
                                    <select name="pay_type" x-model="payType" required>
                                        @foreach (\App\Models\Employee::payTypes() as $val => $label)
                                            <option value="{{ $val }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="af-field" x-show="payType === 'salary'" x-cloak>
                                    <label>Basic Salary (R / month)</label>
                                    <input type="number" name="basic_salary" value="{{ old('basic_salary', '0.00') }}" step="0.01" min="0">
                                </div>
                                <div class="af-field" x-show="payType === 'hourly'" x-cloak>
                                    <label>Hourly Rate (R / hour)</label>
                                    <input type="number" name="hourly_rate" value="{{ old('hourly_rate', '0.00') }}" step="0.01" min="0">
                                </div>
                            </div>

                            <hr class="divider light">

                            {{-- Tax & Benefits --}}
                            <div class="section-header">Tax &amp; Benefits</div>
                            <p class="section-note">Used to calculate PAYE using the 2025/2026 SARS tax tables (medical aid credits, section 11F retirement deduction).</p>
                            <div class="af-row">
                                <div class="af-field">
                                    <label>Medical Aid Members (incl. employee)</label>
                                    <input type="number" name="medical_aid_members" value="{{ old('medical_aid_members', '1') }}" min="1" max="20">
                                </div>
                                <div class="af-field">
                                    <label>Medical Aid Employee Contribution (R / month)</label>
                                    <input type="number" name="medical_aid_employee_contribution" value="{{ old('medical_aid_employee_contribution', '0.00') }}" step="0.01" min="0">
                                </div>
                                <div class="af-field">
                                    <label>Retirement Fund / RA Contribution (R / month)</label>
                                    <input type="number" name="retirement_fund_contribution" value="{{ old('retirement_fund_contribution', '0.00') }}" step="0.01" min="0">
                                </div>
                            </div>

                            <hr class="divider light">

                            {{-- IAS 19 --}}
                            <div class="section-header">IAS 19 — Employee Benefits</div>
                            <p class="section-note">Employer contributions, leave entitlement, and bonus provisions required by IAS 19 to correctly accrue employee benefit liabilities each payroll period.</p>
                            <div class="af-row">
                                <div class="af-field">
                                    <label>Retirement Fund Type</label>
                                    <select name="retirement_fund_type">
                                        <option value="defined_contribution" @selected(old('retirement_fund_type', 'defined_contribution') === 'defined_contribution')>Defined Contribution (DC)</option>
                                        <option value="defined_benefit" @selected(old('retirement_fund_type') === 'defined_benefit')>Defined Benefit (DB)</option>
                                    </select>
                                </div>
                                <div class="af-field">
                                    <label>Employer Retirement Contribution (R / month)</label>
                                    <input type="number" name="employer_retirement_contribution" value="{{ old('employer_retirement_contribution', '0.00') }}" step="0.01" min="0">
                                </div>
                                <div class="af-field">
                                    <label>Medical Aid Employer Subsidy (R / month)</label>
                                    <input type="number" name="medical_aid_employer_contribution" value="{{ old('medical_aid_employer_contribution', '0.00') }}" step="0.01" min="0">
                                </div>
                                <div class="af-field">
                                    <label>Annual Leave Entitlement (days / year)</label>
                                    <input type="number" name="leave_days_per_year" value="{{ old('leave_days_per_year', '15') }}" step="0.5" min="0" max="365">
                                </div>
                                <div class="af-field">
                                    <label>Opening Leave Balance (days)</label>
                                    <input type="number" name="leave_balance_days" value="{{ old('leave_balance_days', '0') }}" step="0.5" min="0">
                                </div>
                                <div class="af-field">
                                    <label>Annual Bonus Months (e.g. 1 = 13th cheque)</label>
                                    <input type="number" name="bonus_months" value="{{ old('bonus_months', '0') }}" step="0.5" min="0" max="12">
                                </div>
                            </div>

                            <hr class="divider light">

                            {{-- Banking --}}
                            <div class="section-header">Banking Details</div>
                            <div class="af-row">
                                <div class="af-field wide">
                                    <label>Bank Name</label>
                                    <input type="text" name="bank_name" value="{{ old('bank_name') }}" placeholder="e.g. First National Bank">
                                </div>
                                <div class="af-field">
                                    <label>Account Number</label>
                                    <input type="text" name="bank_account_number" value="{{ old('bank_account_number') }}">
                                </div>
                                <div class="af-field">
                                    <label>Account Type</label>
                                    <select name="bank_account_type">
                                        <option value="">— Select —</option>
                                        <option value="current" @selected(old('bank_account_type') === 'current')>Current / Cheque</option>
                                        <option value="savings" @selected(old('bank_account_type') === 'savings')>Savings</option>
                                    </select>
                                </div>
                                <div class="af-field narrow">
                                    <label>Branch Code</label>
                                    <input type="text" name="bank_branch_code" value="{{ old('bank_branch_code') }}" maxlength="10">
                                </div>
                            </div>

                            <hr class="divider light">

                            {{-- Address --}}
                            <div class="section-header">Address</div>
                            <div class="af-row">
                                <div class="af-field wide">
                                    <label>Address Line 1</label>
                                    <input type="text" name="address_line_1" value="{{ old('address_line_1') }}" placeholder="e.g. 12 Main Street">
                                </div>
                                <div class="af-field wide">
                                    <label>Address Line 2</label>
                                    <input type="text" name="address_line_2" value="{{ old('address_line_2') }}" placeholder="e.g. Unit 4, Block B">
                                </div>
                            </div>
                            <div class="af-row">
                                <div class="af-field">
                                    <label>City / Town</label>
                                    <input type="text" name="city" value="{{ old('city') }}">
                                </div>
                                <div class="af-field">
                                    <label>Province</label>
                                    <input type="text" name="province" value="{{ old('province') }}">
                                </div>
                                <div class="af-field narrow">
                                    <label>Postal Code</label>
                                    <input type="text" name="postal_code" value="{{ old('postal_code') }}" maxlength="10">
                                </div>
                            </div>

                            @if ($components->count())
                                <hr class="divider light">

                                {{-- Pay Components --}}
                                <div class="section-header">Additional Pay Components</div>
                                <p class="section-note">Set monthly amounts for allowances and deductions beyond basic salary. Leave at R 0.00 to exclude.</p>
                                @foreach ($components as $i => $component)
                                    <div class="comp-row">
                                        <input type="hidden" name="components[{{ $i }}][id]" value="{{ $component->id }}">
                                        <div style="flex:1;">
                                            <span style="font-size:0.82rem;font-weight:700;">{{ $component->name }}</span>
                                            <span class="comp-badge">{{ ucfirst(str_replace('_', ' ', $component->type)) }}</span>
                                        </div>
                                        <div class="af-field" style="flex:0 0 150px;margin:0;">
                                            <label>Amount (R / month)</label>
                                            <input type="number" name="components[{{ $i }}][amount]" value="{{ old("components.{$i}.amount", '0.00') }}" step="0.01" min="0" placeholder="0.00">
                                        </div>
                                    </div>
                                @endforeach
                            @endif

                            <hr class="divider" style="margin-top:1.5rem;">

                            <div style="display:flex;gap:0.75rem;align-items:center;">
                                <button type="submit" class="reg-btn primary">Save Employee</button>
                                <a href="{{ route('companies.payroll.employees.index', $company) }}"
                                    class="reg-btn">Cancel</a>
                            </div>

                        </div>
                    </div>

                </form>

            </main>
        </div>
    </div>
@endsection
