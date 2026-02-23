<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\VehicleArrival;
use App\Models\VehicleUnload;
use App\Models\VehicleUnloadStep;
use Illuminate\Support\Facades\DB;

final readonly class CreateUnloadFromArrival
{
    public function handle(VehicleArrival $arrival, int $userId): VehicleUnload
    {
        $existing = VehicleUnload::query()
            ->where('vehicle_arrival_id', $arrival->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($arrival, $userId): VehicleUnload {
            $unload = VehicleUnload::query()->create([
                'vehicle_arrival_id' => $arrival->id,
                'siding_id' => $arrival->siding_id,
                'vehicle_id' => $arrival->vehicle_id,
                'arrival_time' => $arrival->arrived_at,
                'shift' => $arrival->shift,
                'state' => 'IN_PROGRESS',
                'created_by' => $userId,
            ]);

            $steps = [
                ['step_number' => 1, 'status' => 'COMPLETED'],
                ['step_number' => 2, 'status' => 'IN_PROGRESS'],
                ['step_number' => 3, 'status' => 'PENDING'],
                ['step_number' => 4, 'status' => 'PENDING'],
                ['step_number' => 5, 'status' => 'PENDING'],
            ];

            foreach ($steps as $step) {
                VehicleUnloadStep::query()->create([
                    'vehicle_unload_id' => $unload->id,
                    'step_number' => $step['step_number'],
                    'status' => $step['status'],
                    'completed_at' => $step['step_number'] === 1 ? now() : null,
                    'updated_by' => $userId,
                ]);
            }

            return $unload->load('steps');
        });
    }
}
