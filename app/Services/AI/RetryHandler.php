<?php

namespace App\Services\AI;

use App\Services\Logging\AILogger;
use Illuminate\Support\Facades\Log;

class RetryHandler
{
    protected int $maxRetries;

    protected int $baseDelayMs;

    protected int $jitterMs;

    protected array $retryOnStatus;

    protected ?int $currentLogId = null;

    public function __construct(array $config = [])
    {
        $this->maxRetries = $config['max_retries'] ?? config('gemini.providers.gemini.max_retries', 3);
        $this->baseDelayMs = $config['retry_delay'] ?? (int) config('gemini.providers.gemini.retry_delay', 1000);
        $this->jitterMs = $config['jitter_ms'] ?? (int) config('gemini.providers.gemini.jitter_ms', 500);
        $this->retryOnStatus = $config['retry_on_status'] ?? config('gemini.providers.gemini.retry_on_status', [429, 500, 502, 503, 504]);
    }

    public function withLogId(int $logId): self
    {
        $this->currentLogId = $logId;

        return $this;
    }

    public function execute(callable $action, string $operation = 'api_call'): mixed
    {
        $attempt = 0;
        $lastException = null;
        $logger = AILogger::for('gemini', $operation);

        while ($attempt <= $this->maxRetries) {
            try {
                return $action();
            } catch (\Throwable $e) {
                $lastException = $e;

                if (! $this->shouldRetry($e) || $attempt >= $this->maxRetries) {
                    throw $e;
                }

                $attempt++;
                $delay = $this->calculateDelay($attempt);

                $logger->logRetry(
                    logId: $this->currentLogId ?? 0,
                    attempt: $attempt,
                    maxRetries: $this->maxRetries,
                    delayMs: $delay,
                    error: $e->getMessage(),
                );

                usleep($delay * 1000);
            }
        }

        throw $lastException ?? new \RuntimeException('Retry handler exhausted without exception.');
    }

    protected function shouldRetry(\Throwable $e): bool
    {
        if ($e instanceof \App\Exceptions\AIException) {
            $status = $e->getCode();

            if (in_array($status, $this->retryOnStatus)) {
                return true;
            }

            if (in_array($e->context()['error_type'] ?? '', ['timeout', 'service_unavailable'])) {
                return true;
            }

            if ($e->context()['error_type'] ?? '' === 'rate_limit') {
                return true;
            }

            return false;
        }

        if ($e instanceof \Illuminate\Http\Client\ConnectionException) {
            return true;
        }

        return false;
    }

    protected function calculateDelay(int $attempt): int
    {
        $baseDelay = $this->baseDelayMs * (2 ** ($attempt - 1));

        $jitter = random_int(0, $this->jitterMs);

        return $baseDelay + $jitter;
    }
}
