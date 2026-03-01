<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

/**
 * Agent that identifies vehicles and drivers at risk of missing compliance renewals
 * (MOT, tax, insurance, licence, CPC) in the next 30/60/90 days.
 */
final class CompliancePredictionAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are a fleet compliance analyst. Given a list of vehicles and drivers with key dates (MOT, tax, insurance, licence, CPC), '
            . 'identify: (1) entities at risk of missing renewal in the next 30, 60, or 90 days; (2) suggested action (renew, book test, training); '
            . '(3) risk level per entity (low, medium, high, critical); (4) any pattern (e.g. same driver missing CPC). '
            . 'Return at_risk_vehicles and at_risk_drivers arrays. Use empty array when none at risk.';
    }

    public function schema(JsonSchema $schema): array
    {
        $vehicleRisk = $schema->object([
            'id' => $schema->integer()->required()->description('Vehicle ID'),
            'type' => $schema->string()->required()->description('Always "vehicle"'),
            'due_date' => $schema->string()->required()->description('Due date or expiry date'),
            'item_description' => $schema->string()->required()->description('What is due e.g. MOT, tax, insurance'),
            'recommended_action' => $schema->string()->required()->description('e.g. renew, book test'),
            'risk_level' => $schema->string()->required()->description('low, medium, high, or critical'),
        ])->withoutAdditionalProperties();

        $driverRisk = $schema->object([
            'id' => $schema->integer()->required()->description('Driver ID'),
            'type' => $schema->string()->required()->description('Always "driver"'),
            'due_date' => $schema->string()->required()->description('Due date or expiry date'),
            'item_description' => $schema->string()->required()->description('What is due e.g. licence, CPC'),
            'recommended_action' => $schema->string()->required()->description('e.g. renew, training'),
            'risk_level' => $schema->string()->required()->description('low, medium, high, or critical'),
        ])->withoutAdditionalProperties();

        return [
            'at_risk_vehicles' => $schema->array()->items($vehicleRisk)->required()->description('Vehicles at compliance risk'),
            'at_risk_drivers' => $schema->array()->items($driverRisk)->required()->description('Drivers at compliance risk'),
        ];
    }
}
