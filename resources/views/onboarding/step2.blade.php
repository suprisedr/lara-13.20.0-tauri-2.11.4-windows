@extends('layouts.public')

@section('title', 'Tax & Compliance — Step 2')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
<style>
    .ob-page { min-height:100vh; background:#f7f5ff; display:flex; justify-content:center; padding:2rem 1rem 4rem; }
    .cn-form { max-width:920px; width:100%; }
    .cn-section { margin-bottom:1.75rem; }
    .cn-section-title { font-size:0.65rem; font-weight:800; letter-spacing:0.1em; text-transform:uppercase; color:#5e17eb; margin:0 0 0.75rem; }
    .cn-grid { display:grid; grid-template-columns:1fr 1fr; gap:0.75rem 1.25rem; }
    .cn-grid.three { grid-template-columns:1fr 1fr 1fr; }
    .cn-field label { display:block; font-size:0.6rem; font-weight:700; letter-spacing:0.07em; text-transform:uppercase; color:#555; margin-bottom:0.22rem; }
    .cn-field input, .cn-field select {
        width:100%; border:1px solid #ccc; padding:0.35rem 0.55rem;
        font-size:0.8rem; font-family:inherit; color:#000; background:#fff; box-sizing:border-box;
    }
    .cn-field input:focus, .cn-field select:focus { outline:none; border-color:#5e17eb; }
    .cn-field input.has-error, .cn-field select.has-error { border-color:#dc2626; background:#fff8f8; }
    .form-error { color:#dc2626; font-size:0.72rem; margin-top:0.2rem; }
    .form-hint { font-size:0.68rem; color:#9ca3af; margin-top:0.15rem; }
    .btn-submit { background:#000; color:#fff; border:none; padding:0.55rem 1.5rem; font-size:0.82rem; font-weight:700; cursor:pointer; transition:background 0.15s; font-family:inherit; display:inline-flex; align-items:center; gap:0.4rem; }
    .btn-submit:hover { background:#333; }
    .ob-header { margin-bottom:0.5rem; }
    .ob-header h1 { font-size:1.25rem; font-weight:800; color:#000; margin:0 0 0.15rem; letter-spacing:-0.015em; }
    .ob-header p { font-size:0.78rem; color:#6b7280; margin:0; }
    .ob-step-label { font-size:0.6rem; font-weight:700; color:#999; letter-spacing:0.1em; text-transform:uppercase; margin-bottom:0.35rem; }
    .ob-section-sub { font-size:0.72rem; color:#9ca3af; margin:-0.5rem 0 0.75rem; }
    @media (max-width:640px) {
        .cn-grid, .cn-grid.three { grid-template-columns:1fr; }
    }
</style>
@endpush

@section('content')
<div class="ob-page">
    <div class="cn-form">

        <p class="ob-step-label">Step 2 of 3 — {{ $company->registered_name }}</p>
        <div class="ob-header">
            <h1>Tax &amp; Compliance Details</h1>
            <p>Required by SARS for accurate tax filing and payroll.</p>
        </div>

        @include('onboarding.partials.steps', ['current' => 2])

        @if ($errors->any())
            <div style="background:#fee2e2;border:1px solid #fecaca;color:#b91c1c;padding:0.75rem 1.25rem;font-size:0.82rem;margin-bottom:1.25rem;">
                <strong>Please fix the following:</strong>
                <ul style="margin:0.4rem 0 0 1.1rem;padding:0;">
                    @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('onboarding.step2.store', $company) }}">
            @csrf

            <div class="cn-section">
                <p class="cn-section-title">Income Tax</p>
                <div class="cn-grid">
                    <div class="cn-field">
                        <label>Income Tax Number <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                        <input type="text" name="income_tax_number"
                            value="{{ old('income_tax_number', $company->income_tax_number) }}"
                            placeholder="10-digit SARS reference" maxlength="10"
                            class="{{ $errors->has('income_tax_number') ? 'has-error' : '' }}">
                        <p class="form-hint">Your 10-digit SARS reference number.</p>
                        @error('income_tax_number')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="cn-field">
                        <label>VAT Number <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                        <input type="text" name="vat_number"
                            value="{{ old('vat_number', $company->vat_number) }}"
                            placeholder="10-digit VAT number" maxlength="10"
                            class="{{ $errors->has('vat_number') ? 'has-error' : '' }}">
                        <p class="form-hint">Required if turnover exceeds R1 million.</p>
                        @error('vat_number')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div class="cn-section">
                <p class="cn-section-title">Payroll &amp; Employee Taxes</p>
                <p class="ob-section-sub">Only required if you have employees — used for EMP201 returns.</p>
                <div class="cn-grid three">
                    <div class="cn-field">
                        <label>PAYE Number <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                        <input type="text" name="paye_number"
                            value="{{ old('paye_number', $company->paye_number) }}"
                            placeholder="e.g. 7000000000"
                            class="{{ $errors->has('paye_number') ? 'has-error' : '' }}">
                        @error('paye_number')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="cn-field">
                        <label>UIF Number <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                        <input type="text" name="uif_number"
                            value="{{ old('uif_number', $company->uif_number) }}"
                            placeholder="UIF reference"
                            class="{{ $errors->has('uif_number') ? 'has-error' : '' }}">
                        @error('uif_number')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="cn-field">
                        <label>SDL Number <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                        <input type="text" name="sdl_number"
                            value="{{ old('sdl_number', $company->sdl_number) }}"
                            placeholder="SDL reference"
                            class="{{ $errors->has('sdl_number') ? 'has-error' : '' }}">
                        @error('sdl_number')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;">
                <div style="display:flex;align-items:center;gap:1.25rem;">
                    <a href="{{ route('onboarding.step1') }}" style="font-size:0.78rem;color:#6b7280;text-decoration:none;">← Back</a>
                    <form method="POST" action="{{ route('onboarding.cancel', $company) }}" style="margin:0;"
                        data-confirm-title="Abandon onboarding?"
                        data-confirm-body="This will permanently delete the company &ldquo;{{ $company->registered_name }}&rdquo; and all data entered so far."
                        data-confirm-text="Delete &amp; Exit"
                        data-confirm-danger="true">
                        @csrf @method('DELETE')
                        <button type="submit" style="background:none;border:none;padding:0;font-size:0.78rem;color:#dc2626;cursor:pointer;font-family:inherit;">Cancel Setup</button>
                    </form>
                </div>
                <button type="submit" class="btn-submit">Continue: Financial Setup</button>
            </div>
        </form>

    </div>
</div>
@endsection
