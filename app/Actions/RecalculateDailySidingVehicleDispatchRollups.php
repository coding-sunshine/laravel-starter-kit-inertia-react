<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DailySidingVehicleDispatchRollup;
use App\Support\Rollups\SidingVehicleDispatchRollupSql;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Rebuild {@see DailySidingVehicleDispatchRollup} rows from {@see \App\Models\SidingVehicleDispatch} facts using DB-side aggregates.
 */
final readonly class RecalculateDailySidingVehicleDispatchRollups
{
    /**
     * Ensures optional CLI overrides fall inside the FY containing {@see CarbonInterface $anchor}.
     *
     * @throws InvalidArgumentException
     */
    public static function assertDatesWithinIndianFiscalYear(CarbonInterface $anchor, string $fromDate, string $toDate): void
    {
        $anchorDay = CarbonImmutable::parse($anchor)->startOfDay();
        $tzName = $anchorDay->timezone->getName();
        $fyStartYear = $anchorDay->month >= 4 ? $anchorDay->year : $anchorDay->year - 1;
        $fyAprilFirst = CarbonImmutable::parse(sprintf('%d-04-01', $fyStartYear), $tzName)->startOfDay();
        $fyEndInclusive = $fyAprilFirst->addYear()->subDay()->startOfDay();
        $fyLabel = sprintf('%d-%s', $fyStartYear, mb_substr((string) ($fyStartYear + 1), -2));

        $from = CarbonImmutable::parse($fromDate)->startOfDay();
        $to = CarbonImmutable::parse($toDate)->startOfDay();

        if ($from->lt($fyAprilFirst) || $to->gt($fyEndInclusive)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Dates must fall within FY %s (%s → %s). Got %s → %s.',
                    $fyLabel,
                    $fyAprilFirst->toDateString(),
                    $fyEndInclusive->toDateString(),
                    $from->toDateString(),
                    $to->toDateString(),
                )
            );
        }

        if ($from->gt($to)) {
            throw new InvalidArgumentException(sprintf(
                'From date (%s) must be before or equal to to date (%s).',
                $from->toDateString(),
                $to->toDateString(),
            ));
        }
    }

    /**
     * Run the same aggregate as {@see self::handle()} but return rows only (no delete, no insert). For dry-run / inspection.
     *
     * @return Collection<int, object{issued_on_date: string, siding_id: int, shift_number: int, dispatches_count: int, qty_mineral_mt: string}>
     */
    public function preview(string $fromDate, string $toDate, ?int $sidingId = null): Collection
    {
        $fromDateParsed = CarbonImmutable::parse($fromDate)->startOfDay()->toDateString();
        $toDateParsed = CarbonImmutable::parse($toDate)->startOfDay()->toDateString();

        return $this->groupedAggregatesQuery($fromDateParsed, $toDateParsed, $sidingId, null)
            ->orderBy('issued_on_date')
            ->orderBy('siding_id')
            ->orderBy('shift_number')
            ->get();
    }

    /**
     * Rebuild rollup rows for every calendar {@see issued_on} day between {@see string $fromDate} and {@see string $toDate} inclusive (Y-m-d).
     *
     * Passing {@see int|null $sidingId} scopes deletes and recomputation to one siding (optional targeted rebuild).
     *
     * @return int Rows inserted into {@see DailySidingVehicleDispatchRollup}.
     */
    public function handle(string $fromDate, string $toDate, ?int $sidingId = null): int
    {
        $fromDateParsed = CarbonImmutable::parse($fromDate)->startOfDay()->toDateString();
        $toDateParsed = CarbonImmutable::parse($toDate)->startOfDay()->toDateString();

        return (int) DB::transaction(function () use ($fromDateParsed, $toDateParsed, $sidingId): int {
            $delete = DailySidingVehicleDispatchRollup::query()
                ->whereBetween('issued_on_date', [$fromDateParsed, $toDateParsed]);

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

            return DB::table('daily_siding_vehicle_dispatch_rollups')->insertUsing(
                [
                    'issued_on_date',
                    'siding_id',
                    'shift_number',
                    'dispatches_count',
                    'qty_mineral_mt',
                    'created_at',
                    'updated_at',
                ],
                $subquery
            );
        });
    }

    /**
     * One row per (issued calendar day, siding, shift tier). Pass {@see non-null array $createdUpdatedSameTimestampPair} for insert subquery bindings.
     *
     * @param  array{0: string, 1: string}|null  $createdUpdatedSameTimestampPair
     */
    private function groupedAggregatesQuery(string $fromDateParsed, string $toDateParsed, ?int $sidingId, ?array $createdUpdatedSameTimestampPair): Builder
    {
        $issuedExpr = SidingVehicleDispatchRollupSql::issuedOnCalendarDateExpression();
        $shiftExpr = SidingVehicleDispatchRollupSql::shiftNumberExpression();
        $qtySumExpr = SidingVehicleDispatchRollupSql::roundedSumMineralWeight();

        $select = "{$issuedExpr} as issued_on_date, siding_id, {$shiftExpr} as shift_number, COUNT(*) as dispatches_count, {$qtySumExpr} as qty_mineral_mt";
        $bindings = [];

        if ($createdUpdatedSameTimestampPair !== null) {
            $select .= ', ? as created_at, ? as updated_at';
            $bindings = $createdUpdatedSameTimestampPair;
        }

        return DB::table('siding_vehicle_dispatches')
            ->whereNotNull('issued_on')
            ->when($sidingId !== null, fn ($q) => $q->where('siding_id', $sidingId))
            ->whereRaw("({$issuedExpr}) BETWEEN ? AND ?", [$fromDateParsed, $toDateParsed])
            ->groupBy(DB::raw($issuedExpr), 'siding_id', DB::raw($shiftExpr))
            ->selectRaw($select, $bindings);
    }
}
