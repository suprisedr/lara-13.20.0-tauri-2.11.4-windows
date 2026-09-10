@extends('layouts.public')

@section('title', 'Financial Setup — Step 3')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
<style>
    .ob-page { min-height:100vh; background:#f7fbfd; display:flex; justify-content:center; padding:2rem 1rem 4rem; }
    .cn-form { max-width:920px; width:100%; }
    .cn-section { margin-bottom:1.75rem; }
    .cn-section-title { font-size:0.65rem; font-weight:800; letter-spacing:0.1em; text-transform:uppercase; color:#005bf0; margin:0 0 0.75rem; }
    .cn-grid { display:grid; grid-template-columns:1fr 1fr; gap:0.75rem 1.25rem; }
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
    .ob-section-sub { font-size:0.72rem; color:#6f869b; margin:-0.5rem 0 0.75rem; }
    @media (max-width:640px) {
        .cn-grid { grid-template-columns:1fr; }
    }
</style>
@endpush

@section('content')
<div class="ob-page">
    <div class="cn-form">

        <p class="ob-step-label">Step 3 of 3 — {{ $company->registered_name }}</p>
        <div class="ob-header">
            <h1>Financial Setup</h1>
            <p>Set up your industry and primary bank account for reconciliation.</p>
        </div>

        @include('onboarding.partials.steps', ['current' => 3])

        @if ($errors->any())
            <div style="background:#fee2e2;border:1px solid #fecaca;color:#b91c1c;padding:0.75rem 1.25rem;font-size:0.82rem;margin-bottom:1.25rem;">
                <strong>Please fix the following:</strong>
                <ul style="margin:0.4rem 0 0 1.1rem;padding:0;">
                    @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('onboarding.step3.store', $company) }}" id="step3-form">
            @csrf

            <div class="cn-section">
                <p class="cn-section-title">Industry</p>
                <div class="cn-grid">
                    <div class="cn-field" style="grid-column:span 2;">
                        <label>Industry <span style="color:#dc2626;">*</span></label>
                        <select name="industry" class="{{ $errors->has('industry') ? 'has-error' : '' }}">
                            <option value="">Select your industry…</option>
                            @foreach ($industries as $value => $label)
                                <option value="{{ $value }}"
                                    {{ old('industry', $company->industry) === $value ? 'selected' : '' }}>
                                    {{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="form-hint">Used to tailor your chart of accounts to your sector.</p>
                        @error('industry')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div class="cn-section">
                <p class="cn-section-title">Primary Bank Account</p>
                <p class="ob-section-sub">Used for automated bank reconciliation. You can add more accounts after onboarding.</p>
                <div class="cn-grid">
                    <div class="cn-field">
                        <label>Bank <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                        <select name="bank_name" class="{{ $errors->has('bank_name') ? 'has-error' : '' }}">
                            <option value="">Select bank…</option>
                            @foreach (['Absa', 'African Bank', 'Capitec Bank', 'Discovery Bank', 'FNB (First National Bank)', 'Investec', 'Nedbank', 'Standard Bank', 'TymeBank', 'Other'] as $bank)
                                <option value="{{ $bank }}"
                                    {{ old('bank_name', $company->bank_name) === $bank ? 'selected' : '' }}>
                                    {{ $bank }}</option>
                            @endforeach
                        </select>
                        @error('bank_name')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="cn-field">
                        <label>Account Type <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                        <select name="bank_account_type" class="{{ $errors->has('bank_account_type') ? 'has-error' : '' }}">
                            <option value="">Select type…</option>
                            <option value="current" {{ old('bank_account_type', $company->bank_account_type) === 'current' ? 'selected' : '' }}>Current / Cheque</option>
                            <option value="savings" {{ old('bank_account_type', $company->bank_account_type) === 'savings' ? 'selected' : '' }}>Savings</option>
                            <option value="cheque"  {{ old('bank_account_type', $company->bank_account_type) === 'cheque'  ? 'selected' : '' }}>Business Cheque</option>
                        </select>
                        @error('bank_account_type')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="cn-field">
                        <label>Account Number <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                        <input type="text" name="bank_account_number"
                            value="{{ old('bank_account_number', $company->bank_account_number) }}"
                            placeholder="e.g. 62012345678" maxlength="20"
                            class="{{ $errors->has('bank_account_number') ? 'has-error' : '' }}">
                        @error('bank_account_number')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="cn-field">
                        <label>Universal Branch Code <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                        <input type="text" name="bank_branch_code"
                            value="{{ old('bank_branch_code', $company->bank_branch_code) }}"
                            placeholder="e.g. 632005" maxlength="6"
                            class="{{ $errors->has('bank_branch_code') ? 'has-error' : '' }}">
                        <p class="form-hint">6-digit universal branch code.</p>
                        @error('bank_branch_code')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </form>

            <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;">
                <div style="display:flex;align-items:center;gap:1.25rem;">
                    <a href="{{ route('onboarding.step2', $company) }}" style="font-size:0.78rem;color:#5a7186;text-decoration:none;">← Back</a>
                    <form method="POST" action="{{ route('onboarding.cancel', $company) }}" style="margin:0;"
                        data-confirm-title="Abandon onboarding?"
                        data-confirm-body="This will permanently delete the company &ldquo;{{ $company->registered_name }}&rdquo; and all data entered so far."
                        data-confirm-text="Delete &amp; Exit"
                        data-confirm-danger="true">
                        @csrf @method('DELETE')
                        <button type="submit" style="background:none;border:none;padding:0;font-size:0.78rem;color:#dc2626;cursor:pointer;font-family:inherit;">Cancel Setup</button>
                    </form>
                </div>
                <button type="submit" form="step3-form" class="btn-submit">
                    <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="flex-shrink:0;"><polyline points="20 6 9 17 4 12"/></svg>
                    Complete Setup
                </button>
            </div>

    </div>
</div>
@endsection
