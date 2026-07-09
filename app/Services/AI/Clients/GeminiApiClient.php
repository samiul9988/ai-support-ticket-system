<?php

namespace App\Services\AI\Clients;

use App\Exceptions\AIException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\PendingRequest;

class GeminiApiClient
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $model;
    protected float $connectTimeout;
    protected float $requestTimeout;

    public function __construct(array $config)
    {
        $this->baseUrl = rtrim($config['base_url'] ?? '', '/');
        $this->apiKey = $config['api_key'] ?? '';
        $this->model = $config['model'] ?? 'gemini-2.0-flash';
        $this->connectTimeout = (float) ($config['connect_timeout'] ?? 10);
        $this->requestTimeout = (float) ($config['request_timeout'] ?? 30);
    }

    public function generateContent(array $payload): Response
    {
        $this->validateConfiguration();

        $endpoint = "{$this->baseUrl}/models/{$this->model}:generateContent";

        Log::debug('Gemini API request', [
            'endpoint' => $endpoint,
            'model' => $this->model,
            'payload_size' => strlen(json_encode($payload)),
        ]);

        try {
            $response = $this->httpClient()
                ->post("{$endpoint}?key={$this->apiKey}", $payload);

            if (! $response->successful()) {
                $statusCode = $response->status();
                $body = $response->body();

                Log::error('Gemini API HTTP error', [
                    'status' => $statusCode,
                    'body' => substr($body, 0, 500),
                ]);

                $errorData = $this->parseErrorBody($body);

                throw match (true) {
                    $statusCode === 429 => AIException::rateLimitExceeded(
                        $errorData['message'] ?? 'Rate limit exceeded',
                        ['status' => $statusCode, 'retry_after' => $response->header('Retry-After')]
                    ),
                    $statusCode === 400 && str_contains(strtolower($body), 'safety') => AIException::contentFiltered(
                        'Content blocked by safety filters',
                        ['status' => $statusCode]
                    ),
                    $statusCode >= 500 => AIException::serviceUnavailable(
                        "Gemini API server error (HTTP {$statusCode})",
                        ['status' => $statusCode, 'body' => $body]
                    ),
                    $statusCode === 403 => AIException::configurationError(
                        "API key does not have permission (HTTP {$statusCode})"
                    ),
                    default => AIException::serviceUnavailable(
                        "Gemini API returned HTTP {$statusCode}",
                        ['status' => $statusCode, 'body' => $body]
                    ),
                };
            }

            return $response;
        } catch (ConnectionException $e) {
            Log::error('Gemini connection failed', ['error' => $e->getMessage()]);

            throw AIException::serviceUnavailable(
                "Cannot connect to Gemini API: {$e->getMessage()}",
                ['exception' => $e->getMessage()]
            );
        }
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    protected function httpClient(): PendingRequest
    {
        return Http::withHeaders([
            'Content-Type' => 'application/json',
        ])
        ->connectTimeout($this->connectTimeout)
        ->timeout($this->requestTimeout);
    }

    protected function validateConfiguration(): void
    {
        if (empty($this->apiKey)) {
            throw AIException::configurationError(
                'GEMINI_API_KEY is not set. Add it to your .env file.'
            );
        }
    }

    protected function parseErrorBody(string $body): array
    {
        $decoded = json_decode($body, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return [
                'message' => $decoded['error']['message'] ?? $decoded['error']['status'] ?? 'Unknown error',
                'code' => $decoded['error']['code'] ?? 0,
                'status' => $decoded['error']['status'] ?? 'UNKNOWN',
            ];
        }

        return ['message' => $body];
    }
}
