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
 * Classifies penalty root causes into standardized categories
 * and determines if the penalty was preventable.
 */
#[MaxTokens(1024)]
#[Temperature(0.1)]
#[Timeout(30)]
final class RootCauseClassifierAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are a railway penalty root cause classifier. Given a penalty description, root cause text, '
            .'and penalty type, classify the root cause into a standardized category and subcategory. '
            .'Determine if the penalty was preventable by the siding operator. '
            .'Categories: equipment_failure, operational_delay, documentation_error, overloading, '
            .'weather_force_majeure, railway_authority_issue, communication_gap, resource_shortage. '
            .'Be precise and consistent. If the root cause is ambiguous, use the penalty type and description as context.';
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'category' => $schema->string()->enum([
                'equipment_failure',
                'operational_delay',
                'documentation_error',
                'overloading',
                'weather_force_majeure',
                'railway_authority_issue',
                'communication_gap',
                'resource_shortage',
            ])->required(),
            'subcategory' => $schema->string()->required(),
            'is_preventable' => $schema->boolean()->required(),
            'confidence' => $schema->number()->min(0)->max(1)->required(),
            'suggested_remediation' => $schema->string()->required(),
        ];
    }
}
