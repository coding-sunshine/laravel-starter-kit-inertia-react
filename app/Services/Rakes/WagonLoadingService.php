<?php

declare(strict_types=1);

namespace App\Services\Rakes;

use App\Models\Rake;
use App\Models\RakeLoad;
use App\Models\RakeWagonLoading;
use App\Models\Wagon;
use Exception;
use Illuminate\Support\Facades\DB;

final readonly class WagonLoadingService
{
    public function loadWagon(Rake $rake, array $data): RakeWagonLoading
    {
        return DB::transaction(function () use ($rake, $data) {
            // Ensure TXR is completed
            if (! $rake->txr || $rake->txr->status !== 'completed') {
                throw new Exception('TXR must be completed before loading wagons');
            }

            // Ensure wagon exists on this rake (unfit wagons may still be recorded)
            $wagon = Wagon::where('id', $data['wagon_id'])
                ->where('rake_id', $rake->id)
                ->firstOrFail();

            // Ensure wagon is not already loaded
            if (RakeWagonLoading::where('wagon_id', $wagon->id)->exists()) {
                throw new Exception('Wagon is already loaded');
            }

            // Get or create rake load
            $rakeLoad = $this->getOrCreateRakeLoad($rake);

            // Create wagon loading record
            $wagonLoading = RakeWagonLoading::create([
                'rake_load_id' => $rakeLoad->id,
                'wagon_id' => $wagon->id,
                'loader_id' => $data['loader_id'],
                'loaded_quantity_mt' => $data['loaded_quantity_mt'],
                'attempt_no' => 1,
                'started_at' => now(),
                'completed_at' => now(),
            ]);

            // Check if all wagons are loaded
            $this->checkAndUpdateLoadingCompletion($rake);

            return $wagonLoading;
        });
    }

    public function canLoadWagon(Rake $rake): bool
    {
        return $rake->txr && $rake->txr->status === 'completed';
    }

    public function isAllWagonsLoaded(Rake $rake): bool
    {
        return $rake->allFitWagonsHavePositiveLoading();
    }

    private function getOrCreateRakeLoad(Rake $rake): RakeLoad
    {
        $rakeLoad = $rake->rakeLoad;

        if (! $rakeLoad) {
            $rakeLoad = RakeLoad::create([
                'rake_id' => $rake->id,
                'placement_time' => now(),
                'free_time_minutes' => $rake->loading_free_minutes ?? 180, // Default 3 hours
                'status' => 'in_progress',
            ]);

            // Update rake state
            $this->updateRakeState($rake, 'loading');
        }

        return $rakeLoad;
    }

    private function checkAndUpdateLoadingCompletion(Rake $rake): void
    {
        if ($this->isAllWagonsLoaded($rake)) {
            $rakeLoad = $rake->rakeLoad;
            if ($rakeLoad) {
                $rakeLoad->update([
                    'status' => 'completed',
                ]);
            }

            // Update rake state
            $this->updateRakeState($rake, 'loading_completed');
        }
    }

    private function updateRakeState(Rake $rake, string $state): void
    {
        $rake->update(['state' => $state]);
    }
}
