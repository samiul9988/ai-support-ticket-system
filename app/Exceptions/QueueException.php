<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class QueueException extends Exception
{
    public function __construct(
        string $message = 'A background job failed.',
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

    public static function jobFailed(string $detail = '', array $context = []): self
    {
        return new self(
            $detail ?: 'The background process encountered an error. Our team has been notified.',
            500,
            null,
            array_merge(['error_type' => 'job_failed'], $context)
        );
    }

    public static function maxRetriesExceeded(string $detail = '', array $context = []): self
    {
        return new self(
            $detail ?: 'The background process exceeded maximum retries. Our team will investigate.',
            500,
            null,
            array_merge(['error_type' => 'max_retries'], $context)
        );
    }

    public static function queueConnectionFailed(string $detail = '', array $context = []): self
    {
        return new self(
            $detail ?: 'The queue service is temporarily unavailable.',
            503,
            null,
            array_merge(['error_type' => 'queue_connection'], $context)
        );
    }
}
