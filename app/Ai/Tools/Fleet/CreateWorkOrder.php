<?php

declare(strict_types=1);

namespace App\Ai\Tools\Fleet;

use App\Actions\Fleet\CreateWorkOrderFromPromptAction;
use App\Enums\Fleet\WorkOrderPriority;
use App\Enums\Fleet\WorkOrderType;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use InvalidArgumentException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final readonly class CreateWorkOrder implements Tool
{
    public function __construct(
        private int $organizationId,
        private int $userId,
    ) {}

    public function description(): string
    {
        return 'Create a work order (maintenance/repair) for a vehicle. Use when the user asks to create, schedule, or add a work order. Requires vehicle_id and title. Returns a link to the new work order.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'vehicle_id' => $schema->integer()->description('Vehicle ID (must belong to current organization)')->required(),
            'title' => $schema->string()->description('Short title for the work order')->required(),
            'description' => $schema->string()->description('Optional longer description'),
            'work_type' => $schema->string()->description('One of: '.implode(', ', array_column(WorkOrderType::cases(), 'value'))),
            'priority' => $schema->string()->description('One of: '.implode(', ', array_column(WorkOrderPriority::cases(), 'value'))),
            'scheduled_date' => $schema->string()->description('Optional date (Y-m-d) for scheduling'),
        ];
    }

    public function handle(Request $request): string
    {
        $input = [
            'vehicle_id' => (int) $request['vehicle_id'],
            'title' => (string) $request['title'],
            'description' => isset($request['description']) && $request['description'] !== '' ? (string) $request['description'] : null,
            'work_type' => isset($request['work_type']) ? (string) $request['work_type'] : null,
            'priority' => isset($request['priority']) ? (string) $request['priority'] : null,
            'scheduled_date' => isset($request['scheduled_date']) && $request['scheduled_date'] !== '' ? (string) $request['scheduled_date'] : null,
        ];

        try {
            $workOrder = resolve(CreateWorkOrderFromPromptAction::class)->handle(
                $this->organizationId,
                $this->userId,
                $input,
            );
        } catch (InvalidArgumentException $e) {
            return $e->getMessage();
        }

        $url = url("/fleet/work-orders/{$workOrder->id}");

        return "Work order created: #{$workOrder->id} {$workOrder->work_order_number} – {$workOrder->title}. View it here: {$url}";
    }
}
