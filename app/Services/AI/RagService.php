<?php

namespace App\Services\AI;

use App\Models\KnowledgeArticle;
use App\Models\Ticket;
use App\Services\AI\Contracts\AIServiceInterface;
use Illuminate\Support\Facades\Log;

class RagService
{
    public function __construct(
        protected AIServiceInterface $aiService,
        protected PromptBuilder $promptBuilder,
    ) {}

    public function answer(string $question, ?int $categoryId = null): array
    {
        $pipeline = [];

        $step1Start = microtime(true);
        $pipeline['retrieve'] = $this->retrieve($question, $categoryId);
        $pipeline['retrieve']['duration_ms'] = $this->msSince($step1Start);

        $step2Start = microtime(true);
        $pipeline['augment'] = $this->augment($question, $pipeline['retrieve']['articles']);
        $pipeline['augment']['duration_ms'] = $this->msSince($step2Start);

        $step3Start = microtime(true);
        $pipeline['generate'] = $this->generate(
            $pipeline['augment']['prompt'],
            $question,
            $pipeline['retrieve']['articles'],
        );
        $pipeline['generate']['duration_ms'] = $this->msSince($step3Start);

        $totalMs = $this->msSince($step1Start);

        Log::info('RAG pipeline completed', [
            'articles_retrieved' => count($pipeline['retrieve']['articles']),
            'top_score' => $pipeline['retrieve']['top_score'],
            'total_duration_ms' => $totalMs,
        ]);

        return [
            'answer' => $pipeline['generate']['answer'],
            'sources' => $pipeline['retrieve']['articles'],
            'pipeline' => $pipeline,
            'total_duration_ms' => $totalMs,
        ];
    }

    public function answerForTicket(Ticket $ticket): array
    {
        $question = $this->buildQuestionFromTicket($ticket);

        $contextBuilder = new ConversationContextBuilder(5);

        $conversationText = $contextBuilder->buildText($ticket);

        if ($conversationText !== '') {
            $question = "Conversation history:\n{$conversationText}\n\nCurrent question:\n{$question}";
        }

        return $this->answer($question, $ticket->category_id);
    }

    protected function retrieve(string $question, ?int $categoryId = null): array
    {
        Log::info('RAG Step 1: Retrieve', [
            'question' => substr($question, 0, 100),
            'category_id' => $categoryId,
        ]);

        $articles = KnowledgeArticle::retrieveForRag(
            question: $question,
            categoryId: $categoryId,
            limit: 5,
        );

        $scores = array_column($articles, 'score');
        $topScore = ! empty($scores) ? max($scores) : 0;

        return [
            'articles' => $articles,
            'count' => count($articles),
            'top_score' => $topScore,
            'query' => $question,
        ];
    }

    protected function augment(string $question, array $articles): array
    {
        Log::info('RAG Step 2: Augment', [
            'articles_count' => count($articles),
        ]);

        if (empty($articles)) {
            Log::info('RAG: No relevant articles found, using fallback prompt');

            return [
                'prompt' => $this->promptBuilder->ragAnswerFallback($question),
                'article_count' => 0,
                'context_length' => 0,
            ];
        }

        $contexts = [];
        foreach ($articles as $i => $article) {
            $num = $i + 1;
            $contexts[] = sprintf(
                "[Article %d] Title: %s\nContent: %s",
                $num,
                $article['title'],
                $article['content'],
            );
        }

        $formattedContext = implode("\n\n---\n\n", $contexts);

        $prompt = $this->promptBuilder->ragAnswer(
            question: $question,
            context: $formattedContext,
        );

        return [
            'prompt' => $prompt,
            'article_count' => count($articles),
            'context_length' => strlen($formattedContext),
            'formatted_context' => $formattedContext,
        ];
    }

    protected function generate(string $prompt, string $question, array $articles): array
    {
        Log::info('RAG Step 3: Generate', [
            'articles_count' => count($articles),
        ]);

        try {
            $result = $this->aiService->generateRagAnswer($prompt);

            return [
                'answer' => $result['text'],
                'model' => config('gemini.providers.gemini.model'),
                'tokens' => $result['tokens'] ?? [],
            ];
        } catch (\Throwable $e) {
            Log::error('RAG generation failed', ['error' => $e->getMessage()]);

            return [
                'answer' => $this->fallbackAnswer($articles),
                'model' => 'fallback',
                'tokens' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function fallbackAnswer(array $articles): string
    {
        if (empty($articles)) {
            return "I couldn't find any relevant articles to answer your question. An agent will review this and get back to you shortly.";
        }

        $titles = array_column($articles, 'title');
        $titleList = implode(', ', array_slice($titles, 0, 3));

        return "I found some articles that may help: **{$titleList}**. Please review these while an agent looks into your specific situation.";
    }

    protected function buildQuestionFromTicket(Ticket $ticket): string
    {
        $parts = [$ticket->title, $ticket->description];

        $lastReply = $ticket->replies()
            ->where('is_ai_generated', false)
            ->latest()
            ->first();

        if ($lastReply) {
            $parts[] = $lastReply->content;
        }

        return implode('. ', $parts);
    }

    protected function msSince(float $start): int
    {
        return max(0, (int) ((microtime(true) - $start) * 1000));
    }
}
