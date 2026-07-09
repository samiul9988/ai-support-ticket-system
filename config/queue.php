<?php

return [

    'default' => env('QUEUE_CONNECTION', 'database'),

    'connections' => [

        'sync' => ['driver' => 'sync'],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_QUEUE_CONNECTION'),
            'table' => env('DB_QUEUE_TABLE', 'jobs'),
            'queue' => env('DB_QUEUE', 'default'),
            'retry_after' => (int) env('DB_QUEUE_RETRY_AFTER', 120),
            'after_commit' => false,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => env('BEANSTALKD_QUEUE_HOST', 'localhost'),
            'queue' => env('BEANSTALKD_QUEUE', 'default'),
            'retry_after' => (int) env('BEANSTALKD_QUEUE_RETRY_AFTER', 90),
            'block_for' => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'default'),
            'suffix' => env('SQS_SUFFIX'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_QUEUE_CONNECTION', 'default'),
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => (int) env('REDIS_QUEUE_RETRY_AFTER', 120),
            'block_for' => null,
            'after_commit' => false,
        ],

    ],

    'batching' => [
        'database' => env('DB_CONNECTION', 'sqlite'),
        'table' => 'job_batches',
    ],

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'sqlite'),
        'table' => 'failed_jobs',
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Queue Configuration
    |--------------------------------------------------------------------------
    | The AI queue processes Gemini API calls. These jobs are I/O bound
    | (HTTP requests), not CPU bound. A single worker with concurrent
    | HTTP connections can handle ~10-20 AI jobs per minute.
    */

    'queues' => [

        'ai-responses' => [
            'worker_count' => (int) env('AI_QUEUE_WORKERS', 3),
            'timeout' => (int) env('AI_QUEUE_TIMEOUT', 45),
            'sleep' => (int) env('AI_QUEUE_SLEEP', 3),
            'max_jobs_per_minute' => (int) env('AI_QUEUE_MAX_PER_MINUTE', 20),
            'retry_after' => (int) env('AI_QUEUE_RETRY_AFTER', 120),
        ],

        'notifications' => [
            'worker_count' => (int) env('NOTIFY_QUEUE_WORKERS', 2),
            'timeout' => (int) env('NOTIFY_QUEUE_TIMEOUT', 30),
            'sleep' => (int) env('NOTIFY_QUEUE_SLEEP', 5),
        ],

    ],

];
