<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Rake;
use App\Services\PrismService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Throwable;

final readonly class GenerateLoadingRecommendationAction
{
    public function __construct(private PrismService $prism) {}

    public function handle(Rake $rake): ?string
    {
        if ($rake->siding_id === null) {
            return null;
        }

        $sidingId = (int) $rake->siding_id;
        $cacheKey = "loading_recommendation:rake:{$rake->id}:siding:{$sidingId}";

        if (Cache::has($cacheKey)) {
            /** @var string|null $cached */
            $cached = Cache::get($cacheKey);

            return $cached === '__unavailable__' ? null : $cached;
        }

        if (! $this->prism->isAvailable()) {
            Cache::put($cacheKey, '__unavailable__', 21600);

            return null;
        }

        $data = $this->aggregateData($rake, $sidingId);
        $prompt = $this->buildPrompt($rake, $data);

        try {
            $response = $this->prism->structured($this->prism->fastModel())
                ->withPrompt($prompt)
                ->withSchema($this->buildSchema())
                ->asStructured();

            /** @var array{recommendation: string} $structured */
            $structured = $response->structured;

            $recommendation = mb_trim($structured['recommendation'] ?? '');

            if ($recommendation === '') {
                Cache::put($cacheKey, '__unavailable__', 3600);

                return null;
            }

            Cache::put($cacheKey, $recommendation, 21600);

            return $recommendation;
        } catch (Throwable) {
            Cache::put($cacheKey, '__unavailable__', 3600);

            return null;
        }
    }

    private function buildSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'loading_recommendation',
            description: 'AI loading recommendation to prevent PCC violations',
            properties: [
                new StringSchema(
                    name: 'recommendation',
                    description: 'Plain-text recommendation under 200 words. Suggest target load per wagon type in MT to stay under PCC. Be specific with numbers.',
                ),
            ],
            requiredFields: ['recommendation'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function aggregateData(Rake $rake, int $sidingId): array
    {
        $avgByType = DB::select(
            <<<'SQL'
            SELECT
                w.wagon_type,
                COUNT(wl.id)                              AS loading_count,
                ROUND(AVG(wl.loaded_quantity_mt)::numeric, 2) AS avg_loaded_mt,
                ROUND(AVG(w.pcc_weight_mt)::numeric, 2)  AS avg_pcc_mt,
                SUM(CASE WHEN wl.loaded_quantity_mt > w.pcc_weight_mt THEN 1 ELSE 0 END) AS overload_count
            FROM wagon_loading wl
            JOIN wagons w ON w.id = wl.wagon_id
            JOIN rakes r  ON r.id = wl.rake_id
            WHERE r.siding_id = ?
              AND r.deleted_at IS NULL
              AND wl.created_at >= NOW() - INTERVAL '30 days'
              AND w.wagon_type IS NOT NULL
            GROUP BY w.wagon_type
            ORDER BY overload_count DESC
            SQL,
            [$sidingId],
        );

        $rakeWagonTypes = $rake->wagons
            ->whereNull('is_unfit')
            ->groupBy('wagon_type')
            ->map(fn ($wagons, $type): array => [
                'wagon_type' => $type,
                'count' => $wagons->count(),
                'pcc_weight_mt' => $wagons->first()?->pcc_weight_mt,
            ])
            ->values()
            ->all();

        return [
            'siding_avg_by_wagon_type' => $avgByType,
            'rake_wagon_composition' => $rakeWagonTypes,
            'rake_total_wagons' => $rake->wagons->count(),
            'siding_name' => $rake->siding?->name ?? 'Unknown',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function buildPrompt(Rake $rake, array $data): string
    {
        $json = json_encode($data, JSON_PRETTY_PRINT);

        return <<<PROMPT
        You are RRMCS AI, a loading optimization assistant for railway coal operations at BGR Mining.

        A rake is about to start loading at siding "{$data['siding_name']}". The rake has {$data['rake_total_wagons']} wagons.

        Based on the last 30 days of wagon loading data for this siding, provide a concise recommendation (max 200 words) on:
        1. Target load per wagon type (in MT) to stay under PCC limits
        2. Which wagon types historically get overloaded and by how much
        3. One specific tip to reduce PCC violations

        Historical loading data:
        {$json}

        Be specific with numbers. Mention wagon types by name. Keep it practical and actionable.
        PROMPT;
    }
}
