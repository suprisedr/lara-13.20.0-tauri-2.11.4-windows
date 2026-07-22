@extends('layouts.public')

@section('title', 'Financial Setup — Step 3')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
<style>
    body { background: #f5f5f0; }
    .ob-wrap { min-height: 100vh; background: #f5f5f0; padding-bottom: 4rem; }
    .ob-content { max-width: 720px; margin: 0 auto; padding: 0 1.25rem 3rem; }
    .ob-topbar {
        background: linear-gradient(135deg, #5e17eb 0%, #3b0ea8 100%);
        padding: 1.5rem 1.75rem;
        margin: 3.5rem 0 0;
        position: relative; overflow: hidden;
    }
    .ob-topbar::before {
        content: '';
        position: absolute; inset: 0;
        background: repeating-linear-gradient(-45deg, transparent, transparent 40px, rgba(255,255,255,0.025) 40px, rgba(255,255,255,0.025) 41px);
        pointer-events: none;
    }
    .cust-doc { background: #fff; border: 1px solid #ddd; }
    .cust-doc-body { padding: 1.75rem 2rem; }
    .ob-section-header { border-top: 2px solid #000; margin: 1.5rem 0 0.5rem; padding-top: 0.35rem; }
    .ob-section-header:first-child { margin-top: 0; }
    .ob-section-title { font-size: 0.62rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.12em; color: #000; }
    .ob-section-sub { font-size: 0.72rem; color: #9ca3af; margin: 0.15rem 0 1rem; }
    .af-row { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.65rem 1.25rem; margin-bottom: 0.65rem; }
    .af-field label { display: block; font-size: 0.6rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.09em; color: #888; margin-bottom: 0.3rem; }
    .af-field input, .af-field select {
        width: 100%; box-sizing: border-box;
        border: 1.5px solid #e5e7eb;
        padding: 0.48rem 0.65rem;
        font-size: 0.82rem; font-family: inherit; color: #1b1b18;
        background: #fff; outline: none;
        transition: border-color 0.15s;
        appearance: none;
    }
    .af-field input:focus, .af-field select:focus { border-color: #5e17eb; }
    .af-field input.has-error, .af-field select.has-error { border-color: #dc2626; background: #fff8f8; }
    .ob-hint { font-size: 0.71rem; color: #9ca3af; margin: 0.25rem 0 0; }
    .ob-error { font-size: 0.71rem; color: #dc2626; margin: 0.2rem 0 0; }
    .mgmt-btn { display:inline-flex; align-items:center; gap:0.4rem; background:#fff; border:1px solid #000; color:#000; font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; padding:0.45rem 1rem; text-decoration:none; cursor:pointer; font-family:inherit; transition:background 0.15s,color 0.15s; }
    .mgmt-btn:hover { background:#000; color:#fff; }
    .mgmt-btn.primary { background:#000; color:#fff; }
    .mgmt-btn.primary:hover { background:#333; }
    .mgmt-btn.ghost { background:transparent; border-color:#ccc; color:#555; }
    .mgmt-btn.ghost:hover { background:#f3f4f6; color:#000; border-color:#999; }
    @media (max-width: 640px) {
        .ob-topbar { margin-top: 0.75rem; padding: 1.1rem 1.25rem; }
        .af-row { grid-template-columns: 1fr; }
        .cust-doc-body { padding: 1.25rem 1.1rem; }
    }
</style>
@endpush

@section('content')
<div class="ob-wrap">
    <div class="ob-content">

        <div class="ob-topbar" style="position:relative;">
            <div style="position:relative;z-index:1;display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;">
                <div>
                    <p style="font-size:0.6rem;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:rgba(255,255,255,0.45);margin:0 0 0.25rem;">{{ $company->registered_name }}</p>
                    <h1 style="font-size:1.3rem;font-weight:900;color:#fff;margin:0;letter-spacing:-0.02em;">Financial Setup</h1>
                    <p style="font-size:0.74rem;color:rgba(255,255,255,0.55);margin:0.2rem 0 0;">Set up your industry and primary bank account for reconciliation.</p>
                </div>
                <span style="font-size:0.62rem;font-weight:700;color:rgba(255,255,255,0.45);letter-spacing:0.1em;text-transform:uppercase;padding-top:0.15rem;flex-shrink:0;">Step 3 of 3</span>
            </div>
            <div style="position:relative;z-index:1;">
                @include('onboarding.partials.steps', ['current' => 3])
            </div>
        </div>

        <div class="cust-doc">
            <div class="cust-doc-body">

                @if ($errors->any())
                    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#b91c1c;padding:0.6rem 0.85rem;font-size:0.77rem;margin-bottom:1.25rem;">
                        <strong>Please fix the following:</strong>
                        <ul style="margin:0.3rem 0 0 1rem;padding:0;">
                            @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('onboarding.step3.store', $company) }}">
                    @csrf

                    <div class="ob-section-header"><span class="ob-section-title">Industry</span></div>
                    <div class="af-row" style="grid-template-columns:1fr;">
                        <div class="af-field">
                            <label>Industry *</label>
                            <select id="industry" name="industry"
                                class="{{ $errors->has('industry') ? 'has-error' : '' }}">
                                <option value="">Select your industry…</option>
                                @foreach ($industries as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ old('industry', $company->industry) === $value ? 'selected' : '' }}>
                                        {{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="ob-hint">Used to tailor your chart of accounts to your sector.</p>
                            @error('industry')<p class="ob-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="ob-section-header"><span class="ob-section-title">Primary Bank Account</span></div>
                    <p class="ob-section-sub">Used for automated bank reconciliation. You can add more accounts after onboarding.</p>

                    <div class="af-row">
                        <div class="af-field">
                            <label>Bank <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#bbb;">(optional)</span></label>
                            <select id="bank_name" name="bank_name"
                                class="{{ $errors->has('bank_name') ? 'has-error' : '' }}">
                                <option value="">Select bank…</option>
                                @foreach (['Absa', 'African Bank', 'Capitec Bank', 'Discovery Bank', 'FNB (First National Bank)', 'Investec', 'Nedbank', 'Standard Bank', 'TymeBank', 'Other'] as $bank)
                                    <option value="{{ $bank }}"
                                        {{ old('bank_name', $company->bank_name) === $bank ? 'selected' : '' }}>
                                        {{ $bank }}</option>
                                @endforeach
                            </select>
                            @error('bank_name')<p class="ob-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="af-field">
                            <label>Account Type <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#bbb;">(optional)</span></label>
                            <select id="bank_account_type" name="bank_account_type"
                                class="{{ $errors->has('bank_account_type') ? 'has-error' : '' }}">
                                <option value="">Select type…</option>
                                <option value="current" {{ old('bank_account_type', $company->bank_account_type) === 'current' ? 'selected' : '' }}>Current / Cheque</option>
                                <option value="savings" {{ old('bank_account_type', $company->bank_account_type) === 'savings' ? 'selected' : '' }}>Savings</option>
                                <option value="cheque"  {{ old('bank_account_type', $company->bank_account_type) === 'cheque'  ? 'selected' : '' }}>Business Cheque</option>
                            </select>
                            @error('bank_account_type')<p class="ob-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="af-row">
                        <div class="af-field">
                            <label>Account Number <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#bbb;">(optional)</span></label>
                            <input type="text" id="bank_account_number" name="bank_account_number"
                                value="{{ old('bank_account_number', $company->bank_account_number) }}"
                                placeholder="e.g. 62012345678" maxlength="20"
                                class="{{ $errors->has('bank_account_number') ? 'has-error' : '' }}">
                            @error('bank_account_number')<p class="ob-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="af-field">
                            <label>Universal Branch Code <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#bbb;">(optional)</span></label>
                            <input type="text" id="bank_branch_code" name="bank_branch_code"
                                value="{{ old('bank_branch_code', $company->bank_branch_code) }}"
                                placeholder="e.g. 632005" maxlength="6"
                                class="{{ $errors->has('bank_branch_code') ? 'has-error' : '' }}">
                            <p class="ob-hint">6-digit universal branch code.</p>
                            @error('bank_branch_code')<p class="ob-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div style="border-top:2px solid #000;margin-top:1.75rem;padding-top:1rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
                        <a href="{{ route('onboarding.step2', $company) }}" class="mgmt-btn ghost">&larr; Back</a>
                        <button type="submit" class="mgmt-btn primary">
                            <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" style="flex-shrink:0;"><polyline points="20 6 9 17 4 12"/></svg>
                            Complete Setup
                        </button>
                    </div>
                </form>

            </div>
        </div>

    </div>
</div>
@endsection
