<?php

namespace Chainbook\Paystack;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class PaystackClient
{
    public function __construct(
        private readonly string $secretKey,
        private readonly string $baseUrl = 'https://api.paystack.co',
    ) {}

    // ── Plans ───────────────────────────────────────────

    public function createPlan(array $data): array
    {
        return $this->post('/plan', $data);
    }

    public function listPlans(): array
    {
        return $this->get('/plan');
    }

    public function fetchPlan(string $idOrCode): array
    {
        return $this->get("/plan/{$idOrCode}");
    }

    public function updatePlan(string $idOrCode, array $data): array
    {
        return $this->put("/plan/{$idOrCode}", $data);
    }

    // ── Transactions ────────────────────────────────────

    public function initializeTransaction(array $data): array
    {
        return $this->post('/transaction/initialize', $data);
    }

    public function verifyTransaction(string $reference): array
    {
        return $this->get("/transaction/verify/{$reference}");
    }

    // ── Subscriptions ───────────────────────────────────

    public function createSubscription(array $data): array
    {
        return $this->post('/subscription', $data);
    }

    public function fetchSubscription(string $idOrCode): array
    {
        return $this->get("/subscription/{$idOrCode}");
    }

    public function enableSubscription(array $data): array
    {
        return $this->post('/subscription/enable', $data);
    }

    public function disableSubscription(array $data): array
    {
        return $this->post('/subscription/disable', $data);
    }

    public function manageSubscriptionLink(string $subscriptionCode): array
    {
        return $this->get("/subscription/{$subscriptionCode}/manage/link");
    }

    // ── Customers ───────────────────────────────────────

    public function createCustomer(array $data): array
    {
        return $this->post('/customer', $data);
    }

    public function fetchCustomer(string $emailOrCode): array
    {
        return $this->get("/customer/{$emailOrCode}");
    }

    // ── Webhook verification ────────────────────────────

    public static function verifyWebhookSignature(string $payload, string $signature, string $secret): bool
    {
        $computed = hash_hmac('sha512', $payload, $secret);

        return hash_equals($computed, $signature);
    }

    // ── HTTP transport ──────────────────────────────────

    private function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->secretKey)
            ->acceptJson()
            ->contentType('application/json')
            ->timeout(30);
    }

    private function get(string $path): array
    {
        return $this->handleResponse($this->request()->get($path));
    }

    private function post(string $path, array $data): array
    {
        return $this->handleResponse($this->request()->post($path, $data));
    }

    private function put(string $path, array $data): array
    {
        return $this->handleResponse($this->request()->put($path, $data));
    }

    private function handleResponse(Response $response): array
    {
        $response->throw();

        return $response->json();
    }
}
