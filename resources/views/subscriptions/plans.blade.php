@extends('layouts.public')

@section('title', 'Pricing — Chainbook')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    <style>
        .plans-wrap { min-height:100vh; background:#f7fbfd; }

        .plans-hero {
            background:linear-gradient(135deg,#1a345b 0%,#005bf0 50%,#005bf0 100%);
            padding:28pt 24pt 36pt;
            position:relative;
            overflow:hidden;
        }
        .plans-hero::before {
            content:''; position:absolute; inset:0;
            background:repeating-linear-gradient(-45deg,transparent,transparent 40px,rgba(255,255,255,0.025) 40px,rgba(255,255,255,0.025) 41px);
            pointer-events:none;
        }
        .plans-hero-inner { position:relative; max-width:560pt; margin:0 auto; text-align:center; }
        .plans-hero h1 { font-size:16pt; font-weight:800; color:#fff; margin:0 0 3pt; letter-spacing:-0.02em; }
        .plans-hero p { font-size:7pt; color:#9ec1f5; margin:0; }

        .plans-grid {
            display:grid;
            grid-template-columns:repeat(3,1fr);
            gap:8pt;
            max-width:560pt;
            margin:-20pt auto 0;
            padding:0 16pt 24pt;
            position:relative;
        }

        .plan-card {
            background:#fff;
            border:0.5pt solid #9ec1f5;
            padding:14pt 12pt;
            display:flex;
            flex-direction:column;
            position:relative;
        }
        .plan-card.best { border-color:#1a345b; border-width:1.5pt; }
        .plan-card.current { border-color:#15803d; }

        .plan-badge {
            position:absolute;
            top:-5pt;
            right:8pt;
            font-size:4.5pt;
            font-weight:700;
            text-transform:uppercase;
            letter-spacing:0.08em;
            padding:1.5pt 5pt;
        }
        .plan-badge.best-value { background:#1a345b; color:#fff; }
        .plan-badge.active-badge { background:#dcfce7; color:#15803d; border:0.5pt solid #bbf7d0; }

        .plan-interval { font-size:5.5pt; font-weight:700; text-transform:uppercase; letter-spacing:0.07em; color:#5a7186; margin:0 0 4pt; }

        .plan-price { font-size:14pt; font-weight:800; color:#1a345b; margin:0; line-height:1.1; }
        .plan-price small { font-size:6pt; font-weight:400; color:#6f869b; }

        .plan-effective { font-size:6pt; color:#6f869b; margin:2pt 0 0; }

        .plan-saving {
            display:inline-block;
            font-size:5pt;
            font-weight:700;
            color:#15803d;
            background:#dcfce7;
            padding:1pt 4pt;
            margin:4pt 0;
        }

        .plan-divider { border:none; border-top:0.4pt solid #d3e2f5; margin:8pt 0; }

        .plan-features { list-style:none; padding:0; margin:0 0 10pt; flex:1; }
        .plan-features li {
            font-size:6pt;
            color:#5a7186;
            padding:2pt 0;
            display:flex;
            align-items:center;
            gap:3pt;
        }
        .plan-features .check { color:#15803d; font-weight:700; }

        .plan-btn {
            display:block;
            width:100%;
            padding:5pt 0;
            font-size:6.5pt;
            font-weight:700;
            text-align:center;
            text-decoration:none;
            border:0.5pt solid #1a345b;
            background:#1a345b;
            color:#fff;
            cursor:pointer;
            font-family:inherit;
            transition:all 0.15s;
            box-sizing:border-box;
        }
        .plan-btn:hover { background:#005bf0; border-color:#005bf0; }
        .plan-btn.outline { background:#fff; color:#1a345b; }
        .plan-btn.outline:hover { background:#f4fafc; }
        .plan-btn.active-btn { background:#dcfce7; color:#15803d; border-color:#15803d; cursor:default; }

        .plans-back {
            display:block;
            max-width:560pt;
            margin:0 auto;
            padding:10pt 16pt 0;
            font-size:6.5pt;
            color:#9ec1f5;
            text-decoration:none;
        }
        .plans-back:hover { color:#fff; }

        .flash-msg { max-width:560pt; margin:8pt auto; padding:4pt 8pt; font-size:7pt; font-weight:600; }
        .flash-msg.success { background:#dcfce7; border:1px solid #bbf7d0; color:#15803d; }
        .flash-msg.error { background:#fee2e2; border:1px solid #fca5a5; color:#b91c1c; }

        .plans-footer { max-width:560pt; margin:0 auto; padding:0 16pt 20pt; text-align:center; font-size:6pt; color:#6f869b; }
        .plans-footer a { color:#1a345b; text-decoration:none; font-weight:600; }

        @media (max-width: 500px) { .plans-grid { grid-template-columns:1fr; } }
    </style>
@endpush

@section('content')
    <div class="plans-wrap">
        <div class="plans-hero">
            <a href="{{ route('dashboard') }}" class="plans-back">&larr; Back to Dashboard</a>
            <div class="plans-hero-inner">
                <h1>Simple Pricing</h1>
                <p>Full access to every feature. Choose how you pay.</p>
            </div>
        </div>

        @if (session('success'))
            <div class="flash-msg success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="flash-msg error">{{ session('error') }}</div>
        @endif

        <div class="plans-grid">
            @foreach ($plans as $plan)
                @php
                    $isCurrent = $activeSubscription && $activeSubscription->subscription_plan_id === $plan->id;
                    $isBest = $plan->slug === 'annual';
                @endphp
                <div class="plan-card {{ $isBest ? 'best' : '' }} {{ $isCurrent ? 'current' : '' }}">
                    @if ($isCurrent)
                        <span class="plan-badge active-badge">Current</span>
                    @elseif ($isBest)
                        <span class="plan-badge best-value">Best Value</span>
                    @endif

                    <p class="plan-interval">{{ $plan->name }}</p>

                    <p class="plan-price">
                        {{ $plan->formattedMonthlyEquivalent() }}
                        <small>/ month{{ $plan->interval_months > 1 ? '*' : '' }}</small>
                    </p>

                    @if ($plan->interval_months > 1)
                        <p class="plan-effective">
                            Billed {{ $plan->formattedPrice() }} every {{ $plan->interval_months }} months
                        </p>
                    @else
                        <p class="plan-effective">Billed monthly</p>
                    @endif

                    @if ($plan->discount_percent > 0)
                        <span class="plan-saving">Save {{ $plan->discount_percent }}%</span>
                    @else
                        <div style="height:13pt;"></div>
                    @endif

                    <hr class="plan-divider">

                    <ul class="plan-features">
                        <li><span class="check">&#10003;</span> Full access to all features</li>
                        <li><span class="check">&#10003;</span> Invoicing, quotations &amp; credit notes</li>
                        <li><span class="check">&#10003;</span> Payroll with PAYE, UIF &amp; SDL</li>
                        <li><span class="check">&#10003;</span> IRP5 &amp; EMP201 generation</li>
                        <li><span class="check">&#10003;</span> Fixed asset register &amp; IFRS</li>
                        <li><span class="check">&#10003;</span> Inventory management</li>
                        <li><span class="check">&#10003;</span> AI transaction matching</li>
                        @if ($plan->interval_months >= 12)
                            <li><span class="check">&#10003;</span> <strong>Priority support</strong></li>
                        @endif
                    </ul>

                    @if ($isCurrent)
                        <span class="plan-btn active-btn">Active</span>
                    @elseif ($activeSubscription)
                        <span class="plan-btn outline" style="opacity:0.5;cursor:not-allowed;">Current plan active</span>
                    @else
                        <form method="POST" action="{{ route('subscriptions.subscribe', $plan) }}">
                            @csrf
                            <button type="submit" class="plan-btn {{ $isBest ? '' : 'outline' }}">Subscribe</button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="plans-footer">
            <p>All prices in ZAR. No VAT charged. Cancel anytime — access continues until the end of your billing period.</p>
            @if ($activeSubscription)
                <a href="{{ route('subscriptions.manage') }}">Manage Subscription &rarr;</a>
            @endif
        </div>
    </div>
@endsection
