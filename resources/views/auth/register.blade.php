@extends('layouts.public')

@section('title', 'Create Account')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
<style>
    .auth-page {
        min-height:100vh; display:flex; align-items:center; justify-content:center;
        background:#f7fbfd;
    }

    .auth-card {
        width:100%; max-width:400px; background:#fff; border:1px solid #d3e2f5;
        padding:2.5rem 2.25rem; margin:2rem 1rem;
    }

    .auth-brand {
        display:flex; align-items:center; gap:0.5rem; margin-bottom:2rem;
    }
    .auth-brand-mark {
        width:32px; height:32px; background:#005bf0; display:flex;
        align-items:center; justify-content:center;
    }
    .auth-brand-mark svg { color:#fff; }
    .auth-brand-name {
        font-size:1rem; font-weight:800; color:#191919; letter-spacing:-0.02em;
    }

    .auth-card h1 {
        font-size:1.35rem; font-weight:800; color:#191919;
        margin:0 0 0.2rem; letter-spacing:-0.015em;
    }
    .auth-card .auth-sub {
        font-size:0.82rem; color:#5a7186; margin:0 0 1.5rem;
    }

    .auth-label {
        display:block; font-size:0.7rem; font-weight:700;
        letter-spacing:0.07em; text-transform:uppercase;
        color:#5a7186; margin-bottom:0.22rem;
    }
    .auth-input {
        display:block; width:100%; padding:0.45rem 0.65rem;
        font-size:0.85rem; font-family:inherit; color:#000;
        background:#fff; border:1px solid #d3e2f5; outline:none;
        transition:border-color 0.15s; box-sizing:border-box;
    }
    .auth-input:focus { border-color:#005bf0; }
    .auth-input.error { border-color:#dc2626; background:#fff5f5; }
    .auth-error { font-size:0.72rem; color:#dc2626; margin:0.2rem 0 0; }

    .auth-btn {
        display:flex; align-items:center; justify-content:center; width:100%;
        padding:0.6rem 1.25rem; background:#000; color:#fff;
        font-size:0.85rem; font-weight:700; font-family:inherit;
        border:none; cursor:pointer; transition:background 0.15s;
    }
    .auth-btn:hover { background:#1a345b; }

    .auth-field-row {
        display:grid; grid-template-columns:1fr 1fr; gap:0.65rem;
    }

    .auth-link { color:#005bf0; font-weight:700; text-decoration:none; font-size:0.78rem; }
    .auth-link:hover { text-decoration:underline; }

    .auth-footer {
        margin:1.25rem 0 0; font-size:0.82rem; color:#5a7186; text-align:center;
    }
</style>
@endpush

@section('content')
<div class="auth-page">
    <div class="auth-card">

        <div class="auth-brand">
            <img src="{{ asset('storage/images/chainbook-intelligence-logo.png') }}" alt="Chainbook Intelligence" style="height:40px;width:auto;display:block;">
        </div>

        <h1>Create your account</h1>
        <p class="auth-sub">Set up in under a minute.</p>

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div style="margin-bottom:0.85rem;">
                <label class="auth-label" for="name">Full name</label>
                <input id="name" class="auth-input {{ $errors->has('name') ? 'error' : '' }}"
                    type="text" name="name" value="{{ old('name') }}"
                    required autofocus autocomplete="name" placeholder="Jane Smith">
                @error('name')<p class="auth-error">{{ $message }}</p>@enderror
            </div>

            <div style="margin-bottom:0.85rem;">
                <label class="auth-label" for="email">Email address</label>
                <input id="email" class="auth-input {{ $errors->has('email') ? 'error' : '' }}"
                    type="email" name="email" value="{{ old('email') }}"
                    required autocomplete="username" placeholder="you@company.com">
                @error('email')<p class="auth-error">{{ $message }}</p>@enderror
            </div>

            <div class="auth-field-row" style="margin-bottom:1.25rem;">
                <div>
                    <label class="auth-label" for="password">Password</label>
                    <input id="password" class="auth-input {{ $errors->has('password') ? 'error' : '' }}"
                        type="password" name="password" required
                        autocomplete="new-password" placeholder="Min. 8 characters">
                    @error('password')<p class="auth-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="auth-label" for="password_confirmation">Confirm</label>
                    <input id="password_confirmation" class="auth-input"
                        type="password" name="password_confirmation" required
                        autocomplete="new-password" placeholder="Repeat password">
                </div>
            </div>

            <button type="submit" class="auth-btn">Create account</button>

            <p class="auth-footer">
                Already have an account? <a href="{{ route('login') }}" class="auth-link">Log in</a>
            </p>
        </form>
    </div>
</div>
@endsection
