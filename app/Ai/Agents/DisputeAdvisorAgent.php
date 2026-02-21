<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Tools\HistoricalDisputeTool;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

/**
 * Advises on whether to dispute a specific penalty based on historical outcomes.
 */
#[MaxSteps(3)]
#[MaxTokens(2048)]
#[Temperature(0.3)]
#[Timeout(60)]
final class DisputeAdvisorAgent implements Agent, HasStructuredOutput, HasTools
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are a railway penalty dispute advisor. Given a specific penalty and historical dispute data, '
            .'recommend whether to dispute. Consider: penalty type, amount, responsible party, '
            .'historical success rates for similar penalties, and the cost of the dispute process. '
            .'Be data-driven and specific in your reasoning. '
            .'Only recommend disputing if the expected value (success probability × penalty amount) justifies the effort.';
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'should_dispute' => $schema->boolean()->required(),
            'confidence' => $schema->number()->min(0)->required(),
            'estimated_success_probability' => $schema->number()->min(0)->required(),
            'reasoning' => $schema->string()->required(),
            'recommended_grounds' => $schema->array($schema->string())->required(),
        ];
    }

    /**
     * @return iterable<\Laravel\Ai\Contracts\Tool>
     */
    public function tools(): iterable
    {
        return [
            new HistoricalDisputeTool,
        ];
    }
}
