<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

/**
 * Analyzes 90 days of historical penalty data and outputs risk predictions per siding.
 * Runs daily via scheduled command.
 */
#[MaxTokens(4096)]
#[Temperature(0.3)]
#[Timeout(120)]
final class PenaltyPredictionAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are a railway penalty prediction analyst. Given historical penalty data for coal sidings, '
            .'predict the penalty risk for each siding over the next 7 days. '
            .'Consider: penalty frequency trends, seasonal patterns, day-of-week patterns, '
            .'recent escalation or de-escalation, and root cause recurrence. '
            .'Be specific with predicted penalty types and amount ranges based on historical averages. '
            .'Provide actionable recommendations to reduce risk.';
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'predictions' => $schema->array(
                $schema->object([
                    'siding_name' => $schema->string()->required(),
                    'risk_level' => $schema->string()->enum(['high', 'medium', 'low'])->required(),
                    'predicted_penalty_types' => $schema->array($schema->string())->required(),
                    'predicted_amount_min' => $schema->number()->required(),
                    'predicted_amount_max' => $schema->number()->required(),
                    'contributing_factors' => $schema->array($schema->string())->required(),
                    'recommended_actions' => $schema->array($schema->string())->required(),
                ])
            )->required(),
        ];
    }
}
