<?php

namespace App\Services\AI\Contracts;

interface AIServiceInterface
{
    public function generateResponse(
        string $ticketTitle,
        string $ticketDescription,
        string $userMessage,
        array $context = []
    ): array;

    public function analyzeTicket(string $title, string $description): array;

    public function generateTicketInsights(
        string $ticketTitle,
        string $ticketDescription,
        array $conversationHistory = [],
        array $knowledgeBase = [],
    ): array;

    public function analyzeSentiment(
        string $userMessage,
        string $ticketContext = '',
    ): array;

    public function generateRagAnswer(string $prompt): array;

    public function getUsageToday(): array;

    public function isCircuitOpen(): bool;
}
