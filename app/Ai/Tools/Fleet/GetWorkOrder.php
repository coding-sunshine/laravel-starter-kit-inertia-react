<?php

declare(strict_types=1);

namespace App\Ai\Tools\Fleet;

use App\Models\Fleet\WorkOrder;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class GetWorkOrder implements Tool
{
    public function __construct(private int $organizationId) {}

    public function description(): string
    {
        return 'Get a single work order by ID. Returns number, title, status, vehicle, due date, cost.';
    }

    public function schema(JsonSchema $schema): array
    {
        return ['id' => $schema->integer()->description('Work order ID')];
    }

    public function handle(Request $request): string
    {
        $id = (int) ($request['id'] ?? 0);
        if ($id <= 0) {
            return 'Please provide a valid work order ID.';
        }
        $wo = WorkOrder::query()->withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $this->organizationId)
            ->with('vehicle:id,registration')
            ->find($id);
        if ($wo === null) {
            return 'Work order not found.';
        }
        $reg = $wo->vehicle?->registration ?? '—';

        return sprintf(
            'Work order #%d: %s – %s. Status: %s. Vehicle: %s. Due: %s. Total cost: %s. View: /fleet/work-orders/%d',
            $wo->id,
            $wo->work_order_number,
            $wo->title,
            $wo->status,
            $reg,
            $wo->due_date?->format('Y-m-d') ?? '—',
            $wo->total_cost !== null ? (string) $wo->total_cost : '—',
            $wo->id
        );
    }
}
