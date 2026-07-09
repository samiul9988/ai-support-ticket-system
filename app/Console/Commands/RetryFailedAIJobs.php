<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class RetryFailedAIJobs extends Command
{
    protected $signature = 'queue:ai-retry
                            {--all : Retry all failed AI jobs, not just today}
                            {--limit=10 : Maximum jobs to retry}';

    protected $description = 'Retry failed AI response generation jobs';

    public function handle(): int
    {
        $query = DB::table('failed_jobs')->where('queue', 'ai-responses');

        if (! $this->option('all')) {
            $query->whereDate('failed_at', today());
        }

        $totalFailed = $query->count();

        if ($totalFailed === 0) {
            $this->info('No failed AI jobs found.');
            return self::SUCCESS;
        }

        $limit = (int) $this->option('limit');
        $jobs = $query->orderBy('failed_at', 'asc')->limit($limit)->get();

        $this->info("Retrying {$jobs->count()} of {$totalFailed} failed AI jobs...");
        $this->newLine();

        $retried = 0;
        $skipped = 0;

        foreach ($jobs as $failedJob) {
            $uuid = $failedJob->uuid;
            $failedAt = $failedJob->failed_at;

            try {
                Artisan::call('queue:retry', ['id' => [$uuid]]);
                $this->line("  [OK]  {$uuid} (failed at {$failedAt})");
                $retried++;
            } catch (\Throwable $e) {
                $this->line("  [SKIP] {$uuid} - {$e->getMessage()}");
                $skipped++;
            }
        }

        $this->newLine();
        $this->info("Result: {$retried} retried, {$skipped} skipped.");

        if ($retried > 0) {
            $this->warn('Run: php artisan queue:work --queue=ai-responses to process the retried jobs.');
        }

        return self::SUCCESS;
    }
}
