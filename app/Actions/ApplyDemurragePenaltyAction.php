<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AppliedPenalty;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\RakeLoad;

final readonly class ApplyDemurragePenaltyAction
{
    public function handle(Rake $rake, RakeLoad $rakeLoad, float $demurrageRatePerMtHour): void
    {
        if (! $rakeLoad->placement_time || $rakeLoad->free_time_minutes <= 0) {
            return;
        }

        $freeWindowEnd = $rakeLoad->placement_time->copy()->addMinutes($rakeLoad->free_time_minutes);
        $now = now();

        if ($now->lessThanOrEqualTo($freeWindowEnd)) {
            return;
        }

        $minutesOver = (int) ceil($freeWindowEnd->diffInMinutes($now, false));
        if ($minutesOver <= 0) {
            return;
        }

        $penaltyType = PenaltyType::query()
            ->where('code', 'DEM')
            ->where('is_active', true)
            ->first();

        if (! $penaltyType) {
            return;
        }

        $existing = AppliedPenalty::query()
            ->where('rake_id', $rake->id)
            ->where('penalty_type_id', $penaltyType->id)
            ->first();

        $weightMt = (float) ($rake->loaded_weight_mt ?? $rake->predicted_weight_mt ?? 0.0);
        $hoursOver = $minutesOver / 60;

        $rate = $demurrageRatePerMtHour > 0.0
            ? $demurrageRatePerMtHour
            : (float) ($penaltyType->default_rate ?? 0.0);

        if ($rate <= 0.0 || $weightMt <= 0.0) {
            return;
        }

        $amount = $rate * $weightMt * $hoursOver;

        $payload = [
            'penalty_type_id' => $penaltyType->id,
            'rake_id' => $rake->id,
            'wagon_id' => null,
            'quantity' => $weightMt,
            'distance' => null,
            'rate' => $rate,
            'amount' => $amount,
            'meta' => [
                'minutes_over' => $minutesOver,
                'hours_over' => $hoursOver,
                'free_minutes' => $rakeLoad->free_time_minutes,
                'placement_time' => $rakeLoad->placement_time->toIso8601String(),
                'free_window_end' => $freeWindowEnd->toIso8601String(),
            ],
        ];

        if ($existing) {
            $existing->update($payload);

            return;
        }

        AppliedPenalty::create($payload);
    }
}
