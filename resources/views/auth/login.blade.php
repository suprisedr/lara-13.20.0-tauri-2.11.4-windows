@extends('layouts.public')

@section('title', 'Log In')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
<style>
    .auth-page {
        min-height:100vh; display:flex; align-items:center; justify-content:center;
        background:#f7f5ff;
    }

    .auth-card {
        width:100%; max-width:400px; background:#fff; border:1px solid #e5e7eb;
        padding:2.5rem 2.25rem; margin:2rem 1rem;
    }

    .auth-brand {
        display:flex; align-items:center; gap:0.5rem; margin-bottom:2rem;
    }
    .auth-brand-mark {
        width:32px; height:32px; background:#5e17eb; display:flex;
        align-items:center; justify-content:center;
    }
    .auth-brand-mark svg { color:#fff; }
    .auth-brand-name {
        font-size:1rem; font-weight:800; color:#0a0a0a; letter-spacing:-0.02em;
    }

    .auth-card h1 {
        font-size:1.35rem; font-weight:800; color:#0a0a0a;
        margin:0 0 0.2rem; letter-spacing:-0.015em;
    }
    .auth-card .auth-sub {
        font-size:0.82rem; color:#6b7280; margin:0 0 1.5rem;
    }

    .auth-label {
        display:block; font-size:0.6rem; font-weight:700;
        letter-spacing:0.07em; text-transform:uppercase;
        color:#555; margin-bottom:0.22rem;
    }
    .auth-input {
        display:block; width:100%; padding:0.45rem 0.65rem;
        font-size:0.85rem; font-family:inherit; color:#000;
        background:#fff; border:1px solid #ccc; outline:none;
        transition:border-color 0.15s; box-sizing:border-box;
    }
    .auth-input:focus { border-color:#5e17eb; }
    .auth-input.error { border-color:#dc2626; background:#fff5f5; }
    .auth-error { font-size:0.72rem; color:#dc2626; margin:0.2rem 0 0; }

    .auth-btn {
        display:flex; align-items:center; justify-content:center; width:100%;
        padding:0.6rem 1.25rem; background:#000; color:#fff;
        font-size:0.85rem; font-weight:700; font-family:inherit;
        border:none; cursor:pointer; transition:background 0.15s;
    }
    .auth-btn:hover { background:#333; }

    .auth-password-wrap { position:relative; }
    .auth-password-wrap .auth-input { padding-right:2.5rem; }
    .auth-password-toggle {
        position:absolute; top:0; right:0; height:100%; width:2.5rem;
        display:flex; align-items:center; justify-content:center;
        background:none; border:none; cursor:pointer; color:#9ca3af;
        transition:color 0.15s;
    }
    .auth-password-toggle:hover { color:#000; }
    .auth-password-toggle svg { width:16px; height:16px; }

    .auth-status {
        padding:0.55rem 0.75rem; background:#dcfce7; border:1px solid #bbf7d0;
        font-size:0.78rem; color:#15803d; margin-bottom:1rem;
    }

    .auth-link { color:#5e17eb; font-weight:700; text-decoration:none; font-size:0.78rem; }
    .auth-link:hover { text-decoration:underline; }

    .auth-footer {
        margin:1.25rem 0 0; font-size:0.82rem; color:#6b7280; text-align:center;
    }
</style>
@endpush

@section('content')
<div class="auth-page">
    <div class="auth-card">

        <div class="auth-brand">
            <img src="{{ asset('storage/images/chainbook-intelligence-logo.png') }}" alt="Chainbook Intelligence" style="height:40px;width:auto;display:block;">
        </div>

        <h1>Welcome back</h1>
        <p class="auth-sub">Log in to continue.</p>

        @if (session('status'))
            <div class="auth-status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div style="margin-bottom:0.85rem;">
                <label class="auth-label" for="email">Email address</label>
                <input id="email" class="auth-input {{ $errors->has('email') ? 'error' : '' }}"
                    type="email" name="email" value="{{ old('email') }}"
                    required autofocus autocomplete="username" placeholder="you@company.com">
                @error('email')<p class="auth-error">{{ $message }}</p>@enderror
            </div>

            <div style="margin-bottom:0.85rem;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.22rem;">
                    <label class="auth-label" for="password" style="margin-bottom:0;">Password</label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="auth-link">Forgot?</a>
                    @endif
                </div>
                <div class="auth-password-wrap">
                    <input id="password" class="auth-input {{ $errors->has('password') ? 'error' : '' }}"
                        type="password" name="password" required
                        autocomplete="current-password" placeholder="••••••••">
                    <button type="button" class="auth-password-toggle" aria-label="Show password" onclick="
                        var input = document.getElementById('password');
                        var show = input.type === 'password';
                        input.type = show ? 'text' : 'password';
                        this.querySelector('.icon-eye').style.display = show ? 'none' : 'block';
                        this.querySelector('.icon-eye-off').style.display = show ? 'block' : 'none';
                    ">
                        <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="icon-eye-off" style="display:none;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>
                @error('password')<p class="auth-error">{{ $message }}</p>@enderror
            </div>

            <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.25rem;">
                <input id="remember_me" type="checkbox" name="remember" style="accent-color:#000;width:0.9rem;height:0.9rem;cursor:pointer;">
                <label for="remember_me" style="font-size:0.8rem;color:#555;cursor:pointer;">Keep me logged in</label>
            </div>

            <button type="submit" class="auth-btn">Log in</button>

            <p class="auth-footer">
                Don't have an account? <a href="{{ route('register') }}" class="auth-link">Create one</a>
            </p>
        </form>
    </div>
</div>
@endsection
