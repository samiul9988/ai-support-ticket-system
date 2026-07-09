<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class AIException extends Exception
{
    public function __construct(
        string $message = 'AI service error',
        int $code = 500,
        ?Throwable $previous = null,
        protected array $context = [],
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function context(): array
    {
        return $this->context;
    }

    public static function configurationError(string $detail = ''): self
    {
        return new self(
            $detail ?: 'AI service is not properly configured. Set GEMINI_API_KEY in your .env file.',
            500,
            null,
            ['error_type' => 'configuration']
        );
    }

    public static function serviceUnavailable(string $detail = '', array $context = []): self
    {
        return new self(
            $detail ?: 'AI service is currently unavailable.',
            503,
            null,
            array_merge(['error_type' => 'service_unavailable'], $context)
        );
    }

    public static function timeout(float $timeoutSeconds, array $context = []): self
    {
        return new self(
            "AI request timed out after {$timeoutSeconds} seconds.",
            504,
            null,
            array_merge(['error_type' => 'timeout', 'timeout_seconds' => $timeoutSeconds], $context)
        );
    }

    public static function rateLimitExceeded(string $detail = '', array $context = []): self
    {
        return new self(
            $detail ?: 'AI API rate limit exceeded. Please try again later.',
            429,
            null,
            array_merge(['error_type' => 'rate_limit'], $context)
        );
    }

    public static function invalidResponse(string $detail = '', array $context = []): self
    {
        return new self(
            $detail ?: 'AI returned an invalid or unexpected response.',
            502,
            null,
            array_merge(['error_type' => 'invalid_response'], $context)
        );
    }

    public static function contentFiltered(string $detail = '', array $context = []): self
    {
        return new self(
            $detail ?: 'AI response was blocked by content safety filters.',
            422,
            null,
            array_merge(['error_type' => 'content_filtered'], $context)
        );
    }

    public static function circuitOpen(int $cooldownSeconds): self
    {
        return new self(
            "AI circuit breaker is open. Cooldown: {$cooldownSeconds}s.",
            503,
            null,
            ['error_type' => 'circuit_open', 'cooldown_seconds' => $cooldownSeconds]
        );
    }

    public static function analysisFailed(string $detail = '', array $context = []): self
    {
        return new self(
            $detail ?: 'AI ticket analysis failed.',
            500,
            null,
            array_merge(['error_type' => 'analysis_failed'], $context)
        );
    }
}
