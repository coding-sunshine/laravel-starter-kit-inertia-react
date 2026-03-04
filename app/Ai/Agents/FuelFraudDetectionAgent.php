<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

/**
 * Agent that analyses fuel transactions for anomalies (location, time, volume) and
 * returns fraud-risk findings with score and severity.
 */
final class FuelFraudDetectionAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are a fuel fraud analyst. Given fuel transaction data (timestamps, locations, volumes, costs, vehicles, drivers), '
            .'identify transactions that show anomalies suggesting possible fraud: e.g. impossible location or time patterns, '
            .'unusual volume or frequency, mismatched vehicle/driver patterns. '
            .'For each suspicious transaction return: transaction_id (the ID from the data), fraud_score (0-1), '
            .'a short reason, and severity (low, medium, high, or critical). '
            .'Return only transactions that warrant review; omit clearly normal ones.';
    }

    public function schema(JsonSchema $schema): array
    {
        $finding = $schema->object([
            'transaction_id' => $schema->integer()->required()->description('ID of the fuel transaction'),
            'fraud_score' => $schema->number()->min(0)->max(1)->required()->description('Fraud risk 0-1'),
            'reason' => $schema->string()->required()->description('Short reason for the flag'),
            'severity' => $schema->string()->required()->description('low, medium, high, or critical'),
        ])->withoutAdditionalProperties();

        return [
            'findings' => $schema->array()->items($finding)->required()->description('List of fraud-risk findings'),
        ];
    }
}
