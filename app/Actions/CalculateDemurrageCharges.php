<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Penalty;
use App\Models\Rake;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CalculateDemurrageCharges - Compute and apply demurrage penalties
 *
 * Railway demurrage is charged per MT per hour when rakes exceed free time.
 * Rate and formula are configurable (config/rrmcs.php). Formula:
 * demurrage_charge = demurrage_hours × loaded_weight_mt × rate_per_mt_hour
 */
final readonly class CalculateDemurrageCharges
{
    /**
     * Calculate demurrage for a single rake
     *
     * @param array{
     *     rate_per_mt_hour?: float,
     *     penalty_type?: string,
     *     apply_penalty?: bool,
     * } $options
     */
    public function calculateForRake(Rake $rake, array $options = []): array
    {
        $rate = (float) ($options['rate_per_mt_hour'] ?? config('rrmcs.demurrage_rate_per_mt_hour', 50));

        // Must have loading end time and expected RR date
        if (! $rake->loading_end_time || ! $rake->rr_expected_date) {
            return [
                'rake_id' => $rake->id,
                'rake_number' => $rake->rake_number,
                'demurrage_hours' => 0,
                'gross_weight_mt' => (float) ($rake->loaded_weight_mt ?? 0),
                'demurrage_charge' => 0,
                'status' => 'incomplete',
                'reason' => 'Missing RR date or loading end time',
            ];
        }

        // Calculate dwell time
        $freeHours = $rake->free_time_minutes / 60;
        $dwellHours = $rake->loading_end_time->diffInHours($rake->rr_expected_date);
        $demurrageHours = max(0, $dwellHours - $freeHours);

        // Calculate charges
        $weight = (float) ($rake->loaded_weight_mt ?? 0);
        $totalCharge = $demurrageHours * $weight * $rate;

        $calculation = [
            'rake_id' => $rake->id,
            'rake_number' => $rake->rake_number,
            'siding_id' => $rake->siding_id,
            'free_hours' => $freeHours,
            'dwell_hours' => $dwellHours,
            'demurrage_hours' => $demurrageHours,
            'gross_weight_mt' => $weight,
            'rate_per_mt_hour' => $rate,
            'demurrage_charge' => $totalCharge,
            'loading_end_time' => $rake->loading_end_time,
            'rr_expected_date' => $rake->rr_expected_date,
            'status' => $demurrageHours > 0 ? 'charged' : 'free',
        ];

        // Apply penalty if requested
        if ($options['apply_penalty'] ?? false) {
            $this->createPenalty(
                $rake,
                $totalCharge,
                $demurrageHours,
                $options['penalty_type'] ?? 'demurrage'
            );
        }

        return $calculation;
    }

    /**
     * Calculate demurrage for all rakes at a siding
     */
    public function calculateForSiding(int $sidingId, array $options = []): Collection
    {
        $rakes = Rake::query()->where('siding_id', $sidingId)
            ->whereIn('state', ['in_transit', 'delivered'])
            ->get();

        return $rakes->map(fn (Rake $rake): array => $this->calculateForRake($rake, $options));
    }

    /**
     * Get pending penalties for a siding
     */
    public function getPendingPenalties(int $sidingId): Collection
    {
        return Penalty::query()->whereHas('rake', function ($query) use ($sidingId): void {
            $query->where('siding_id', $sidingId);
        })
            ->where('penalty_status', 'pending')
            ->with('rake.siding')
            ->get();
    }

    /**
     * Get total pending charges for a siding
     */
    public function getTotalPendingCharges(int $sidingId): float
    {
        return (float) Penalty::query()->whereHas('rake', function ($query) use ($sidingId): void {
            $query->where('siding_id', $sidingId);
        })
            ->where('penalty_status', 'pending')
            ->sum('penalty_amount');
    }

    /**
     * Mark penalties as collected
     */
    public function markAsCollected(int $penaltyId, string $reference = ''): Penalty
    {
        return DB::transaction(function () use ($penaltyId, $reference): Penalty {
            $penalty = Penalty::query()->findOrFail($penaltyId);

            $penalty->update([
                'penalty_status' => 'incurred',
                'remediation_notes' => mb_trim(($penalty->remediation_notes ?? '')."\nCollected: ".$reference),
            ]);

            return $penalty->refresh();
        });
    }

    /**
     * Get demurrage summary report
     */
    public function getSummaryReport(int $sidingId, ?DateTimeImmutable $fromDate = null, ?DateTimeImmutable $toDate = null): array
    {
        $fromDate ??= DateTimeImmutable::createFromMutable(now()->subMonths(1));
        $toDate ??= DateTimeImmutable::createFromMutable(now());

        $penalties = Penalty::query()->whereHas('rake', function ($query) use ($sidingId): void {
            $query->where('siding_id', $sidingId);
        })
            ->whereBetween('penalty_date', [$fromDate->format('Y-m-d'), $toDate->format('Y-m-d')])
            ->get();

        return [
            'period_from' => $fromDate,
            'period_to' => $toDate,
            'total_rakes_charged' => $penalties->pluck('rake_id')->unique()->count(),
            'total_charges' => (float) $penalties->sum('penalty_amount'),
            'collected' => (float) $penalties->where('penalty_status', 'incurred')->sum('penalty_amount'),
            'pending' => (float) $penalties->where('penalty_status', 'pending')->sum('penalty_amount'),
            'charge_breakdown' => $penalties->groupBy('penalty_type')
                ->map(fn ($group): array => [
                    'count' => $group->count(),
                    'total' => (float) $group->sum('penalty_amount'),
                ])->all(),
        ];
    }

    /**
     * Create penalty record with transparent calculation breakdown.
     */
    private function createPenalty(Rake $rake, float $amount, float $hours, string $type): Penalty
    {
        $rate = (float) config('rrmcs.demurrage_rate_per_mt_hour', 50);
        $weight = (float) ($rake->loaded_weight_mt ?? 0);
        $breakdown = [
            'formula' => 'demurrage_hours × weight_mt × rate_per_mt_hour',
            'demurrage_hours' => round($hours, 2),
            'weight_mt' => round($weight, 2),
            'rate_per_mt_hour' => $rate,
            'free_hours' => $rake->free_time_minutes ? round($rake->free_time_minutes / 60, 2) : null,
            'dwell_hours' => null,
        ];
        if ($rake->loading_end_time && $rake->rr_expected_date) {
            $breakdown['dwell_hours'] = round($rake->loading_end_time->diffInHours($rake->rr_expected_date), 2);
        }

        return Penalty::query()->create([
            'rake_id' => $rake->id,
            'penalty_type' => $type,
            'penalty_amount' => $amount,
            'penalty_status' => 'pending',
            'penalty_date' => now()->toDateString(),
            'description' => sprintf(
                'Demurrage: %s h × %s MT × ₹%s/MT/h = ₹%s',
                number_format($hours, 1),
                number_format($weight, 1),
                number_format($rate, 0),
                number_format($amount, 2)
            ),
            'calculation_breakdown' => $breakdown,
        ]);
    }
}
