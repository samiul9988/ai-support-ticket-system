<?php

namespace App\Repositories\Contracts;

use App\Models\TicketReply;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TicketReplyRepositoryInterface
{
    public function paginateByTicket(int $ticketId, int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?TicketReply;

    public function create(array $data): TicketReply;
}
