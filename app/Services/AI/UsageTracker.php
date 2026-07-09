<?php

namespace App\Services\AI;

use App\Models\AiUsageLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UsageTracker
{
    public function track(
        ?int $ticketId = null,
        ?int $promptHistoryId = null,
        string $model = 'gemini-2.0-flash',
        int $promptTokens = 0,
        int $completionTokens = 0,
        int $totalTokens = 0,
        int $durationMs = 0,
        bool $success = true,
        ?string $errorMessage = null,
    ): ?int {
        try {
            $log = AiUsageLog::create([
                'ticket_id' => $ticketId,
                'prompt_history_id' => $promptHistoryId,
                'model' => $model,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $totalTokens,
                'cost' => $this->calculateCost($totalTokens, $model),
                'duration_ms' => $durationMs,
                'success' => $success,
                'error_message' => $errorMessage,
            ]);

            $this->incrementDailyCounters($totalTokens);

            return $log->id;
        } catch (\Throwable $e) {
            Log::warning('Failed to track AI usage', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function getRequestCountToday(): int
    {
        return AiUsageLog::whereDate('created_at', today())->count();
    }

    public function getTokenCountToday(): int
    {
        return (int) AiUsageLog::whereDate('created_at', today())->sum('total_tokens');
    }

    public function getCostToday(): float
    {
        return (float) AiUsageLog::whereDate('created_at', today())->sum('cost');
    }

    public function getUsageStats(int $days = 30): array
    {
        $stats = AiUsageLog::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as requests'),
            DB::raw('SUM(total_tokens) as tokens'),
            DB::raw('SUM(cost) as cost'),
            DB::raw('AVG(duration_ms) as avg_duration_ms'),
            DB::raw('SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failures')
        )
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'daily' => $stats,
            'totals' => [
                'requests' => $stats->sum('requests'),
                'tokens' => $stats->sum('tokens'),
                'cost' => $stats->sum('cost'),
                'failures' => $stats->sum('failures'),
                'success_rate' => $stats->sum('requests') > 0
                    ? round((($stats->sum('requests') - $stats->sum('failures')) / $stats->sum('requests')) * 100, 2)
                    : 100,
            ],
        ];
    }

    protected function calculateCost(int $totalTokens, string $model): float
    {
        $pricing = [
            'gemini-2.0-flash' => [
                'input' => 0.00000015,
                'output' => 0.00000060,
            ],
            'gemini-2.0-pro' => [
                'input' => 0.00000125,
                'output' => 0.00000500,
            ],
        ];

        $rates = $pricing[$model] ?? $pricing['gemini-2.0-flash'];

        $inputTokens = (int) ($totalTokens * 0.8);
        $outputTokens = $totalTokens - $inputTokens;

        return ($inputTokens * $rates['input']) + ($outputTokens * $rates['output']);
    }

    protected function incrementDailyCounters(int $totalTokens): void
    {
        $key = 'ai_usage:' . today()->toDateString();

        Cache::increment($key . ':requests');
        Cache::increment($key . ':tokens', $totalTokens);
    }
}
