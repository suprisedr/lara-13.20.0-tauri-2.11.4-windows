@extends('layouts.public')

@section('title', 'Create Account')
@section('meta-robots', 'noindex, nofollow')
@section('meta-description', 'Create your free Chainbook Intelligence account — AI-driven double-entry accounting for South African businesses.')

@push('styles')
<style>
    .reg-wrap {
        min-height: calc(100vh - 4rem);
        padding-top: 4rem;
        background: #fff;
        display: flex;
        align-items: stretch;
        justify-content: center;
    }

    .reg-grid {
        display: grid;
        grid-template-columns: 1.05fr 1fr;
        width: 100%;
        max-width: 1180px;
        margin: 0 auto;
        padding: 2rem;
        gap: 2.5rem;
        align-items: stretch;
    }

    /* Left: violet brand "marketing" panel */
    .reg-pitch {
        background: linear-gradient(160deg, #5e17eb 0%, #3b0ea8 55%, #1a0560 100%);
        color: #fff;
        border-radius: 0;
        padding: 2.5rem;
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .reg-pitch::after {
        content: '';
        position: absolute;
        top: -120px;
        right: -120px;
        width: 320px;
        height: 320px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.04);
        pointer-events: none;
    }
    .reg-tag {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background: rgba(255, 255, 255, 0.08);
        color: rgba(255, 255, 255, 0.9);
        padding: 0.3rem 0.8rem;
        border-radius:0;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        margin-bottom: 1.5rem;
        width: fit-content;
    }
    .reg-tag::before {
        content: '';
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #10b981;
        box-shadow: 0 0 6px #10b981;
    }
    .reg-pitch h2 {
        position: relative;
        font-size: clamp(1.6rem, 3vw, 2.2rem);
        font-weight: 900;
        line-height: 1.15;
        margin: 0 0 1rem;
        letter-spacing: -0.02em;
    }
    .reg-pitch h2 em {
        font-style: normal;
        color: rgba(255, 255, 255, 0.6);
    }
    .reg-pitch p.lead {
        position: relative;
        font-size: 0.95rem;
        color: rgba(255, 255, 255, 0.78);
        line-height: 1.6;
        margin: 0 0 1.75rem;
        max-width: 360px;
    }

    .reg-proof {
        position: relative;
        display: grid;
        gap: 0.85rem;
        margin-top: 0.5rem;
    }
    .reg-proof-row {
        display: grid;
        grid-template-columns: 22px 1fr;
        gap: 0.7rem;
        align-items: flex-start;
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.88);
        line-height: 1.45;
    }
    .reg-proof-row svg {
        width: 18px;
        height: 18px;
        color: #c4b5fd;
        margin-top: 0.1rem;
        flex-shrink: 0;
    }
    .reg-proof-row strong {
        color: #fff;
        font-weight: 700;
    }

    .reg-foot {
        position: relative;
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
    }
    .reg-foot div .lbl {
        font-size: 0.62rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.45);
        margin-bottom: 0.2rem;
    }
    .reg-foot div .val {
        font-size: 0.85rem;
        font-weight: 700;
        color: #fff;
    }

    /* Right: form */
    .reg-card {
        background: #fff;
        border: 1px solid #ede9fe;
        border-radius: 0;
        padding: 2.25rem;
        box-shadow: 0 1px 2px rgba(94, 23, 235, 0.04), 0 12px 30px rgba(94, 23, 235, 0.07);
        align-self: stretch;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .reg-card h1 {
        font-size: 1.65rem;
        font-weight: 800;
        color: #0a0a0a;
        margin: 0 0 0.3rem;
        letter-spacing: -0.015em;
    }
    .reg-card .sub {
        font-size: 0.875rem;
        color: #6b7280;
        margin: 0 0 1.75rem;
    }

    .auth-label {
        display: block;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #1f2937;
        margin-bottom: 0.4rem;
    }
    .auth-input {
        display: block;
        width: 100%;
        padding: 0.72rem 0.9rem;
        font-size: 0.9rem;
        font-family: inherit;
        color: #1b1b18;
        background: #fff;
        border: 1px solid #ede9fe;
        border-radius: 0;
        outline: none;
        transition: border-color 0.18s, box-shadow 0.18s, background 0.18s;
        box-sizing: border-box;
    }
    .auth-input:focus {
        border-color: #5e17eb;
        box-shadow: 0 0 0 3px rgba(94, 23, 235, 0.12);
    }
    .auth-input.error {
        border-color: #dc2626;
        background: #fff5f5;
    }
    .auth-error {
        font-size: 0.78rem;
        color: #dc2626;
        margin: 0.35rem 0 0;
    }

    .auth-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        width: 100%;
        padding: 0.85rem 1.25rem;
        background: #5e17eb;
        color: #fff;
        font-size: 0.92rem;
        font-weight: 700;
        font-family: inherit;
        letter-spacing: 0.01em;
        border: none;
        border-radius: 0;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(94, 23, 235, 0.22);
        transition: background 0.18s, transform 0.15s, box-shadow 0.18s;
    }
    .auth-btn:hover { background: #3b0ea8; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(94, 23, 235, 0.28); }
    .auth-btn:active { transform: none; }

    .auth-field-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.9rem;
    }

    .reg-login-link {
        margin: 1.25rem 0 0;
        font-size: 0.85rem;
        color: #6b7280;
        text-align: center;
    }
    .reg-login-link a {
        color: #5e17eb;
        font-weight: 700;
        text-decoration: none;
    }
    .reg-login-link a:hover { text-decoration: underline; }

    @media (max-width: 960px) {
        .reg-grid { grid-template-columns: 1fr; gap: 1.5rem; }
        .reg-pitch { order: 2; }
        .reg-card { order: 1; }
        .reg-foot { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 480px) {
        .reg-grid { padding: 1rem; }
        .reg-pitch, .reg-card { padding: 1.5rem; }
        .auth-field-row { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="reg-wrap">
    <div class="reg-grid">

        {{-- ── Left: product pitch ────────────────────────────── --}}
        <aside class="reg-pitch" aria-hidden="true">
            <div>
                <span class="reg-tag">Free to start</span>
                <h2>One ledger. <em>AI does the rest.</em></h2>
                <p class="lead">Built for SA businesses.</p>

                <div class="reg-proof">
                    <div class="reg-proof-row">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        <span><strong>Auto-generated IFRS COA.</strong></span>
                    </div>
                    <div class="reg-proof-row">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        <span><strong>AI-posted journals.</strong></span>
                    </div>
                    <div class="reg-proof-row">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        <span><strong>SA payroll &amp; VAT.</strong></span>
                    </div>
                    <div class="reg-proof-row">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        <span><strong>Reports &amp; AFS bundle.</strong></span>
                    </div>
                </div>
            </div>

            <div class="reg-foot">
                <div><div class="lbl">Standard</div><div class="val">IFRS-for-SMEs</div></div>
                <div><div class="lbl">Tax</div><div class="val">VAT201 · EMP201</div></div>
                <div><div class="lbl">Consolidation</div><div class="val">IFRS 10</div></div>
            </div>
        </aside>

        {{-- ── Right: form ───────────────────────────────────── --}}
        <div class="reg-card">
            <h1>Create your account</h1>
            <p class="sub">Set up in under a minute.</p>

            <form method="POST" action="{{ route('register') }}">
                @csrf

                {{-- Name --}}
                <div style="margin-bottom:1rem;">
                    <label class="auth-label" for="name">Full name</label>
                    <input
                        id="name"
                        class="auth-input {{ $errors->has('name') ? 'error' : '' }}"
                        type="text" name="name"
                        value="{{ old('name') }}"
                        required autofocus
                        autocomplete="name"
                        placeholder="Jane Smith">
                    @error('name')<p class="auth-error">{{ $message }}</p>@enderror
                </div>

                {{-- Email --}}
                <div style="margin-bottom:1rem;">
                    <label class="auth-label" for="email">Email address</label>
                    <input
                        id="email"
                        class="auth-input {{ $errors->has('email') ? 'error' : '' }}"
                        type="email" name="email"
                        value="{{ old('email') }}"
                        required autocomplete="username"
                        placeholder="you@company.com">
                    @error('email')<p class="auth-error">{{ $message }}</p>@enderror
                </div>

                {{-- Password + Confirm --}}
                <div class="auth-field-row" style="margin-bottom:1.5rem;">
                    <div>
                        <label class="auth-label" for="password">Password</label>
                        <input
                            id="password"
                            class="auth-input {{ $errors->has('password') ? 'error' : '' }}"
                            type="password" name="password" required
                            autocomplete="new-password"
                            placeholder="Min. 8 characters">
                        @error('password')<p class="auth-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="auth-label" for="password_confirmation">Confirm password</label>
                        <input
                            id="password_confirmation"
                            class="auth-input {{ $errors->has('password_confirmation') ? 'error' : '' }}"
                            type="password" name="password_confirmation" required
                            autocomplete="new-password"
                            placeholder="Repeat password">
                        @error('password_confirmation')<p class="auth-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <button type="submit" class="auth-btn">Create account &rarr;</button>

                <p class="reg-login-link">
                    Already have an account?
                    <a href="{{ route('login') }}">Log in</a>
                </p>
            </form>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.gsap) return;
    var pitch = document.querySelector('.reg-pitch');
    var card  = document.querySelector('.reg-card');
    if (pitch) gsap.from(pitch, { x: -30, opacity: 0, duration: 0.85, ease: 'expo.out', delay: 0.05 });
    if (card)  gsap.from(card,  { x:  30, opacity: 0, duration: 0.85, ease: 'expo.out', delay: 0.15 });
});
</script>
@endpush
