<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DailyVehicleEntry;
use App\Models\DailyVehicleEntryRollup;
use App\Support\Rollups\DailyVehicleEntryRollupSql;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Rebuild {@see DailyVehicleEntryRollup} rows from {@see DailyVehicleEntry} road-dispatch facts using DB-side aggregates.
 */
final readonly class RecalculateDailyVehicleEntryRollups
{
    /**
     * Run the same aggregate as {@see self::handle()} but return rows only (no delete, no insert). For dry-run / inspection.
     *
     * @return Collection<int, object{rollup_day: string, siding_id: int, shift: int, entries_count: int, completed_entries_count: int, pending_entries_count: int, completed_net_wt_mt: string, pending_gross_wt_mt: string}>
     */
    public function preview(string $fromDate, string $toDate, ?int $sidingId = null): Collection
    {
        $fromDateParsed = CarbonImmutable::parse($fromDate)->startOfDay()->toDateString();
        $toDateParsed = CarbonImmutable::parse($toDate)->startOfDay()->toDateString();

        return $this->groupedAggregatesQuery($fromDateParsed, $toDateParsed, $sidingId, null)
            ->orderBy('rollup_day')
            ->orderBy('siding_id')
            ->orderBy('shift')
            ->get();
    }

    /**
     * Rebuild rollup rows for every calendar {@see rollup_day} between {@see string $fromDate} and {@see string $toDate} inclusive (Y-m-d).
     *
     * Only {@see DailyVehicleEntry::ENTRY_TYPE_ROAD_DISPATCH} rows contribute (same scope as road-dispatch daily vehicle entries UI).
     *
     * @return int Rows inserted into {@see DailyVehicleEntryRollup}.
     */
    public function handle(string $fromDate, string $toDate, ?int $sidingId = null): int
    {
        $fromDateParsed = CarbonImmutable::parse($fromDate)->startOfDay()->toDateString();
        $toDateParsed = CarbonImmutable::parse($toDate)->startOfDay()->toDateString();

        return (int) DB::transaction(function () use ($fromDateParsed, $toDateParsed, $sidingId): int {
            $delete = DailyVehicleEntryRollup::query()
                ->whereBetween('rollup_day', [$fromDateParsed, $toDateParsed]);

            if ($sidingId !== null) {
                $delete->where('siding_id', $sidingId);
            }

            $delete->delete();

            $timestamp = now()->format('Y-m-d H:i:s');

            $subquery = $this->groupedAggregatesQuery(
                $fromDateParsed,
                $toDateParsed,
                $sidingId,
                [$timestamp, $timestamp],
            );

            return DB::table('daily_vehicle_entry_rollups')->insertUsing(
                [
                    'rollup_day',
                    'siding_id',
                    'shift',
                    'entries_count',
                    'completed_entries_count',
                    'pending_entries_count',
                    'completed_net_wt_mt',
                    'pending_gross_wt_mt',
                    'created_at',
                    'updated_at',
                ],
                $subquery
            );
        });
    }

    /**
     * One row per (entry calendar day, siding, shift). Pass {@see non-null array $createdUpdatedSameTimestampPair} for insert subquery bindings.
     *
     * @param  array{0: string, 1: string}|null  $createdUpdatedSameTimestampPair
     */
    private function groupedAggregatesQuery(string $fromDateParsed, string $toDateParsed, ?int $sidingId, ?array $createdUpdatedSameTimestampPair): Builder
    {
        $rollupDayExpr = DailyVehicleEntryRollupSql::rollupDayExpression();
        $completedCountExpr = DailyVehicleEntryRollupSql::completedEntriesCountExpression();
        $pendingCountExpr = DailyVehicleEntryRollupSql::pendingEntriesCountExpression();
        $completedNetSumExpr = DailyVehicleEntryRollupSql::roundedSumCompletedNetWt();
        $pendingGrossSumExpr = DailyVehicleEntryRollupSql::roundedSumPendingGrossWt();

        $select = "{$rollupDayExpr} as rollup_day, daily_vehicle_entries.siding_id, daily_vehicle_entries.shift, "
            .'COUNT(*) as entries_count, '
            ."{$completedCountExpr} as completed_entries_count, "
            ."{$pendingCountExpr} as pending_entries_count, "
            ."{$completedNetSumExpr} as completed_net_wt_mt, "
            ."{$pendingGrossSumExpr} as pending_gross_wt_mt";

        $bindings = [];

        if ($createdUpdatedSameTimestampPair !== null) {
            $select .= ', ? as created_at, ? as updated_at';
            $bindings = $createdUpdatedSameTimestampPair;
        }

        return DB::table('daily_vehicle_entries')
            ->where('daily_vehicle_entries.entry_type', DailyVehicleEntry::ENTRY_TYPE_ROAD_DISPATCH)
            ->when($sidingId !== null, fn ($q) => $q->where('daily_vehicle_entries.siding_id', $sidingId))
            ->whereRaw("({$rollupDayExpr}) BETWEEN ? AND ?", [$fromDateParsed, $toDateParsed])
            ->groupBy(DB::raw($rollupDayExpr), 'daily_vehicle_entries.siding_id', 'daily_vehicle_entries.shift')
            ->selectRaw($select, $bindings);
    }
}
