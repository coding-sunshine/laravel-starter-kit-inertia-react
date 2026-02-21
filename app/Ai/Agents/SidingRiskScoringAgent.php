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
 * Generates composite risk scores (0-100) for each siding.
 * Runs nightly via scheduled command.
 */
#[MaxTokens(4096)]
#[Temperature(0.2)]
#[Timeout(120)]
final class SidingRiskScoringAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are a railway siding risk analyst. Given operational data for coal sidings, '
            .'compute a composite risk score (0-100) for each siding where 100 is highest risk. '
            .'Consider: penalty frequency, penalty amounts, demurrage hours, overload incidents, '
            .'month-over-month trends, and operational volume. '
            .'A siding with many penalties relative to rakes processed is higher risk. '
            .'Identify the top risk factors and whether the trend is improving, stable, or worsening.';
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'scores' => $schema->array(
                $schema->object([
                    'siding_name' => $schema->string()->required(),
                    'risk_score' => $schema->integer()->min(0)->required(),
                    'risk_factors' => $schema->array($schema->string())->required(),
                    'trend' => $schema->string()->enum(['improving', 'stable', 'worsening'])->required(),
                ])
            )->required(),
        ];
    }
}
