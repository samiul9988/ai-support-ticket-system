<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Models\Ticket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendTicketNotification implements ShouldQueue
{
    public function handle(TicketCreated $event): void
    {
        Log::info('Ticket notification', [
            'ticket_id' => $event->ticket->id,
            'title' => $event->ticket->title,
            'user' => $event->ticket->user?->name,
        ]);
    }
}
