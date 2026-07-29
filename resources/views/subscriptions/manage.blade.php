@extends('layouts.public')

@section('title', 'Manage Subscription')
@section('meta-robots', 'noindex, nofollow')

@push('styles')
    <style>
        .mgmt-wrap { min-height:100vh; background:#f7f5ff; }

        .mgmt-hero {
            background:linear-gradient(135deg,#4c1d95 0%,#7c3aed 50%,#6d28d9 100%);
            padding:20pt 24pt 24pt;
            position:relative;
        }
        .mgmt-hero-inner { max-width:500pt; margin:0 auto; }
        .mgmt-hero h1 { font-size:12pt; font-weight:800; color:#fff; margin:0 0 2pt; }
        .mgmt-hero p { font-size:6.5pt; color:#c4b5fd; margin:0; }

        .mgmt-card {
            max-width:500pt;
            margin:-10pt auto 0;
            padding:12pt 14pt;
            background:#fff;
            border:0.5pt solid #c4b5fd;
            position:relative;
        }

        .mgmt-section-title {
            font-size:5.5pt;
            font-weight:700;
            text-transform:uppercase;
            letter-spacing:0.07em;
            color:#4c1d95;
            border-bottom:0.5pt solid #c4b5fd;
            padding-bottom:3pt;
            margin:10pt 0 5pt;
        }
        .mgmt-section-title:first-child { margin-top:0; }

        .mgmt-row {
            display:flex;
            justify-content:space-between;
            padding:2pt 0;
            border-bottom:0.4pt solid #ddd6fe;
            font-size:7pt;
        }
        .mgmt-row:last-child { border-bottom:none; }
        .mgmt-row .label { color:#6b5b8a; }
        .mgmt-row .value { font-weight:700; color:#23282d; }

        .status-active { color:#15803d; }
        .status-cancelled { color:#dc2626; }
        .status-past-due { color:#854d0e; }

        .mgmt-back {
            display:block;
            max-width:500pt;
            margin:0 auto;
            padding:8pt 0 0;
            font-size:6.5pt;
            color:#c4b5fd;
            text-decoration:none;
        }

        .payment-table {
            width:100%;
            border-collapse:collapse;
            font-size:6.5pt;
        }
        .payment-table th {
            font-size:5.5pt;
            font-weight:700;
            text-transform:uppercase;
            letter-spacing:0.07em;
            color:#6b5b8a;
            text-align:left;
            padding:3pt 4pt;
            border-bottom:0.5pt solid #c4b5fd;
            background:#f5f3ff;
        }
        .payment-table td { padding:3pt 4pt; border-bottom:0.4pt solid #ddd6fe; }
        .payment-table .amt { text-align:right; }

        .cancel-btn {
            display:inline-block;
            padding:3pt 10pt;
            font-size:6pt;
            font-weight:700;
            color:#dc2626;
            border:0.5pt solid #dc2626;
            background:#fff;
            cursor:pointer;
            font-family:inherit;
            margin-top:6pt;
        }
        .cancel-btn:hover { background:#fee2e2; }

        .flash-msg { max-width:500pt; margin:8pt auto; padding:4pt 8pt; font-size:7pt; font-weight:600; }
        .flash-msg.success { background:#dcfce7; border:1px solid #bbf7d0; color:#15803d; }
        .flash-msg.error { background:#fee2e2; border:1px solid #fca5a5; color:#b91c1c; }
    </style>
@endpush

@section('content')
    <div class="mgmt-wrap">
        <div class="mgmt-hero">
            <a href="{{ route('subscriptions.plans') }}" class="mgmt-back" style="padding:0 0 6pt;">&larr; Back to Plans</a>
            <div class="mgmt-hero-inner">
                <h1>Manage Subscription</h1>
                <p>{{ auth()->user()->name }} &middot; {{ auth()->user()->email }}</p>
            </div>
        </div>

        @if (session('success'))
            <div class="flash-msg success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="flash-msg error">{{ session('error') }}</div>
        @endif

        <div class="mgmt-card">
            <div class="mgmt-section-title">Current Plan</div>
            <div class="mgmt-row">
                <span class="label">Plan</span>
                <span class="value">{{ $subscription->plan->name }}</span>
            </div>
            <div class="mgmt-row">
                <span class="label">Price</span>
                <span class="value">{{ $subscription->plan->formattedPrice() }} / {{ $subscription->plan->interval_months === 1 ? 'month' : ($subscription->plan->interval_months === 6 ? '6 months' : 'year') }}</span>
            </div>
            <div class="mgmt-row">
                <span class="label">Status</span>
                <span class="value {{ $subscription->isActive() ? 'status-active' : ($subscription->status === 'past_due' ? 'status-past-due' : 'status-cancelled') }}">
                    {{ ucfirst(str_replace('_', ' ', $subscription->status)) }}
                    @if ($subscription->isOnGracePeriod())
                        &mdash; {{ $subscription->daysRemaining() }} {{ Str::plural('day', $subscription->daysRemaining()) }} left (access until {{ $subscription->current_period_end->format('d M Y') }})
                    @endif
                </span>
            </div>
            @if ($subscription->current_period_start)
                <div class="mgmt-row">
                    <span class="label">Current Period</span>
                    <span class="value">{{ $subscription->current_period_start->format('d M Y') }} — {{ $subscription->current_period_end?->format('d M Y') ?? 'Ongoing' }}</span>
                </div>
            @endif

            @if ($payments->isNotEmpty())
                <div class="mgmt-section-title">Payment History</div>
                <table class="payment-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th class="amt">Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr>
                                <td>{{ $payment->paid_at?->format('d M Y') ?? '---' }}</td>
                                <td style="font-family:'DejaVu Sans Mono',monospace;font-size:5.5pt;color:#8b7aad;">{{ $payment->paystack_reference }}</td>
                                <td class="amt">R {{ number_format($payment->amount / 100, 2) }}</td>
                                <td>
                                    @if ($payment->status === 'success')
                                        <span style="color:#15803d;font-weight:700;">Paid</span>
                                    @else
                                        <span style="color:#dc2626;">{{ ucfirst($payment->status) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if ($subscription->isOnGracePeriod())
                <div class="mgmt-section-title">Access Ending</div>
                <p style="font-size:6.5pt;color:#8b7aad;margin:0 0 6pt;">
                    Your subscription is cancelled. You have <strong style="color:#b45309;">{{ $subscription->daysRemaining() }} {{ Str::plural('day', $subscription->daysRemaining()) }}</strong> of access left, until {{ $subscription->current_period_end->format('d M Y') }}. Choose a plan to keep using Chainbook after that.
                </p>
                <a href="{{ route('subscriptions.plans') }}" class="cancel-btn" style="color:#4c1d95;border-color:#4c1d95;">Choose a Plan</a>
            @elseif ($subscription->isActive())
                <div class="mgmt-section-title">Cancel Subscription</div>
                <p style="font-size:6.5pt;color:#8b7aad;margin:0 0 4pt;">
                    You will retain access until the end of your current billing period.
                </p>
                <form method="POST" action="{{ route('subscriptions.cancel') }}"
                    onsubmit="return confirm('Are you sure you want to cancel your subscription?')">
                    @csrf
                    <button type="submit" class="cancel-btn">Cancel Subscription</button>
                </form>
            @endif
        </div>
    </div>
@endsection
