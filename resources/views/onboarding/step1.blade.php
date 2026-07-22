@extends('layouts.public')

@section('title', 'Onboard Your Company — Step 1')
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
    .ob-section-header { border-top: 2px solid #000; margin: 1.5rem 0 0.85rem; padding-top: 0.35rem; }
    .ob-section-header:first-child { margin-top: 0; }
    .ob-section-title { font-size: 0.62rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.12em; color: #000; }
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

        {{-- Violet topbar --}}
        <div class="ob-topbar" style="position:relative;">
            <div style="position:relative;z-index:1;display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;">
                <div>
                    <p style="font-size:0.6rem;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:rgba(255,255,255,0.45);margin:0 0 0.25rem;">Chainbook Intelligence</p>
                    <h1 style="font-size:1.3rem;font-weight:900;color:#fff;margin:0;letter-spacing:-0.02em;">Company Identity &amp; Statutory Info</h1>
                    <p style="font-size:0.74rem;color:rgba(255,255,255,0.55);margin:0.2rem 0 0;">Your company's legal identity as registered with CIPC.</p>
                </div>
                <span style="font-size:0.62rem;font-weight:700;color:rgba(255,255,255,0.45);letter-spacing:0.1em;text-transform:uppercase;padding-top:0.15rem;flex-shrink:0;">Step 1 of 3</span>
            </div>
            <div style="position:relative;z-index:1;">
                @include('onboarding.partials.steps', ['current' => 1])
            </div>
        </div>

        {{-- Form card --}}
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

                <form method="POST" action="{{ route('onboarding.step1.store') }}">
                    @csrf

                    <div class="ob-section-header"><span class="ob-section-title">Company Details</span></div>
                    <div class="af-row">
                        <div class="af-field">
                            <label>Registered Name *</label>
                            <input type="text" name="registered_name" value="{{ old('registered_name') }}"
                                placeholder="e.g. Acme Solutions (Pty) Ltd" required
                                class="{{ $errors->has('registered_name') ? 'has-error' : '' }}">
                            @error('registered_name')<p class="ob-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="af-field">
                            <label>Company Type *</label>
                            <select name="company_type" required class="{{ $errors->has('company_type') ? 'has-error' : '' }}">
                                <option value="" disabled {{ old('company_type') ? '' : 'selected' }}>Select type…</option>
                                @foreach (\App\Models\Company::companyTypes() as $key => $label)
                                    <option value="{{ $key }}" @selected(old('company_type') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('company_type')<p class="ob-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="af-row">
                        <div class="af-field">
                            <label>CIPC Registration Number</label>
                            <input type="text" name="registration_number" value="{{ old('registration_number') }}"
                                placeholder="2024/123456/07"
                                class="{{ $errors->has('registration_number') ? 'has-error' : '' }}">
                            <p class="ob-hint">14-char CIPC format — optional for Sole Proprietors.</p>
                            @error('registration_number')<p class="ob-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="af-field">
                            <label>Financial Year-End *</label>
                            <select name="financial_year_end_month" required class="{{ $errors->has('financial_year_end_month') ? 'has-error' : '' }}">
                                <option value="" disabled {{ old('financial_year_end_month') ? '' : 'selected' }}>Select month…</option>
                                @foreach (\App\Models\Company::months() as $num => $name)
                                    <option value="{{ $num }}" @selected((int) old('financial_year_end_month') === $num)>{{ $name }}</option>
                                @endforeach
                            </select>
                            <p class="ob-hint">Most SA businesses end in February.</p>
                            @error('financial_year_end_month')<p class="ob-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="ob-section-header"><span class="ob-section-title">Registered Address</span></div>
                    <div class="af-row" style="grid-template-columns:1fr;">
                        <div class="af-field">
                            <label>Address Line 1 *</label>
                            <input type="text" name="address_line_1" value="{{ old('address_line_1') }}"
                                placeholder="Street address or P.O. Box" required
                                class="{{ $errors->has('address_line_1') ? 'has-error' : '' }}">
                            @error('address_line_1')<p class="ob-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="af-row" style="grid-template-columns:1fr;">
                        <div class="af-field">
                            <label>Address Line 2 <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#bbb;">(optional)</span></label>
                            <input type="text" name="address_line_2" value="{{ old('address_line_2') }}"
                                placeholder="Suite, floor, building…">
                        </div>
                    </div>
                    <div class="af-row">
                        <div class="af-field">
                            <label>City *</label>
                            <input type="text" name="city" value="{{ old('city') }}"
                                placeholder="e.g. Johannesburg" required
                                class="{{ $errors->has('city') ? 'has-error' : '' }}">
                            @error('city')<p class="ob-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="af-field">
                            <label>Province *</label>
                            <select name="province" required class="{{ $errors->has('province') ? 'has-error' : '' }}">
                                <option value="" disabled {{ old('province') ? '' : 'selected' }}>Select…</option>
                                @foreach (\App\Models\Company::saProvinces() as $code => $name)
                                    <option value="{{ $code }}" @selected(old('province') === $code)>{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('province')<p class="ob-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="af-field">
                            <label>Postal Code *</label>
                            <input type="text" name="postal_code" value="{{ old('postal_code') }}"
                                placeholder="0000" maxlength="10" required
                                class="{{ $errors->has('postal_code') ? 'has-error' : '' }}">
                            @error('postal_code')<p class="ob-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div style="border-top:2px solid #000;margin-top:1.75rem;padding-top:1rem;display:flex;justify-content:flex-end;">
                        <button type="submit" class="mgmt-btn primary">
                            Continue: Tax &amp; Compliance &rarr;
                        </button>
                    </div>
                </form>

            </div>
        </div>

    </div>
</div>
@endsection
