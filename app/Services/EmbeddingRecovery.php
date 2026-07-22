<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Tracks a "recovery pivot" — the single embedding job currently nominated to
 * prove the upstream service is healthy again.
 *
 * Lifecycle:
 *   1. Any embed job that throws records itself as the pivot.
 *   2. The middleware then bounces every other embed job; only the pivot
 *      flows through to retry against Gemini.
 *   3. When the pivot eventually succeeds, it clears the pivot — and the
 *      entire backlog resumes flowing.
 *   4. If the pivot exhausts its retries and ends in `failed_jobs`, it clears
 *      the pivot so the next failing job can take over the role (otherwise
 *      the queue would be wedged forever).
 *
 * Kinds:
 *   - "tx"  → App\Jobs\EmbedTransactionJob, id = $transactionId
 *   - "inv" → App\Jobs\EmbedInvoiceJob,     id = $invoiceId
 */
class EmbeddingRecovery
{
    private const KEY = 'embedding:recovery_pivot';

    public static function set(string $kind, int $id): void
    {
        Cache::put(self::KEY, "{$kind}:{$id}", now()->addHours(6));
    }

    /** @return array{kind:string,id:int}|null */
    public static function current(): ?array
    {
        $raw = (string) Cache::get(self::KEY, '');
        if ($raw === '' || ! str_contains($raw, ':')) {
            return null;
        }
        [$kind, $id] = explode(':', $raw, 2);
        return ['kind' => $kind, 'id' => (int) $id];
    }

    public static function isSuspended(): bool
    {
        return self::current() !== null;
    }

    public static function isPivot(string $kind, int $id): bool
    {
        $r = self::current();
        return $r !== null && $r['kind'] === $kind && $r['id'] === $id;
    }

    public static function clear(): void
    {
        Cache::forget(self::KEY);
    }
}
