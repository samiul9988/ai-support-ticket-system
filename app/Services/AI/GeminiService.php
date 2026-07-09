<?php

namespace App\Services\AI;

use App\Exceptions\AIException;
use App\Services\AI\Clients\GeminiApiClient;
use App\Services\AI\Contracts\AIServiceInterface;
use Illuminate\Support\Facades\Log;

class GeminiService implements AIServiceInterface
{
    protected GeminiApiClient $client;
    protected ResponseParser $parser;
    protected CircuitBreaker $circuitBreaker;
    protected RetryHandler $retryHandler;
    protected UsageTracker $usageTracker;
    protected PromptBuilder $promptBuilder;

    protected float $temperature;
    protected int $maxOutputTokens;
    protected float $topP;

    public function __construct(
        GeminiApiClient $client,
        ResponseParser $parser,
        CircuitBreaker $circuitBreaker,
        RetryHandler $retryHandler,
        UsageTracker $usageTracker,
        PromptBuilder $promptBuilder,
    ) {
        $this->client = $client;
        $this->parser = $parser;
        $this->circuitBreaker = $circuitBreaker;
        $this->retryHandler = $retryHandler;
        $this->usageTracker = $usageTracker;
        $this->promptBuilder = $promptBuilder;

        $this->temperature = (float) config('gemini.providers.gemini.temperature', 0.7);
        $this->maxOutputTokens = (int) config('gemini.providers.gemini.max_output_tokens', 1024);
        $this->topP = (float) config('gemini.providers.gemini.top_p', 0.95);
    }

    public function generateResponse(
        string $ticketTitle,
        string $ticketDescription,
        string $userMessage,
        array $context = []
    ): array {
        $this->circuitBreaker->isAvailable();

        $this->validateContext($context);

        $hasHistory = ! empty($context['previous_replies']);

        if ($hasHistory) {
            $fullPrompt = $this->promptBuilder->followUpReply(
                ticketTitle: $ticketTitle,
                ticketDescription: $ticketDescription,
                userMessage: $userMessage,
                conversationHistory: $context['previous_replies'] ?? [],
                knowledgeBase: $context['knowledge_base'] ?? [],
            );
        } else {
            $fullPrompt = $this->promptBuilder->initialAutoReply(
                ticketTitle: $ticketTitle,
                ticketDescription: $ticketDescription,
                knowledgeBase: $context['knowledge_base'] ?? [],
            );
        }

        return $this->executeWithTracking(
            prompt: $fullPrompt,
            temperature: $this->temperature,
            maxTokens: $this->maxOutputTokens,
            operation: 'generate_response',
            ticketId: $context['ticket_id'] ?? null,
            promptType: $hasHistory ? 'follow_up_reply' : 'initial_auto_reply',
        );
    }

    public function analyzeTicket(string $title, string $description): array
    {
        $this->circuitBreaker->isAvailable();

        $fullPrompt = $this->promptBuilder->ticketAnalysis($title, $description);

        $startTime = microtime(true);

        try {
            $result = $this->retryHandler->execute(function () use ($fullPrompt) {
                return $this->executeApiCall($fullPrompt, 0.3, 512);
            }, 'analyze_ticket');

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            $parsed = $this->parser->parseJson($result['response']);
            $usage = $this->parser->parseUsageMetadata($result['response']);

            $promptHistoryId = $this->logPrompt('analysis', $fullPrompt, null);

            $this->usageTracker->track(
                promptHistoryId: $promptHistoryId,
                model: $this->client->getModel(),
                promptTokens: $usage['prompt_tokens'],
                completionTokens: $usage['completion_tokens'],
                totalTokens: $usage['total_tokens'],
                durationMs: $durationMs,
                success: true,
            );

            $this->circuitBreaker->recordSuccess();

            Log::info('AI ticket analysis completed', [
                'tokens' => $usage['total_tokens'],
                'duration_ms' => $durationMs,
            ]);

            return array_merge($parsed, ['_usage' => $usage]);
        } catch (\Throwable $e) {
            $this->circuitBreaker->recordFailure();

            Log::error('AI ticket analysis failed', ['error' => $e->getMessage()]);

            return $this->defaultAnalysis();
        }
    }

