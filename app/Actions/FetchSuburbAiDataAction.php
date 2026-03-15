<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\SuburbAiData;
use App\Services\PrismService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fetch AI-generated suburb price and rental data for a given suburb/state.
 *
 * Uses Prism AI to generate market insights including median prices, rental
 * yields, and annual growth data. Results are cached in the suburb_ai_data
 * table to avoid redundant API calls.
 */
final readonly class FetchSuburbAiDataAction
{
    public function __construct(private PrismService $prism)
    {
        //
    }

    public function handle(
        string $suburbName,
        ?string $state = null,
        ?string $postcode = null,
        ?int $organizationId = null,
        bool $forceRefresh = false,
    ): SuburbAiData {
        if (! $forceRefresh) {
            $existing = SuburbAiData::query()
                ->where('suburb_name', $suburbName)
                ->where('organization_id', $organizationId)
                ->where('fetched_at', '>=', now()->subDays(7))
                ->first();

            if ($existing instanceof SuburbAiData) {
                return $existing;
            }
        }

        $aiData = $this->fetchFromAi($suburbName, $state, $postcode);

        return SuburbAiData::query()->updateOrCreate(
            [
                'suburb_name' => $suburbName,
                'organization_id' => $organizationId,
            ],
            [
                'state' => $state,
                'postcode' => $postcode,
                'source' => 'ai',
                'median_house_price' => $aiData['median_house_price'] ?? null,
                'median_unit_price' => $aiData['median_unit_price'] ?? null,
                'median_rent_house' => $aiData['median_rent_house'] ?? null,
                'median_rent_unit' => $aiData['median_rent_unit'] ?? null,
                'rental_yield' => $aiData['rental_yield'] ?? null,
                'annual_growth' => $aiData['annual_growth'] ?? null,
                'price_rent_json' => $aiData,
                'ai_insights' => $aiData['insights'] ?? null,
                'fetched_at' => now(),
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchFromAi(string $suburb, ?string $state, ?string $postcode): array
    {
        if (! $this->prism->isAvailable()) {
            Log::info("SuburbAiData: Prism not available, returning empty data for {$suburb}");

            return $this->emptyData();
        }

        $location = mb_trim("{$suburb}".($state ? ", {$state}" : '').($postcode ? " {$postcode}" : ''));

        try {
            $response = $this->prism->text()
                ->withSystemPrompt('You are an Australian real estate market data analyst. Provide realistic market data estimates based on your knowledge of Australian property markets. Always respond with valid JSON only.')
                ->withPrompt(<<<PROMPT
                    Provide current Australian property market data for: {$location}

                    Return ONLY valid JSON with these fields (use null if unknown):
                    {
                        "median_house_price": <number in AUD>,
                        "median_unit_price": <number in AUD>,
                        "median_rent_house": <weekly rent in AUD>,
                        "median_rent_unit": <weekly rent in AUD>,
                        "rental_yield": <percentage as decimal, e.g. 4.5>,
                        "annual_growth": <percentage as decimal, e.g. 7.2>,
                        "insights": {
                            "market_summary": "<2 sentence summary>",
                            "demand_level": "<low|medium|high>",
                            "vacancy_rate": <percentage>,
                            "days_on_market": <number>
                        }
                    }
                    PROMPT)
                ->generate();

            $text = $response->text;
            $jsonStart = mb_strpos($text, '{');
            $jsonEnd = mb_strrpos($text, '}');

            if ($jsonStart !== false && $jsonEnd !== false) {
                $json = mb_substr($text, $jsonStart, $jsonEnd - $jsonStart + 1);
                $parsed = json_decode($json, true);

                if (is_array($parsed)) {
                    return $parsed;
                }
            }
        } catch (Throwable $e) {
            Log::warning("SuburbAiData: Failed to fetch AI data for {$suburb}", ['error' => $e->getMessage()]);
        }

        return $this->emptyData();
    }

    /**
     * @return array<string, null>
     */
    private function emptyData(): array
    {
        return [
            'median_house_price' => null,
            'median_unit_price' => null,
            'median_rent_house' => null,
            'median_rent_unit' => null,
            'rental_yield' => null,
            'annual_growth' => null,
            'insights' => null,
        ];
    }
}
