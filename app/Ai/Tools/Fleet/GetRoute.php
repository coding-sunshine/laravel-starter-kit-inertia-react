<?php

declare(strict_types=1);

namespace App\Ai\Tools\Fleet;

use App\Models\Fleet\Route;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

final class GetRoute implements Tool
{
    public function __construct(private readonly int $organizationId) {}

    public function description(): string
    {
        return 'Get a single route by ID with its stops in order.';
    }

    public function schema(JsonSchema $schema): array
    {
        return ['id' => $schema->integer()->description('Route ID')];
    }

    public function handle(Request $request): string|Stringable
    {
        $id = (int) ($request['id'] ?? 0);
        if ($id <= 0) {
            return 'Please provide a valid route ID.';
        }
        $route = Route::withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $this->organizationId)
            ->with('stops:id,route_id,name,sort_order')
            ->find($id);
        if ($route === null) {
            return 'Route not found.';
        }
        $stops = $route->stops->sortBy('sort_order')->map(fn ($s, $i) => ($i + 1) . '. ' . $s->name)->implode(', ');
        return sprintf('Route #%d: %s (%s). Stops: %s. View: /fleet/routes/%d', $route->id, $route->name, $route->route_type, $stops ?: 'none', $route->id);
    }
}
