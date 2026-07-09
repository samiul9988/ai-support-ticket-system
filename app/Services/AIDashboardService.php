<?php

namespace App\Services;

use App\Models\AiUsageLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AIDashboardService
{
    public function overview(): array
    {
        return [
            'total_requests' => $this->totalRequests(),
            'today_requests' => $this->todayRequests(),
            'avg_response_time_ms' => $this->avgResponseTime(),
            'total_tokens' => $this->totalTokens(),
            'today_tokens' => $this->todayTokens(),
            'total_cost' => $this->totalCost(),
            'failed_requests' => $this->failedRequests(),
            'failure_rate' => $this->failureRate(),
            'success_rate' => $this->successRate(),
            'active_tickets_using_ai' => $this->activeTicketsUsingAI(),
        ];
    }

    public function totalRequests(): int
    {
        return AiUsageLog::count();
    }

    public function todayRequests(): int
    {
        return AiUsageLog::today()->count();
    }

    public function avgResponseTime(?string $period = null): float
    {
        $query = AiUsageLog::successful();

        if ($period === 'today') {
            $query->today();
        } elseif ($period === 'week') {
            $query->where('created_at', '>=', now()->subWeek());
        } elseif ($period === 'month') {
            $query->where('created_at', '>=', now()->subMonth());
        }

        return round($query->avg('duration_ms') ?? 0, 2);
    }

    public function totalTokens(): int
    {
        return (int) AiUsageLog::sum('total_tokens');
    }

    public function todayTokens(): int
    {
        return (int) AiUsageLog::today()->sum('total_tokens');
    }

    public function totalCost(): float
    {
        return round((float) AiUsageLog::sum('cost'), 6);
    }

    public function failedRequests(): int
    {
        return AiUsageLog::failed()->count();
    }

    public function todayFailedRequests(): int
    {
        return AiUsageLog::today()->failed()->count();
    }

    public function failureRate(): float
    {
        $total = $this->totalRequests();

        if ($total === 0) {
            return 0.0;
        }

        return round(($this->failedRequests() / $total) * 100, 2);
    }

    public function successRate(): float
    {
        return round(100 - $this->failureRate(), 2);
    }

    public function activeTicketsUsingAI(): int
    {
        return AiUsageLog::whereNotNull('ticket_id')
            ->distinct('ticket_id')
            ->count('ticket_id');
    }

    public function dailyUsage(int $days = 30): array
    {
        $records = AiUsageLog::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as requests'),
            DB::raw('SUM(total_tokens) as tokens'),
            DB::raw('AVG(duration_ms) as avg_duration_ms'),
            DB::raw('SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failures'),
            DB::raw('SUM(cost) as cost'),
        )
            ->where('created_at', '>=', now()->subDays($days)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'days' => $days,
            'total_requests' => $records->sum('requests'),
            'total_tokens' => $records->sum('tokens'),
            'total_failures' => $records->sum('failures'),
            'total_cost' => round((float) $records->sum('cost'), 6),
            'avg_daily_requests' => $records->count() > 0 ? round($records->sum('requests') / $records->count(), 1) : 0,
            'records' => $records,
        ];
    }

    public function monthlyUsage(int $months = 12): array
    {
        $records = AiUsageLog::select(
            DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
            DB::raw('COUNT(*) as requests'),
            DB::raw('SUM(total_tokens) as tokens'),
            DB::raw('AVG(duration_ms) as avg_duration_ms'),
            DB::raw('SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failures'),
            DB::raw('SUM(cost) as cost'),
        )
            ->where('created_at', '>=', now()->subMonths($months)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return [
            'months' => $months,
            'total_requests' => $records->sum('requests'),
            'total_tokens' => $records->sum('tokens'),
            'total_failures' => $records->sum('failures'),
            'total_cost' => round((float) $records->sum('cost'), 6),
            'records' => $records,
        ];
    }

    public function topCategories(int $limit = 8): Collection
    {
        return DB::table('ai_usage_logs')
            ->select(
                'ai_prompt_history.prompt_type',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(ai_usage_logs.total_tokens) as tokens'),
                DB::raw('AVG(ai_usage_logs.duration_ms) as avg_duration_ms'),
                DB::raw('SUM(CASE WHEN ai_usage_logs.success = 0 THEN 1 ELSE 0 END) as failures'),
            )
            ->join('ai_prompt_history', 'ai_usage_logs.prompt_history_id', '=', 'ai_prompt_history.id')
            ->groupBy('ai_prompt_history.prompt_type')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();
    }

    public function recentFailures(int $limit = 10): Collection
    {
        return AiUsageLog::failed()
            ->with('ticket:id,title')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'ticket_id' => $log->ticket_id,
                'ticket_title' => $log->ticket?->title,
                'model' => $log->model,
                'error' => $log->error_message,
                'duration_ms' => $log->duration_ms,
                'failed_at' => $log->created_at?->toIso8601String(),
            ]);
    }

    public function recentRequests(int $limit = 20): Collection
    {
        return AiUsageLog::with('ticket:id,title')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'ticket_id' => $log->ticket_id,
                'ticket_title' => $log->ticket?->title,
                'model' => $log->model,
                'total_tokens' => $log->total_tokens,
                'duration_ms' => $log->duration_ms,
                'success' => $log->success,
                'cost' => round($log->cost, 8),
                'created_at' => $log->created_at?->toIso8601String(),
            ]);
    }

    public function modelBreakdown(): Collection
    {
        return AiUsageLog::select(
            'model',
            DB::raw('COUNT(*) as requests'),
            DB::raw('SUM(total_tokens) as tokens'),
            DB::raw('AVG(duration_ms) as avg_duration_ms'),
            DB::raw('SUM(cost) as cost'),
        )
            ->groupBy('model')
            ->orderByDesc('requests')
            ->get();
    }

    public function hourlyUsageToday(): array
    {
        $records = AiUsageLog::select(
            DB::raw('HOUR(created_at) as hour'),
            DB::raw('COUNT(*) as requests'),
            DB::raw('SUM(total_tokens) as tokens'),
            DB::raw('AVG(duration_ms) as avg_duration_ms'),
        )
            ->today()
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $padded = [];
        for ($h = 0; $h <= 23; $h++) {
            $padded[$h] = $records->firstWhere('hour', $h) ?? (object) [
                'hour' => $h,
                'requests' => 0,
                'tokens' => 0,
                'avg_duration_ms' => 0,
            ];
        }

        return array_values($padded);
    }
}
