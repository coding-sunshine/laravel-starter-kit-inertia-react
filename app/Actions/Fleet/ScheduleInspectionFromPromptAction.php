<?php

declare(strict_types=1);

namespace App\Actions\Fleet;

use App\Models\Fleet\Vehicle;
use App\Models\Fleet\VehicleCheck;
use App\Models\Fleet\VehicleCheckTemplate;
use App\Services\TenantContext;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class ScheduleInspectionFromPromptAction
{
    /**
     * Schedule an inspection (vehicle check) from AI/conversation input.
     *
     * @param  array{vehicle_id: int, scheduled_date?: string, template_id?: int}  $input
     */
    public function handle(int $organizationId, int $userId, array $input): VehicleCheck
    {
        $vehicleId = (int) $input['vehicle_id'];
        $vehicle = Vehicle::query()->withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)
            ->where('organization_id', $organizationId)
            ->find($vehicleId);

        throw_if($vehicle === null, InvalidArgumentException::class, "Vehicle {$vehicleId} not found or does not belong to this organization.");

        $templateId = $input['template_id'] ?? null;
        if ($templateId !== null) {
            $template = VehicleCheckTemplate::query()->withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)
                ->where('organization_id', $organizationId)
                ->where('id', $templateId)
                ->first();
            throw_if($template === null, InvalidArgumentException::class, "Template {$templateId} not found or does not belong to this organization.");
        } else {
            $template = VehicleCheckTemplate::query()->withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)
                ->where('organization_id', $organizationId)
                ->where('is_active', true)
                ->whereIn('check_type', ['DVIR', 'inspection', 'Inspection', 'DVIR'])
                ->first()
                ?? VehicleCheckTemplate::query()->withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)
                    ->where('organization_id', $organizationId)
                    ->where('is_active', true)
                    ->first();
            throw_if($template === null, InvalidArgumentException::class, 'No inspection template found for this organization. Create a vehicle check template first.');
        }

        $checkDate = isset($input['scheduled_date']) && $input['scheduled_date'] !== ''
            ? $input['scheduled_date']
            : now()->toDateString();

        $templateId = $template->id;

        return DB::transaction(function () use ($organizationId, $vehicleId, $templateId, $checkDate, $userId): VehicleCheck {
            TenantContext::set($organizationId);

            return VehicleCheck::query()->create([
                'organization_id' => $organizationId,
                'vehicle_id' => $vehicleId,
                'vehicle_check_template_id' => $templateId,
                'check_date' => $checkDate,
                'status' => 'in_progress',
                'created_by' => $userId,
            ]);
        });
    }
}
