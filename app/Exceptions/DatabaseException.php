<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class DatabaseException extends Exception
{
    public function __construct(
        string $message = 'A database error occurred.',
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

    public static function queryFailed(string $detail = '', array $context = []): self
    {
        return new self(
            $detail ?: 'The database query failed. Please try again.',
            500,
            null,
            array_merge(['error_type' => 'query_failed'], $context)
        );
    }

    public static function deadlock(string $detail = '', array $context = []): self
    {
        return new self(
            $detail ?: 'The database is temporarily busy. Please retry your request.',
            500,
            null,
            array_merge(['error_type' => 'deadlock'], $context)
        );
    }

    public static function connectionLost(string $detail = '', array $context = []): self
    {
        return new self(
            $detail ?: 'The database connection was lost. Please try again in a moment.',
            503,
            null,
            array_merge(['error_type' => 'connection_lost'], $context)
        );
    }
}
