<?php

declare(strict_types=1);

namespace App\Actions\Fleet;

use App\Enums\Fleet\WorkOrderPriority;
use App\Enums\Fleet\WorkOrderStatus;
use App\Enums\Fleet\WorkOrderType;
use App\Enums\Fleet\WorkOrderUrgency;
use App\Models\Fleet\Vehicle;
use App\Models\Fleet\WorkOrder;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class CreateWorkOrderFromPromptAction
{
    /**
     * Create a work order from AI/conversation input. Validates vehicle belongs to org.
     *
     * @param  array{vehicle_id: int, title: string, description?: string, work_type?: string, priority?: string, scheduled_date?: string}  $input
     */
    public function handle(int $organizationId, int $userId, array $input): WorkOrder
    {
        $vehicleId = (int) $input['vehicle_id'];
        $vehicle = Vehicle::query()->withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)
            ->where('organization_id', $organizationId)
            ->find($vehicleId);

        throw_if($vehicle === null, InvalidArgumentException::class, "Vehicle {$vehicleId} not found or does not belong to this organization.");

        $workType = isset($input['work_type']) && $input['work_type'] !== ''
            ? $input['work_type']
            : WorkOrderType::Repair->value;
        if (! in_array($workType, array_column(WorkOrderType::cases(), 'value'), true)) {
            $workType = WorkOrderType::Repair->value;
        }

        $priority = isset($input['priority']) && $input['priority'] !== ''
            ? $input['priority']
            : WorkOrderPriority::Medium->value;
        if (! in_array($priority, array_column(WorkOrderPriority::cases(), 'value'), true)) {
            $priority = WorkOrderPriority::Medium->value;
        }

        $workOrderNumber = 'WO-'.now()->format('Ymd').'-'.mb_str_pad((string) (WorkOrder::query()->where('organization_id', $organizationId)->count() + 1), 3, '0', STR_PAD_LEFT);

        return DB::transaction(function () use ($organizationId, $userId, $input, $vehicleId, $workType, $priority, $workOrderNumber): WorkOrder {
            TenantContext::set($organizationId);

            return WorkOrder::query()->create([
                'vehicle_id' => $vehicleId,
                'work_order_number' => $workOrderNumber,
                'title' => $input['title'],
                'description' => $input['description'] ?? null,
                'work_type' => $workType,
                'priority' => $priority,
                'status' => WorkOrderStatus::Pending->value,
                'urgency' => WorkOrderUrgency::Routine->value,
                'scheduled_date' => isset($input['scheduled_date']) && $input['scheduled_date'] !== '' ? $input['scheduled_date'] : null,
                'requested_by' => $userId,
            ]);
        });
    }
}
