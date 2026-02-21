<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Penalty;
use App\Models\Siding;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

final class PenaltiesIndexTool extends Tool
{
    protected string $name = 'penalties_index';

    protected string $title = 'List penalties';

    protected string $description = <<<'MARKDOWN'
        List penalties for the authenticated user's accessible sidings. Supports filters for date range, penalty type, status, and grouping.
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

        $query = Penalty::query()
            ->with(['rake:id,siding_id,rake_number'])
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));

        if ($request->get('date_from')) {
            $query->where('penalty_date', '>=', $request->get('date_from'));
        }
        if ($request->get('date_to')) {
            $query->where('penalty_date', '<=', $request->get('date_to'));
        }
        if ($request->get('penalty_type')) {
            $query->where('penalty_type', $request->get('penalty_type'));
        }
        if ($request->get('status')) {
            $query->where('penalty_status', $request->get('status'));
        }

        $perPage = min(50, max(1, (int) ($request->get('per_page') ?? 15)));
        $penalties = $query->latest('penalty_date')->paginate($perPage);

        $data = [
            'data' => $penalties->map(fn (Penalty $p): array => [
                'id' => $p->id,
                'rake_number' => $p->rake?->rake_number,
                'penalty_type' => $p->penalty_type,
                'penalty_amount' => $p->penalty_amount,
                'penalty_status' => $p->penalty_status,
                'responsible_party' => $p->responsible_party,
                'penalty_date' => $p->penalty_date?->toDateString(),
            ])->all(),
            'total' => $penalties->total(),
            'per_page' => $penalties->perPage(),
            'current_page' => $penalties->currentPage(),
        ];

        return Response::json($data);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'date_from' => $schema->string()->description('Start date (YYYY-MM-DD)')->nullable(),
            'date_to' => $schema->string()->description('End date (YYYY-MM-DD)')->nullable(),
            'penalty_type' => $schema->string()->description('Filter by type: DEM, POL1, POLA, PLO, ULC, SPL, WMC, MCF')->nullable(),
            'status' => $schema->string()->enum(['pending', 'incurred', 'waived', 'disputed'])->description('Filter by status')->nullable(),
            'per_page' => $schema->integer()->description('Items per page (1-50)')->nullable(),
        ];
    }
}
