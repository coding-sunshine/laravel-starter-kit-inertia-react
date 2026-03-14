<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Sale;
use App\Services\AiCreditService;
use App\Services\PrismService;
use Throwable;

/**
 * Generate an AI-powered deal forecast for a sale using Prism.
 */
final readonly class GenerateDealForecastAction
{
    public function __construct(
        private PrismService $prism,
        private AiCreditService $aiCredits,
    ) {
        //
    }

    /**
     * @return array{probability: int, confidence: string, reasoning: string, next_steps: string[]}
     */
    public function handle(Sale $sale): array
    {
        $user = auth()->user();

        if ($user === null || ! $this->aiCredits->canUse($user, 'ai_insights')) {
            return $this->fallbackForecast($sale);
        }

        if (! $this->prism->isAvailable()) {
            return $this->fallbackForecast($sale);
        }

        try {
            $prompt = $this->buildPrompt($sale);

            $response = $this->prism->text()
                ->withSystemPrompt('You are a real estate CRM analyst. Respond in JSON only with keys: probability (integer 0-100), confidence (low/medium/high), reasoning (string), next_steps (array of strings).')
                ->withPrompt($prompt)
                ->generate();

            $data = json_decode($response->text, true);

            if (! is_array($data)) {
                return $this->fallbackForecast($sale);
            }

            return [
                'probability' => (int) ($data['probability'] ?? 50),
                'confidence' => (string) ($data['confidence'] ?? 'low'),
                'reasoning' => (string) ($data['reasoning'] ?? 'Unable to generate reasoning.'),
                'next_steps' => (array) ($data['next_steps'] ?? []),
            ];
        } catch (Throwable) {
            return $this->fallbackForecast($sale);
        }
    }

    private function buildPrompt(Sale $sale): string
    {
        $status = $sale->status ?? 'unknown';
        $settledAt = $sale->settled_at?->toDateString() ?? 'not settled';
        $createdAt = $sale->created_at->toDateString();

        return <<<PROMPT
        Analyze this real estate sale and provide a deal forecast:

        Sale ID: {$sale->id}
        Status: {$status}
        Created: {$createdAt}
        Settled At: {$settledAt}

        Provide probability of closing (0-100), confidence level (low/medium/high), brief reasoning, and 3 recommended next steps.
        Respond in JSON format only.
        PROMPT;
    }

    /**
     * @return array{probability: int, confidence: string, reasoning: string, next_steps: string[]}
     */
    private function fallbackForecast(Sale $sale): array
    {
        $probability = match ($sale->status ?? '') {
            'settled' => 100,
            'unconditional' => 90,
            'conditional' => 70,
            'reserved' => 60,
            'prospect' => 30,
            default => 50,
        };

        return [
            'probability' => $probability,
            'confidence' => 'low',
            'reasoning' => 'Forecast based on current sale status. AI analysis unavailable.',
            'next_steps' => [
                'Follow up with the client within 48 hours.',
                'Review contract terms and conditions.',
                'Schedule a site visit if not completed.',
            ],
        ];
    }
}
