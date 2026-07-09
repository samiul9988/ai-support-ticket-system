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

    public function widgets(Request $request): JsonResponse
    {
        if ($request->user()->isCustomer()) {
            return $this->forbidden();
        }
        return $this->success($this->dashboardService->widgets());
    }

    public function statusDistribution(Request $request): JsonResponse
    {
        if ($request->user()->isCustomer()) return $this->forbidden();
        return $this->success($this->dashboardService->statusDistribution());
    }

    public function priorityDistribution(Request $request): JsonResponse
    {
        if ($request->user()->isCustomer()) return $this->forbidden();
        return $this->success($this->dashboardService->priorityDistribution());
    }

    public function topAgents(Request $request): JsonResponse
    {
        if ($request->user()->isCustomer()) return $this->forbidden();
        $limit = min(20, (int) $request->input('limit', 5));
        return $this->success($this->dashboardService->topAgents($limit));
    }
    public function dailyTrend(Request $request): JsonResponse
    {
        if ($request->user()->isCustomer()) return $this->forbidden();
        $days = min(90, (int) $request->input('days', 7));
        return $this->success($this->dashboardService->dailyTrend($days));
    }
    public function aiResponseTrend(Request $request): JsonResponse
    {
        if ($request->user()->isCustomer()) return $this->forbidden();
        $days = min(90, (int) $request->input('days', 7));
        return $this->success($this->dashboardService->aiResponseTrend($days));
    }
    public function customerSatisfaction(Request $request): JsonResponse
    {
        if ($request->user()->isCustomer()) return $this->forbidden();
        return $this->success($this->dashboardService->customerSatisfaction());
    }
}
