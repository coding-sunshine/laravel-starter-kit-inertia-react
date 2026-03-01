<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

/**
 * Agent that suggests an optimized stop order for a route (distance, time, cost, carbon).
 */
final class RouteOptimizationAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are a route optimization analyst. Given a list of route stops with current order and optional time windows, '
            . 'suggest a better visit order to minimize total distance and/or time while respecting constraints. '
            . 'Return the suggested stop IDs in visit order, plus estimated total distance (km), duration (minutes), cost estimate, and carbon (kg CO2).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'suggested_stop_order' => $schema->array()->items($schema->integer()->required())->required()->description('Route stop IDs in recommended visit order'),
            'estimated_total_distance_km' => $schema->number()->required()->description('Estimated total distance in km'),
            'estimated_total_duration_minutes' => $schema->number()->required()->description('Estimated total duration in minutes'),
            'estimated_cost' => $schema->number()->required()->description('Estimated cost (monetary)'),
            'estimated_carbon_kg' => $schema->number()->required()->description('Estimated CO2 in kg'),
            'summary' => $schema->string()->required()->description('Short explanation of the optimization'),
        ];
    }
}
