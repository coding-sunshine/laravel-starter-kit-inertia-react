<?php

declare(strict_types=1);

namespace App\Actions\Fleet;

use App\Models\Fleet\VehicleCheck;
use App\Models\Fleet\VehicleCheckItem;
use Illuminate\Support\Facades\DB;

/**
 * @param  array<int, array{item_index: int, label: string, result_type: string, result?: string|null, value_text?: string|null, notes?: string|null}>  $items
 */
final readonly class SubmitDvirCheck
{
    public function handle(
        int $vehicleId,
        int $vehicleCheckTemplateId,
        string $checkDate,
        ?int $performedByDriverId = null,
        ?int $performedByUserId = null,
        ?int $defectId = null,
        array $items = [],
    ): VehicleCheck {
        return DB::transaction(function () use ($vehicleId, $vehicleCheckTemplateId, $checkDate, $performedByDriverId, $performedByUserId, $defectId, $items): VehicleCheck {
            $check = VehicleCheck::query()->create([
                'vehicle_id' => $vehicleId,
                'vehicle_check_template_id' => $vehicleCheckTemplateId,
                'performed_by_driver_id' => $performedByDriverId,
                'performed_by_user_id' => $performedByUserId,
                'defect_id' => $defectId,
                'check_date' => $checkDate,
                'status' => 'completed',
            ]);

            foreach ($items as $item) {
                VehicleCheckItem::query()->create([
                    'vehicle_check_id' => $check->id,
                    'item_index' => $item['item_index'],
                    'label' => $item['label'],
                    'result_type' => $item['result_type'],
                    'result' => $item['result'] ?? null,
                    'value_text' => $item['value_text'] ?? null,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            return $check;
        });
    }
}
