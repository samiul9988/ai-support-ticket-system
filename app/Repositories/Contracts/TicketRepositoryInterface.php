<?php

namespace App\Repositories\Contracts;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface TicketRepositoryInterface
{
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?Ticket;

    public function findOrFail(int $id): Ticket;

    public function create(array $data): Ticket;

    public function update(Ticket $ticket, array $data): Ticket;

    public function delete(Ticket $ticket): bool;

    public function assignAgent(Ticket $ticket, int $agentId): Ticket;

    public function changeStatus(Ticket $ticket, TicketStatus $status, ?string $note = null): Ticket;

    public function changePriority(Ticket $ticket, TicketPriority $priority): Ticket;

    public function getByCustomer(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function getByAgent(int $agentId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function getUnassigned(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function getStats(): Collection;
}
