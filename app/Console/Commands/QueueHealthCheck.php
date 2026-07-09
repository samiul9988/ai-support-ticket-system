<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class QueueHealthCheck extends Command
{
    protected $signature = 'queue:ai-health';

    protected $description = 'Check health of the AI response queue';

    public function handle(): int
    {
        $this->info('=== AI Queue Health Report ===');
        $this->newLine();

        $pending = DB::table('jobs')
            ->where('queue', 'ai-responses')
            ->count();
        $this->line("Pending AI jobs: <fg={$this->color($pending)}>{$pending}</>");

        $failed = DB::table('failed_jobs')
            ->where('queue', 'ai-responses')
            ->count();
        $this->line("Failed AI jobs:  <fg={$this->color($failed, 0)}>{$failed}</>");

        $failedToday = DB::table('failed_jobs')
            ->where('queue', 'ai-responses')
            ->whereDate('failed_at', today())
            ->count();
        $this->line("Failed today:    <fg={$this->color($failedToday, 0)}>{$failedToday}</>");

        $totalPending = DB::table('jobs')->count();
        $processed = DB::table('jobs')
            ->where('queue', 'ai-responses')
            ->where('reserved_at', '>', 0)
            ->count();
        $this->line("Processing now:  {$processed}");

        $this->newLine();
        $this->info('=== Queue Configuration ===');
        $this->line('Connection:  ' . config('queue.default'));
        $this->line('AI workers:  ' . config('queue.queues.ai-responses.worker_count'));
        $this->line('AI timeout:  ' . config('queue.queues.ai-responses.timeout') . 's');
        $this->line('Rate limit:  ' . config('queue.queues.ai-responses.max_jobs_per_minute') . '/min');
        $this->line('Retry after: ' . config('queue.queues.ai-responses.retry_after') . 's');

        if ($failed > 50) {
            $this->newLine();
            $this->warn('WARNING: High number of failed AI jobs. Consider running queue:ai-retry');
        }

        $circuitState = cache()->get('circuit_breaker:gemini:state', 'closed');
        $this->newLine();
        $stateColor = $circuitState === 'open' ? 'red' : 'green';
        $this->line("Circuit breaker: <fg={$stateColor}>{$circuitState}</>");

        return self::SUCCESS;
    }

    protected function color(int $count, int $warnThreshold = 10): string
    {
        if ($count === 0) {
            return 'green';
        }
        if ($count > $warnThreshold) {
            return 'yellow';
        }
        return 'white';
    }
}
