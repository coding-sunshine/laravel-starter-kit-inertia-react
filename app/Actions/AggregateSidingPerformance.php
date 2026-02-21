<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\CoalStock;
use App\Models\Penalty;
use App\Models\Rake;
use App\Models\Siding;
use App\Models\SidingPerformance;
use App\Models\Wagon;
use Carbon\CarbonImmutable;

/**
 * Aggregates daily performance metrics per siding into the siding_performance table.
 *
 * Designed to run nightly via `rrmcs:aggregate-performance`, with optional
 * --from / --to flags for backfilling historical data.
 */
final readonly class AggregateSidingPerformance
{
    /**
     * Aggregate performance for all sidings on a given date.
     *
     * @return int Number of records upserted
     */
    public function handle(?CarbonImmutable $date = null): int
    {
        $date ??= CarbonImmutable::yesterday();
        $sidingIds = Siding::query()->pluck('id');
        $count = 0;

        foreach ($sidingIds as $sidingId) {
            $this->aggregateForSiding((int) $sidingId, $date);
            $count++;
        }

        return $count;
    }

    /**
     * Aggregate performance for a single siding on a given date.
     */
    public function aggregateForSiding(int $sidingId, CarbonImmutable $date): SidingPerformance
    {
        $rakeIds = Rake::query()
            ->where('siding_id', $sidingId)
            ->pluck('id');

        $rakesProcessed = Rake::query()
            ->where('siding_id', $sidingId)
            ->whereDate('loading_end_time', $date)
            ->count();

        $penalties = Penalty::query()
            ->whereIn('rake_id', $rakeIds)
            ->whereDate('penalty_date', $date);

        $totalPenaltyAmount = (float) $penalties->sum('penalty_amount');
        $penaltyIncidents = $penalties->count();

        $avgDemurrageHours = (int) round(
            (float) Rake::query()
                ->where('siding_id', $sidingId)
                ->whereDate('loading_end_time', $date)
                ->where('demurrage_hours', '>', 0)
                ->avg('demurrage_hours') ?? 0
        );

        $overloadIncidents = Wagon::query()
            ->whereIn('rake_id', $rakeIds)
            ->where('is_overloaded', true)
            ->whereDate('created_at', $date)
            ->count();

        $closingStock = CoalStock::query()
            ->where('siding_id', $sidingId)
            ->whereDate('as_of_date', $date)
            ->value('closing_balance_mt') ?? 0;

        return SidingPerformance::query()->updateOrCreate(
            [
                'siding_id' => $sidingId,
                'as_of_date' => $date->toDateString(),
            ],
            [
                'rakes_processed' => $rakesProcessed,
                'total_penalty_amount' => $totalPenaltyAmount,
                'penalty_incidents' => $penaltyIncidents,
                'average_demurrage_hours' => $avgDemurrageHours,
                'overload_incidents' => $overloadIncidents,
                'closing_stock_mt' => (float) $closingStock,
            ]
        );
    }
}
