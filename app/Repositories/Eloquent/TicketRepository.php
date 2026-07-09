<?php

namespace App\Repositories\Eloquent;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Repositories\Contracts\TicketRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TicketRepository implements TicketRepositoryInterface
{
    protected function baseQuery(array $filters = [])
    {
        $query = Ticket::query()
            ->with(['user', 'assignedAgent', 'category']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (! empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['sort_by'])) {
            $direction = $filters['sort_dir'] ?? 'desc';
            $query->orderBy($filters['sort_by'], $direction);
        } else {
            $query->latest();
        }

        return $query;
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->baseQuery($filters)->paginate($perPage);
    }

    public function find(int $id): ?Ticket
    {
        return Ticket::with([
            'user', 'assignedAgent', 'category',
            'replies.user', 'attachments.user', 'statusHistory.changedBy',
        ])->find($id);
    }

    public function findOrFail(int $id): Ticket
    {
        return Ticket::with([
            'user', 'assignedAgent', 'category',
            'replies.user', 'attachments.user', 'statusHistory.changedBy',
        ])->findOrFail($id);
    }

    public function create(array $data): Ticket
    {
        return DB::transaction(function () use ($data) {
            $ticket = Ticket::create(array_merge([
                'source' => 'web',
                'status' => TicketStatus::OPEN->value,
                'priority' => TicketPriority::MEDIUM->value,
            ], $data));

            $ticket->statusHistory()->create([
                'from_status' => null,
                'to_status' => $ticket->status->value,
                'changed_by' => $data['user_id'] ?? null,
                'note' => 'Ticket created',
            ]);

            $ticket->load(['user', 'category']);
            return $ticket;
        });
    }

    public function update(Ticket $ticket, array $data): Ticket
    {
        return DB::transaction(function () use ($ticket, $data) {
            $oldPriority = $ticket->priority;

            $ticket->update($data);
            $ticket->refresh();

            if (isset($data['priority']) && $data['priority'] !== $oldPriority?->value) {
                $ticket->statusHistory()->create([
                    'from_status' => $ticket->status->value,
                    'to_status' => $ticket->status->value,
                    'changed_by' => auth()->id(),
                    'note' => "Priority changed from {$oldPriority?->value} to {$data['priority']}",
                ]);
            }

            $ticket->load(['user', 'assignedAgent', 'category']);
            return $ticket;
        });
    }

    public function delete(Ticket $ticket): bool
    {
        return DB::transaction(function () use ($ticket) {
            return $ticket->delete() ?? false;
        });
    }

    public function assignAgent(Ticket $ticket, int $agentId): Ticket
    {
        return DB::transaction(function () use ($ticket, $agentId) {
            $oldStatus = $ticket->status;
            $newStatus = $ticket->isOpen() ? TicketStatus::IN_PROGRESS : $ticket->status;

            $ticket->update([
                'assigned_to' => $agentId,
                'status' => $newStatus,
            ]);

            $ticket->statusHistory()->create([
                'from_status' => $oldStatus->value,
                'to_status' => $newStatus->value,
                'changed_by' => auth()->id(),
                'note' => "Ticket assigned to agent #{$agentId}",
            ]);

            $ticket->refresh();
            $ticket->load(['assignedAgent']);
            return $ticket;
        });
    }

    public function changeStatus(Ticket $ticket, TicketStatus $status, ?string $note = null): Ticket
    {
        return DB::transaction(function () use ($ticket, $status, $note) {
            $oldStatus = $ticket->status;

            $data = ['status' => $status];

            if ($status === TicketStatus::RESOLVED) {
                $data['resolved_at'] = now();
            }

            if ($status === TicketStatus::CLOSED) {
                $data['closed_at'] = now();
            }

            if ($status === TicketStatus::OPEN) {
                $data['resolved_at'] = null;
                $data['closed_at'] = null;
            }

            $ticket->update($data);

            $ticket->statusHistory()->create([
                'from_status' => $oldStatus->value,
                'to_status' => $status->value,
                'changed_by' => auth()->id(),
                'note' => $note ?? "Status changed from {$oldStatus->value} to {$status->value}",
            ]);

            $ticket->refresh();
            return $ticket;
        });
    }

    public function changePriority(Ticket $ticket, TicketPriority $priority): Ticket
    {
        return DB::transaction(function () use ($ticket, $priority) {
            $oldPriority = $ticket->priority;

            $ticket->update(['priority' => $priority]);

            $ticket->statusHistory()->create([
                'from_status' => $ticket->status->value,
                'to_status' => $ticket->status->value,
                'changed_by' => auth()->id(),
                'note' => "Priority changed from {$oldPriority->value} to {$priority->value}",
            ]);

            $ticket->refresh();
            return $ticket;
        });
    }

    public function getByCustomer(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->baseQuery($filters)
            ->where('user_id', $userId)
            ->paginate($perPage);
    }

    public function getByAgent(int $agentId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->baseQuery($filters)
            ->where('assigned_to', $agentId)
            ->paginate($perPage);
    }

    public function getUnassigned(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->baseQuery($filters)
            ->whereNull('assigned_to')
            ->whereNotIn('status', [TicketStatus::CLOSED->value])
            ->paginate($perPage);
    }

    public function getStats(): Collection
    {
        return collect([
            'total' => Ticket::count(),
            'open' => Ticket::open()->count(),
            'in_progress' => Ticket::inProgress()->count(),
            'resolved' => Ticket::resolved()->count(),
            'closed' => Ticket::closed()->count(),
            'urgent' => Ticket::byPriority(TicketPriority::URGENT)->whereNotIn('status', ['closed'])->count(),
            'high' => Ticket::byPriority(TicketPriority::HIGH)->whereNotIn('status', ['closed'])->count(),
            'unassigned' => Ticket::whereNull('assigned_to')->whereNotIn('status', ['closed'])->count(),
            'resolved_today' => Ticket::whereDate('resolved_at', today())->count(),
            'created_today' => Ticket::whereDate('created_at', today())->count(),
        ]);
    }
}
