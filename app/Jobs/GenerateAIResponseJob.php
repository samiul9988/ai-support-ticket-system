<?php

namespace App\Jobs;

use App\Jobs\Middleware\PreventDuplicateAIJobs;
use App\Jobs\Middleware\RateLimitAIJobs;
use App\Models\Ticket;
use App\Services\AI\Contracts\AIServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateAIResponseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array|int $backoff = [30, 60, 120];

    public ?int $maxExceptions = 3;

    public int $timeout = 45;

    public bool $failOnTimeout = true;

    public bool $deleteWhenMissingModels = true;

    public function __construct(
        public Ticket $ticket,
        public string $message,
        public bool $isInitialAnalysis = false,
    ) {
        $this->onQueue('ai-responses');
    }

    public function middleware(): array
    {
        return [
            new RateLimitAIJobs,
            new PreventDuplicateAIJobs(
                $this->ticket,
                $this->isInitialAnalysis ? 'analysis' : 'response'
            ),
        ];
    }

    public function uniqueId(): string
    {
        return 'ai-' . $this->ticket->id . '-' . ($this->isInitialAnalysis ? 'a' : 'r');
    }

    public function handle(AIServiceInterface $aiService): void
    {
        try {
            if ($this->isInitialAnalysis) {
                $this->performInitialAnalysis($aiService);
            } else {
                $this->generateAiResponse($aiService);
            }
        } catch (\App\Exceptions\AIException $e) {
            $this->handleAIException($e);
        } catch (\Throwable $e) {
            $this->handleGeneralException($e);
        }
    }

    protected function performInitialAnalysis(AIServiceInterface $aiService): void
    {
        Log::info('AI analysis job started', [
            'ticket_id' => $this->ticket->id,
            'ticket_title' => $this->ticket->title,
            'attempt' => $this->attempts(),
        ]);

        $analysis = $aiService->analyzeTicket(
            $this->ticket->title,
            $this->message
        );

        $this->ticket->update(['ai_context' => $analysis]);

        $this->storeClassification($analysis);

        $this->autoApplyCategory($analysis);

        Log::info('AI analysis completed', [
            'ticket_id' => $this->ticket->id,
            'category' => $analysis['suggested_category'] ?? 'unknown',
            'confidence' => $analysis['category_confidence'] ?? 0,
            'priority' => $analysis['suggested_priority'] ?? 'unknown',
            'sentiment' => $analysis['sentiment'] ?? 'unknown',
        ]);
    }

    protected function storeClassification(array $analysis): void
    {
        $category = $analysis['suggested_category'] ?? null;

        if (! $category) {
            return;
        }

        $this->ticket->classifications()->create([
            'category' => $category,
            'confidence' => $analysis['category_confidence'] ?? 0.0,
            'reasoning' => $analysis['category_reasoning'] ?? null,
            'model' => config('gemini.providers.gemini.model'),
            'is_auto_applied' => ($analysis['category_confidence'] ?? 0) >= 0.70,
        ]);

        Log::info('Ticket classification stored', [
            'ticket_id' => $this->ticket->id,
            'category' => $category,
            'confidence' => $analysis['category_confidence'] ?? 0,
        ]);
    }

    protected function autoApplyCategory(array $analysis): void
    {
        $confidence = $analysis['category_confidence'] ?? 0;
        $category = $analysis['suggested_category'] ?? null;

        if (! $category || $confidence < 0.70) {
            Log::info('Classification confidence too low for auto-assign', [
                'ticket_id' => $this->ticket->id,
                'confidence' => $confidence,
            ]);

            return;
        }

        $categoryModel = \App\Models\TicketCategory::where('slug', $category)->first();

        if ($categoryModel) {
            $this->ticket->update(['category_id' => $categoryModel->id]);

            Log::info('Ticket category auto-assigned', [
                'ticket_id' => $this->ticket->id,
                'category' => $category,
                'category_id' => $categoryModel->id,
                'confidence' => $confidence,
            ]);
        } else {
            Log::info('No matching TicketCategory found for auto-assign', [
                'ticket_id' => $this->ticket->id,
                'suggested_category' => $category,
            ]);
        }
    }

    protected function generateAiResponse(AIServiceInterface $aiService): void
    {
        Log::info('AI auto-reply job started', [
            'ticket_id' => $this->ticket->id,
            'ticket_title' => $this->ticket->title,
            'attempt' => $this->attempts(),
        ]);

        $previousReplies = $this->ticket->replies()
            ->orderBy('created_at')
            ->limit(10)
            ->get()
            ->map(fn ($reply) => ($reply->is_ai_generated ? 'AI' : $reply->user?->name ?? 'Customer') . ': ' . $reply->content)
            ->toArray();

        $knowledgeArticles = \App\Models\KnowledgeArticle::published()
            ->where(function ($query) {
                $query->where('category_id', $this->ticket->category_id)
                      ->orWhereNull('category_id');
            })
            ->limit(5)
            ->get()
            ->map(fn ($a) => '[' . $a->title . '] ' . $a->content)
            ->toArray();

        $result = $aiService->generateResponse(
            $this->ticket->title,
            $this->ticket->description,
            $this->message,
            [
                'ticket_id' => $this->ticket->id,
                'previous_replies' => $previousReplies,
                'knowledge_base' => $knowledgeArticles,
            ]
        );

        $this->ticket->replies()->create([
            'user_id' => null,
            'content' => $result['text'],
            'is_ai_generated' => true,
            'reply_type' => 'ai',
        ]);

        Log::info('AI auto-reply saved', [
            'ticket_id' => $this->ticket->id,
            'tokens' => $result['tokens']['total_tokens'] ?? 0,
            'duration_ms' => $result['duration_ms'] ?? 0,
            'response_length' => strlen($result['text']),
        ]);
    }

    protected function handleAIException(\App\Exceptions\AIException $e): void
    {
        $context = $e->context();
        $errorType = $context['error_type'] ?? 'unknown';

        Log::error('AI job failed with AIException', [
            'ticket_id' => $this->ticket->id,
            'error' => $e->getMessage(),
            'error_type' => $errorType,
            'attempt' => $this->attempts(),
            'max_attempts' => $this->tries,
        ]);

        if ($this->shouldRetry($errorType)) {
            $delay = $this->backoff[$this->attempts() - 1] ?? 120;
            Log::info('AI job scheduled for retry', [
                'ticket_id' => $this->ticket->id,
                'delay_seconds' => $delay,
                'error_type' => $errorType,
            ]);
            $this->release($delay);
        } else {
            Log::critical('AI job permanently failed (non-retryable)', [
                'ticket_id' => $this->ticket->id,
                'error_type' => $errorType,
            ]);
            $this->fail($e);
        }
    }

    protected function handleGeneralException(\Throwable $e): void
    {
        Log::error('AI job failed with unexpected error', [
            'ticket_id' => $this->ticket->id,
            'error' => $e->getMessage(),
            'attempt' => $this->attempts(),
        ]);

        if ($this->attempts() < $this->tries) {
            $delay = $this->backoff[$this->attempts() - 1] ?? 120;
            $this->release($delay);
        } else {
            $this->fail($e);
        }
    }

    protected function shouldRetry(string $errorType): bool
    {
        if ($this->attempts() >= $this->tries) {
            return false;
        }

        $retryable = ['rate_limit', 'timeout', 'service_unavailable'];

        if (in_array($errorType, $retryable)) {
            return true;
        }

        $nonRetryable = ['configuration', 'content_filtered', 'circuit_open'];

        if (in_array($errorType, $nonRetryable)) {
            return false;
        }

        return true;
    }

    public function failed(?\Throwable $e): void
    {
        Log::critical('AI job permanently failed and moved to failed_jobs', [
            'ticket_id' => $this->ticket->id,
            'is_analysis' => $this->isInitialAnalysis,
            'error' => $e?->getMessage(),
        ]);
    }
}
