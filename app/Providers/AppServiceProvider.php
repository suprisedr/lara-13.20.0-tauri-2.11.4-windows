<?php

namespace App\Providers;

use App\Models\Asset;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Observers\AssetObserver;
use App\Observers\ChartOfAccountObserver;
use App\Observers\CompanyObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\RoadRunnerEmbeddingDispatcher::class);
        $this->app->singleton(\App\Services\RoadRunnerAssetPostingDispatcher::class);
        $this->app->singleton(\App\Services\RoadRunnerIntangiblePostingDispatcher::class);
        $this->app->singleton(\App\Services\RoadRunnerInventoryPostingDispatcher::class);
        $this->app->singleton(\App\Services\RoadRunnerLeasePostingDispatcher::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Listeners in App\Listeners are auto-discovered by their handle()
        // method's event type-hint, so no manual Event::listen() is needed.

        Company::observe(CompanyObserver::class);
        Asset::observe(AssetObserver::class);
        ChartOfAccount::observe(ChartOfAccountObserver::class);

        $this->overrideAiKeysFromDatabase();
    }

    private function overrideAiKeysFromDatabase(): void
    {
        if (! $this->app->bound('db') || $this->app->runningInConsole() && ! $this->app->runningUnitTests()) {
            try {
                \Illuminate\Support\Facades\Schema::hasTable('app_settings');
            } catch (\Throwable) {
                return;
            }
        }

        try {
            $keyMap = [
                'gemini_api_key'    => 'ai.providers.gemini.key',
                'openai_api_key'    => 'ai.providers.openai.key',
                'anthropic_api_key' => 'ai.providers.anthropic.key',
                'meilisearch_key'   => 'scout.meilisearch.key',
            ];

            $settings = \App\Models\AppSetting::whereIn('key', array_keys($keyMap))->pluck('value', 'key');

            foreach ($settings as $settingKey => $value) {
                if ($value !== null && $value !== '' && isset($keyMap[$settingKey])) {
                    config()->set($keyMap[$settingKey], $value);
                }
            }
        } catch (\Throwable) {
            // Table may not exist yet (pre-migration)
        }
    }
}
