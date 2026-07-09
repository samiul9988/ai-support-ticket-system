<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Jobs\GenerateAIResponseJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class GenerateAIResponse implements ShouldQueue
{
    public function handle(TicketCreated $event): void
    {
        GenerateAIResponseJob::dispatch(
            ticket: $event->ticket,
            message: $event->ticket->description,
            isInitialAnalysis: true,
        );

        GenerateAIResponseJob::dispatch(
            ticket: $event->ticket,
            message: $event->ticket->description,
            isInitialAnalysis: false,
        );
    }
}
