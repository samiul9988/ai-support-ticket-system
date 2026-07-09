<?php

namespace App\Services\AI;

use App\Exceptions\AIException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CircuitBreaker
{
    protected string $serviceName;

    protected int $failureThreshold;

    protected int $cooldownSeconds;

    protected bool $enabled;

    public function __construct(
        string $serviceName = 'gemini',
        ?int $failureThreshold = null,
        ?int $cooldownSeconds = null,
        ?bool $enabled = null,
    ) {
        $this->serviceName = $serviceName;
        $this->failureThreshold = $failureThreshold ?? config('gemini.providers.gemini.circuit_failure_threshold', 5);
        $this->cooldownSeconds = $cooldownSeconds ?? config('gemini.providers.gemini.circuit_cooldown_seconds', 60);
        $this->enabled = $enabled ?? config('gemini.providers.gemini.circuit_breaker_enabled', true);
    }

    public function isAvailable(): bool
    {
        if (! $this->enabled) {
            return true;
        }

        if ($this->isOpen()) {
            if ($this->canAttemptReset()) {
                $this->transitionToHalfOpen();
                Log::info('Circuit breaker: OPEN -> HALF_OPEN', ['service' => $this->serviceName]);

                return true;
            }

            Log::warning('Circuit breaker is OPEN', [
                'service' => $this->serviceName,
                'cooldown_remaining' => $this->cooldownRemaining(),
            ]);

            throw AIException::circuitOpen($this->cooldownRemaining());
        }

        return true;
    }

    public function recordSuccess(): void
    {
        if (! $this->enabled) {
            return;
        }

        if ($this->getState() === 'half_open') {
            $this->reset();
            Log::info('Circuit breaker: HALF_OPEN -> CLOSED', ['service' => $this->serviceName]);
        }

        Cache::forget($this->cacheKey('failures'));
    }

    public function recordFailure(): void
    {
        if (! $this->enabled) {
            return;
        }

        $failures = $this->getFailureCount() + 1;

        Cache::put($this->cacheKey('failures'), $failures, now()->addHours(24));

        Log::warning('Circuit breaker: Failure recorded', [
            'service' => $this->serviceName,
            'failures' => $failures,
            'threshold' => $this->failureThreshold,
        ]);

        if ($failures >= $this->failureThreshold) {
            $this->open();
        }
    }

    public function reset(): void
    {
        Cache::forget($this->cacheKey('failures'));
        Cache::forget($this->cacheKey('opened_at'));
        Cache::put($this->cacheKey('state'), 'closed');
    }

    protected function open(): void
    {
        Cache::put($this->cacheKey('state'), 'open');
        Cache::put($this->cacheKey('opened_at'), now()->timestamp);

        Log::error('Circuit breaker: OPEN', [
            'service' => $this->serviceName,
            'cooldown' => $this->cooldownSeconds,
        ]);
    }

    protected function isOpen(): bool
    {
        return $this->getState() === 'open';
    }

    protected function transitionToHalfOpen(): void
    {
        Cache::put($this->cacheKey('state'), 'half_open');
        Cache::put($this->cacheKey('failures'), 0);
    }

    protected function canAttemptReset(): bool
    {
        $openedAt = Cache::get($this->cacheKey('opened_at'));

        if (! $openedAt) {
            return true;
        }

        return (now()->timestamp - (int) $openedAt) >= $this->cooldownSeconds;
    }

    protected function cooldownRemaining(): int
    {
        $openedAt = Cache::get($this->cacheKey('opened_at'), now()->timestamp);

        return max(0, $this->cooldownSeconds - (now()->timestamp - (int) $openedAt));
    }

    protected function getState(): string
    {
        return Cache::get($this->cacheKey('state'), 'closed');
    }

    protected function getFailureCount(): int
    {
        return (int) Cache::get($this->cacheKey('failures'), 0);
    }

    protected function cacheKey(string $suffix): string
    {
        return "circuit_breaker:{$this->serviceName}:{$suffix}";
    }
}
