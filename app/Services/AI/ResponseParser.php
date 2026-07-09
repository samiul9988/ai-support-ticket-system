<?php

namespace App\Services\AI;

use App\Exceptions\AIException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class ResponseParser
{
    protected int $maxParseAttempts = 3;

    public function parseText(Response $response): string
    {
        $data = $this->decodeJson($response);

        $this->validateStructure($data);

        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if ($text === null || $text === '') {
            Log::warning('Gemini returned empty response', [
                'data' => $data,
                'finishReason' => $data['candidates'][0]['finishReason'] ?? 'unknown',
            ]);

            if (($data['candidates'][0]['finishReason'] ?? '') === 'SAFETY') {
                throw AIException::contentFiltered('Response blocked by safety filters.');
            }

            throw AIException::invalidResponse('AI returned an empty response.');
        }

        return trim($text);
    }

    public function parseJson(Response $response): array
    {
        $text = $this->parseText($response);

        $json = $this->extractJsonFromText($text);

        $decoded = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('Gemini returned invalid JSON', [
                'text' => substr($text, 0, 500),
                'json_error' => json_last_error_msg(),
            ]);

            throw AIException::invalidResponse(
                'AI returned malformed JSON: ' . json_last_error_msg(),
                ['raw_text' => substr($text, 0, 200)]
            );
        }

        return $decoded;
    }

    public function parseUsageMetadata(Response $response): array
    {
        $data = $this->decodeJson($response);

        return [
            'prompt_tokens' => $data['usageMetadata']['promptTokenCount'] ?? 0,
            'completion_tokens' => $data['usageMetadata']['candidatesTokenCount'] ?? 0,
            'total_tokens' => $data['usageMetadata']['totalTokenCount'] ?? 0,
        ];
    }

    protected function decodeJson(Response $response): array
    {
        $data = $response->json();

        if (! is_array($data)) {
            throw AIException::invalidResponse(
                'API response is not valid JSON object.',
                ['body' => substr($response->body(), 0, 500)]
            );
        }

        return $data;
    }

    protected function validateStructure(array $data): void
    {
        if (empty($data['candidates'])) {
            throw AIException::invalidResponse(
                'API response missing "candidates" array.',
                ['response_keys' => array_keys($data)]
            );
        }

        if (empty($data['candidates'][0]['content']['parts'])) {
            throw AIException::invalidResponse(
                'API response missing "content.parts" structure.',
                ['candidate' => $data['candidates'][0] ?? []]
            );
        }
    }

    protected function extractJsonFromText(string $text): string
    {
        $original = $text;

        $text = trim($text);

        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);

        $text = trim($text);

        if ($this->isJson($text)) {
            return $text;
        }

        if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $original, $matches)) {
            return $matches[0];
        }

        return $text;
    }

    protected function isJson(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
