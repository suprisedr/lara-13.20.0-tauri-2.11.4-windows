@extends('layouts.public')

@section('title', 'Onboard Your Company — Step 1')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
<style>
    .ob-page { min-height:100vh; background:#f7fbfd; display:flex; justify-content:center; padding:2rem 1rem 4rem; }
    .cn-form { max-width:920px; width:100%; }
    .cn-section { margin-bottom:1.75rem; }
    .cn-section-title { font-size:0.65rem; font-weight:800; letter-spacing:0.1em; text-transform:uppercase; color:#005bf0; margin:0 0 0.75rem; }
    .cn-grid { display:grid; grid-template-columns:1fr 1fr; gap:0.75rem 1.25rem; }
    .cn-grid.three { grid-template-columns:1fr 1fr 1fr; }
    .cn-field label { display:block; font-size:0.7rem; font-weight:700; letter-spacing:0.07em; text-transform:uppercase; color:#5a7186; margin-bottom:0.22rem; }
    .cn-field input, .cn-field select {
        width:100%; border:1px solid #d3e2f5; padding:0.35rem 0.55rem;
        font-size:0.8rem; font-family:inherit; color:#000; background:#fff; box-sizing:border-box;
    }
    .cn-field input:focus, .cn-field select:focus { outline:none; border-color:#005bf0; }
    .cn-field input.has-error, .cn-field select.has-error { border-color:#dc2626; background:#fff8f8; }
    .form-error { color:#dc2626; font-size:0.72rem; margin-top:0.2rem; }
    .form-hint { font-size:0.68rem; color:#6f869b; margin-top:0.15rem; }
    .btn-submit { background:#000; color:#fff; border:none; padding:0.55rem 1.5rem; font-size:0.82rem; font-weight:700; cursor:pointer; transition:background 0.15s; font-family:inherit; display:inline-flex; align-items:center; gap:0.4rem; }
    .btn-submit:hover { background:#1a345b; }
    .ob-header { margin-bottom:0.5rem; }
    .ob-header h1 { font-size:1.25rem; font-weight:800; color:#000; margin:0 0 0.15rem; letter-spacing:-0.015em; }
    .ob-header p { font-size:0.78rem; color:#5a7186; margin:0; }
    .ob-step-label { font-size:0.7rem; font-weight:700; color:#6f869b; letter-spacing:0.1em; text-transform:uppercase; margin-bottom:0.35rem; }
    @media (max-width:640px) {
        .cn-grid, .cn-grid.three { grid-template-columns:1fr; }
    }
</style>
@endpush

@section('content')
<div class="ob-page">
    <div class="cn-form">

        <p class="ob-step-label">Step 1 of 3</p>
        <div class="ob-header">
            <h1>Company Identity &amp; Statutory Info</h1>
            <p>Your company's legal identity as registered with CIPC.</p>
        </div>

        @include('onboarding.partials.steps', ['current' => 1])

        @if ($errors->any())
            <div style="background:#fee2e2;border:1px solid #fecaca;color:#b91c1c;padding:0.75rem 1.25rem;font-size:0.82rem;margin-bottom:1.25rem;">
                <strong>Please fix the following:</strong>
                <ul style="margin:0.4rem 0 0 1.1rem;padding:0;">
                    @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('onboarding.step1.store') }}">
            @csrf

            <div class="cn-section">
                <p class="cn-section-title">Company Details</p>
                <div class="cn-grid">
                    <div class="cn-field">
                        <label>Registered Name <span style="color:#dc2626;">*</span></label>
                        <input type="text" name="registered_name" value="{{ old('registered_name') }}"
                            placeholder="e.g. Acme Solutions (Pty) Ltd" required
                            class="{{ $errors->has('registered_name') ? 'has-error' : '' }}">
                        @error('registered_name')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="cn-field">
                        <label>Company Type <span style="color:#dc2626;">*</span></label>
                        <select name="company_type" required class="{{ $errors->has('company_type') ? 'has-error' : '' }}">
                            <option value="" disabled {{ old('company_type') ? '' : 'selected' }}>Select type…</option>
                            @foreach (\App\Models\Company::companyTypes() as $key => $label)
                                <option value="{{ $key }}" @selected(old('company_type') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('company_type')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="cn-field">
                        <label>CIPC Registration Number <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                        <input type="text" name="registration_number" value="{{ old('registration_number') }}"
                            placeholder="2024/123456/07"
                            class="{{ $errors->has('registration_number') ? 'has-error' : '' }}">
                        <p class="form-hint">14-char CIPC format — optional for Sole Proprietors.</p>
                        @error('registration_number')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="cn-field">
                        <label>Financial Year-End <span style="color:#dc2626;">*</span></label>
                        <select name="financial_year_end_month" required class="{{ $errors->has('financial_year_end_month') ? 'has-error' : '' }}">
                            <option value="" disabled {{ old('financial_year_end_month') ? '' : 'selected' }}>Select month…</option>
                            @foreach (\App\Models\Company::months() as $num => $name)
                                <option value="{{ $num }}" @selected((int) old('financial_year_end_month') === $num)>{{ $name }}</option>
                            @endforeach
                        </select>
                        <p class="form-hint">Most SA businesses end in February.</p>
                        @error('financial_year_end_month')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div class="cn-section">
                <p class="cn-section-title">Registered Address</p>
                <div class="cn-grid">
                    <div class="cn-field" style="grid-column:span 2;">
                        <label>Address Line 1 <span style="color:#dc2626;">*</span></label>
                        <input type="text" name="address_line_1" value="{{ old('address_line_1') }}"
                            placeholder="Street address or P.O. Box" required
                            class="{{ $errors->has('address_line_1') ? 'has-error' : '' }}">
                        @error('address_line_1')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="cn-field" style="grid-column:span 2;">
                        <label>Address Line 2 <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                        <input type="text" name="address_line_2" value="{{ old('address_line_2') }}"
                            placeholder="Suite, floor, building…">
                    </div>
                </div>
                <div class="cn-grid three" style="margin-top:0.75rem;">
                    <div class="cn-field">
                        <label>City <span style="color:#dc2626;">*</span></label>
                        <input type="text" name="city" value="{{ old('city') }}"
                            placeholder="e.g. Johannesburg" required
                            class="{{ $errors->has('city') ? 'has-error' : '' }}">
                        @error('city')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="cn-field">
                        <label>Province <span style="color:#dc2626;">*</span></label>
                        <select name="province" required class="{{ $errors->has('province') ? 'has-error' : '' }}">
                            <option value="" disabled {{ old('province') ? '' : 'selected' }}>Select…</option>
                            @foreach (\App\Models\Company::saProvinces() as $code => $name)
                                <option value="{{ $code }}" @selected(old('province') === $code)>{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('province')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="cn-field">
                        <label>Postal Code <span style="color:#dc2626;">*</span></label>
                        <input type="text" name="postal_code" value="{{ old('postal_code') }}"
                            placeholder="0000" maxlength="10" required
                            class="{{ $errors->has('postal_code') ? 'has-error' : '' }}">
                        @error('postal_code')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;">
                <a href="{{ route('dashboard') }}" style="font-size:0.78rem;color:#5a7186;text-decoration:none;">Cancel</a>
                <button type="submit" class="btn-submit">Continue: Tax &amp; Compliance</button>
            </div>
        </form>

    </div>
</div>
@endsection
