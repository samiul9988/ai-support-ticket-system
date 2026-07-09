<?php

namespace App\Services\AI;

class PromptBuilder
{
    protected array $templates;

    protected string $systemIdentity;

    public function __construct()
    {
        $this->templates = config('prompts', []);
        $this->systemIdentity = config('prompts.system_identity', '');
    }

    public function build(string $templateName, array $variables = []): string
    {
        $template = $this->templates[$templateName] ?? null;

        if (! $template) {
            throw new \InvalidArgumentException("Prompt template '{$templateName}' not found.");
        }

        $prompt = $this->systemIdentity . "\n\n" . $template;

        return $this->substitute($prompt, $variables);
    }

    public function buildWithoutSystem(string $templateName, array $variables = []): string
    {
        $template = $this->templates[$templateName] ?? null;

        if (! $template) {
            throw new \InvalidArgumentException("Prompt template '{$templateName}' not found.");
        }

        return $this->substitute($template, $variables);
    }

    public function initialAutoReply(
        string $ticketTitle,
        string $ticketDescription,
        array $knowledgeBase = [],
    ): string {
        return $this->build('initial_auto_reply', [
            'ticket_title' => $ticketTitle,
            'ticket_description' => $ticketDescription,
            'knowledge_base' => $this->formatKnowledgeBase($knowledgeBase),
        ]);
    }

    public function followUpReply(
        string $ticketTitle,
        string $ticketDescription,
        string $userMessage,
        array $conversationHistory = [],
        array $knowledgeBase = [],
    ): string {
        return $this->build('follow_up_reply', [
            'ticket_title' => $ticketTitle,
            'ticket_description' => $ticketDescription,
            'user_message' => $userMessage,
            'previous_conversation' => $this->formatConversation($conversationHistory),
            'knowledge_base' => $this->formatKnowledgeBase($knowledgeBase),
        ]);
    }

    public function ticketAnalysis(string $title, string $description): string
    {
        return $this->buildWithoutSystem('ticket_analysis', [
            'ticket_title' => $title,
            'ticket_description' => $description,
        ]);
    }

    public function missingInfoRequest(
        string $ticketTitle,
        string $ticketDescription,
        string $missingFields,
    ): string {
        return $this->build('missing_info_request', [
            'ticket_title' => $ticketTitle,
            'ticket_description' => $ticketDescription,
            'missing_fields' => $missingFields,
        ]);
    }

    public function escalationNotice(
        string $ticketTitle,
        string $escalationReason,
    ): string {
        return $this->build('escalation_notice', [
            'ticket_title' => $ticketTitle,
            'escalation_reason' => $escalationReason,
        ]);
    }

    public function resolutionConfirmation(
        string $ticketTitle,
        string $solutionSummary,
    ): string {
        return $this->build('resolution_confirmation', [
            'ticket_title' => $ticketTitle,
            'solution_summary' => $solutionSummary,
        ]);
    }

    public function ticketSummary(array $conversationHistory): string
    {
        return $this->buildWithoutSystem('ticket_summary', [
            'previous_conversation' => $this->formatConversation($conversationHistory),
        ]);
    }

    public function knowledgeBaseAnswer(
        string $userMessage,
        string $articleContent,
    ): string {
        return $this->build('knowledge_base_answer', [
            'user_message' => $userMessage,
            'kb_article' => $articleContent,
        ]);
    }

    public function ticketInsights(
        string $ticketTitle,
        string $ticketDescription,
        array $conversationHistory = [],
        array $knowledgeBase = [],
    ): string {
        return $this->buildWithoutSystem('ticket_insights', [
            'ticket_title' => $ticketTitle,
            'ticket_description' => $ticketDescription,
            'conversation_history' => $this->formatConversation($conversationHistory),
            'knowledge_base' => $this->formatKnowledgeBase($knowledgeBase),
        ]);
    }

    public function getSystemIdentity(): string
    {
        return $this->systemIdentity;
    }

    public function getTemplate(string $name): ?string
    {
        return $this->templates[$name] ?? null;
    }

    protected function substitute(string $template, array $variables): string
    {
        $replacements = [];
        foreach ($variables as $key => $value) {
            $replacements['{' . $key . '}'] = (string) $value;
        }

        $result = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template,
        );

        return $this->cleanOutput($result);
    }

    protected function formatKnowledgeBase(array $articles): string
    {
        if (empty($articles)) {
            return 'No relevant knowledge base articles found.';
        }

        $formatted = [];
        foreach ($articles as $i => $article) {
            $num = $i + 1;
            $formatted[] = "**Article {$num}**: {$article}";
        }

        return implode("\n\n", $formatted);
    }

    protected function formatConversation(array $messages): string
    {
        if (empty($messages)) {
            return 'No previous conversation. This is the first interaction.';
        }

        $formatted = [];
        foreach ($messages as $i => $msg) {
            $num = $i + 1;
            $formatted[] = "{$num}. {$msg}";
        }

        return implode("\n", $formatted);
    }

    protected function cleanOutput(string $text): string
    {
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }
}
