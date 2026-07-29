<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Chainbook\Paystack\PaystackClient;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
    public function plans()
    {
        $plans = SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get();
        $activeSubscription = auth()->user()->activeSubscription();

        return view('subscriptions.plans', compact('plans', 'activeSubscription'));
    }

    public function subscribe(Request $request, SubscriptionPlan $plan, PaystackClient $paystack)
    {
        $user = $request->user();

        if ($user->subscribed()) {
            return back()->with('error', 'You already have an active subscription.');
        }

        $reference = 'sub_' . Str::random(24);

        $payload = [
            'email' => $user->email,
            'amount' => $plan->price,
            'currency' => $plan->currency,
            'reference' => $reference,
            'callback_url' => route('subscriptions.callback'),
            'metadata' => [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ],
        ];

        if ($plan->paystack_plan_code) {
            $payload['plan'] = $plan->paystack_plan_code;
        }

        $response = $paystack->initializeTransaction($payload);

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'pending',
        ]);

        session([
            'pending_subscription_id' => $subscription->id,
            'paystack_reference' => $reference,
        ]);

        return redirect($response['data']['authorization_url']);
    }

    public function callback(Request $request, PaystackClient $paystack)
    {
        $reference = $request->query('reference', session('paystack_reference'));

        if (! $reference) {
            return redirect()->route('subscriptions.plans')->with('error', 'Invalid payment reference.');
        }

        $verification = $paystack->verifyTransaction($reference);
        $data = $verification['data'];

        $subscriptionId = session('pending_subscription_id');

        if ($data['status'] !== 'success') {
            if ($subscriptionId) {
                Subscription::where('id', $subscriptionId)->update(['status' => 'failed']);
            }

            return redirect()->route('subscriptions.plans')->with('error', 'Payment was not successful.');
        }

        $subscription = Subscription::findOrFail($subscriptionId);
        $plan = $subscription->plan;

        $subscription->update([
            'status' => 'active',
            'paystack_subscription_code' => $data['plan_object']['subscriptions'][0]['subscription_code'] ?? null,
            'paystack_customer_code' => $data['customer']['customer_code'] ?? null,
            'paystack_email_token' => $data['plan_object']['subscriptions'][0]['email_token'] ?? null,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonths($plan->interval_months),
        ]);

        $subscription->payments()->create([
            'paystack_reference' => $reference,
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'status' => 'success',
            'paid_at' => now(),
            'paystack_data' => $data,
        ]);

        session()->forget(['pending_subscription_id', 'paystack_reference']);

        return redirect()->route('dashboard')->with('success', 'Subscription activated — welcome to Chainbook!');
    }

    public function manage()
    {
        $user = auth()->user();
        $subscription = $user->activeSubscription();

        if (! $subscription) {
            return redirect()->route('subscriptions.plans');
        }

        $payments = $subscription->payments()->latest()->limit(12)->get();

        return view('subscriptions.manage', compact('subscription', 'payments'));
    }

    public function cancel(Request $request, PaystackClient $paystack)
    {
        $subscription = $request->user()->activeSubscription();

        if (! $subscription) {
            return back()->with('error', 'No active subscription to cancel.');
        }

        if ($subscription->paystack_subscription_code && $subscription->paystack_email_token) {
            $paystack->disableSubscription([
                'code' => $subscription->paystack_subscription_code,
                'token' => $subscription->paystack_email_token,
            ]);
        }

        $subscription->update([
            'cancelled_at' => now(),
        ]);

        return back()->with('success', 'Subscription cancelled. You retain access until ' . $subscription->current_period_end?->format('d M Y') . '.');
    }
}
