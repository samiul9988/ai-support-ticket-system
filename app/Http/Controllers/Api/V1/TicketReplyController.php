<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\TicketException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\StoreTicketReplyRequest;
use App\Http\Resources\TicketReplyResource;
use App\Services\TicketService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketReplyController extends Controller
{
    use ApiResponse;

    public function __construct(protected TicketService $ticketService) {}

    public function index(int $ticketId, Request $request): JsonResponse
    {
        $ticket = $this->ticketService->find($ticketId);

        if ($request->user()->cannot('view', $ticket)) {
            return $this->forbidden();
        }

        $replies = $this->ticketService->getReplies($ticketId);

        return $this->success(TicketReplyResource::collection($replies));
    }

    public function store(int $ticketId, StoreTicketReplyRequest $request): JsonResponse
    {
        $ticket = $this->ticketService->find($ticketId);

        if ($request->user()->cannot('reply', $ticket)) {
            return $this->forbidden();
        }

        try {
            $data = $request->validated();
            $data['user_id'] = $request->user()->id;

            $reply = $this->ticketService->addReply($ticket, $data);

            return $this->created(new TicketReplyResource($reply), 'Reply added successfully');
        } catch (TicketException $e) {
            return $this->error($e->getMessage(), $e->getCode());
        }
    }
}
