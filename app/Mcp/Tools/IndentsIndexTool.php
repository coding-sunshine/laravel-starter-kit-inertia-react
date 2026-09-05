<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Indent;
use App\Models\Siding;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

final class IndentsIndexTool extends Tool
{
    protected string $name = 'indents_index';

    protected string $title = 'List indents';

    protected string $description = <<<'MARKDOWN'
        List indents for the authenticated user's accessible sidings. Supports filters for state (pending, acknowledged, placed, etc.) and siding.
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

        $query = Indent::query()
            ->with('siding:id,name,code')
            ->whereIn('siding_id', $sidingIds);

        if ($request->get('state')) {
            $query->where('state', $request->get('state'));
        }
        if ($request->get('siding_id')) {
            $query->where('siding_id', (int) $request->get('siding_id'));
        }

        $perPage = min(50, max(1, (int) ($request->get('per_page') ?? 15)));
        $indents = $query->latest('indent_date')->paginate($perPage);

        $data = [
            'data' => $indents->map(fn (Indent $i): array => [
                'id' => $i->id,
                'indent_number' => $i->indent_number,
                'state' => $i->state,
                'siding' => $i->siding ? ['id' => $i->siding->id, 'name' => $i->siding->name, 'code' => $i->siding->code] : null,
                'target_quantity_mt' => $i->target_quantity_mt,
                'indent_date' => $i->indent_date?->toDateString(),
                'required_by_date' => $i->required_by_date?->toDateString(),
            ])->all(),
            'total' => $indents->total(),
            'per_page' => $indents->perPage(),
            'current_page' => $indents->currentPage(),
        ];

        return Response::json($data);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'state' => $schema->string()->description('Filter by state: pending, acknowledged, placed, etc.')->nullable(),
            'siding_id' => $schema->integer()->description('Filter by siding ID')->nullable(),
            'per_page' => $schema->integer()->description('Items per page (1-50)')->nullable(),
        ];
    }
}
