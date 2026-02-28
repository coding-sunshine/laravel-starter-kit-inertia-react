<?php

declare(strict_types=1);

namespace App\Ai\Tools\Fleet;

use App\Models\Fleet\Vehicle;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

final class ListVehicles implements Tool
{
    private const DEFAULT_LIMIT = 15;

    public function __construct(
        private readonly int $organizationId,
    ) {}

    public function description(): string
    {
        return 'List fleet vehicles. Optional filters: status (e.g. active, maintenance), limit (max number to return).';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->description('Filter by status: active, maintenance, vor, disposed'),
            'limit' => $schema->integer()->description('Max number of vehicles to return (default 15)'),
        ];
    }

    public function handle(Request $request): string|Stringable
    {
        $query = Vehicle::withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $this->organizationId);

        $status = $request['status'] ?? null;
        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $limit = (int) ($request['limit'] ?? self::DEFAULT_LIMIT);
        $limit = min(max(1, $limit), 50);

        $vehicles = $query->orderBy('registration')->take($limit)->get(['id', 'registration', 'make', 'model', 'status']);

        if ($vehicles->isEmpty()) {
            return 'No vehicles found for this organization.';
        }

        $lines = $vehicles->map(fn ($v) => sprintf('#%d %s – %s %s (%s)', $v->id, $v->registration, $v->make, $v->model, $v->status));

        return 'Vehicles: '."\n".$lines->implode("\n");
    }
}
