<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Chainbook\Paystack\PaystackClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaystackWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $secret = config('paystack.webhook_secret') ?? config('paystack.secret_key');
        $signature = $request->header('X-Paystack-Signature', '');
        $payload = $request->getContent();

        if (! PaystackClient::verifyWebhookSignature($payload, $signature, $secret)) {
            Log::warning('Paystack webhook signature verification failed.');

            return response('Invalid signature', 401);
        }

        $event = $request->input('event');
        $data = $request->input('data');

        match ($event) {
            'subscription.create' => $this->handleSubscriptionCreate($data),
            'subscription.not_renew' => $this->handleSubscriptionNotRenew($data),
            'subscription.disable' => $this->handleSubscriptionDisable($data),
            'charge.success' => $this->handleChargeSuccess($data),
            'invoice.payment_failed' => $this->handlePaymentFailed($data),
            default => Log::info("Unhandled Paystack event: {$event}"),
        };

        return response('OK', 200);
    }

    private function handleSubscriptionCreate(array $data): void
    {
        $sub = $this->findSubscription($data['subscription_code'] ?? null);
        if (! $sub) return;

        $sub->update([
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => isset($data['next_payment_date']) ? \Carbon\Carbon::parse($data['next_payment_date']) : now()->addMonths($sub->plan->interval_months),
        ]);
    }

    private function handleSubscriptionNotRenew(array $data): void
    {
        $sub = $this->findSubscription($data['subscription_code'] ?? null);
        if (! $sub) return;

        $sub->update([
            'cancelled_at' => now(),
        ]);
    }

    private function handleSubscriptionDisable(array $data): void
    {
        $sub = $this->findSubscription($data['subscription_code'] ?? null);
        if (! $sub) return;

        $sub->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    private function handleChargeSuccess(array $data): void
    {
        if (empty($data['plan_object'])) return;

        $subscriptionCode = $data['plan_object']['subscriptions'][0]['subscription_code'] ?? null;
        $sub = $this->findSubscription($subscriptionCode);
        if (! $sub) return;

        $sub->update([
            'status' => 'active',
            'current_period_end' => now()->addMonths($sub->plan->interval_months),
        ]);

        $sub->payments()->updateOrCreate(
            ['paystack_reference' => $data['reference']],
            [
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'status' => 'success',
                'paid_at' => now(),
                'paystack_data' => $data,
            ]
        );
    }

    private function handlePaymentFailed(array $data): void
    {
        $subscriptionCode = $data['subscription']['subscription_code'] ?? null;
        $sub = $this->findSubscription($subscriptionCode);
        if (! $sub) return;

        $sub->update(['status' => 'past_due']);
    }

    private function findSubscription(?string $code): ?Subscription
    {
        if (! $code) return null;

        return Subscription::where('paystack_subscription_code', $code)->first();
    }
}
