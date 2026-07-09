<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Services\AI\Contracts\AIServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeSentimentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public array|int $backoff = [10, 30];

    public int $timeout = 20;

    public function __construct(
        public Ticket $ticket,
        public TicketReply $reply,
    ) {
        $this->onQueue('ai-responses');
    }

    public function handle(AIServiceInterface $aiService): void
    {
        Log::info('Sentiment analysis job started', [
            'ticket_id' => $this->ticket->id,
            'reply_id' => $this->reply->id,
        ]);

        $ticketContext = "Ticket title: {$this->ticket->title}. ";
        $ticketContext .= "Description: {$this->ticket->description}";

        $result = $aiService->analyzeSentiment(
            userMessage: $this->reply->content,
            ticketContext: $ticketContext,
        );

        $this->ticket->sentiments()->create([
            'ticket_reply_id' => $this->reply->id,
            'sentiment' => $result['sentiment'],
            'confidence' => $result['confidence'],
            'analysis_text' => $result['analysis_text'] ?? null,
            'model' => config('gemini.providers.gemini.model'),
        ]);

        $this->updateTicketContext($result);

        Log::info('Sentiment analysis saved', [
            'ticket_id' => $this->ticket->id,
            'sentiment' => $result['sentiment'],
            'confidence' => $result['confidence'],
            'escalation' => $result['escalation_recommended'] ?? false,
        ]);

        if ($result['escalation_recommended'] ?? false) {
            Log::warning('Sentiment escalation triggered', [
                'ticket_id' => $this->ticket->id,
                'sentiment' => $result['sentiment'],
                'confidence' => $result['confidence'],
            ]);
        }
    }

    protected function updateTicketContext(array $result): void
    {
        $aiContext = $this->ticket->ai_context ?? [];

        $aiContext['current_sentiment'] = [
            'sentiment' => $result['sentiment'],
            'confidence' => $result['confidence'],
            'detected_at' => now()->toIso8601String(),
        ];

        $this->ticket->update(['ai_context' => $aiContext]);
    }
}
