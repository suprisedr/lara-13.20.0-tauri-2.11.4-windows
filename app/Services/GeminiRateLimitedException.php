<?php

namespace App\Services;

use RuntimeException;

class GeminiRateLimitedException extends RuntimeException
{
    public function __construct(
        public readonly int $retryAfterSeconds,
        string $message = '',
    ) {
        parent::__construct($message ?: "Gemini rate limited; retry after {$retryAfterSeconds}s.", 429);
    }
}
