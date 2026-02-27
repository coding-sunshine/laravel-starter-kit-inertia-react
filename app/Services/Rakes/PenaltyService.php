<?php

declare(strict_types=1);

namespace App\Services\Rakes;

use App\Models\Penalty;
use App\Models\Rake;
use Illuminate\Support\Facades\DB;

final readonly class PenaltyService
{
    public function __construct(private float $demurrageRatePerMtHour) {}

    public function createManualPenalty(Rake $rake, array $data): Penalty
    {
        return DB::transaction(function () use ($rake, $data) {
            return Penalty::create([
                'rake_id' => $rake->id,
                'penalty_type' => $data['penalty_type'],
                'penalty_amount' => $data['penalty_amount'],
                'penalty_status' => 'pending',
                'penalty_date' => now()->toDateString(),
                'description' => $data['description'] ?? null,
            ]);
        });
    }

    public function calculateDemurrage(Rake $rake): ?array
    {
        $rakeLoad = $rake->rakeLoad;

        if (! $rakeLoad || $rakeLoad->status !== 'completed') {
            return null;
        }

        $placementTime = $rakeLoad->placement_time;
        $completionTime = $rakeLoad->updated_at; // When status changed to completed
        $freeTimeMinutes = $rakeLoad->free_time_minutes;

        $elapsedMinutes = $completionTime->diffInMinutes($placementTime);
        $excessMinutes = max(0, $elapsedMinutes - $freeTimeMinutes);

        if ($excessMinutes <= 0) {
            return null;
        }

        $demurrageHours = $excessMinutes / 60;

        // Get total weight from weighment or loading
        $totalWeight = $this->getTotalWeight($rake);

        if ($totalWeight <= 0) {
            return null;
        }

        $penaltyAmount = $demurrageHours * $totalWeight * $this->demurrageRatePerMtHour;

        return [
            'formula' => '(elapsed_hours - free_hours) × weight × rate',
            'elapsed_hours' => $elapsedMinutes / 60,
            'free_hours' => $freeTimeMinutes / 60,
            'demurrage_hours' => $demurrageHours,
            'weight_mt' => $totalWeight,
            'rate_per_mt_hour' => $this->demurrageRatePerMtHour,
            'penalty_amount' => $penaltyAmount,
        ];
    }

    public function createDemurragePenalty(Rake $rake): ?Penalty
    {
        $calculation = $this->calculateDemurrage($rake);

        if (! $calculation) {
            return null;
        }

        return DB::transaction(function () use ($rake, $calculation) {
            // Remove any existing demurrage penalties
            Penalty::where('rake_id', $rake->id)
                ->where('penalty_type', 'demurrage')
                ->delete();

            return Penalty::create([
                'rake_id' => $rake->id,
                'penalty_type' => 'demurrage',
                'penalty_amount' => number_format($calculation['penalty_amount'], 2),
                'penalty_status' => 'pending',
                'penalty_date' => now()->toDateString(),
                'description' => 'Demurrage charges for excess loading time',
                'calculation_breakdown' => $calculation,
            ]);
        });
    }

    public function getTotalPenalties(Rake $rake): float
    {
        return $rake->penalties()
            ->where('penalty_status', '!=', 'waived')
            ->sum('penalty_amount');
    }

    public function getPenaltySummary(Rake $rake): array
    {
        $penalties = $rake->penalties;

        return [
            'total_count' => $penalties->count(),
            'total_amount' => $penalties->sum('penalty_amount'),
            'pending_count' => $penalties->where('penalty_status', 'pending')->count(),
            'paid_count' => $penalties->where('penalty_status', 'paid')->count(),
            'demurrage_count' => $penalties->where('penalty_type', 'demurrage')->count(),
            'manual_count' => $penalties->where('penalty_type', '!=', 'demurrage')->count(),
        ];
    }

    private function getTotalWeight(Rake $rake): float
    {
        // Try to get weight from weighment first
        $weighment = $rake->weighments()->where('status', 'success')->first();
        if ($weighment) {
            return (float) $weighment->total_weight_mt;
        }

        // Fallback to loading data
        return $rake->wagonLoadings()->sum('loaded_quantity_mt');
    }
}
