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

    public function errorCode(): string
    {
        return match ($this->context['error_type'] ?? 'unknown') {
            'rate_limit' => ErrorCode::AI_RATE_LIMIT,
            'timeout' => ErrorCode::AI_TIMEOUT,
            'service_unavailable' => ErrorCode::AI_UNAVAILABLE,
            'invalid_response' => ErrorCode::AI_INVALID_RESPONSE,
            'configuration' => ErrorCode::AI_CONFIGURATION,
            'content_filtered' => ErrorCode::AI_CONTENT_FILTERED,
            'circuit_open' => ErrorCode::AI_CIRCUIT_OPEN,
            'analysis_failed' => ErrorCode::AI_ANALYSIS_FAILED,
            default => ErrorCode::AI_UNAVAILABLE,
        };
    }

    public function userMessage(): string
    {
        return match ($this->context['error_type'] ?? 'unknown') {
            'rate_limit' => 'The AI service is experiencing high demand. Please try again in a moment.',
            'timeout' => 'The AI service took too long to respond. Our team has been notified.',
            'service_unavailable' => 'The AI service is temporarily unavailable. Please try again later.',
            'invalid_response' => 'The AI returned an unexpected response. Our team is investigating.',
            'configuration' => 'The AI service is not configured correctly. Please contact your administrator.',
            'content_filtered' => 'The request was blocked by content safety filters.',
            'circuit_open' => 'AI service is temporarily paused due to repeated errors. It will resume shortly.',
            'analysis_failed' => 'AI ticket analysis could not be completed.',
            default => 'The AI service encountered an error. Please try again.',
        };
    }

    public static function configurationError(string $detail = ''): self
    {
        return new self(
            $detail ?: 'AI service is not properly configured. Set GEMINI_API_KEY.',
            500, null,
            ['error_type' => 'configuration']
        );
    }

    public static function serviceUnavailable(string $detail = '', array $context = []): self
    {
        return new self(
            $detail ?: 'AI service is currently unavailable.',
            503, null,
            array_merge(['error_type' => 'service_unavailable'], $context)
        );
    }

    public static function timeout(float $timeoutSeconds, array $context = []): self
    {
        return new self(
            "AI request timed out after {$timeoutSeconds} seconds.",
            504, null,
            array_merge(['error_type' => 'timeout', 'timeout_seconds' => $timeoutSeconds], $context)
        );
    }

    public static function rateLimitExceeded(string $detail = '', array $context = []): self
    {
        return new self(
            $detail ?: 'AI API rate limit exceeded.',
            429, null,
            array_merge(['error_type' => 'rate_limit'], $context)
        );
    }

    public static function invalidResponse(string $detail = '', array $context = []): self
    {
        return new self(
            $detail ?: 'AI returned an invalid response.',
            502, null,
            array_merge(['error_type' => 'invalid_response'], $context)
        );
    }

    public static function contentFiltered(string $detail = '', array $context = []): self
    {
        return new self(
            $detail ?: 'AI response blocked by content safety filters.',
            422, null,
            array_merge(['error_type' => 'content_filtered'], $context)
        );
    }

    public static function circuitOpen(int $cooldownSeconds): self
    {
        return new self(
            "AI circuit breaker is open. Cooldown: {$cooldownSeconds}s.",
            503, null,
            ['error_type' => 'circuit_open', 'cooldown_seconds' => $cooldownSeconds]
        );
    }

    public static function analysisFailed(string $detail = '', array $context = []): self
    {
        return new self(
            $detail ?: 'AI ticket analysis failed.',
            500, null,
            array_merge(['error_type' => 'analysis_failed'], $context)
        );
    }
}
