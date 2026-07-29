<?php

namespace App\Ai\Concerns;

use Laravel\Ai\Enums\Lab;

trait HasProviderFallback
{
    /**
     * Return an ordered provider chain: Gemini first, then any other
     * providers whose API keys are configured in the database.
     */
    public function provider(): Lab|array
    {
        $providers = [Lab::Gemini];

        if (config('ai.providers.openai.key')) {
            $providers[] = Lab::OpenAI;
        }

        if (config('ai.providers.anthropic.key')) {
            $providers[] = Lab::Anthropic;
        }

        return count($providers) === 1 ? $providers[0] : $providers;
    }
}
