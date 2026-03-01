<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Ai\Agents\RouteOptimizationAgent;
use App\Models\Fleet\Route;
use Laravel\Ai\Responses\StructuredAgentResponse;

final class RouteOptimizationService
{
    public function __construct(
        private readonly RouteOptimizationAgent $agent
    ) {}

    /**
     * Suggest optimized stop order for a route. Does not persist; returns suggestion only.
     *
     * @return array{suggested_stop_order: int[], estimated_total_distance_km: float, estimated_total_duration_minutes: float, estimated_cost: float, estimated_carbon_kg: float, summary: string}|null
     */
    public function optimize(Route $route): ?array
    {
        $route->load(['stops' => fn ($q) => $q->with('location')->orderBy('sort_order')]);
        $stops = $route->stops;
        if ($stops->isEmpty()) {
            return null;
        }

        $context = $this->buildContext($route, $stops);
        $response = $this->agent->prompt($context);

        if (! $response instanceof StructuredAgentResponse) {
            return null;
        }

        $s = $response->structured;
        $order = $s['suggested_stop_order'] ?? [];
        if (! is_array($order)) {
            return null;
        }
        $order = array_values(array_filter(array_map('intval', $order)));
        $stopIds = $stops->pluck('id')->all();
        $order = array_values(array_intersect($order, $stopIds));
        if ($order === []) {
            $order = $stopIds;
        }

        return [
            'suggested_stop_order' => $order,
            'estimated_total_distance_km' => (float) ($s['estimated_total_distance_km'] ?? $route->estimated_distance_km ?? 0),
            'estimated_total_duration_minutes' => (float) ($s['estimated_total_duration_minutes'] ?? $route->estimated_duration_minutes ?? 0),
            'estimated_cost' => (float) ($s['estimated_cost'] ?? 0),
            'estimated_carbon_kg' => (float) ($s['estimated_carbon_kg'] ?? 0),
            'summary' => (string) ($s['summary'] ?? 'Optimized order suggested.'),
        ];
    }

    private function buildContext(Route $route, $stops): string
    {
        $stopData = $stops->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'location_id' => $s->location_id,
            'location_name' => $s->location?->name,
            'sort_order' => $s->sort_order,
            'planned_arrival_time' => $s->planned_arrival_time?->toIso8601String(),
            'planned_departure_time' => $s->planned_departure_time?->toIso8601String(),
        ])->toArray();

        $data = [
            'route_id' => $route->id,
            'route_name' => $route->name,
            'route_type' => $route->route_type,
            'current_estimated_distance_km' => $route->estimated_distance_km,
            'current_estimated_duration_minutes' => $route->estimated_duration_minutes,
            'stops' => $stopData,
        ];

        return "Optimize the visit order for this route. Return suggested_stop_order as an array of stop IDs in the best visit sequence.\n\n" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
