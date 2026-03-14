<?php

declare(strict_types=1);

namespace App\Actions;

use App\Services\AiCreditService;
use App\Services\PrismService;
use Throwable;

/**
 * Interpret a natural language analytics query and return a structured result using Prism.
 */
final readonly class NlAnalyticsQueryAction
{
    public function __construct(
        private PrismService $prism,
        private AiCreditService $aiCredits,
    ) {
        //
    }

    /**
     * @return array{answer: string, data: array<int, mixed>, chart_type: string|null, sql_hint: string|null}
     */
    public function handle(string $query, string $context = 'general'): array
    {
        $user = auth()->user();

        if ($user === null || ! $this->aiCredits->canUse($user, 'nlq_query')) {
            return $this->fallbackResult($query);
        }

        if (! $this->prism->isAvailable()) {
            return $this->fallbackResult($query);
        }

        try {
            $systemPrompt = <<<'SYSTEM'
            You are a real estate CRM analytics assistant. When given a natural language query, respond in JSON with:
            - answer: a human-readable answer string
            - data: an array of data points (can be empty)
            - chart_type: null or one of "bar", "line", "pie", "scatter"
            - sql_hint: null or a simplified SQL query hint

            Always respond with valid JSON only.
            SYSTEM;

            $prompt = "Context: {$context}\nQuery: {$query}";

            $response = $this->prism->text()
                ->withSystemPrompt($systemPrompt)
                ->withPrompt($prompt)
                ->generate();

            $data = json_decode($response->text, true);

            if (! is_array($data)) {
                return $this->fallbackResult($query);
            }

            return [
                'answer' => (string) ($data['answer'] ?? 'No answer available.'),
                'data' => (array) ($data['data'] ?? []),
                'chart_type' => isset($data['chart_type']) ? (string) $data['chart_type'] : null,
                'sql_hint' => isset($data['sql_hint']) ? (string) $data['sql_hint'] : null,
            ];
        } catch (Throwable) {
            return $this->fallbackResult($query);
        }
    }

    /**
     * @return array{answer: string, data: array<int, mixed>, chart_type: string|null, sql_hint: string|null}
     */
    private function fallbackResult(string $query): array
    {
        return [
            'answer' => 'AI analytics is currently unavailable. Please try again later.',
            'data' => [],
            'chart_type' => null,
            'sql_hint' => null,
        ];
    }
}
