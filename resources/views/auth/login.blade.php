@extends('layouts.public')

@section('title', 'Log In')
@section('meta-robots', 'noindex, nofollow')
@section('meta-description', 'Log in to your Chainbook Intelligence account.')

@push('styles')
<style>
    :root {
        --brand:        #5e17eb;
        --brand-dark:   #3b0ea8;
        --brand-deep:   #1a0560;
        --brand-soft:   #ede9fe;
        --brand-tint:   #f7f5fc;
    }

    .login-wrap {
        min-height: calc(100vh - 4rem);
        padding-top: 4rem;
        background: #fff;
        display: flex;
        align-items: stretch;
        justify-content: center;
    }

    .login-grid {
        display: grid;
        grid-template-columns: 1.05fr 1fr;
        width: 100%;
        max-width: 1180px;
        margin: 0 auto;
        padding: 2rem;
        gap: 2.5rem;
        align-items: stretch;
    }

    /* ── Left: violet pitch panel ──────────────────────────── */
    .login-pitch {
        background: linear-gradient(160deg, var(--brand) 0%, var(--brand-dark) 55%, var(--brand-deep) 100%);
        color: #fff;
        border-radius: 0;
        padding: 2.5rem;
        position: relative;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .login-pitch::before {
        content: '';
        position: absolute;
        top: -120px;
        right: -120px;
        width: 360px;
        height: 360px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.06);
        pointer-events: none;
    }
    .login-pitch::after {
        content: '';
        position: absolute;
        bottom: -100px;
        left: -80px;
        width: 240px;
        height: 240px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.04);
        pointer-events: none;
    }
    .login-tag {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background: rgba(255, 255, 255, 0.14);
        color: #fff;
        padding: 0.3rem 0.8rem;
        border-radius:0;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        margin-bottom: 1.5rem;
        width: fit-content;
    }
    .login-tag::before {
        content: '';
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #4ade80;
        box-shadow: 0 0 6px #4ade80;
    }
    .login-pitch h2 {
        position: relative;
        font-size: clamp(1.7rem, 3.2vw, 2.3rem);
        font-weight: 900;
        line-height: 1.15;
        margin: 0 0 1rem;
        letter-spacing: -0.02em;
    }
    .login-pitch h2 em {
        font-style: normal;
        color: rgba(255, 255, 255, 0.65);
    }
    .login-pitch p.lead {
        position: relative;
        font-size: 0.95rem;
        color: rgba(255, 255, 255, 0.82);
        line-height: 1.6;
        margin: 0 0 1.75rem;
        max-width: 360px;
    }

    .login-proof {
        position: relative;
        display: grid;
        gap: 0.85rem;
        margin-top: 0.5rem;
    }
    .login-proof-row {
        display: grid;
        grid-template-columns: 22px 1fr;
        gap: 0.7rem;
        align-items: flex-start;
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.88);
        line-height: 1.45;
    }
    .login-proof-row svg {
        width: 18px;
        height: 18px;
        color: #c4b5fd;
        margin-top: 0.1rem;
        flex-shrink: 0;
    }
    .login-proof-row strong { color: #fff; font-weight: 700; }

    .login-foot {
        position: relative;
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 1px solid rgba(255, 255, 255, 0.16);
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
    }
    .login-foot div .lbl {
        font-size: 0.62rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.5);
        margin-bottom: 0.2rem;
    }
    .login-foot div .val {
        font-size: 0.85rem;
        font-weight: 700;
        color: #fff;
    }

    /* ── Right: form card ──────────────────────────────────── */
    .login-card {
        background: #fff;
        border: 1px solid var(--brand-soft);
        border-radius: 0;
        padding: 2.25rem;
        box-shadow: 0 1px 2px rgba(94, 23, 235, 0.04), 0 12px 30px rgba(94, 23, 235, 0.07);
        align-self: stretch;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .login-card h1 {
        font-size: 1.65rem;
        font-weight: 800;
        color: #1b1b18;
        margin: 0 0 0.3rem;
        letter-spacing: -0.015em;
    }
    .login-card .sub {
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
        border: 1px solid var(--brand-soft);
        border-radius: 0;
        outline: none;
        transition: border-color 0.18s, box-shadow 0.18s, background 0.18s;
        box-sizing: border-box;
    }
    .auth-input:focus {
        border-color: var(--brand);
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
        background: var(--brand);
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
    .auth-btn:hover {
        background: var(--brand-dark);
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(94, 23, 235, 0.28);
    }
    .auth-btn:active { transform: none; }

    .auth-checkbox {
        width: 1rem;
        height: 1rem;
        accent-color: var(--brand);
        cursor: pointer;
        flex-shrink: 0;
    }

    .auth-status {
        padding: 0.7rem 0.95rem;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-left-width: 3px;
        border-left-color: #16a34a;
        border-radius: 0;
        font-size: 0.82rem;
        color: #15803d;
        margin-bottom: 1.25rem;
    }

    .auth-password-wrap { position: relative; }
    .auth-password-wrap .auth-input { padding-right: 2.75rem; }
    .auth-password-toggle {
        position: absolute;
        top: 0;
        right: 0;
        height: 100%;
        width: 2.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: none;
        border: none;
        cursor: pointer;
        color: #9ca3af;
        transition: color 0.18s;
    }
    .auth-password-toggle:hover { color: var(--brand); }
    .auth-password-toggle svg { width: 18px; height: 18px; }

    .login-link {
        color: var(--brand);
        font-weight: 700;
        text-decoration: none;
    }
    .login-link:hover { text-decoration: underline; }

    .login-register-row {
        margin: 1.25rem 0 0;
        font-size: 0.85rem;
        color: #6b7280;
        text-align: center;
    }

    @media (max-width: 960px) {
        .login-grid { grid-template-columns: 1fr; gap: 1.5rem; }
        .login-pitch { order: 2; }
        .login-card  { order: 1; }
        .login-foot  { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 480px) {
        .login-grid { padding: 1rem; }
        .login-pitch, .login-card { padding: 1.5rem; }
    }
</style>
@endpush

@section('content')
<div class="login-wrap">
    <div class="login-grid">

        {{-- ── Left: pitch panel (violet brand) ───────────────── --}}
        <aside class="login-pitch" aria-hidden="true">
            <div>
                <span class="login-tag">Welcome back</span>
                <h2>Your AI agents are <em>waiting.</em></h2>
                <p class="lead">Pick up where you left off.</p>

                <div class="login-proof">
                    <div class="login-proof-row">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        <span><strong>AI-posted journals.</strong></span>
                    </div>
                    <div class="login-proof-row">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        <span><strong>Instant IFRS reports.</strong></span>
                    </div>
                    <div class="login-proof-row">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        <span><strong>Automated month-end posting.</strong></span>
                    </div>
                </div>
            </div>

            <div class="login-foot">
                <div><div class="lbl">Standard</div><div class="val">IFRS-for-SMEs</div></div>
                <div><div class="lbl">Tax</div><div class="val">VAT201 · EMP201</div></div>
                <div><div class="lbl">Consolidation</div><div class="val">IFRS 10</div></div>
            </div>
        </aside>

        {{-- ── Right: form ────────────────────────────────────── --}}
        <div class="login-card">
            <h1>Welcome back</h1>
            <p class="sub">Log in to continue.</p>

            @if (session('status'))
                <div class="auth-status">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                {{-- Email --}}
                <div style="margin-bottom:1rem;">
                    <label class="auth-label" for="email">Email address</label>
                    <input
                        id="email"
                        class="auth-input {{ $errors->has('email') ? 'error' : '' }}"
                        type="email" name="email"
                        value="{{ old('email') }}"
                        required autofocus
                        autocomplete="username"
                        placeholder="you@company.com">
                    @error('email')<p class="auth-error">{{ $message }}</p>@enderror
                </div>

                {{-- Password --}}
                <div style="margin-bottom:1rem;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.4rem;">
                        <label class="auth-label" for="password" style="margin-bottom:0;">Password</label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="login-link" style="font-size:0.74rem;font-weight:600;">Forgot password?</a>
                        @endif
                    </div>
                    <div class="auth-password-wrap">
                        <input
                            id="password"
                            class="auth-input {{ $errors->has('password') ? 'error' : '' }}"
                            type="password" name="password" required
                            autocomplete="current-password"
                            placeholder="••••••••">
                        <button type="button" class="auth-password-toggle" aria-label="Show password" onclick="
                            var input = document.getElementById('password');
                            var show = input.type === 'password';
                            input.type = show ? 'text' : 'password';
                            this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                            this.querySelector('.icon-eye').style.display = show ? 'none' : 'block';
                            this.querySelector('.icon-eye-off').style.display = show ? 'block' : 'none';
                        ">
                            <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="icon-eye-off" style="display:none;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>
                    @error('password')<p class="auth-error">{{ $message }}</p>@enderror
                </div>

                {{-- Remember me --}}
                <div style="display:flex;align-items:center;gap:0.6rem;margin-bottom:1.5rem;">
                    <input id="remember_me" class="auth-checkbox" type="checkbox" name="remember">
                    <label for="remember_me" style="font-size:0.85rem;color:#444;cursor:pointer;">Keep me logged in</label>
                </div>

                <button type="submit" class="auth-btn">Log in &rarr;</button>

                <p class="login-register-row">
                    Don't have an account?
                    <a href="{{ route('register') }}" class="login-link">Create one free</a>
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
    var pitch = document.querySelector('.login-pitch');
    var card  = document.querySelector('.login-card');
    if (pitch) gsap.from(pitch, { x: -30, opacity: 0, duration: 0.85, ease: 'expo.out', delay: 0.05 });
    if (card)  gsap.from(card,  { x:  30, opacity: 0, duration: 0.85, ease: 'expo.out', delay: 0.15 });
});
</script>
@endpush
