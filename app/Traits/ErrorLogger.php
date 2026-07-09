<?php

namespace App\Traits;

use App\Models\ErrorLog;
use Illuminate\Support\Facades\Log;

trait ErrorLogger
{
    protected function logError(
        \Throwable $e,
        string $errorCode,
        int $httpStatus,
        string $userMessage,
        ?string $technicalMessage = null,
    ): void {
        try {
            ErrorLog::create([
                'error_code' => $errorCode,
                'exception_class' => get_class($e),
                'http_status' => $httpStatus,
                'user_message' => $userMessage,
                'technical_message' => $technicalMessage ?? $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'context' => $e instanceof \App\Exceptions\AIException ? $e->context() : null,
                'user_id' => auth()->id(),
                'url' => request()->fullUrl(),
                'method' => request()->method(),
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable) {
            // If logging fails, don't compound the error
        }

        Log::error($userMessage, [
            'error_code' => $errorCode,
            'exception' => get_class($e),
            'message' => $technicalMessage ?? $e->getMessage(),
            'url' => request()->fullUrl(),
            'user_id' => auth()->id(),
        ]);
    }
}
