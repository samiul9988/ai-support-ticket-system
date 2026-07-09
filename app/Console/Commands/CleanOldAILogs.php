<?php

namespace App\Console\Commands;

use App\Models\AiUsageLog;
use Illuminate\Console\Command;

class CleanOldAILogs extends Command
{
    protected $signature = 'ai:clean-logs
                            {--days=30 : Delete logs older than this many days}
                            {--keep-errors : Keep error logs regardless of age}';

    protected $description = 'Clean up old AI usage logs to manage database size';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $keepErrors = $this->boolean('keep-errors');
        $cutoff = now()->subDays($days);

        $this->info("Cleaning AI logs older than {$days} days (before {$cutoff->toDateString()})...");

        $query = AiUsageLog::where('created_at', '<', $cutoff);

        if ($keepErrors) {
            $query->where('log_level', '!=', 'error')
                  ->where('success', true);
        }

        $count = $query->count();

        if ($count === 0) {
            $this->info('No old logs to clean.');

            return self::SUCCESS;
        }

        if (! $this->option('no-interaction') && ! $this->confirm("Delete {$count} old log entries?")) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        $query->delete();

        $this->info("Deleted {$count} old AI log entries.");
        $this->info('Run "php artisan ai:clean-logs --days=7" for weekly cleanup.');

        return self::SUCCESS;
    }
}
