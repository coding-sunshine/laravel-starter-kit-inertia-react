<?php

declare(strict_types=1);

namespace App\Services\Rakes;

use App\Models\Rake;
use App\Models\Txr;
use App\Models\Wagon;
use Exception;
use Illuminate\Support\Facades\DB;

final readonly class TxrService
{
    public function startTxr(Rake $rake): Txr
    {
        return DB::transaction(function () use ($rake) {
            // Ensure no active TXR exists
            if ($rake->txr && $rake->txr->status === 'in_progress') {
                throw new Exception('TXR is already in progress for this rake');
            }

            // Create new TXR record
            $txr = Txr::create([
                'rake_id' => $rake->id,
                'inspection_time' => now(),
                'status' => 'in_progress',
            ]);

            // Update rake state
            $this->updateRakeState($rake, 'txr_in_progress');

            return $txr;
        });
    }

    public function endTxr(Rake $rake, array $data): Txr
    {
        return DB::transaction(function () use ($rake, $data) {
            $txr = $rake->txr;

            if (! $txr || $txr->status !== 'in_progress') {
                throw new Exception('No active TXR found for this rake');
            }

            // Update TXR record
            $txr->update([
                'inspection_end_time' => now(),
                'status' => 'completed',
                'remarks' => $data['remarks'] ?? null,
            ]);

            // Mark unfit wagons if provided
            if (! empty($data['unfit_wagons'])) {
                Wagon::whereIn('id', $data['unfit_wagons'])
                    ->where('rake_id', $rake->id)
                    ->update(['is_unfit' => true]);
            }

            // Update rake state
            $this->updateRakeState($rake, 'txr_completed');

            return $txr;
        });
    }

    public function updateTxr(Rake $rake, array $data): Txr
    {
        $txr = $rake->txr;

        if (! $txr) {
            throw new Exception('No TXR found for this rake');
        }

        $txr->update($data);

        return $txr;
    }

    public function canStartTxr(Rake $rake): bool
    {
        return ! $rake->txr || $rake->txr->status !== 'in_progress';
    }

    public function canEndTxr(Rake $rake): bool
    {
        return $rake->txr && $rake->txr->status === 'in_progress';
    }

    private function updateRakeState(Rake $rake, string $state): void
    {
        $rake->update(['state' => $state]);
    }
}
