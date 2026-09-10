<?php

declare(strict_types=1);

namespace App\Services\CoalTransportReport;

use App\Models\Siding;
use App\Models\VehicleDispatch;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class CoalTransportReportDataBuilder
{
    /** @var list<string> */
    private const array EXPORT_SIDING_CODES = ['PKUR', 'DUMK', 'KURWA'];

    /**
     * Coal Transport Report: trips and qty by shift and siding for one day.
     * Data from `siding_vehicle_dispatches`: trip count = rows, qty = sum of `mineral_weight`.
     * Shift bucket follows {@see VehicleDispatch::scopeForShift} (time of `issued_on`).
     *
     * @param  array<int>  $sidingIds
     * @param  string|null  $shiftFilter  '1'|'2'|'3' to show one shift, null for all
     * @return array{date: string, sidings: array<int, array{id: int, name: string}>, rows: array<int, array{sl_no: int, shift_label: string, siding_metrics: array<int, array{siding_name: string, trips: int, qty: float}>, total_trips: int, total_qty: float}>, totals: array{siding_metrics: array<int, array{siding_name: string, trips: int, qty: float}>, total_trips: int, total_qty: float}}
     */
    public function buildCoalTransportReport(array $sidingIds, CarbonInterface $date, ?string $shiftFilter): array
    {
        $dateStr = $date->toDateString();
        if ($sidingIds === []) {
            return [
                'date' => $dateStr,
                'sidings' => [],
                'rows' => [],
                'totals' => ['siding_metrics' => [], 'total_trips' => 0, 'total_qty' => 0.0],
            ];
        }

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $shiftsToShow = ($shiftFilter !== null && $shiftFilter !== '') ? [(int) $shiftFilter] : [1, 2, 3];
        $shiftLabels = [1 => '1st', 2 => '2nd', 3 => '3rd'];

        $byShiftSiding = $this->aggregateDayByShiftSiding($dateStr, $sidingIds, $shiftsToShow);

        $rows = [];
        $slNo = 1;
        $totalsBySiding = [];
        foreach ($sidings as $s) {
            $totalsBySiding[$s->name] = ['trips' => 0, 'qty' => 0.0];
        }
        $grandTrips = 0;
        $grandQty = 0.0;

        foreach ($shiftsToShow as $shiftNum) {
            $sidingMetrics = [];
            $rowTrips = 0;
            $rowQty = 0.0;
            foreach ($sidings as $siding) {
                $cell = $byShiftSiding[$shiftNum][$siding->id] ?? ['trips' => 0, 'qty' => 0.0];
                $sidingMetrics[] = [
                    'siding_name' => $siding->name,
                    'trips' => $cell['trips'],
                    'qty' => $cell['qty'],
                ];
                $rowTrips += $cell['trips'];
                $rowQty += $cell['qty'];
                $totalsBySiding[$siding->name]['trips'] += $cell['trips'];
                $totalsBySiding[$siding->name]['qty'] += $cell['qty'];
            }
            $grandTrips += $rowTrips;
            $grandQty += $rowQty;
            $rows[] = [
                'sl_no' => $slNo++,
                'shift_label' => $shiftLabels[$shiftNum] ?? (string) $shiftNum,
                'siding_metrics' => $sidingMetrics,
                'total_trips' => $rowTrips,
                'total_qty' => round($rowQty, 2),
            ];
        }

        $totalsSidingMetrics = [];
        foreach ($sidings as $siding) {
            $totalsSidingMetrics[] = [
                'siding_name' => $siding->name,
                'trips' => $totalsBySiding[$siding->name]['trips'],
                'qty' => round($totalsBySiding[$siding->name]['qty'], 2),
            ];
        }

        return [
            'date' => $dateStr,
            'sidings' => $sidings->map(fn (Siding $s): array => ['id' => $s->id, 'name' => $s->name])->values()->all(),
            'rows' => $rows,
            'totals' => [
                'siding_metrics' => $totalsSidingMetrics,
                'total_trips' => $grandTrips,
                'total_qty' => round($grandQty, 2),
            ],
        ];
    }

    /**
     * Month-to-date (start of month through $date) totals per siding from `siding_vehicle_dispatches`.
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array{siding_id: int, trips: int, qty: float}> keyed by siding_id
     */
    public function buildMonthToDateTotalsBySiding(array $sidingIds, CarbonInterface $date): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $rawRows = VehicleDispatch::query()
            ->whereNotNull('issued_on')
            ->whereBetween('issued_on', [
                $date->copy()->startOfMonth()->startOfDay(),
                $date->copy()->endOfDay(),
            ])
            ->whereIn('siding_id', $sidingIds)
            ->selectRaw('siding_id, COUNT(*) as trips, COALESCE(SUM(mineral_weight), 0) as qty')
            ->groupBy('siding_id')
            ->get();

        $out = [];
        foreach ($rawRows as $r) {
            $sid = (int) $r->siding_id;
            $out[$sid] = [
                'siding_id' => $sid,
                'trips' => (int) $r->trips,
                'qty' => round((float) $r->qty, 2),
            ];
        }

        return $out;
    }

    /**
     * Dataset for XLSX: fixed Pakur / Dumka / Kurwa columns (by code), always three shifts, day + month totals.
     *
     * @param  array<int>  $allowedSidingIds
     * @return array{
     *     date: string,
     *     date_display: string,
     *     columns: array<int, array{code: string, label: string, siding_id: int|null}>,
     *     rows: array<int, array{sl_no: int, shift_label: string, cells: array<int, array{trips: int, qty: float}>, total_trips: int, total_qty: float}>,
     *     day_totals: array{cells: array<int, array{trips: int, qty: float}>, total_trips: int, total_qty: float},
     *     month_totals: array{cells: array<int, array{trips: int, qty: float}>, total_trips: int, total_qty: float}
     * }
     */
    public function buildExportData(array $allowedSidingIds, CarbonInterface $date): array
    {
        $dateStr = $date->toDateString();
        $allowedSet = array_fill_keys($allowedSidingIds, true);

        $columns = [];
        foreach (self::EXPORT_SIDING_CODES as $code) {
            $siding = Siding::query()->where('code', $code)->first();
            $id = null;
            if ($siding instanceof Siding && isset($allowedSet[(int) $siding->id])) {
                $id = (int) $siding->id;
            }
            $columns[] = [
                'code' => $code,
                'label' => $this->shortSidingLabel($code, $siding),
                'siding_id' => $id,
            ];
        }

        $sidingIdsForQuery = array_values(array_filter(array_column($columns, 'siding_id'), fn ($v): bool => $v !== null));
        $byShiftSiding = $sidingIdsForQuery === []
            ? []
            : $this->aggregateDayByShiftSiding($dateStr, $sidingIdsForQuery, [1, 2, 3]);

        $shiftLabels = [1 => '1st', 2 => '2nd', 3 => '3rd'];
        $rows = [];
        $slNo = 1;
        $dayColTotals = [
            ['trips' => 0, 'qty' => 0.0],
            ['trips' => 0, 'qty' => 0.0],
            ['trips' => 0, 'qty' => 0.0],
        ];
        $grandDayTrips = 0;
        $grandDayQty = 0.0;

        foreach ([1, 2, 3] as $shiftNum) {
            $cells = [];
            $rowTrips = 0;
            $rowQty = 0.0;
            foreach ($columns as $i => $col) {
                $sid = $col['siding_id'];
                $cell = ($sid !== null && isset($byShiftSiding[$shiftNum][$sid]))
                    ? $byShiftSiding[$shiftNum][$sid]
                    : ['trips' => 0, 'qty' => 0.0];
                $cells[] = $cell;
                $rowTrips += $cell['trips'];
                $rowQty += $cell['qty'];
                $dayColTotals[$i]['trips'] += $cell['trips'];
                $dayColTotals[$i]['qty'] += $cell['qty'];
            }
            $grandDayTrips += $rowTrips;
            $grandDayQty += $rowQty;
            $rows[] = [
                'sl_no' => $slNo++,
                'shift_label' => $shiftLabels[$shiftNum],
                'cells' => $cells,
                'total_trips' => $rowTrips,
                'total_qty' => round($rowQty, 2),
            ];
        }

        $monthBySiding = $this->buildMonthToDateTotalsBySiding($sidingIdsForQuery, $date);
        $monthCells = [];
        $monthRowTrips = 0;
        $monthRowQty = 0.0;
        foreach ($columns as $col) {
            $sid = $col['siding_id'];
            $m = ($sid !== null && isset($monthBySiding[$sid]))
                ? ['trips' => $monthBySiding[$sid]['trips'], 'qty' => $monthBySiding[$sid]['qty']]
                : ['trips' => 0, 'qty' => 0.0];
            $monthCells[] = $m;
            $monthRowTrips += $m['trips'];
            $monthRowQty += $m['qty'];
        }

        return [
            'date' => $dateStr,
            'date_display' => $date->format('d-m-Y'),
            'columns' => $columns,
            'rows' => $rows,
            'day_totals' => [
                'cells' => array_map(fn (array $t): array => [
                    'trips' => $t['trips'],
                    'qty' => round($t['qty'], 2),
                ], $dayColTotals),
                'total_trips' => $grandDayTrips,
                'total_qty' => round($grandDayQty, 2),
            ],
            'month_totals' => [
                'cells' => $monthCells,
                'total_trips' => $monthRowTrips,
                'total_qty' => round($monthRowQty, 2),
            ],
        ];
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array<int>  $shifts
     * @return array<int, array<int, array{trips: int, qty: float}>> shift => siding_id => metrics
     */
    private function aggregateDayByShiftSiding(string $dateStr, array $sidingIds, array $shifts): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $shiftSql = $this->shiftBucketSqlExpression();
        $tbl = (new VehicleDispatch)->getTable();

        $rawRows = VehicleDispatch::query()
            ->from($tbl)
            ->whereDate('issued_on', $dateStr)
            ->whereNotNull('issued_on')
            ->whereIn('siding_id', $sidingIds)
            ->selectRaw("( {$shiftSql} ) as shift_num, {$tbl}.siding_id, COUNT(*) as trips, COALESCE(SUM(mineral_weight), 0) as qty")
            ->groupBy(DB::raw("( {$shiftSql} )"))
            ->groupBy("{$tbl}.siding_id")
            ->get();

        $byShiftSiding = [];
        foreach ($rawRows as $r) {
            $shiftNum = (int) $r->shift_num;
            if (! in_array($shiftNum, $shifts, true)) {
                continue;
            }
            $byShiftSiding[$shiftNum][(int) $r->siding_id] = [
                'trips' => (int) $r->trips,
                'qty' => round((float) $r->qty, 2),
            ];
        }

        return $byShiftSiding;
    }

    /**
     * SQL expression yielding shift bucket 1|2|3 from `issued_on`, aligned with {@see VehicleDispatch::scopeForShift}.
     */
    private function shiftBucketSqlExpression(): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "CASE
                WHEN issued_on IS NULL THEN NULL
                WHEN time(issued_on) <= '08:00:00' THEN 1
                WHEN time(issued_on) <= '16:00:00' THEN 2
                ELSE 3
            END",
            'pgsql' => "CASE
                WHEN issued_on IS NULL THEN NULL
                WHEN (issued_on::time) <= TIME '08:00:00' THEN 1
                WHEN (issued_on::time) <= TIME '16:00:00' THEN 2
                ELSE 3
            END",
            default => "CASE
                WHEN issued_on IS NULL THEN NULL
                WHEN TIME(issued_on) <= '08:00:00' THEN 1
                WHEN TIME(issued_on) <= '16:00:00' THEN 2
                ELSE 3
            END",
        };
    }

    private function shortSidingLabel(string $code, ?Siding $siding): string
    {
        if ($siding instanceof Siding) {
            $name = $siding->name;

            return (string) (str_ends_with($name, ' Siding') ? mb_substr($name, 0, -7) : $name);
        }

        return match ($code) {
            'PKUR' => 'Pakur',
            'DUMK' => 'Dumka',
            'KURWA' => 'Kurwa',
            default => $code,
        };
    }
}
