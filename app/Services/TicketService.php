<?php

namespace App\Services;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Events\TicketCreated;
use App\Events\TicketReplied;
use App\Exceptions\TicketException;
use App\Models\Ticket;
use App\Repositories\Contracts\TicketRepositoryInterface;
use App\Repositories\Contracts\TicketReplyRepositoryInterface;
use App\Services\AI\Contracts\AIServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class TicketService
{
    public function __construct(
        protected TicketRepositoryInterface $ticketRepository,
        protected TicketReplyRepositoryInterface $replyRepository,
        protected AIServiceInterface $aiService,
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        return $this->ticketRepository->paginate($filters);
    }

    public function find(int $id): Ticket
    {
        $ticket = $this->ticketRepository->find($id);

        if (! $ticket) {
            throw TicketException::notFound();
        }

        return $ticket;
    }

    public function findOrFail(int $id): Ticket
    {
        return $this->ticketRepository->findOrFail($id);
    }

    public function create(array $data): Ticket
    {
        $ticket = $this->ticketRepository->create($data);

        Log::info('Ticket created', [
            'ticket_id' => $ticket->id,
            'user_id' => $ticket->user_id,
        ]);

        event(new TicketCreated($ticket));

        return $ticket;
    }

    public function update(Ticket $ticket, array $data): Ticket
    {
        if ($ticket->isClosed()) {
            throw TicketException::cannotModifyClosed();
        }

        return $this->ticketRepository->update($ticket, $data);
    }

    public function delete(Ticket $ticket): bool
    {
        return $this->ticketRepository->delete($ticket);
    }

    public function assignAgent(Ticket $ticket, int $agentId): Ticket
    {
        if ($ticket->isClosed()) {
            throw TicketException::cannotModifyClosed();
        }

        return $this->ticketRepository->assignAgent($ticket, $agentId);
    }

    public function changeStatus(Ticket $ticket, TicketStatus $status, ?string $note = null): Ticket
    {
        $this->validateStatusTransition($ticket, $status);

        return $this->ticketRepository->changeStatus($ticket, $status, $note);
    }

    public function closeTicket(Ticket $ticket, ?string $note = null): Ticket
    {
        if ($ticket->isClosed()) {
            throw TicketException::sameStatus(TicketStatus::CLOSED);
        }

        $this->validateStatusTransition($ticket, TicketStatus::CLOSED);

        return $this->ticketRepository->changeStatus($ticket, TicketStatus::CLOSED, $note ?? 'Ticket closed');
    }

    public function changePriority(Ticket $ticket, TicketPriority $priority): Ticket
    {
        if ($ticket->isClosed()) {
            throw TicketException::cannotModifyClosed();
        }

        if ($ticket->priority === $priority) {
            throw TicketException::samePriority($priority);
        }

        return $this->ticketRepository->changePriority($ticket, $priority);
    }

    public function addReply(Ticket $ticket, array $data): mixed
    {
        if ($ticket->isClosed()) {
            throw TicketException::cannotModifyClosed();
        }

        $data['ticket_id'] = $ticket->id;
        $data['is_ai_generated'] = false;

        $reply = $this->replyRepository->create($data);

        if ($ticket->isResolved()) {
            $this->ticketRepository->changeStatus($ticket, TicketStatus::IN_PROGRESS, 'Auto-reopened due to new reply');
        }

        event(new TicketReplied($ticket, $reply));

        return $reply;
    }

    public function getCustomerTickets(int $userId, array $filters = []): LengthAwarePaginator
    {
        return $this->ticketRepository->getByCustomer($userId, $filters);
    }

    public function getAgentTickets(int $agentId, array $filters = []): LengthAwarePaginator
    {
        return $this->ticketRepository->getByAgent($agentId, $filters);
    }

    public function getUnassignedTickets(array $filters = []): LengthAwarePaginator
    {
        return $this->ticketRepository->getUnassigned($filters);
    }

    public function getStats(): Collection
    {
        return $this->ticketRepository->getStats();
    }

    public function getReplies(int $ticketId): LengthAwarePaginator
    {
        return $this->replyRepository->paginateByTicket($ticketId);
    }

    protected function validateStatusTransition(Ticket $ticket, TicketStatus $newStatus): void
    {
        if ($ticket->status === $newStatus) {
            throw TicketException::sameStatus($newStatus);
        }

        $allowedTransitions = [
            TicketStatus::OPEN->value => [
                TicketStatus::IN_PROGRESS->value,
                TicketStatus::CLOSED->value,
            ],
            TicketStatus::IN_PROGRESS->value => [
                TicketStatus::RESOLVED->value,
                TicketStatus::CLOSED->value,
                TicketStatus::OPEN->value,
            ],
            TicketStatus::RESOLVED->value => [
                TicketStatus::CLOSED->value,
                TicketStatus::IN_PROGRESS->value,
            ],
            TicketStatus::CLOSED->value => [
                TicketStatus::OPEN->value,
            ],
        ];

        $allowed = $allowedTransitions[$ticket->status->value] ?? [];

        if (! in_array($newStatus->value, $allowed)) {
            throw TicketException::invalidTransition($ticket->status, $newStatus);
        }
    }
}
