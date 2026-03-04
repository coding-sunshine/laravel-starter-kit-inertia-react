<?php

declare(strict_types=1);

namespace App\Actions\Fleet;

use App\Services\PrismService;
use App\Services\TenantContext;
use Throwable;

/**
 * Interpret a natural-language query into suggested list filters for fleet list pages.
 * Returns structured filters (e.g. status, odometer_min) for vehicles, drivers, or work_orders.
 */
final readonly class InterpretFleetFiltersAction
{
    private const array SCHEMA = [
        'type' => 'object',
        'properties' => [
            'filters' => [
                'type' => 'object',
                'properties' => [
                    'status' => ['type' => 'string', 'description' => 'Filter by status e.g. active, inactive'],
                    'odometer_min' => ['type' => 'integer', 'description' => 'Minimum odometer reading'],
                    'odometer_max' => ['type' => 'integer', 'description' => 'Maximum odometer reading'],
                    'search' => ['type' => 'string', 'description' => 'Search term for registration, make, or model'],
                ],
                'additionalProperties' => false,
            ],
            'description' => ['type' => 'string', 'description' => 'Short human-readable summary of applied filters'],
        ],
        'required' => ['filters'],
    ];

    public function __construct(
        private PrismService $prism,
    ) {}

    /**
     * @param  'vehicles'|'drivers'|'work_orders'  $listType
     * @return array{filters: array<string, mixed>, description?: string}|null
     */
    public function handle(string $listType, string $naturalLanguageQuery): ?array
    {
        if (! $this->prism->isAvailable()) {
            return null;
        }

        $orgId = TenantContext::id();
        if ($orgId === null) {
            return null;
        }

        $context = match ($listType) {
            'vehicles' => 'Vehicle list: filterable by status (active, inactive, maintenance, etc.), odometer (odometer_min, odometer_max), and search (registration, make, model).',
            'drivers' => 'Driver list: filterable by status (active, inactive, etc.) and search (name).',
            'work_orders' => 'Work order list: filterable by status (pending, in_progress, completed, etc.) and search (title).',
            default => 'List with optional status and search filters.',
        };

        $prompt = sprintf(
            "You are a filter assistant. The user is on a fleet %s page. They said: \"%s\".\n\n%s\n\nReturn a JSON object with a 'filters' object containing only the keys that apply (status, odometer_min, odometer_max, search). Use odometer_min/odometer_max for mileage (e.g. 'over 100k miles' -> odometer_min: 100000). Use 'search' for free-text search. Use 'status' for status filter. Include a short 'description' of what filters you applied. If the query does not imply any filter, return empty filters {}.",
            $listType,
            $naturalLanguageQuery,
            $context,
        );

        try {
            $result = $this->prism->generateStructured($prompt, self::SCHEMA);

            if (! is_array($result) || ! isset($result['filters']) || ! is_array($result['filters'])) {
                return ['filters' => [], 'description' => 'No filters suggested.'];
            }

            $filters = $result['filters'];
            $description = $result['description'] ?? null;

            return [
                'filters' => array_filter($filters, fn ($v): bool => $v !== null && $v !== ''),
                'description' => $description,
            ];
        } catch (Throwable) {
            return null;
        }
    }
}
