@extends('layouts.public')

@section('title', 'Forgot Password')
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
    .auth-brand { display:flex; align-items:center; gap:0.5rem; margin-bottom:2rem; }
    .auth-brand-mark { width:32px; height:32px; background:#005bf0; display:flex; align-items:center; justify-content:center; }
    .auth-brand-mark svg { color:#fff; }
    .auth-brand-name { font-size:1rem; font-weight:800; color:#191919; letter-spacing:-0.02em; }
    .auth-card h1 { font-size:1.35rem; font-weight:800; color:#191919; margin:0 0 0.2rem; letter-spacing:-0.015em; }
    .auth-card .auth-sub { font-size:0.82rem; color:#5a7186; margin:0 0 1.5rem; line-height:1.5; }
    .auth-label { display:block; font-size:0.7rem; font-weight:700; letter-spacing:0.07em; text-transform:uppercase; color:#5a7186; margin-bottom:0.22rem; }
    .auth-input { display:block; width:100%; padding:0.45rem 0.65rem; font-size:0.85rem; font-family:inherit; color:#000; background:#fff; border:1px solid #d3e2f5; outline:none; transition:border-color 0.15s; box-sizing:border-box; }
    .auth-input:focus { border-color:#005bf0; }
    .auth-input.error { border-color:#dc2626; background:#fff5f5; }
    .auth-error { font-size:0.72rem; color:#dc2626; margin:0.2rem 0 0; }
    .auth-btn { display:flex; align-items:center; justify-content:center; width:100%; padding:0.6rem 1.25rem; background:#000; color:#fff; font-size:0.85rem; font-weight:700; font-family:inherit; border:none; cursor:pointer; transition:background 0.15s; }
    .auth-btn:hover { background:#1a345b; }
    .auth-status { padding:0.55rem 0.75rem; background:#dcfce7; border:1px solid #bbf7d0; font-size:0.78rem; color:#15803d; margin-bottom:1rem; }
    .auth-link { color:#005bf0; font-weight:700; text-decoration:none; font-size:0.78rem; }
    .auth-link:hover { text-decoration:underline; }
    .auth-footer { margin:1.25rem 0 0; font-size:0.82rem; color:#5a7186; text-align:center; }
</style>
@endpush

@section('content')
<div class="auth-page">
    <div class="auth-card">

        <div class="auth-brand">
            <img src="{{ asset('storage/images/chainbook-intelligence-logo.png') }}" alt="Chainbook Intelligence" style="height:40px;width:auto;display:block;">
        </div>

        <h1>Reset password</h1>
        <p class="auth-sub">Enter your email address and we'll send you a reset link.</p>

        @if (session('status'))
            <div class="auth-status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div style="margin-bottom:1.25rem;">
                <label class="auth-label" for="email">Email address</label>
                <input id="email" class="auth-input {{ $errors->has('email') ? 'error' : '' }}"
                    type="email" name="email" value="{{ old('email') }}"
                    required autofocus autocomplete="username" placeholder="you@company.com">
                @error('email')<p class="auth-error">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="auth-btn">Send reset link</button>

            <p class="auth-footer">
                <a href="{{ route('login') }}" class="auth-link">Back to login</a>
            </p>
        </form>
    </div>
</div>
@endsection
