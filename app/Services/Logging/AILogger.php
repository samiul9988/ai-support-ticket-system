<?php

namespace App\Services\Logging;

use App\Models\AiUsageLog;
use Illuminate\Support\Facades\Log;

class AILogger
{
    protected ?int $ticketId = null;

    protected ?int $promptHistoryId = null;

    protected string $model = '';

    protected string $operation = '';

    protected array $context = [];

    public function ticket(?int $id): self
    {
        $this->ticketId = $id;

        return $this;
    }

    public function promptHistory(?int $id): self
    {
        $this->promptHistoryId = $id;

        return $this;
    }

    public function model(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function operation(string $operation): self
    {
        $this->operation = $operation;

        return $this;
    }

    public function context(array $context): self
    {
        $this->context = $context;

        return $this;
    }

    public static function for(string $model, string $operation, ?int $ticketId = null): self
    {
        $instance = new self;
        $instance->model = $model;
        $instance->operation = $operation;
        $instance->ticketId = $ticketId;
        $instance->context = [];

        return $instance;
    }

    public function logRequest(string $endpoint, int $payloadSize): int
    {
        Log::info('AI Request', [
            'operation' => $this->operation,
            'model' => $this->model,
            'endpoint' => $endpoint,
            'payload_size' => $payloadSize,
            'ticket_id' => $this->ticketId,
        ]);

        $log = AiUsageLog::create([
            'ticket_id' => $this->ticketId,
            'prompt_history_id' => $this->promptHistoryId,
            'model' => $this->model,
            'operation' => $this->operation,
            'request_endpoint' => $endpoint,
            'request_payload_size' => $payloadSize,
            'prompt_tokens' => 0,
            'completion_tokens' => 0,
            'total_tokens' => 0,
            'cost' => 0,
            'duration_ms' => 0,
            'success' => false,
            'log_level' => 'info',
        ]);

        return $log->id;
    }

    public function logResponse(
        int $logId,
        int $httpStatus,
        array $tokens,
        int $durationMs,
        ?string $responseBody = null,
    ): void {
        $log = AiUsageLog::find($logId);

        if (! $log) {
            return;
        }

        $log->update([
            'http_status' => (string) $httpStatus,
            'prompt_tokens' => $tokens['prompt_tokens'] ?? 0,
            'completion_tokens' => $tokens['completion_tokens'] ?? 0,
            'total_tokens' => $tokens['total_tokens'] ?? 0,
            'cost' => $this->calculateCost($tokens['total_tokens'] ?? 0),
            'duration_ms' => $durationMs,
            'response_body' => $responseBody ? mb_substr($responseBody, 0, 2000) : null,
            'success' => $httpStatus >= 200 && $httpStatus < 300,
            'log_level' => $httpStatus >= 200 && $httpStatus < 300 ? 'info' : 'error',
        ]);

        Log::info('AI Response', [
            'operation' => $this->operation,
            'status' => $httpStatus,
            'tokens' => $tokens['total_tokens'] ?? 0,
            'duration_ms' => $durationMs,
            'ticket_id' => $this->ticketId,
        ]);
    }

    public function logError(
        int $logId,
        int $httpStatus,
        string $errorType,
        string $errorMessage,
        int $durationMs = 0,
        ?string $responseBody = null,
    ): void {
        $log = AiUsageLog::find($logId);

        if (! $log) {
            return;
        }

        $log->update([
            'http_status' => (string) $httpStatus,
            'success' => false,
            'error_type' => $errorType,
            'error_message' => mb_substr($errorMessage, 0, 500),
            'duration_ms' => $durationMs,
            'response_body' => $responseBody ? mb_substr($responseBody, 0, 2000) : null,
            'log_level' => 'error',
        ]);

        Log::error('AI Error', [
            'operation' => $this->operation,
            'status' => $httpStatus,
            'error_type' => $errorType,
            'error' => $errorMessage,
            'duration_ms' => $durationMs,
            'ticket_id' => $this->ticketId,
        ]);
    }

    public function logRetry(
        int $logId,
        int $attempt,
        int $maxRetries,
        int $delayMs,
        string $error,
    ): void {
        $log = AiUsageLog::find($logId);

        if ($log) {
            $log->increment('retry_count');
        }

        Log::warning('AI Retry', [
            'operation' => $this->operation,
            'attempt' => $attempt,
            'max_retries' => $maxRetries,
            'delay_ms' => $delayMs,
            'error' => $error,
            'ticket_id' => $this->ticketId,
        ]);
    }

    public function logCircuitOpen(): void
    {
        Log::error('AI Circuit Breaker Open', [
            'ticket_id' => $this->ticketId,
            'operation' => $this->operation,
        ]);
    }

    public function logInfo(string $message, array $extra = []): void
    {
        Log::info($message, array_merge([
            'operation' => $this->operation,
            'ticket_id' => $this->ticketId,
        ], $extra));
    }

    public function logWarning(string $message, array $extra = []): void
    {
        Log::warning($message, array_merge([
            'operation' => $this->operation,
            'ticket_id' => $this->ticketId,
        ], $extra));
    }

    protected function calculateCost(int $totalTokens): float
    {
        $pricing = [
            'gemini-2.0-flash' => ['input' => 0.00000015, 'output' => 0.00000060],
            'gemini-2.0-pro' => ['input' => 0.00000125, 'output' => 0.00000500],
        ];

        $rates = $pricing[$this->model] ?? $pricing['gemini-2.0-flash'];

        $inputTokens = (int) ($totalTokens * 0.8);
        $outputTokens = $totalTokens - $inputTokens;

        return ($inputTokens * $rates['input']) + ($outputTokens * $rates['output']);
    }
}
