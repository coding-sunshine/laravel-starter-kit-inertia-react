<?php

declare(strict_types=1);

namespace App\Ai\Tools\Fleet;

use App\Models\Fleet\Trip;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

final class ListTrips implements Tool
{
    private const DEFAULT_LIMIT = 10;

    public function __construct(
        private readonly int $organizationId,
    ) {}

    public function description(): string
    {
        return 'List recent trips. Optional: limit (max number), vehicle_id or driver_id to filter.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()->description('Max number of trips to return (default 10)'),
            'vehicle_id' => $schema->integer()->description('Filter by vehicle ID'),
            'driver_id' => $schema->integer()->description('Filter by driver ID'),
        ];
    }

    public function handle(Request $request): string|Stringable
    {
        $query = Trip::withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $this->organizationId)
            ->with(['vehicle:id,registration', 'driver:id,first_name,last_name'])
            ->orderByDesc('started_at');

        $vehicleId = $request['vehicle_id'] ?? null;
        if (is_numeric($vehicleId)) {
            $query->where('vehicle_id', (int) $vehicleId);
        }
        $driverId = $request['driver_id'] ?? null;
        if (is_numeric($driverId)) {
            $query->where('driver_id', (int) $driverId);
        }

        $limit = (int) ($request['limit'] ?? self::DEFAULT_LIMIT);
        $limit = min(max(1, $limit), 30);

        $trips = $query->take($limit)->get();

        if ($trips->isEmpty()) {
            return 'No trips found.';
        }

        $lines = $trips->map(function ($t) {
            $reg = $t->vehicle?->registration ?? '—';
            $driver = $t->driver ? $t->driver->first_name.' '.$t->driver->last_name : '—';
            $dist = $t->distance_km ? round((float) $t->distance_km, 1).' km' : '—';
            return sprintf('#%d %s | Vehicle %s | Driver %s | %s', $t->id, $t->started_at?->format('Y-m-d H:i') ?? '—', $reg, $driver, $dist);
        });

        return 'Recent trips: '."\n".$lines->implode("\n");
    }
}
