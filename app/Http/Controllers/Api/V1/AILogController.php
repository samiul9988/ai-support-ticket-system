<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AiUsageLog;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AILogController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = AiUsageLog::with('ticket:id,title')->latest();

        if ($request->has('operation')) {
            $query->byOperation($request->input('operation'));
        }

        if ($request->boolean('errors_only')) {
            $query->errorsOnly();
        }

        if ($request->boolean('retried')) {
            $query->retried();
        }

        if ($request->has('log_level')) {
            $query->where('log_level', $request->input('log_level'));
        }

        if ($request->has('ticket_id')) {
            $query->where('ticket_id', $request->input('ticket_id'));
        }

        if ($request->has('http_status')) {
            $query->where('http_status', $request->input('http_status'));
        }

        $logs = $query->paginate($request->input('per_page', 25));

        return $this->success($logs);
    }

    public function show(int $id): JsonResponse
    {
        $log = AiUsageLog::with('ticket:id,title', 'promptHistory')->find($id);

        if (! $log) {
            return $this->notFound('Log entry not found');
        }

        return $this->success($log);
    }

    public function summary(): JsonResponse
    {
        $today = AiUsageLog::today();

        return $this->success([
            'total_logs' => AiUsageLog::count(),
            'today_logs' => $today->count(),
            'error_logs_today' => $today->clone()->errorsOnly()->count(),
            'retried_today' => $today->clone()->retried()->count(),
            'error_types_today' => $today->clone()
                ->whereNotNull('error_type')
                ->select('error_type', \DB::raw('COUNT(*) as count'))
                ->groupBy('error_type')
                ->orderByDesc('count')
                ->get(),
            'by_operation_today' => $today->clone()
                ->whereNotNull('operation')
                ->select('operation', \DB::raw('COUNT(*) as count'), \DB::raw('AVG(duration_ms) as avg_ms'))
                ->groupBy('operation')
                ->orderByDesc('count')
                ->get(),
        ]);
    }
}
