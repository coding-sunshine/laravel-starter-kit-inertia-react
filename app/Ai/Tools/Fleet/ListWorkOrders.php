<?php

declare(strict_types=1);

namespace App\Ai\Tools\Fleet;

use App\Models\Fleet\WorkOrder;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class ListWorkOrders implements Tool
{
    private const int DEFAULT_LIMIT = 15;

    public function __construct(
        private int $organizationId,
    ) {}

    public function description(): string
    {
        return 'List work orders (maintenance/repair). Optional filters: status (e.g. open, in_progress, completed), vehicle_id, limit.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->description('Filter by status: open, in_progress, completed, etc.'),
            'vehicle_id' => $schema->integer()->description('Filter by vehicle ID'),
            'limit' => $schema->integer()->description('Max number of work orders to return (default 15)'),
        ];
    }

    public function handle(Request $request): string
    {
        $query = WorkOrder::query()->withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $this->organizationId)
            ->with('vehicle:id,registration')
            ->latest('scheduled_date');

        $status = $request['status'] ?? null;
        if (is_string($status) && $status !== '') {
            $query->where('status', $status);
        }

        $vehicleId = $request['vehicle_id'] ?? null;
        if ($vehicleId !== null && $vehicleId !== '') {
            $query->where('vehicle_id', (int) $vehicleId);
        }

        $limit = (int) ($request['limit'] ?? self::DEFAULT_LIMIT);
        $limit = min(max(1, $limit), 50);

        $orders = $query->take($limit)->get(['id', 'work_order_number', 'title', 'status', 'vehicle_id', 'scheduled_date']);

        if ($orders->isEmpty()) {
            return 'No work orders found for this organization.';
        }

        $lines = $orders->map(fn ($w): string => sprintf(
            '#%d %s – %s (%s) %s',
            $w->id,
            $w->work_order_number,
            $w->title,
            $w->status,
            $w->scheduled_date?->format('Y-m-d') ?? 'no date'
        ));

        return 'Work orders: '."\n".$lines->implode("\n");
    }
}