    public function generateTicketInsights(
        string $ticketTitle,
        string $ticketDescription,
        array $conversationHistory = [],
        array $knowledgeBase = [],
    ): array {
        $this->circuitBreaker->isAvailable();

        $fullPrompt = $this->promptBuilder->ticketInsights(
            ticketTitle: $ticketTitle,
            ticketDescription: $ticketDescription,
            conversationHistory: $conversationHistory,
            knowledgeBase: $knowledgeBase,
        );

        $startTime = microtime(true);

        try {
            $result = $this->retryHandler->execute(function () use ($fullPrompt) {
                return $this->executeApiCall($fullPrompt, 0.4, 1024);
            }, 'generate_insights');

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            $parsed = $this->parser->parseJson($result['response']);
            $usage = $this->parser->parseUsageMetadata($result['response']);

            $promptHistoryId = $this->logPrompt('ticket_insights', $fullPrompt, null);

            $this->usageTracker->track(
                promptHistoryId: $promptHistoryId,
                model: $this->client->getModel(),
                promptTokens: $usage['prompt_tokens'],
                completionTokens: $usage['completion_tokens'],
                totalTokens: $usage['total_tokens'],
                durationMs: $durationMs,
                success: true,
            );

            $this->circuitBreaker->recordSuccess();

            Log::info('AI ticket insights generated', [
                'tokens' => $usage['total_tokens'],
                'duration_ms' => $durationMs,
            ]);

            return array_merge($parsed, [
                'generated_at' => now()->toIso8601String(),
                '_usage' => $usage,
            ]);
        } catch (\Throwable $e) {
            $this->circuitBreaker->recordFailure();

            Log::error('AI ticket insights failed', ['error' => $e->getMessage()]);

            return $this->defaultInsights();
        }
    }

    public function analyzeSentiment(
        string $userMessage,
        string $ticketContext = '',
    ): array {
        $this->circuitBreaker->isAvailable();

        $fullPrompt = $this->promptBuilder->sentimentAnalysis($userMessage, $ticketContext);

        $startTime = microtime(true);

        try {
            $result = $this->retryHandler->execute(function () use ($fullPrompt) {
                return $this->executeApiCall($fullPrompt, 0.2, 256);
            }, 'analyze_sentiment');

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            $parsed = $this->parser->parseJson($result['response']);
            $usage = $this->parser->parseUsageMetadata($result['response']);

            $this->usageTracker->track(
                model: $this->client->getModel(),
                promptTokens: $usage['prompt_tokens'],
                completionTokens: $usage['completion_tokens'],
                totalTokens: $usage['total_tokens'],
                durationMs: $durationMs,
                success: true,
            );

            $this->circuitBreaker->recordSuccess();

            Log::info('AI sentiment analysis completed', [
                'sentiment' => $parsed['sentiment'] ?? '?',
                'confidence' => $parsed['confidence'] ?? 0,
                'duration_ms' => $durationMs,
            ]);

            return array_merge($parsed, ['_usage' => $usage]);
        } catch (\Throwable $e) {
            $this->circuitBreaker->recordFailure();

            Log::error('AI sentiment analysis failed', ['error' => $e->getMessage()]);

            return $this->defaultSentiment();
        }
    }

    public function generateRagAnswer(string $prompt): array
    {
        $this->circuitBreaker->isAvailable();

        return $this->executeWithTracking(
            prompt: $prompt,
            temperature: 0.4,
            maxTokens: $this->maxOutputTokens,
            operation: 'rag_answer',
            ticketId: null,
            promptType: 'rag_answer',
        );
    }

    public function getUsageToday(): array
    {
        return [
            'count' => $this->usageTracker->getRequestCountToday(),
            'tokens' => $this->usageTracker->getTokenCountToday(),
            'cost' => $this->usageTracker->getCostToday(),
        ];
    }

    public function isCircuitOpen(): bool
    {
        try {
            $this->circuitBreaker->isAvailable();
            return false;
        } catch (AIException) {
            return true;
        }
    }

    public function getPromptBuilder(): PromptBuilder
    {
        return $this->promptBuilder;
    }

    protected function validateContext(array $context): void
    {
        if (empty($context['previous_replies'])) {
            Log::info('AI response generated without conversation history', [
                'ticket_id' => $context['ticket_id'] ?? null,
            ]);
        }

        if (empty($context['knowledge_base'])) {
            Log::info('AI response generated without knowledge base', [
                'ticket_id' => $context['ticket_id'] ?? null,
            ]);
        }
    }

