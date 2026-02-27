<?php

declare(strict_types=1);

namespace App\Services\Rakes;

use App\Models\GuardInspection;
use App\Models\Rake;
use Exception;
use Illuminate\Support\Facades\DB;

final readonly class GuardInspectionService
{
    public function recordInspection(Rake $rake, array $data): GuardInspection
    {
        return DB::transaction(function () use ($rake, $data) {
            // Ensure loading is completed
            if (! $this->isLoadingCompleted($rake)) {
                throw new Exception('All wagons must be loaded before guard inspection');
            }

            // Remove any existing inspection records
            $rake->guardInspections()->delete();

            // Create new inspection record
            $inspection = GuardInspection::create([
                'rake_id' => $rake->id,
                'inspection_time' => $data['inspection_time'],
                'movement_permission_time' => $data['movement_permission_time'],
                'is_approved' => $data['is_approved'],
                'remarks' => $data['remarks'] ?? null,
            ]);

            // Update rake state based on approval
            if ($data['is_approved']) {
                $this->updateRakeState($rake, 'guard_approved');
            } else {
                $this->updateRakeState($rake, 'guard_rejected');
            }

            return $inspection;
        });
    }

    public function canInspect(Rake $rake): bool
    {
        return $this->isLoadingCompleted($rake) && ! $rake->guardInspections()->exists();
    }

    public function isGuardApproved(Rake $rake): bool
    {
        $inspection = $rake->guardInspections()->first();

        return $inspection?->is_approved ?? false;
    }

    public function isGuardRejected(Rake $rake): bool
    {
        $inspection = $rake->guardInspections()->first();

        return $inspection && ! $inspection->is_approved;
    }

    private function isLoadingCompleted(Rake $rake): bool
    {
        $fitWagonsCount = $rake->wagons()->where('is_unfit', false)->count();
        $loadedWagonsCount = $rake->wagonLoadings()->count();

        return $fitWagonsCount > 0 && $loadedWagonsCount >= $fitWagonsCount;
    }

    private function updateRakeState(Rake $rake, string $state): void
    {
        $rake->update(['state' => $state]);
    }
}
