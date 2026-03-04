<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Fleet\WorkOrder;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

final class ListWorkOrdersTool extends Tool
{
    protected string $name = 'fleet_list_work_orders';

    protected string $title = 'List fleet work orders';

    protected string $description = <<<'MARKDOWN'
        List work orders for the authenticated user's organization. Requires organization context (uses user's default org).
    MARKDOWN;

    public function handle(Request $request): Response
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return Response::json(['error' => 'Unauthenticated']);
        }

        $org = $user->defaultOrganization() ?? $user->organizations()->first();
        if ($org === null) {
            return Response::json(['data' => [], 'message' => 'User has no organization']);
        }

        TenantContext::set($org);

        $query = WorkOrder::query()->with('vehicle:id,registration');

        $status = $request->get('filter_status');
        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $sort = $request->get('sort', '-created_at');
        if (is_string($sort) && $sort !== '') {
            $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
            $query->orderBy(mb_ltrim($sort, '-'), $direction);
        }

        $perPage = (int) $request->get('per_page', 15);
        $workOrders = $query->paginate($perPage);

        $data = [
            'data' => $workOrders->map(fn (WorkOrder $w): array => [
                'id' => $w->id,
                'work_order_number' => $w->work_order_number,
                'title' => $w->title,
                'status' => $w->status,
                'priority' => $w->priority,
                'vehicle' => $w->vehicle ? ['id' => $w->vehicle->id, 'registration' => $w->vehicle->registration] : null,
            ])->all(),
            'meta' => [
                'current_page' => $workOrders->currentPage(),
                'last_page' => $workOrders->lastPage(),
                'per_page' => $workOrders->perPage(),
                'total' => $workOrders->total(),
            ],
        ];

        return Response::json($data);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'filter_status' => $schema->string()->description('Filter by status (draft, pending, in_progress, completed, cancelled)')->nullable(),
            'sort' => $schema->string()->description('Sort column, prefix with - for desc (e.g. -created_at)')->nullable(),
            'per_page' => $schema->integer()->description('Items per page')->nullable(),
        ];
    }
}
