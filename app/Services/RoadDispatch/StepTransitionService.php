<?php

declare(strict_types=1);

namespace App\Services\RoadDispatch;

use App\Actions\UpdateStockLedger;
use App\Events\VehicleUnloadStepUpdated;
use App\Models\VehicleUnload;
use App\Models\VehicleUnloadStep;
use App\Models\VehicleUnloadWeighment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class StepTransitionService
{
    public function __construct(
        private UpdateStockLedger $updateStockLedger
    ) {}

    public function recordGrossWeighment(
        VehicleUnload $unload,
        float $grossWeightMt,
        string $status,
        int $userId
    ): VehicleUnload {
        if (! in_array($status, ['PASS', 'FAIL'], true)) {
            throw new InvalidArgumentException('Status must be PASS or FAIL');
        }

        return DB::transaction(function () use ($unload, $grossWeightMt, $status, $userId): VehicleUnload {
            VehicleUnloadWeighment::query()->create([
                'vehicle_unload_id' => $unload->id,
                'gross_weight_mt' => $grossWeightMt,
                'tare_weight_mt' => null,
                'net_weight_mt' => $grossWeightMt,
                'weighment_type' => 'GROSS',
                'weighment_status' => $status,
                'weighment_time' => now(),
            ]);

            $step2 = $unload->steps()->where('step_number', 2)->firstOrFail();
            $step5 = $unload->steps()->where('step_number', 5)->firstOrFail();

            $step2->update([
                'status' => $status === 'PASS' ? 'PASSED' : 'FAILED',
                'completed_at' => now(),
                'updated_by' => $userId,
            ]);

            if ($status === 'PASS') {
                // When gross weighment passes, start the unloading process
                $unload->steps()->where('step_number', 3)->update([
                    'status' => 'IN_PROGRESS',
                    'started_at' => now(),
                    'updated_by' => $userId,
                ]);
                
                // Update the related vehicle arrival status to unloading
                if ($unload->vehicleArrival) {
                    $unload->vehicleArrival->startUnloading();
                    $unload->vehicleArrival->update(['updated_by' => $userId]);
                }
            }
            // If FAIL, don't do anything else - user can try again

            $unload = $unload->refresh()->load('steps', 'weighments');
            event(new VehicleUnloadStepUpdated($unload));

            return $unload;
        });
    }

    public function startUnload(VehicleUnload $unload, int $userId): VehicleUnload
    {
        return DB::transaction(function () use ($unload, $userId): VehicleUnload {
            $unload->update([
                'unload_start_time' => now(),
                'updated_by' => $userId,
            ]);

            $step3 = $unload->steps()->where('step_number', 3)->firstOrFail();
            $step3->update([
                'status' => 'COMPLETED',
                'completed_at' => now(),
                'updated_by' => $userId,
            ]);

            $unload->steps()->where('step_number', 4)->update([
                'status' => 'IN_PROGRESS',
                'started_at' => now(),
                'updated_by' => $userId,
            ]);

            $unload = $unload->refresh()->load('steps', 'weighments', 'vehicleArrival');
            event(new VehicleUnloadStepUpdated($unload));

            return $unload;
        });
    }

    public function recordTareWeighment(
        VehicleUnload $unload,
        float $grossWeightMt,
        float $tareWeightMt,
        string $status,
        int $userId
    ): VehicleUnload {
        if (! in_array($status, ['PASS', 'FAIL'], true)) {
            throw new InvalidArgumentException('Status must be PASS or FAIL');
        }

        $netWeightMt = $grossWeightMt - $tareWeightMt;

        return DB::transaction(function () use ($unload, $grossWeightMt, $tareWeightMt, $netWeightMt, $status, $userId): VehicleUnload {
            VehicleUnloadWeighment::query()->create([
                'vehicle_unload_id' => $unload->id,
                'gross_weight_mt' => $grossWeightMt,
                'tare_weight_mt' => $tareWeightMt,
                'net_weight_mt' => $netWeightMt,
                'weighment_type' => 'TARE',
                'weighment_status' => $status,
                'weighment_time' => now(),
            ]);

            $step4 = $unload->steps()->where('step_number', 4)->firstOrFail();
            $step5 = $unload->steps()->where('step_number', 5)->firstOrFail();

            $step4->update([
                'status' => $status === 'PASS' ? 'PASSED' : 'FAILED',
                'completed_at' => now(),
                'updated_by' => $userId,
            ]);

            if ($status === 'PASS') {
                $unload->steps()->where('step_number', 5)->update([
                    'status' => 'IN_PROGRESS',
                    'started_at' => now(),
                    'updated_by' => $userId,
                ]);
            }
            // If FAIL, don't do anything else - user can try again

            $unload = $unload->refresh()->load('steps', 'weighments');
            event(new VehicleUnloadStepUpdated($unload));

            return $unload;
        });
    }

    public function completeUnload(VehicleUnload $unload, int $userId): VehicleUnload
    {
        return DB::transaction(function () use ($unload, $userId): VehicleUnload {
            $lastTare = $unload->weighments()
                ->where('weighment_type', 'TARE')
                ->where('weighment_status', 'PASS')
                ->latest('weighment_time')
                ->first();

            $quantity = $lastTare ? (float) $lastTare->net_weight_mt : 0;

            $unload->update([
                'unload_end_time' => now(),
                'state' => 'COMPLETED',
                'weighment_weight_mt' => $lastTare?->net_weight_mt,
                'updated_by' => $userId,
            ]);

            VehicleUnloadStep::query()
                ->where('vehicle_unload_id', $unload->id)
                ->where('step_number', 5)
                ->update([
                    'status' => 'COMPLETED',
                    'completed_at' => now(),
                    'updated_by' => $userId,
                ]);

            // Update the related vehicle arrival status
            if ($unload->vehicleArrival) {
                $unload->vehicleArrival->completeUnloading($quantity);
                $unload->vehicleArrival->update(['updated_by' => $userId]);
            }

            if ($quantity > 0) {
                $this->updateStockLedger->recordReceiptFromUnload(
                    $unload,
                    $quantity,
                    'Unload completed',
                    $userId
                );
            }

            $unload = $unload->refresh()->load('steps', 'weighments', 'vehicleArrival');
            event(new VehicleUnloadStepUpdated($unload));

            return $unload;
        });
    }
}
