<?php

declare(strict_types=1);

namespace App\Ai\Tools\Fleet;

use App\Models\Fleet\Route;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

final class ListRoutes implements Tool
{
    private const DEFAULT_LIMIT = 15;

    public function __construct(private readonly int $organizationId) {}

    public function description(): string
    {
        return 'List routes. Optional: is_active (true/false), limit.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'is_active' => $schema->boolean()->description('Filter by active status'),
            'limit' => $schema->integer()->description('Max to return (default 15)'),
        ];
    }

    public function handle(Request $request): string|Stringable
    {
        $query = Route::withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $this->organizationId)
            ->orderBy('name');
        if (($active = $request['is_active'] ?? null) !== null && $active !== '') {
            $query->where('is_active', filter_var($active, FILTER_VALIDATE_BOOLEAN));
        }
        $limit = min(max(1, (int) ($request['limit'] ?? self::DEFAULT_LIMIT)), 50);
        $routes = $query->take($limit)->get(['id', 'name', 'route_type', 'is_active']);
        if ($routes->isEmpty()) {
            return 'No routes found for this organization.';
        }
        $lines = $routes->map(fn ($r) => sprintf('#%d %s - %s (%s)', $r->id, $r->name, $r->route_type, $r->is_active ? 'active' : 'inactive'));
        return 'Routes: '."\n".$lines->implode("\n");
    }
}