    protected function executeWithTracking(
        string $prompt,
        float $temperature,
        int $maxTokens,
        string $operation,
        ?int $ticketId,
        string $promptType,
    ): array {
        $startTime = microtime(true);

        try {
            $result = $this->retryHandler->execute(function () use ($prompt, $temperature, $maxTokens) {
                return $this->executeApiCall($prompt, $temperature, $maxTokens);
            }, $operation);

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            $text = $this->parser->parseText($result['response']);
            $usage = $this->parser->parseUsageMetadata($result['response']);

            $promptHistoryId = $this->logPrompt($promptType, $prompt, $ticketId);

            $usageLogId = $this->usageTracker->track(
                ticketId: $ticketId,
                promptHistoryId: $promptHistoryId,
                model: $this->client->getModel(),
                promptTokens: $usage['prompt_tokens'],
                completionTokens: $usage['completion_tokens'],
                totalTokens: $usage['total_tokens'],
                durationMs: $durationMs,
                success: true,
            );

            $this->circuitBreaker->recordSuccess();

            Log::info('AI response generated', [
                'ticket_id' => $ticketId,
                'prompt_type' => $promptType,
                'tokens' => $usage['total_tokens'],
                'duration_ms' => $durationMs,
            ]);

            return [
                'text' => $text,
                'tokens' => $usage,
                'duration_ms' => $durationMs,
                'usage_log_id' => $usageLogId,
            ];
        } catch (\Throwable $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            $this->circuitBreaker->recordFailure();

            $this->usageTracker->track(
                ticketId: $ticketId,
                model: $this->client->getModel(),
                promptTokens: 0,
                completionTokens: 0,
                totalTokens: 0,
                durationMs: $durationMs,
                success: false,
                errorMessage: $e->getMessage(),
            );

            Log::error('AI response generation failed', [
                'ticket_id' => $ticketId,
                'prompt_type' => $promptType,
                'error' => $e->getMessage(),
                'duration_ms' => $durationMs,
            ]);

            throw $e;
        }
    }

    protected function executeApiCall(string $prompt, float $temperature, int $maxTokens): array
    {
        $response = $this->client->generateContent([
            'contents' => [
                [
                    'parts' => [['text' => $prompt]],
                ],
            ],
            'generationConfig' => [
                'temperature' => $temperature,
                'maxOutputTokens' => $maxTokens,
                'topP' => $this->topP,
            ],
        ]);

        return ['response' => $response];
    }

    protected function defaultAnalysis(): array
    {
        return [
            'suggested_category' => 'technical',
            'category_confidence' => 0.00,
            'category_reasoning' => 'AI analysis unavailable.',
            'suggested_priority' => 'medium',
            'summary' => 'No AI analysis available',
            'sentiment' => 'neutral',
            'key_topics' => [],
            'estimated_complexity' => 'simple',
            'recommended_action' => 'Review ticket manually',
            'requires_human' => false,
            'confidence' => 0.0,
            '_usage' => [
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'total_tokens' => 0,
            ],
        ];
    }

    protected function defaultInsights(): array
    {
        return [
            'conversation_summary' => 'AI insights are currently unavailable.',
            'customer_intent' => 'Unable to determine customer intent.',
            'suggested_priority' => 'medium',
            'urgency_level' => 'medium',
            'urgency_reason' => 'Insufficient data for urgency analysis.',
            'suggested_category' => 'general',
            'customer_sentiment' => 'neutral',
            'key_findings' => [],
            'possible_solutions' => [],
            'recommended_next_step' => 'Review ticket manually.',
            'generated_at' => now()->toIso8601String(),
            '_usage' => [
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'total_tokens' => 0,
            ],
        ];
    }

    protected function defaultSentiment(): array
    {
        return [
            'sentiment' => 'neutral',
            'confidence' => 0.00,
            'analysis_text' => 'Sentiment analysis unavailable.',
            'key_phrases' => [],
            'escalation_recommended' => false,
            '_usage' => [
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'total_tokens' => 0,
            ],
        ];
    }

    protected function logPrompt(string $promptType, string $fullPrompt, ?int $ticketId): ?int
    {
        try {
            $history = \App\Models\AiPromptHistory::create([
                'ticket_id' => $ticketId,
                'prompt_type' => $promptType,
                'system_prompt' => $this->promptBuilder->getSystemIdentity(),
                'user_prompt' => $fullPrompt,
                'full_prompt' => $this->promptBuilder->getSystemIdentity() . "\n\n" . $fullPrompt,
                'model' => $this->client->getModel(),
            ]);

            return $history->id;
        } catch (\Throwable $e) {
            Log::warning('Failed to log AI prompt', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
