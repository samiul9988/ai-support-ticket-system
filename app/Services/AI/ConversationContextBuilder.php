<?php

namespace App\Services\AI;

use App\Models\Ticket;
use Illuminate\Support\Facades\Log;

class ConversationContextBuilder
{
    protected int $maxMessages;

    protected bool $includeTimestamps;

    public function __construct(int $maxMessages = 10, bool $includeTimestamps = false)
    {
        $this->maxMessages = $maxMessages;
        $this->includeTimestamps = $includeTimestamps;
    }

    public function build(Ticket $ticket): array
    {
        $replies = $ticket->replies()
            ->with('user')
            ->orderBy('created_at')
            ->limit($this->maxMessages)
            ->get();

        if ($replies->isEmpty()) {
            Log::debug('ConversationContext: No replies found', [
                'ticket_id' => $ticket->id,
            ]);
        }

        $history = $replies->map(function ($reply) {
            return $this->formatMessage($reply);
        })->toArray();

        Log::debug('ConversationContext built', [
            'ticket_id' => $ticket->id,
            'message_count' => count($history),
            'max_messages' => $this->maxMessages,
        ]);

        return $history;
    }

    public function buildFormatted(Ticket $ticket): array
    {
        $structured = [];
        $replies = $ticket->replies()
            ->with('user')
            ->orderBy('created_at')
            ->limit($this->maxMessages)
            ->get();

        foreach ($replies as $reply) {
            $structured[] = [
                'id' => $reply->id,
                'role' => $this->detectRole($reply),
                'author' => $reply->user?->name ?? ($reply->is_ai_generated ? 'AI Assistant' : 'Customer'),
                'content' => $reply->content,
                'timestamp' => $reply->created_at?->toIso8601String(),
            ];
        }

        return $structured;
    }

    public function buildText(Ticket $ticket): string
    {
        $history = $this->build($ticket);

        if (empty($history)) {
            return '';
        }

        $lines = [];
        foreach ($history as $i => $msg) {
            $num = $i + 1;
            $lines[] = "{$num}. {$msg}";
        }

        return implode("\n", $lines);
    }

    public function buildAiPromptContext(Ticket $ticket): array
    {
        $history = $this->build($ticket);

        if (empty($history)) {
            return [];
        }

        return [$history];
    }

    public function buildSummary(Ticket $ticket): string
    {
        $history = $this->build($ticket);

        if (empty($history)) {
            return 'This is the first interaction on this ticket. No conversation history yet.';
        }

        $count = count($history);
        $firstMsg = $history[0] ?? '';
        $lastMsg = $history[$count - 1] ?? '';

        $summary = "Conversation history ({$count} messages):\n";
        $summary .= "Latest message: {$lastMsg}\n";

        if ($count > 1) {
            $summary .= "First message: {$firstMsg}\n";
        }

        return $summary;
    }

    public function isConversationSubstantial(Ticket $ticket): bool
    {
        $count = $ticket->replies()
            ->where('is_ai_generated', false)
            ->count();

        return $count >= 2;
    }

    public function getMaxMessages(): int
    {
        return $this->maxMessages;
    }

    protected function formatMessage($reply): string
    {
        $author = match (true) {
            $reply->is_ai_generated => 'AI',
            $reply->reply_type === 'system' => 'System',
            default => $reply->user?->name ?? 'Customer',
        };

        $message = "{$author}: {$reply->content}";

        if ($this->includeTimestamps && $reply->created_at) {
            $time = $reply->created_at->format('H:i');
            $message = "[{$time}] {$message}";
        }

        return $message;
    }

    protected function detectRole($reply): string
    {
        if ($reply->is_ai_generated) {
            return 'ai';
        }

        if ($reply->reply_type === 'system') {
            return 'system';
        }

        $user = $reply->user;

        if ($user?->isAdmin() || $user?->isAgent()) {
            return 'agent';
        }

        return 'customer';
    }
}
