<?php

namespace App\Jobs\Middleware;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RateLimitAIJobs
{
    protected int $maxPerMinute;

    protected string $cachePrefix;

    public function __construct(?int $maxPerMinute = null)
    {
        $this->maxPerMinute = $maxPerMinute
            ?? (int) config('queue.queues.ai-responses.max_jobs_per_minute', 20);
        $this->cachePrefix = 'ai_rate_limit:';
    }

    public function handle(object $job, callable $next): void
    {
        $currentMinute = now()->format('Y-m-d-H-i');
        $cacheKey = $this->cachePrefix . $currentMinute;

        $currentCount = (int) Cache::get($cacheKey, 0);

        if ($currentCount >= $this->maxPerMinute) {
            Log::warning('AI job rate limited', [
                'job' => get_class($job),
                'current_count' => $currentCount,
                'limit' => $this->maxPerMinute,
            ]);

            $job->release(60);

            return;
        }

        Cache::put($cacheKey, $currentCount + 1, now()->addMinutes(2));

        $next($job);
    }
}
