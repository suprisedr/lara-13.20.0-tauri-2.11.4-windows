<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiEmbeddingService
{
    private const MODEL = 'gemini-embedding-001';
    private const ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-001:embedContent';
    private const DIMENSIONS = 768;

    public function __construct(private readonly ?string $apiKey = null) {}

    private function key(): string
    {
        $key = $this->apiKey
            ?? AppSetting::get('gemini_api_key')
            ?? config('services.gemini.api_key')
            ?? env('GEMINI_API_KEY');
        if (! $key) {
            throw new RuntimeException('GEMINI_API_KEY is not configured.');
        }
        return $key;
    }

    /**
     * Returns a 768-dimensional embedding for the given text.
     *
     * @return array<int, float>
     */
    public function embed(string $text, string $taskType = 'RETRIEVAL_DOCUMENT'): array
    {
        $response = Http::asJson()
            ->timeout(30)
            ->retry(2, 200)
            ->post(self::ENDPOINT.'?key='.$this->key(), [
                'model' => 'models/'.self::MODEL,
                'content' => ['parts' => [['text' => $text]]],
                'taskType' => $taskType,
                'outputDimensionality' => self::DIMENSIONS,
            ]);

        if ($response->status() === 429) {
            $retry = self::parseRetryDelay($response->json('error.details') ?? []) ?? 60;
            throw new GeminiRateLimitedException($retry, 'Gemini rate limited: '.$response->body());
        }

        if (! $response->successful()) {
            throw new RuntimeException('Gemini embed failed: '.$response->status().' '.$response->body());
        }

        $values = $response->json('embedding.values');
        if (! is_array($values) || count($values) !== self::DIMENSIONS) {
            throw new RuntimeException('Unexpected embedding response from Gemini.');
        }

        return array_map(fn ($v) => (float) $v, $values);
    }

    /**
     * Pull the RetryInfo.retryDelay out of a google.rpc.Status error.details array.
     * Returns seconds (ceil for sub-second values) or null if absent/unparseable.
     *
     * @param  array<int, array<string, mixed>>  $details
     */
    public static function parseRetryDelay(array $details): ?int
    {
        foreach ($details as $detail) {
            if (! is_array($detail)) {
                continue;
            }
            $type = $detail['@type'] ?? '';
            if (! str_ends_with($type, 'google.rpc.RetryInfo')) {
                continue;
            }
            $raw = (string) ($detail['retryDelay'] ?? '');
            return self::durationToSeconds($raw);
        }
        return null;
    }

    /**
     * Parse a Protobuf duration string ("23s", "1.500s", "1m30s") into whole seconds.
     */
    private static function durationToSeconds(string $duration): ?int
    {
        if ($duration === '') {
            return null;
        }
        $total = 0.0;
        if (preg_match_all('/(\d+(?:\.\d+)?)([hms])/', $duration, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $value = (float) $m[1];
                $total += match ($m[2]) {
                    'h' => $value * 3600,
                    'm' => $value * 60,
                    's' => $value,
                };
            }
        }
        return $total > 0 ? (int) ceil($total) : null;
    }
}
