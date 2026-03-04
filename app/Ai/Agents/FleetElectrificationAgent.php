<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

/**
 * Agent that produces an electrification plan: readiness score, replacement order,
 * charging recommendations, TCO summary, and milestones.
 */
final class FleetElectrificationAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are a fleet electrification analyst. Given fleet vehicles (types, fuel, mileage), EV charging data, '
            .'carbon targets, and sustainability goals, produce: (1) a readiness_score 0-100; (2) replacement_order for vehicles '
            .'to replace with EVs (vehicle_id, recommended_year, reason, priority); (3) charging_recommendations (type, count, location_type, reason); '
            .'(4) tco_summary (current_tco, projected_ev_tco, savings); (5) milestones (year, description, target).';
    }

    public function schema(JsonSchema $schema): array
    {
        $replacementItem = $schema->object([
            'vehicle_id' => $schema->integer()->required()->description('Vehicle ID'),
            'recommended_year' => $schema->integer()->required()->description('Year to replace'),
            'reason' => $schema->string()->required()->description('Why replace'),
            'priority' => $schema->string()->required()->description('low, medium, or high'),
        ])->withoutAdditionalProperties();

        $chargingItem = $schema->object([
            'type' => $schema->string()->required()->description('e.g. depot, public, fast'),
            'count' => $schema->integer()->required()->description('Number of chargers'),
            'location_type' => $schema->string()->required()->description('e.g. depot, en_route'),
            'reason' => $schema->string()->required()->description('Justification'),
        ])->withoutAdditionalProperties();

        $tcoSummary = $schema->object([
            'current_tco' => $schema->number()->required()->description('Current total cost of ownership'),
            'projected_ev_tco' => $schema->number()->required()->description('Projected TCO with EVs'),
            'savings' => $schema->number()->required()->description('Estimated savings'),
        ])->withoutAdditionalProperties();

        $milestoneItem = $schema->object([
            'year' => $schema->integer()->required()->description('Target year'),
            'description' => $schema->string()->required()->description('Milestone description'),
            'target' => $schema->string()->required()->description('Target metric or count'),
        ])->withoutAdditionalProperties();

        return [
            'readiness_score' => $schema->number()->required()->description('Fleet electrification readiness 0-100'),
            'replacement_order' => $schema->array()->items($replacementItem)->required()->description('Suggested vehicle replacement order'),
            'charging_recommendations' => $schema->array()->items($chargingItem)->required()->description('Charging infrastructure recommendations'),
            'tco_summary' => $tcoSummary->required()->description('TCO comparison'),
            'milestones' => $schema->array()->items($milestoneItem)->required()->description('Electrification milestones'),
        ];
    }
}
