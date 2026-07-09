<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    use ApiResponse;

    public function __construct(protected AdminDashboardService $dashboardService) {}

    public function widgets(): JsonResponse
    {
        return $this->success($this->dashboardService->widgets());
    }

    public function statusDistribution(): JsonResponse
    {
        return $this->success($this->dashboardService->statusDistribution());
    }

    public function priorityDistribution(): JsonResponse
    {
        return $this->success($this->dashboardService->priorityDistribution());
    }

    public function topAgents(Request $request): JsonResponse
    {
        $limit = min(20, (int) $request->input('limit', 5));

        return $this->success($this->dashboardService->topAgents($limit));
    }

    public function dailyTrend(Request $request): JsonResponse
    {
        $days = min(90, (int) $request->input('days', 7));

        return $this->success($this->dashboardService->dailyTrend($days));
    }

    public function aiResponseTrend(Request $request): JsonResponse
    {
        $days = min(90, (int) $request->input('days', 7));

        return $this->success($this->dashboardService->aiResponseTrend($days));
    }

    public function customerSatisfaction(): JsonResponse
    {
        return $this->success($this->dashboardService->customerSatisfaction());
    }
}
