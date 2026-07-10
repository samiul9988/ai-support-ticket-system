<?php

use App\Exceptions\ErrorCode;
use App\Traits\ErrorLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Queue\MaxAttemptsExceededException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withProviders([
        \App\Providers\RepositoryServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'permission' => \App\Http\Middleware\PermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $logger = new class {
            use ErrorLogger {
                logError as doLog;
            }
            public static function log(\Throwable $e, string $c, int $s, string $m, ?string $t = null): void {
                (new self)->doLog($e, $c, $s, $m, $t);
            }
        };

        // -------------------------------------------------------
        // AI EXCEPTIONS (user-friendly messages)
        // -------------------------------------------------------
        $exceptions->renderable(function (\App\Exceptions\AIException $e) use ($logger) {
            $logger::log($e, $e->errorCode(), $e->getCode(), $e->userMessage(), $e->getMessage());

            return response()->json([
                'success' => false,
                'error_code' => $e->errorCode(),
                'message' => $e->userMessage(),
            ], $e->getCode() ?: 500);
        });

        // -------------------------------------------------------
        // TICKET EXCEPTIONS
        // -------------------------------------------------------
        $exceptions->renderable(function (\App\Exceptions\TicketException $e) use ($logger) {
            $errorCode = match ($e->getMessage()) {
                'Ticket not found.' => ErrorCode::TICKET_NOT_FOUND,
                'Cannot modify a closed ticket.' => ErrorCode::TICKET_CLOSED,
                default => ErrorCode::SERVER_ERROR,
            };

            return response()->json([
                'success' => false,
                'error_code' => $errorCode,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 400);
        });

        // -------------------------------------------------------
        // DATABASE EXCEPTIONS (user-friendly)
        // -------------------------------------------------------
        $exceptions->renderable(function (\App\Exceptions\DatabaseException $e) use ($logger) {
            $logger::log($e, ErrorCode::DB_QUERY_FAILED, $e->getCode(), $e->getMessage());

            return response()->json([
                'success' => false,
                'error_code' => ErrorCode::DB_QUERY_FAILED,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        });

        $exceptions->renderable(function (QueryException $e) use ($logger) {
            $sqlCode = $e->getCode();
            $userMessage = match (true) {
                $sqlCode == 2002 => 'The database is currently unavailable. Please try again in a moment.',
                $sqlCode == 1045 => 'Database access is currently restricted.',
                str_contains($e->getMessage(), 'Deadlock') => 'The server is busy processing other requests. Please try again.',
                str_contains($e->getMessage(), 'Connection refused') => 'The database connection was refused. Our team has been notified.',
                default => 'Something went wrong processing your request. Please try again.',
            };

            $logger::log($e, ErrorCode::DB_QUERY_FAILED, 500, $userMessage, $e->getMessage());

            return response()->json([
                'success' => false,
                'error_code' => ErrorCode::DB_QUERY_FAILED,
                'message' => $userMessage,
            ], 500);
        });

        // -------------------------------------------------------
        // QUEUE FAILURES
        // -------------------------------------------------------
        $exceptions->renderable(function (\App\Exceptions\QueueException $e) use ($logger) {
            $logger::log($e, ErrorCode::QUEUE_FAILED, $e->getCode(), $e->getMessage());

            return response()->json([
                'success' => false,
                'error_code' => ErrorCode::QUEUE_FAILED,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        });

        // -------------------------------------------------------
        // AUTH / ACCESS
        // -------------------------------------------------------
        $exceptions->renderable(function (AuthenticationException $e) {
            return response()->json([
                'success' => false,
                'error_code' => ErrorCode::UNAUTHORIZED,
                'message' => 'Please log in to continue.',
            ], 401);
        });

        $exceptions->renderable(function (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'error_code' => ErrorCode::FORBIDDEN,
                'message' => 'You don\'t have permission to perform this action.',
            ], 403);
        });

        // -------------------------------------------------------
        // VALIDATION
        // -------------------------------------------------------
        $exceptions->renderable(function (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error_code' => ErrorCode::VALIDATION_ERROR,
                'message' => 'The provided data is invalid.',
                'errors' => $e->errors(),
            ], 422);
        });

        // -------------------------------------------------------
        // NOT FOUND
        // -------------------------------------------------------
        $exceptions->renderable(function (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error_code' => ErrorCode::NOT_FOUND,
                'message' => 'The requested resource was not found.',
            ], 404);
        });

        $exceptions->renderable(function (NotFoundHttpException $e) {
            return response()->json([
                'success' => false,
                'error_code' => ErrorCode::ROUTE_NOT_FOUND,
                'message' => 'The requested endpoint does not exist.',
            ], 404);
        });

        // -------------------------------------------------------
        // RATE LIMIT (Throttle)
        // -------------------------------------------------------
        $exceptions->renderable(function (ThrottleRequestsException $e) {
            return response()->json([
                'success' => false,
                'error_code' => 'TOO_MANY_REQUESTS',
                'message' => 'You have made too many requests. Please wait before trying again.',
                'retry_after_seconds' => $e->getHeaders()['Retry-After'] ?? 60,
            ], 429);
        });

        // -------------------------------------------------------
        // METHOD NOT ALLOWED
        // -------------------------------------------------------
        $exceptions->renderable(function (MethodNotAllowedHttpException $e) {
            return response()->json([
                'success' => false,
                'error_code' => 'METHOD_NOT_ALLOWED',
                'message' => 'This HTTP method is not supported for this endpoint.',
            ], 405);
        });

        // -------------------------------------------------------
        // CATCH-ALL: Unhandled exceptions → generic message
        // -------------------------------------------------------
        $exceptions->renderable(function (\Throwable $e) use ($logger) {
            if (app()->environment('production')) {
                $logger::log($e, ErrorCode::SERVER_ERROR, 500,
                    'An unexpected error occurred. Our team has been notified.',
                    $e->getMessage()
                );

                return response()->json([
                    'success' => false,
                    'error_code' => ErrorCode::SERVER_ERROR,
                    'message' => 'An unexpected error occurred. Our team has been notified.',
                ], 500);
            }

            // In non-production, show actual error for debugging
            return response()->json([
                'success' => false,
                'error_code' => ErrorCode::SERVER_ERROR,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        });
    })->create();
