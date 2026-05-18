<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\VehicleWorkorder;
use Illuminate\Support\Facades\DB;

final readonly class DeduplicateVehicleWorkordersByVehicleNoAction
{
    /**
     * Non-null plates that occur more than once: `vehicle_no` => row count (always ≥ 2).
     *
     * @return array<string, int>
     */
    public function duplicatePlateTotals(): array
    {
        $map = VehicleWorkorder::query()
            ->toBase()
            ->selectRaw('vehicle_no, COUNT(*) as aggregate')
            ->whereNotNull('vehicle_no')
            ->groupBy('vehicle_no')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('aggregate', 'vehicle_no')
            ->all();

        /** @var array<string, int> $out */
        $out = [];
        foreach ($map as $vehicleNo => $count) {
            $out[(string) $vehicleNo] = (int) $count;
        }

        return $out;
    }

    /**
     * For each duplicated non-null {@see VehicleWorkorder::$vehicle_no}, keep the row with the largest
     * primary key (`id`) and delete the others (aligned with newest-row behaviour elsewhere).
     *
     * Run with {@see $dryRun} first to inspect impact. Take a backup before deleting.
     *
     * @return array{
     *     duplicate_plate_groups: int,
     *     rows_removed: int,
     *     plates?: array<string, int>
     * }
     */
    public function handle(bool $dryRun = false): array
    {
        $countsByPlate = $this->duplicatePlateTotals();
        $groups = count($countsByPlate);

        if ($groups === 0) {
            return ['duplicate_plate_groups' => 0, 'rows_removed' => 0];
        }

        $rowsWouldRemove = 0;

        foreach ($countsByPlate as $total) {
            if ($total < 2) {
                continue;
            }

            $rowsWouldRemove += $total - 1;
        }

        if ($dryRun) {
            return [
                'duplicate_plate_groups' => $groups,
                'rows_removed' => $rowsWouldRemove,
                'plates' => $countsByPlate,
            ];
        }

        /** @var list<string> $duplicateVehicleNos */
        $duplicateVehicleNos = array_keys($countsByPlate);

        return DB::transaction(function () use ($duplicateVehicleNos, $groups): array {
            $rowsRemoved = 0;

            foreach ($duplicateVehicleNos as $vehicleNo) {
                $orderedIds = VehicleWorkorder::query()
                    ->where('vehicle_no', $vehicleNo)
                    ->orderByDesc('id')
                    ->pluck('id');

                if ($orderedIds->count() < 2) {
                    continue;
                }

                /** @var list<int|string> $toDeleteIds */
                $toDeleteIds = $orderedIds->slice(1)->values()->all();
                $rowsRemoved += VehicleWorkorder::query()->whereIn('id', $toDeleteIds)->delete();
            }

            return ['duplicate_plate_groups' => $groups, 'rows_removed' => $rowsRemoved];
        });
    }
}
