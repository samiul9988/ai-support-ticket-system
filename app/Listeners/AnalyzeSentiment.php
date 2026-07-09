<?php

namespace App\Listeners;

use App\Events\TicketReplied;
use App\Jobs\AnalyzeSentimentJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class AnalyzeSentiment implements ShouldQueue
{
    public function handle(TicketReplied $event): void
    {
        if ($event->reply->is_ai_generated) {
            return;
        }

        AnalyzeSentimentJob::dispatch(
            ticket: $event->ticket,
            reply: $event->reply,
        );
    }
}
