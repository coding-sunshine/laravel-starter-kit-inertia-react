<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

/**
 * Agent that analyses fleet data (work orders, service schedules, defects) and returns
 * predictive maintenance findings for the next 2–4 weeks.
 */
final class PredictiveMaintenanceAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public const array URGENCIES = ['low', 'medium', 'high', 'critical'];

    public function instructions(): string
    {
        return 'You are a fleet maintenance analyst. Given data about vehicles, work orders, service schedules, and defects, '
            .'identify maintenance that will likely be needed in the next 2–4 weeks. '
            .'For each finding: specify vehicle_id, the component or system (e.g. brakes, oil, tyres, battery), '
            .'a short reason (e.g. due mileage, recurring defect, schedule due), recommended_action (what to do), '
            .'urgency (low, medium, high, or critical), and confidence between 0 and 1. '
            .'Return only findings you are confident about; omit vehicles with no near-term maintenance needs.';
    }

    public function schema(JsonSchema $schema): array
    {
        $finding = $schema->object([
            'vehicle_id' => $schema->integer()->required()->description('ID of the vehicle'),
            'component' => $schema->string()->required()->description('Component or system, e.g. brakes, oil, tyres'),
            'reason' => $schema->string()->required()->description('Short reason for the recommendation'),
            'recommended_action' => $schema->string()->required()->description('What to do'),
            'urgency' => $schema->string()->required()->description('low, medium, high, or critical'),
            'confidence' => $schema->number()->min(0)->max(1)->required()->description('Confidence 0-1'),
        ])->withoutAdditionalProperties();

        return [
            'findings' => $schema->array()->items($finding)->required()->description('List of predictive maintenance findings'),
        ];
    }
}
