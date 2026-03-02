<?php

declare(strict_types=1);

namespace App\Ai\Tools\Fleet;

use App\Models\Fleet\Trip;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

final class GetTrip implements Tool
{
    public function __construct(
        private readonly int $organizationId,
    ) {}

    public function description(): string
    {
        return 'Get a single trip by ID. Returns vehicle, driver, route, planned start, status. Use for "tell me about trip X".';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('Trip ID')->required(),
        ];
    }

    public function handle(Request $request): string|Stringable
    {
        $id = (int) ($request['id'] ?? 0);
        if ($id <= 0) {
            return 'Please provide a valid trip ID.';
        }

        $trip = Trip::withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $this->organizationId)
            ->with(['vehicle:id,registration', 'driver:id,first_name,last_name', 'route:id,name'])
            ->find($id);

        if ($trip === null) {
            return 'Trip not found.';
        }

        $driver = $trip->driver ? $trip->driver->first_name.' '.$trip->driver->last_name : '—';
        return sprintf(
            "Trip #%d: Vehicle %s, Driver %s, Route %s. Planned start: %s. Status: %s. View: /fleet/trips/%d",
            $trip->id,
            $trip->vehicle?->registration ?? '—',
            $driver,
            $trip->route?->name ?? '—',
            $trip->planned_start_time?->format('Y-m-d H:i') ?? '—',
            $trip->status ?? '—',
            $trip->id
        );
    }
}
