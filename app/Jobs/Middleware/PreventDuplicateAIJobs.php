<?php

namespace App\Jobs\Middleware;

use App\Models\Ticket;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Support\Facades\Log;

class PreventDuplicateAIJobs
{
    public function __construct(
        protected Ticket $ticket,
        protected string $jobType,
    ) {}

    public function handle(object $job, callable $next): void
    {
        $cacheKey = "ai_job:{$this->ticket->id}:{$this->jobType}";

        if (cache()->has($cacheKey)) {
            Log::info('Duplicate AI job skipped', [
                'ticket_id' => $this->ticket->id,
                'job_type' => $this->jobType,
            ]);

            return;
        }

        cache()->put($cacheKey, true, now()->addMinutes(10));

        $next($job);
    }
}
