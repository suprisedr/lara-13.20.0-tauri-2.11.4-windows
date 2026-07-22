<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Tracks a hard "suspend until" timestamp for the Gemini embedding service.
 *
 * Whenever Gemini returns a 429 with a RetryInfo.retryDelay, the offending job
 * writes the absolute resume time here. The queue middleware then bounces every
 * other embedding job until that moment — so one 429 cleanly suspends the entire
 * pipeline, instead of waiting for 5 failures to accumulate in the breaker.
 */
class EmbeddingSuspension
{
    public const CACHE_KEY = 'gemini-embedding:suspended_until';

    public static function suspendFor(int $seconds): void
    {
        $until = now()->addSeconds(max($seconds, 1));
        Cache::put(self::CACHE_KEY, $until->timestamp, $until->addSeconds(30));
    }

    /** Seconds remaining until embedding work may resume, or 0 if not suspended. */
    public static function secondsRemaining(): int
    {
        $until = (int) Cache::get(self::CACHE_KEY, 0);
        if ($until === 0) {
            return 0;
        }
        $remaining = $until - CarbonImmutable::now()->timestamp;
        if ($remaining <= 0) {
            Cache::forget(self::CACHE_KEY);
            return 0;
        }
        return $remaining;
    }

    public static function clear(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
