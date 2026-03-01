<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

/**
 * Agent that suggests fleet optimization: right-sizing, replacement timing, fleet mix, what-if scenarios.
 */
final class FleetOptimizationAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are a fleet optimization analyst. Given vehicle list, utilization, and cost data, suggest: '
            . '(1) right_sizing_summary – whether to add or reduce vehicles; (2) replacement_timing_summary – when to replace which assets; '
            . '(3) fleet_mix_summary – ideal mix of vehicle types; (4) what_if_scenarios – 2-3 brief what-if options (e.g. "Add 2 vans: cost X, benefit Y"). '
            . 'Return all four as strings or arrays. Be concise and actionable.';
    }

    public function schema(JsonSchema $schema): array
    {
        $whatIfItem = $schema->object([
            'title' => $schema->string()->required()->description('Scenario title'),
            'description' => $schema->string()->required()->description('What to change'),
            'estimated_impact' => $schema->string()->required()->description('Cost/benefit impact'),
        ])->withoutAdditionalProperties();

        return [
            'right_sizing_summary' => $schema->string()->required()->description('Right-sizing recommendation'),
            'replacement_timing_summary' => $schema->string()->required()->description('Replacement timing recommendation'),
            'fleet_mix_summary' => $schema->string()->required()->description('Recommended fleet mix'),
            'what_if_scenarios' => $schema->array()->items($whatIfItem)->required()->description('What-if scenario options'),
        ];
    }
}
