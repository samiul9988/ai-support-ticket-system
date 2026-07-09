<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AIDashboardService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AIDashboardController extends Controller
{
    use ApiResponse;

    public function __construct(protected AIDashboardService $dashboardService) {}

    public function overview(): JsonResponse
    {
        return $this->success($this->dashboardService->overview());
    }

    public function dailyUsage(Request $request): JsonResponse
    {
        $days = min(90, (int) $request->input('days', 30));

        return $this->success($this->dashboardService->dailyUsage($days));
    }

    public function monthlyUsage(Request $request): JsonResponse
    {
        $months = min(24, (int) $request->input('months', 12));

        return $this->success($this->dashboardService->monthlyUsage($months));
    }

    public function hourlyToday(): JsonResponse
    {
        return $this->success($this->dashboardService->hourlyUsageToday());
    }

    public function topCategories(Request $request): JsonResponse
    {
        $limit = min(20, (int) $request->input('limit', 8));

        return $this->success($this->dashboardService->topCategories($limit));
    }

    public function modelBreakdown(): JsonResponse
    {
        return $this->success($this->dashboardService->modelBreakdown());
    }

    public function recentFailures(Request $request): JsonResponse
    {
        $limit = min(50, (int) $request->input('limit', 10));

        return $this->success($this->dashboardService->recentFailures($limit));
    }

    public function recentRequests(Request $request): JsonResponse
    {
        $limit = min(50, (int) $request->input('limit', 20));

        return $this->success($this->dashboardService->recentRequests($limit));
    }
}
