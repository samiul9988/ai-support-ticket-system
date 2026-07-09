<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\TicketSentiment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    public function widgets(): array
    {
        return [
            'todays_tickets' => $this->todaysTickets(),
            'pending_tickets' => $this->pendingTickets(),
            'resolved_tickets' => $this->resolvedTickets(),
            'ai_generated_responses' => $this->aiGeneratedResponses(),
            'average_resolution_time' => $this->averageResolutionTime(),
            'customer_satisfaction' => $this->customerSatisfaction(),
            'top_agents' => $this->topAgents(),
        ];
    }

    public function todaysTickets(): array
    {
        $today = Ticket::whereDate('created_at', today());

        return [
            'total' => $today->count(),
            'open' => $today->clone()->where('status', 'open')->count(),
            'in_progress' => $today->clone()->where('status', 'in_progress')->count(),
            'resolved' => $today->clone()->where('status', 'resolved')->count(),
            'closed' => $today->clone()->where('status', 'closed')->count(),
        ];
    }

    public function pendingTickets(): array
    {
        $pending = Ticket::whereIn('status', ['open', 'in_progress']);

        return [
            'total' => $pending->count(),
            'unassigned' => Ticket::where('status', 'open')->whereNull('assigned_to')->count(),
            'urgent' => Ticket::whereIn('status', ['open', 'in_progress'])
                ->where('priority', 'urgent')->count(),
            'high' => Ticket::whereIn('status', ['open', 'in_progress'])
                ->where('priority', 'high')->count(),
            'overdue' => Ticket::whereIn('status', ['open', 'in_progress'])
                ->where('created_at', '<', now()->subHours(24))->count(),
        ];
    }

    public function resolvedTickets(): array
    {
        return [
            'total' => Ticket::where('status', 'resolved')->count(),
            'today' => Ticket::where('status', 'resolved')
                ->whereDate('resolved_at', today())->count(),
            'this_week' => Ticket::where('status', 'resolved')
                ->where('resolved_at', '>=', now()->subWeek())->count(),
            'this_month' => Ticket::where('status', 'resolved')
                ->where('resolved_at', '>=', now()->subMonth())->count(),
        ];
    }

    public function aiGeneratedResponses(): array
    {
        $aiReplies = TicketReply::where('is_ai_generated', true);

        return [
            'total' => $aiReplies->count(),
            'today' => TicketReply::where('is_ai_generated', true)
                ->whereDate('created_at', today())->count(),
            'this_week' => TicketReply::where('is_ai_generated', true)
                ->where('created_at', '>=', now()->subWeek())->count(),
            'distinct_tickets' => TicketReply::where('is_ai_generated', true)
                ->distinct('ticket_id')->count('ticket_id'),
        ];
    }

    public function averageResolutionTime(): array
    {
        $resolved = Ticket::whereNotNull('resolved_at');

        $avgHours = $resolved->count() > 0
            ? round($resolved->select(DB::raw(
                'AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_minutes'
            ))->value('avg_minutes') / 60, 1)
            : 0;

        return [
            'avg_hours' => $avgHours,
            'avg_hours_today' => $this->avgResolutionTimeForPeriod('today'),
            'avg_hours_week' => $this->avgResolutionTimeForPeriod('week'),
            'avg_hours_month' => $this->avgResolutionTimeForPeriod('month'),
            'resolved_count' => $resolved->count(),
        ];
    }

    protected function avgResolutionTimeForPeriod(string $period): float
    {
        $query = Ticket::whereNotNull('resolved_at');

        match ($period) {
            'today' => $query->whereDate('resolved_at', today()),
            'week' => $query->where('resolved_at', '>=', now()->subWeek()),
            'month' => $query->where('resolved_at', '>=', now()->subMonth()),
            default => null,
        };

        $count = $query->count();

        if ($count === 0) {
            return 0;
        }

        $avgMinutes = $query->select(DB::raw(
            'AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg'
        ))->value('avg');

        return round((float) ($avgMinutes / 60), 1);
    }

    public function customerSatisfaction(): array
    {
        $sentiments = TicketSentiment::select(
            'sentiment',
            DB::raw('COUNT(*) as count'),
            DB::raw('AVG(confidence) as avg_confidence'),
        )
            ->groupBy('sentiment')
            ->orderByDesc('count')
            ->get();

        $total = $sentiments->sum('count');

        $positive = $sentiments->firstWhere('sentiment', 'happy')?->count ?? 0;
        $negative = $sentiments->firstWhere('sentiment', 'angry')?->count ?? 0;
        $urgent = $sentiments->firstWhere('sentiment', 'urgent')?->count ?? 0;
        $confused = $sentiments->firstWhere('sentiment', 'confused')?->count ?? 0;

        if ($total > 0) {
            $satisfactionScore = round((($positive * 100) + ($confused * 50) + ($negative * 0) + ($urgent * 25)) / $total, 1);
        } else {
            $satisfactionScore = 0;
        }

        return [
            'satisfaction_score' => $satisfactionScore,
            'label' => $this->satisfactionLabel($satisfactionScore),
            'total_analyzed' => $total,
            'distribution' => [
                'happy' => $positive,
                'neutral' => $sentiments->firstWhere('sentiment', 'neutral')?->count ?? 0,
                'confused' => $confused,
                'angry' => $negative,
                'urgent' => $urgent,
            ],
            'percentages' => $total > 0 ? [
                'happy' => round($positive / $total * 100, 1),
                'neutral' => round(($sentiments->firstWhere('sentiment', 'neutral')?->count ?? 0) / $total * 100, 1),
                'confused' => round($confused / $total * 100, 1),
                'angry' => round($negative / $total * 100, 1),
                'urgent' => round($urgent / $total * 100, 1),
            ] : [],
        ];
    }

    protected function satisfactionLabel(float $score): string
    {
        return match (true) {
            $score >= 80 => 'Excellent',
            $score >= 60 => 'Good',
            $score >= 40 => 'Fair',
            $score >= 20 => 'Poor',
            default => 'Critical',
        };
    }

    public function topAgents(int $limit = 5): array
    {
        $agents = DB::table('users')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                DB::raw('COUNT(DISTINCT tickets.id) as tickets_assigned'),
                DB::raw('SUM(CASE WHEN tickets.status = "resolved" THEN 1 ELSE 0 END) as tickets_resolved'),
                DB::raw('SUM(CASE WHEN tickets.status = "closed" THEN 1 ELSE 0 END) as tickets_closed'),
                DB::raw('AVG(CASE WHEN tickets.resolved_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, tickets.created_at, tickets.resolved_at) END) as avg_resolution_minutes'),
            )
            ->join('tickets', 'tickets.assigned_to', '=', 'users.id')
            ->whereNotNull('users.role_id')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('tickets_resolved')
            ->limit($limit)
            ->get()
            ->map(function ($agent) {
                return [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'email' => $agent->email,
                    'tickets_assigned' => (int) $agent->tickets_assigned,
                    'tickets_resolved' => (int) $agent->tickets_resolved,
                    'tickets_closed' => (int) $agent->tickets_closed,
                    'avg_resolution_hours' => $agent->avg_resolution_minutes
                        ? round((float) $agent->avg_resolution_minutes / 60, 1)
                        : null,
                    'resolution_rate' => $agent->tickets_assigned > 0
                        ? round(((int) $agent->tickets_resolved / (int) $agent->tickets_assigned) * 100, 1)
                        : 0,
                ];
            });

        return [
            'agents' => $agents,
            'total_agents_with_tickets' => $agents->count(),
        ];
    }

    public function statusDistribution(): array
    {
        $statuses = Ticket::select(
            'status',
            DB::raw('COUNT(*) as count'),
        )
            ->groupBy('status')
            ->get();

        return [
            'open' => $statuses->firstWhere('status', 'open')?->count ?? 0,
            'in_progress' => $statuses->firstWhere('status', 'in_progress')?->count ?? 0,
            'resolved' => $statuses->firstWhere('status', 'resolved')?->count ?? 0,
            'closed' => $statuses->firstWhere('status', 'closed')?->count ?? 0,
            'total' => $statuses->sum('count'),
        ];
    }

    public function priorityDistribution(): array
    {
        return [
            'low' => Ticket::where('priority', 'low')->count(),
            'medium' => Ticket::where('priority', 'medium')->count(),
            'high' => Ticket::where('priority', 'high')->count(),
            'urgent' => Ticket::where('priority', 'urgent')->count(),
        ];
    }

    public function dailyTrend(int $days = 7): Collection
    {
        return Ticket::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as created'),
            DB::raw('SUM(CASE WHEN status IN ("resolved","closed") THEN 1 ELSE 0 END) as resolved'),
            DB::raw('SUM(CASE WHEN status IN ("open","in_progress") THEN 1 ELSE 0 END) as pending'),
        )
            ->where('created_at', '>=', now()->subDays($days)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    public function aiResponseTrend(int $days = 7): Collection
    {
        return TicketReply::where('is_ai_generated', true)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
            )
            ->where('created_at', '>=', now()->subDays($days)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }
}
