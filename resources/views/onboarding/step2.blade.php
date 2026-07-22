@extends('layouts.public')

@section('title', 'Tax & Compliance Details — Step 2')
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
    .af-row-3 { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 0.65rem 1.25rem; margin-bottom: 0.65rem; }
    .af-field label { display: block; font-size: 0.6rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.09em; color: #888; margin-bottom: 0.3rem; }
    .af-field input {
        width: 100%; box-sizing: border-box;
        border: 1.5px solid #e5e7eb;
        padding: 0.48rem 0.65rem;
        font-size: 0.82rem; font-family: inherit; color: #1b1b18;
        background: #fff; outline: none;
        transition: border-color 0.15s;
    }
    .af-field input:focus { border-color: #5e17eb; }
    .af-field input.has-error { border-color: #dc2626; background: #fff8f8; }
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
        .af-row, .af-row-3 { grid-template-columns: 1fr; }
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
                    <h1 style="font-size:1.3rem;font-weight:900;color:#fff;margin:0;letter-spacing:-0.02em;">Tax &amp; Compliance Details</h1>
                    <p style="font-size:0.74rem;color:rgba(255,255,255,0.55);margin:0.2rem 0 0;">Required by SARS for accurate tax filing and payroll.</p>
                </div>
                <span style="font-size:0.62rem;font-weight:700;color:rgba(255,255,255,0.45);letter-spacing:0.1em;text-transform:uppercase;padding-top:0.15rem;flex-shrink:0;">Step 2 of 3</span>
            </div>
            <div style="position:relative;z-index:1;">
                @include('onboarding.partials.steps', ['current' => 2])
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

                <form method="POST" action="{{ route('onboarding.step2.store', $company) }}">
                    @csrf

                    <div class="ob-section-header"><span class="ob-section-title">Income Tax</span></div>
                    <div class="af-row">
                        <div class="af-field">
                            <label>Income Tax Number <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#bbb;">(optional)</span></label>
                            <input type="text" name="income_tax_number"
                                value="{{ old('income_tax_number', $company->income_tax_number) }}"
                                placeholder="10-digit SARS reference" maxlength="10"
                                class="{{ $errors->has('income_tax_number') ? 'has-error' : '' }}">
                            <p class="ob-hint">Your 10-digit SARS reference number.</p>
                            @error('income_tax_number')<p class="ob-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="af-field">
                            <label>VAT Number <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#bbb;">(optional)</span></label>
                            <input type="text" name="vat_number"
                                value="{{ old('vat_number', $company->vat_number) }}"
                                placeholder="10-digit VAT number" maxlength="10"
                                class="{{ $errors->has('vat_number') ? 'has-error' : '' }}">
                            <p class="ob-hint">Required if turnover exceeds R1 million.</p>
                            @error('vat_number')<p class="ob-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="ob-section-header"><span class="ob-section-title">Payroll &amp; Employee Taxes</span></div>
                    <p class="ob-section-sub">Only required if you have employees — used for EMP201 returns.</p>
                    <div class="af-row-3">
                        <div class="af-field">
                            <label>PAYE Number <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#bbb;">(optional)</span></label>
                            <input type="text" name="paye_number"
                                value="{{ old('paye_number', $company->paye_number) }}"
                                placeholder="e.g. 7000000000"
                                class="{{ $errors->has('paye_number') ? 'has-error' : '' }}">
                            @error('paye_number')<p class="ob-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="af-field">
                            <label>UIF Number <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#bbb;">(optional)</span></label>
                            <input type="text" name="uif_number"
                                value="{{ old('uif_number', $company->uif_number) }}"
                                placeholder="UIF reference"
                                class="{{ $errors->has('uif_number') ? 'has-error' : '' }}">
                            @error('uif_number')<p class="ob-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="af-field">
                            <label>SDL Number <span style="font-weight:400;text-transform:none;letter-spacing:0;color:#bbb;">(optional)</span></label>
                            <input type="text" name="sdl_number"
                                value="{{ old('sdl_number', $company->sdl_number) }}"
                                placeholder="SDL reference"
                                class="{{ $errors->has('sdl_number') ? 'has-error' : '' }}">
                            @error('sdl_number')<p class="ob-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div style="border-top:2px solid #000;margin-top:1.75rem;padding-top:1rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
                        <a href="{{ route('onboarding.step1') }}" class="mgmt-btn ghost">&larr; Back</a>
                        <button type="submit" class="mgmt-btn primary">Continue: Financial Setup &rarr;</button>
                    </div>
                </form>

            </div>
        </div>

    </div>
</div>
@endsection
