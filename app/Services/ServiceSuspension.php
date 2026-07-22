<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Generic "suspend a service until timestamp X" mechanism, keyed by service name.
 *
 * Used alongside the circuit breaker: when a single failure already tells us
 * how long to back off (Gemini 429 retryDelay, or a hard timeout we want to
 * absorb), we stamp a suspension here so every other queued job for the same
 * service yields immediately — no need to wait for the breaker's failure
 * threshold to accumulate.
 */
class ServiceSuspension
{
    public static function suspendFor(string $service, int $seconds): void
    {
        $until = now()->addSeconds(max($seconds, 1));
        Cache::put(self::key($service), $until->timestamp, $until->addSeconds(30));
    }

    public static function secondsRemaining(string $service): int
    {
        $until = (int) Cache::get(self::key($service), 0);
        if ($until === 0) {
            return 0;
        }
        $remaining = $until - CarbonImmutable::now()->timestamp;
        if ($remaining <= 0) {
            Cache::forget(self::key($service));
            return 0;
        }
        return $remaining;
    }

    public static function clear(string $service): void
    {
        Cache::forget(self::key($service));
    }

    private static function key(string $service): string
    {
        return "service-suspension:{$service}";
    }
}
