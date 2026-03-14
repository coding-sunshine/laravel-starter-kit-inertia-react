<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AutomationRule;
use App\Services\TenantContext;

/**
 * Evaluate active automation rules matching the given event and dispatch processing for matching rules.
 */
final readonly class EvaluateAutomationRulesAction
{
    public function __construct(private ProcessAutomationRuleAction $processor)
    {
        //
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(string $event, array $payload): void
    {
        $organizationId = TenantContext::id();

        if ($organizationId === null) {
            return;
        }

        $rules = AutomationRule::query()
            ->where('organization_id', $organizationId)
            ->where('event', $event)
            ->where('is_active', true)
            ->get();

        foreach ($rules as $rule) {
            if ($this->evaluateConditions($rule->conditions, $payload)) {
                $this->processor->handle($rule, $payload);
            }
        }
    }

    /**
     * @param  array<int, mixed>  $conditions
     * @param  array<string, mixed>  $payload
     */
    private function evaluateConditions(array $conditions, array $payload): bool
    {
        if (empty($conditions)) {
            return true;
        }

        foreach ($conditions as $condition) {
            if (! is_array($condition)) {
                continue;
            }

            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? '=';
            $expectedValue = $condition['value'] ?? null;

            if ($field === null || ! array_key_exists($field, $payload)) {
                continue;
            }

            $actualValue = $payload[$field];

            $matches = match ($operator) {
                '=' => $actualValue === $expectedValue,
                '!=' => $actualValue !== $expectedValue,
                '>' => $actualValue > $expectedValue,
                '<' => $actualValue < $expectedValue,
                'contains' => str_contains((string) $actualValue, (string) $expectedValue),
                default => true,
            };

            if (! $matches) {
                return false;
            }
        }

        return true;
    }
}
