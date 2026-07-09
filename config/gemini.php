<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default AI service provider. Supported: "gemini".
    | This value is used when the AI facade or helper does not explicitly
    | specify which provider should be used.
    |
    */

    'default' => env('AI_PROVIDER', 'gemini'),

    /*
    |--------------------------------------------------------------------------
    | AI Providers
    |--------------------------------------------------------------------------
    */

    'providers' => [

        'gemini' => [

            'api_key' => env('GEMINI_API_KEY'),

            'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),

            'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),

            /*
            |--------------------------------------------------------------------------
            | Request Timeouts (in seconds)
            |--------------------------------------------------------------------------
            | connect_timeout - How long to wait for a TCP connection.
            | request_timeout - How long to wait for the entire HTTP request/response.
            */

            'connect_timeout' => env('GEMINI_CONNECT_TIMEOUT', 10),

            'request_timeout' => env('GEMINI_REQUEST_TIMEOUT', 30),

            /*
            |--------------------------------------------------------------------------
            | Retry Configuration
            |--------------------------------------------------------------------------
            | max_retries  - Maximum number of retry attempts.
            | retry_delay  - Base delay in milliseconds (exponential backoff).
            | retry_on_status - HTTP status codes that trigger a retry.
            | jitter_ms   - Random jitter to add to delay (prevents thundering herd).
            */

            'max_retries' => env('GEMINI_MAX_RETRIES', 3),

            'retry_delay' => (int) env('GEMINI_RETRY_DELAY_MS', 1000),

            'retry_on_status' => [429, 500, 502, 503, 504],

            'jitter_ms' => 500,

            /*
            |--------------------------------------------------------------------------
            | Circuit Breaker
            |--------------------------------------------------------------------------
            | enabled          - Enable/disable circuit breaker.
            | failure_threshold - Consecutive failures before opening circuit.
            | cooldown_seconds  - How long the circuit stays open before half-open.
            */

            'circuit_breaker_enabled' => env('GEMINI_CIRCUIT_BREAKER', true),

            'circuit_failure_threshold' => env('GEMINI_CIRCUIT_FAILURE_THRESHOLD', 5),

            'circuit_cooldown_seconds' => env('GEMINI_CIRCUIT_COOLDOWN', 60),

            /*
            |--------------------------------------------------------------------------
            | Generation Defaults
            |--------------------------------------------------------------------------
            | temperature      - Controls randomness (0.0 = deterministic, 1.0 = creative).
            | max_output_tokens - Maximum tokens in the generated response.
            | top_p            - Nucleus sampling parameter.
            */

            'temperature' => env('GEMINI_TEMPERATURE', 0.7),

            'max_output_tokens' => (int) env('GEMINI_MAX_OUTPUT_TOKENS', 1024),

            'top_p' => env('GEMINI_TOP_P', 0.95),

            /*
            |--------------------------------------------------------------------------
            | System Prompt
            |--------------------------------------------------------------------------
            | The system-level instruction that defines the AI's persona and behavior
            | for all ticket response generations.
            */

            'system_prompt' => <<<'PROMPT'
            You are a professional customer support AI assistant for a software company.
            Your responsibilities:
            1. Provide accurate, helpful technical support based on the available knowledge.
            2. Be empathetic and professional in all interactions.
            3. Suggest clear, step-by-step solutions when possible.
            4. If you cannot resolve the issue, clearly explain what additional information is needed.
            5. Never make up information or features that do not exist.
            6. Reference relevant knowledge base articles when applicable.
            7. Escalate to human agents when the issue is beyond your scope.
            PROMPT,

        ],

    ],

];
