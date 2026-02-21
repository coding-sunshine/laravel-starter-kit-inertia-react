<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Rake;
use App\Models\Siding;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

final class RakesIndexTool extends Tool
{
    protected string $name = 'rakes_index';

    protected string $title = 'List rakes';

    protected string $description = <<<'MARKDOWN'
        List rakes for the authenticated user's accessible sidings. Supports filters for state (placed, loading, loaded, etc.) and siding.
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return Response::error('Authentication required.');
        }

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->pluck('id')->all();

        $query = Rake::query()
            ->with('siding:id,name,code')
            ->whereIn('siding_id', $sidingIds);

        if ($request->get('state')) {
            $query->where('state', $request->get('state'));
        }
        if ($request->get('siding_id')) {
            $query->where('siding_id', (int) $request->get('siding_id'));
        }

        $perPage = min(50, max(1, (int) ($request->get('per_page') ?? 15)));
        $rakes = $query->latest('created_at')->paginate($perPage);

        $data = [
            'data' => $rakes->map(fn (Rake $r): array => [
                'id' => $r->id,
                'rake_number' => $r->rake_number,
                'rake_type' => $r->rake_type,
                'state' => $r->state,
                'siding' => $r->siding ? ['id' => $r->siding->id, 'name' => $r->siding->name, 'code' => $r->siding->code] : null,
                'loading_start_time' => $r->loading_start_time?->toIso8601String(),
                'free_time_minutes' => $r->free_time_minutes,
            ])->all(),
            'total' => $rakes->total(),
            'per_page' => $rakes->perPage(),
            'current_page' => $rakes->currentPage(),
        ];

        return Response::json($data);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'state' => $schema->string()->description('Filter by state: placed, loading, loaded, dispatched, etc.')->nullable(),
            'siding_id' => $schema->integer()->description('Filter by siding ID')->nullable(),
            'per_page' => $schema->integer()->description('Items per page (1-50)')->nullable(),
        ];
    }
}
