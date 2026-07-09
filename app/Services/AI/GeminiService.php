<?php

namespace App\Services\AI;

use App\Exceptions\AIException;
use App\Models\AiUsageLog;
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

    protected string $systemPrompt;
    protected float $temperature;
    protected int $maxOutputTokens;
    protected float $topP;

    public function __construct(
        GeminiApiClient $client,
        ResponseParser $parser,
        CircuitBreaker $circuitBreaker,
        RetryHandler $retryHandler,
        UsageTracker $usageTracker,
    ) {
        $this->client = $client;
        $this->parser = $parser;
        $this->circuitBreaker = $circuitBreaker;
        $this->retryHandler = $retryHandler;
        $this->usageTracker = $usageTracker;

        $this->systemPrompt = config('gemini.providers.gemini.system_prompt', '');
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

        $fullPrompt = $this->buildChatPrompt($ticketTitle, $ticketDescription, $userMessage, $context);

        $startTime = microtime(true);

        try {
            $result = $this->retryHandler->execute(function () use ($fullPrompt) {
                return $this->executeApiCall($fullPrompt, 0.7, $this->maxOutputTokens);
            }, 'generate_response');

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            $text = $this->parser->parseText($result['response']);
            $usage = $this->parser->parseUsageMetadata($result['response']);
            $promptHistoryId = $this->logPrompt('response', $fullPrompt, null);

            $usageLogId = $this->usageTracker->track(
                ticketId: $context['ticket_id'] ?? null,
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
                'ticket_id' => $context['ticket_id'] ?? null,
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
                ticketId: $context['ticket_id'] ?? null,
                model: $this->client->getModel(),
                promptTokens: 0,
                completionTokens: 0,
                totalTokens: 0,
                durationMs: $durationMs,
                success: false,
                errorMessage: $e->getMessage(),
            );

            Log::error('AI response generation failed', [
                'ticket_id' => $context['ticket_id'] ?? null,
                'error' => $e->getMessage(),
                'duration_ms' => $durationMs,
            ]);

            throw $e;
        }
    }

    public function analyzeTicket(string $title, string $description): array
    {
        $this->circuitBreaker->isAvailable();

        $prompt = $this->buildAnalysisPrompt($title, $description);

        $startTime = microtime(true);

        try {
            $result = $this->retryHandler->execute(function () use ($prompt) {
                return $this->executeApiCall($prompt, 0.3, 512);
            }, 'analyze_ticket');

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            $parsed = $this->parser->parseJson($result['response']);

            $usage = $this->parser->parseUsageMetadata($result['response']);

            $promptHistoryId = $this->logPrompt('analysis', $prompt, null);

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

            Log::error('AI ticket analysis failed', [
                'error' => $e->getMessage(),
            ]);

            return $this->defaultAnalysis();
        }
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

    protected function buildChatPrompt(string $title, string $description, string $message, array $context): string
    {
        $parts = [$this->systemPrompt];

        if (! empty($context['knowledge_base'])) {
            $parts[] = "RELEVANT KNOWLEDGE BASE ARTICLES:\n" . implode("\n---\n", $context['knowledge_base']);
        }

        if (! empty($context['previous_replies'])) {
            $parts[] = "CONVERSATION HISTORY:\n" . implode("\n", $context['previous_replies']);
        }

        $parts[] = "CURRENT TICKET:";
        $parts[] = "Title: {$title}";
        $parts[] = "Description: {$description}";
        $parts[] = "Customer Message: {$message}";
        $parts[] = "\nProvide a helpful, professional response:";

        return implode("\n\n", $parts);
    }

    protected function buildAnalysisPrompt(string $title, string $description): string
    {
        return <<<PROMPT
Analyze the following support ticket and return ONLY a valid JSON object. Do not include markdown formatting, code fences, or any text outside the JSON.

Required JSON structure:
{
    "suggested_category": "string (account|billing|technical|feature_request|bug_report|general)",
    "suggested_priority": "string (low|medium|high|urgent)",
    "summary": "string (one-line summary of the issue)",
    "sentiment": "string (positive|neutral|negative|frustrated)",
    "key_topics": ["string array of relevant keywords"],
    "estimated_complexity": "string (simple|moderate|complex)",
    "recommended_action": "string (brief recommended first step)"
}

Ticket Title: {$title}
Ticket Description: {$description}
PROMPT;
    }

    protected function defaultAnalysis(): array
    {
        return [
            'suggested_category' => 'general',
            'suggested_priority' => 'medium',
            'summary' => 'No AI analysis available',
            'sentiment' => 'neutral',
            'key_topics' => [],
            'estimated_complexity' => 'simple',
            'recommended_action' => 'Review ticket manually',
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
                'system_prompt' => $this->systemPrompt,
                'user_prompt' => $fullPrompt,
                'full_prompt' => $this->systemPrompt . "\n\n" . $fullPrompt,
                'model' => $this->client->getModel(),
            ]);

            return $history->id;
        } catch (\Throwable $e) {
            Log::warning('Failed to log AI prompt', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
