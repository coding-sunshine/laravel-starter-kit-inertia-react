<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\NotifySuperAdmins;
use App\Models\AppliedPenalty;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\RakeCharge;
use App\Models\SectionTimer;
use Illuminate\Support\Facades\DB;

final readonly class ApplyDemurragePenaltyAction
{
    /**
     * @return array{applied: bool, chargedHours: int, excessMinutes: int, totalMinutes: int, freeMinutes: int, baseRate: float, rateMultiplier: int, amount: float}|null
     */
    public function handle(Rake $rake): ?array
    {
        if ($rake->placement_time === null || $rake->loading_end_time === null) {
            $this->removeDemurragePenalty($rake);

            return null;
        }

        $totalMinutes = (int) $rake->placement_time->diffInMinutes($rake->loading_end_time);
        $freeMinutes = (int) (SectionTimer::query()
            ->where('section_name', 'loading')
            ->value('free_minutes') ?? 300);

        $excessMinutes = $totalMinutes - $freeMinutes;

        if ($excessMinutes <= 0) {
            $this->removeDemurragePenalty($rake);

            return [
                'applied' => false,
                'chargedHours' => 0,
                'excessMinutes' => 0,
                'totalMinutes' => $totalMinutes,
                'freeMinutes' => $freeMinutes,
                'baseRate' => 0.0,
                'rateMultiplier' => 1,
                'amount' => 0.0,
            ];
        }

        $penaltyType = PenaltyType::query()
            ->where('code', 'DEM')
            ->where('is_active', true)
            ->first();

        if (! $penaltyType) {
            return null;
        }

        $baseRate = (float) ($penaltyType->default_rate ?? 0.0);
        $chargedHours = (int) ceil($excessMinutes / 60);
        $rateMultiplier = $this->progressiveMultiplier($chargedHours);
        $wagonCount = max(1, (int) $rake->wagon_count);
        $amount = round($chargedHours * $baseRate * $rateMultiplier * $wagonCount, 2);

        $created = false;

        DB::transaction(function () use ($rake, $penaltyType, $chargedHours, $baseRate, $rateMultiplier, $wagonCount, $amount, $totalMinutes, $freeMinutes, $excessMinutes, &$created): void {
            $rakeCharge = RakeCharge::query()->firstOrCreate(
                [
                    'rake_id' => $rake->id,
                    'charge_type' => 'PENALTY',
                    'is_actual_charges' => false,
                ],
                [
                    'amount' => 0,
                    'data_source' => 'predicted_penalty',
                    'remarks' => 'Predicted penalty aggregate',
                ],
            );

            $applied = AppliedPenalty::query()->updateOrCreate(
                [
                    'rake_id' => $rake->id,
                    'penalty_type_id' => $penaltyType->id,
                    'meta->source' => 'demurrage',
                ],
                [
                    'rake_charge_id' => $rakeCharge->id,
                    'wagon_id' => null,
                    'wagon_number' => null,
                    'quantity' => $chargedHours,
                    'distance' => null,
                    'rate' => $baseRate * $rateMultiplier,
                    'amount' => $amount,
                    'meta' => [
                        'source' => 'demurrage',
                        'placement_time' => $rake->placement_time->toIso8601String(),
                        'loading_end_time' => $rake->loading_end_time->toIso8601String(),
                        'total_minutes' => $totalMinutes,
                        'free_minutes' => $freeMinutes,
                        'excess_minutes' => $excessMinutes,
                        'excess_hours' => $chargedHours,
                        'wagon_count' => $wagonCount,
                        'base_rate' => $baseRate,
                        'rate_multiplier' => $rateMultiplier,
                        'recalculated_at' => null,
                        'correction_reason' => null,
                    ],
                ],
            );

            $created = $applied->wasRecentlyCreated;

            $this->recalculateChargeTotal($rakeCharge);
        });

        if ($created) {
            DB::afterCommit(function () use ($rake, $amount): void {
                NotifySuperAdmins::dispatch(\App\Notifications\PenaltyCreatedNotification::class, [
                    'source' => 'demurrage',
                    'rake_id' => $rake->id,
                    'rake_number' => (string) $rake->rake_number,
                    'siding_id' => $rake->siding_id,
                    'siding_name' => $rake->siding?->name,
                    'amount_total' => $amount,
                    'breakdown' => [
                        ['code' => 'DEM', 'amount' => $amount],
                    ],
                ]);
            });
        }

        return [
            'applied' => true,
            'chargedHours' => $chargedHours,
            'excessMinutes' => $excessMinutes,
            'totalMinutes' => $totalMinutes,
            'freeMinutes' => $freeMinutes,
            'baseRate' => $baseRate,
            'rateMultiplier' => $rateMultiplier,
            'amount' => $amount,
        ];
    }

    /**
     * Indian Railways Board circular May 2022 progressive multiplier tiers.
     */
    private function progressiveMultiplier(int $excessHours): int
    {
        return match (true) {
            $excessHours <= 6 => 1,
            $excessHours <= 12 => 2,
            $excessHours <= 24 => 3,
            $excessHours <= 48 => 4,
            default => 6,
        };
    }

    private function removeDemurragePenalty(Rake $rake): void
    {
        DB::transaction(function () use ($rake): void {
            AppliedPenalty::query()
                ->where('rake_id', $rake->id)
                ->where('meta->source', 'demurrage')
                ->delete();

            $rakeCharge = RakeCharge::query()
                ->where('rake_id', $rake->id)
                ->where('charge_type', 'PENALTY')
                ->where('is_actual_charges', false)
                ->first();

            if ($rakeCharge) {
                $this->recalculateChargeTotal($rakeCharge);
            }
        });
    }

    private function recalculateChargeTotal(RakeCharge $rakeCharge): void
    {
        $total = AppliedPenalty::query()
            ->where('rake_charge_id', $rakeCharge->id)
            ->sum('amount');

        $rakeCharge->update(['amount' => round((float) $total, 2)]);
    }
}
