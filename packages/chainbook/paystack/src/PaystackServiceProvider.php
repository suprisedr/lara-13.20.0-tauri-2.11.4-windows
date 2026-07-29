<?php

namespace Chainbook\Paystack;

use Illuminate\Support\ServiceProvider;

class PaystackServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/paystack.php', 'paystack');

        $this->app->singleton(PaystackClient::class, function ($app) {
            $config = $app['config']['paystack'];

            throw_unless($config['secret_key'], \RuntimeException::class, 'PAYSTACK_SECRET_KEY is not set.');

            return new PaystackClient(
                secretKey: $config['secret_key'],
                baseUrl: $config['base_url'],
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/paystack.php' => config_path('paystack.php'),
        ], 'paystack-config');
    }
}
