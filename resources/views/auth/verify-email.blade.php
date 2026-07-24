@extends('layouts.public')

@section('title', 'Verify Email')
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
    .auth-brand { display:flex; align-items:center; gap:0.5rem; margin-bottom:2rem; }
    .auth-brand-mark { width:32px; height:32px; background:#5e17eb; display:flex; align-items:center; justify-content:center; }
    .auth-brand-mark svg { color:#fff; }
    .auth-brand-name { font-size:1rem; font-weight:800; color:#0a0a0a; letter-spacing:-0.02em; }
    .auth-card h1 { font-size:1.35rem; font-weight:800; color:#0a0a0a; margin:0 0 0.2rem; letter-spacing:-0.015em; }
    .auth-card .auth-sub { font-size:0.82rem; color:#6b7280; margin:0 0 1.5rem; line-height:1.5; }
    .auth-btn { display:flex; align-items:center; justify-content:center; width:100%; padding:0.6rem 1.25rem; background:#000; color:#fff; font-size:0.85rem; font-weight:700; font-family:inherit; border:none; cursor:pointer; transition:background 0.15s; }
    .auth-btn:hover { background:#333; }
    .auth-status { padding:0.55rem 0.75rem; background:#dcfce7; border:1px solid #bbf7d0; font-size:0.78rem; color:#15803d; margin-bottom:1rem; }
</style>
@endpush

@section('content')
<div class="auth-page">
    <div class="auth-card">

        <div class="auth-brand">
            <img src="{{ asset('storage/images/chainbook-intelligence-logo.png') }}" alt="Chainbook Intelligence" style="height:40px;width:auto;display:block;">
        </div>

        <h1>Verify your email</h1>
        <p class="auth-sub">Before getting started, please verify your email address by clicking the link we just sent you.</p>

        @if (session('status') == 'verification-link-sent')
            <div class="auth-status">A new verification link has been sent to your email address.</div>
        @endif

        <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;">
            <form method="POST" action="{{ route('verification.send') }}" style="flex:1;">
                @csrf
                <button type="submit" class="auth-btn">Resend verification email</button>
            </form>

            <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                @csrf
                <button type="submit" style="background:none;border:none;color:#6b7280;font-size:0.78rem;font-weight:600;cursor:pointer;font-family:inherit;text-decoration:underline;">
                    Log out
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
