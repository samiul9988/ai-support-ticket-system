<?php

namespace App\Repositories\Eloquent;

use App\Models\TicketReply;
use App\Repositories\Contracts\TicketReplyRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TicketReplyRepository implements TicketReplyRepositoryInterface
{
    public function paginateByTicket(int $ticketId, int $perPage = 15): LengthAwarePaginator
    {
        return TicketReply::with('user')
            ->where('ticket_id', $ticketId)
            ->latest()
            ->paginate($perPage);
    }

    public function find(int $id): ?TicketReply
    {
        return TicketReply::with(['user', 'attachments'])->find($id);
    }

    public function create(array $data): TicketReply
    {
        return DB::transaction(function () use ($data) {
            $reply = TicketReply::create($data);
            $reply->load('user');

            return $reply;
        });
    }
}
