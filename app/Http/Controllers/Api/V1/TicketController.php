<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Exceptions\TicketException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\AssignTicketRequest;
use App\Http\Requests\Ticket\StoreTicketRequest;
use App\Http\Requests\Ticket\UpdateTicketPriorityRequest;
use App\Http\Requests\Ticket\UpdateTicketRequest;
use App\Http\Requests\Ticket\UpdateTicketStatusRequest;
use App\Http\Resources\TicketResource;
use App\Services\TicketService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    use ApiResponse;

    public function __construct(protected TicketService $ticketService) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $filters = $request->only([
            'status', 'priority', 'category_id', 'assigned_to',
            'search', 'date_from', 'date_to', 'sort_by', 'sort_dir',
        ]);

        if ($request->boolean('unassigned') && ! $user->isCustomer()) {
            $tickets = $this->ticketService->getUnassignedTickets($filters);
        } elseif ($user->isCustomer()) {
            $tickets = $this->ticketService->getCustomerTickets($user->id, $filters);
        } elseif ($user->isAgent()) {
            $tickets = $this->ticketService->getAgentTickets($user->id, $filters);
        } else {
            $tickets = $this->ticketService->list($filters);
        }

        return $this->success(TicketResource::collection($tickets));
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        if ($request->user()->cannot('create', \App\Models\Ticket::class)) {
            return $this->forbidden();
        }

        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $data['source'] = $data['source'] ?? 'web';
        $data['ip_address'] = $request->ip();
        $data['user_agent'] = $request->userAgent();

        $ticket = $this->ticketService->create($data);

        return $this->created(new TicketResource($ticket), 'Ticket created successfully');
    }

    public function show(int $id, Request $request): JsonResponse
    {
        $ticket = $this->ticketService->findOrFail($id);

        if ($request->user()->cannot('view', $ticket)) {
            return $this->forbidden();
        }

        $ticket->load('statusHistory.changedBy');

        return $this->success(new TicketResource($ticket));
    }

    public function update(int $id, UpdateTicketRequest $request): JsonResponse
    {
        $ticket = $this->ticketService->find($id);

        if ($request->user()->cannot('update', $ticket)) {
            return $this->forbidden();
        }

        try {
            $ticket = $this->ticketService->update($ticket, $request->validated());

            return $this->success(new TicketResource($ticket), 'Ticket updated successfully');
        } catch (TicketException $e) {
            return $this->error($e->getMessage(), $e->getCode());
        }
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        $ticket = $this->ticketService->find($id);

        if ($request->user()->cannot('delete', $ticket)) {
            return $this->forbidden();
        }

        $this->ticketService->delete($ticket);

        return $this->success(null, 'Ticket deleted successfully');
    }

    public function assign(int $id, AssignTicketRequest $request): JsonResponse
    {
        $ticket = $this->ticketService->find($id);

        if ($request->user()->cannot('assignAgent', $ticket)) {
            return $this->forbidden();
        }

        try {
            $ticket = $this->ticketService->assignAgent($ticket, $request->input('agent_id'));

            return $this->success(new TicketResource($ticket), 'Agent assigned successfully');
        } catch (TicketException $e) {
            return $this->error($e->getMessage(), $e->getCode());
        }
    }

    public function changeStatus(int $id, UpdateTicketStatusRequest $request): JsonResponse
    {
        $ticket = $this->ticketService->find($id);

        if ($request->user()->cannot('changeStatus', $ticket)) {
            return $this->forbidden();
        }

        try {
            $status = TicketStatus::from($request->input('status'));
            $note = $request->input('note');
            $ticket = $this->ticketService->changeStatus($ticket, $status, $note);

            $message = $status === TicketStatus::CLOSED
                ? 'Ticket closed successfully'
                : 'Status updated successfully';

            return $this->success(new TicketResource($ticket), $message);
        } catch (TicketException $e) {
            return $this->error($e->getMessage(), $e->getCode());
        }
    }

    public function changePriority(int $id, UpdateTicketPriorityRequest $request): JsonResponse
    {
        $ticket = $this->ticketService->find($id);

        if ($request->user()->cannot('changePriority', $ticket)) {
            return $this->forbidden();
        }

        try {
            $priority = TicketPriority::from($request->input('priority'));
            $ticket = $this->ticketService->changePriority($ticket, $priority);

            return $this->success(new TicketResource($ticket), 'Priority updated successfully');
        } catch (TicketException $e) {
            return $this->error($e->getMessage(), $e->getCode());
        }
    }

    public function stats(Request $request): JsonResponse
    {
        if ($request->user()->cannot('viewAny', \App\Models\Ticket::class)) {
            return $this->forbidden();
        }

        return $this->success($this->ticketService->getStats());
    }

    public function insights(int $id, Request $request): JsonResponse
    {
        $ticket = $this->ticketService->findOrFail($id);

        if ($request->user()->cannot('view', $ticket)) {
            return $this->forbidden();
        }

        if ($request->user()->isCustomer()) {
            return $this->forbidden('AI insights are only available for admins and agents.');
        }

        $insights = $this->ticketService->getAiInsights($ticket);

        return $this->success($insights, 'AI insights generated successfully');
    }

    public function sentiment(int $id, Request $request): JsonResponse
    {
        $ticket = $this->ticketService->findOrFail($id);

        if ($request->user()->cannot('view', $ticket)) {
            return $this->forbidden();
        }

        $sentiments = $ticket->sentiments()
            ->with('reply')
            ->latest()
            ->limit(20)
            ->get();

        $currentSentiment = $ticket->ai_context['current_sentiment'] ?? null;

        return $this->success([
            'current' => $currentSentiment,
            'history' => $sentiments,
            'summary' => [
                'total' => $sentiments->count(),
                'happy' => $sentiments->where('sentiment', 'happy')->count(),
                'neutral' => $sentiments->where('sentiment', 'neutral')->count(),
                'confused' => $sentiments->where('sentiment', 'confused')->count(),
                'angry' => $sentiments->where('sentiment', 'angry')->count(),
                'urgent' => $sentiments->where('sentiment', 'urgent')->count(),
            ],
        ]);
    }
}
