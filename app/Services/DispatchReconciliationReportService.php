<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DailyVehicleEntry;
use App\Models\VehicleDispatch;
use Carbon\Carbon;
use DateTimeInterface;

final readonly class DispatchReconciliationReportService
{
    /**
     * Coal-site dispatch vs railway-siding receipt by calendar day and shift (1–3).
     *
     * @return array{
     *     days: list<array{
     *         date: string,
     *         shifts: list<array{
     *             shift: int,
     *             dispatch_trips: int,
     *             dispatch_qty: float,
     *             received_trips: int,
     *             received_qty: float,
     *             in_transit_trips: int,
     *             in_transit_qty: float,
     *             stock_updated_mt: float,
     *             in_progress_gross_mt: float,
     *         }>,
     *         day_total: array{
     *             dispatch_trips: int,
     *             dispatch_qty: float,
     *             received_trips: int,
     *             received_qty: float,
     *             in_transit_trips: int,
     *             in_transit_qty: float,
     *             stock_updated_mt: float,
     *             in_progress_gross_mt: float,
     *         }
     *     }>,
     *     range_total: array{
     *         dispatch_trips: int,
     *         dispatch_qty: float,
     *         received_trips: int,
     *         received_qty: float,
     *         in_transit_trips: int,
     *         in_transit_qty: float,
     *         stock_updated_mt: float,
     *         in_progress_gross_mt: float,
     *     }
     * }
     */
    public function getReport(int $sidingId, string $from, string $to): array
    {
        $fromCarbon = Carbon::parse($from)->startOfDay();
        $toCarbon = Carbon::parse($to)->startOfDay();

        $dispatchMap = $this->aggregateDispatches($sidingId, $fromCarbon->toDateString(), $toCarbon->toDateString());
        $receivedMap = $this->aggregateReceived($sidingId, $fromCarbon->toDateString(), $toCarbon->toDateString());

        $days = [];
        $rangeTotal = $this->emptyMetrics();

        for ($d = $toCarbon->copy(); $d->gte($fromCarbon); $d->subDay()) {
            $dateStr = $d->format('Y-m-d');
            $shiftRows = [];
            $dayTotal = $this->emptyMetrics();

            for ($shift = 1; $shift <= 3; $shift++) {
                $key = $dateStr.'|'.$shift;
                $dispatch = $dispatchMap[$key] ?? ['dispatch_trips' => 0, 'dispatch_qty' => 0.0];
                $received = $receivedMap[$key] ?? [
                    'received_trips' => 0,
                    'received_qty' => 0.0,
                    'stock_updated_mt' => 0.0,
                    'in_progress_gross_mt' => 0.0,
                ];

                $row = $this->mergeMetrics($dispatch, $received);
                $row['shift'] = $shift;
                $shiftRows[] = $row;
                $dayTotal = $this->addMetrics($dayTotal, $row);
            }

            $days[] = [
                'date' => $dateStr,
                'shifts' => $shiftRows,
                'day_total' => $this->stripShiftKey($dayTotal),
            ];

            $rangeTotal = $this->addMetrics($rangeTotal, $dayTotal);
        }

        return [
            'days' => $days,
            'range_total' => $this->stripShiftKey($rangeTotal),
        ];
    }

    /**
     * @return array<string, array{dispatch_trips: int, dispatch_qty: float}>
     */
    private function aggregateDispatches(int $sidingId, string $from, string $to): array
    {
        $issuedOnDate = VehicleDispatch::ISSUED_ON_DATE_SQL;
        $shiftOrder = VehicleDispatch::shiftSortOrderSql();

        $rows = VehicleDispatch::query()
            ->where('siding_id', $sidingId)
            ->whereNotNull('issued_on')
            ->whereRaw("{$issuedOnDate} between ? and ?", [$from, $to])
            ->groupByRaw("{$issuedOnDate}, {$shiftOrder}")
            ->selectRaw("{$issuedOnDate} as report_date, {$shiftOrder} as report_shift, count(*) as dispatch_trips, coalesce(sum(mineral_weight), 0) as dispatch_qty")
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $dateStr = $this->formatReportDate($row->report_date);
            $shift = (int) $row->report_shift;
            if ($shift < 1 || $shift > 3) {
                continue;
            }

            $key = $dateStr.'|'.$shift;
            $map[$key] = [
                'dispatch_trips' => (int) $row->dispatch_trips,
                'dispatch_qty' => round((float) $row->dispatch_qty, 2),
            ];
        }

        return $map;
    }

    /**
     * @return array<string, array{received_trips: int, received_qty: float, stock_updated_mt: float, in_progress_gross_mt: float}>
     */
    private function aggregateReceived(int $sidingId, string $from, string $to): array
    {
        $rows = DailyVehicleEntry::query()
            ->where('siding_id', $sidingId)
            ->where('entry_type', DailyVehicleEntry::ENTRY_TYPE_ROAD_DISPATCH)
            ->whereBetween('entry_date', [$from, $to])
            ->groupBy('entry_date', 'shift')
            ->selectRaw(
                'entry_date, shift, count(*) as received_trips, coalesce(sum(net_wt), 0) as received_qty, sum(case when status = ? then coalesce(net_wt, 0) else 0 end) as stock_updated_mt, sum(case when status != ? then coalesce(gross_wt, 0) else 0 end) as in_progress_gross_mt',
                ['completed', 'completed'],
            )
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $dateStr = $this->formatReportDate($row->entry_date);
            $shift = (int) $row->shift;
            if ($shift < 1 || $shift > 3) {
                continue;
            }

            $key = $dateStr.'|'.$shift;
            $map[$key] = [
                'received_trips' => (int) $row->received_trips,
                'received_qty' => round((float) $row->received_qty, 2),
                'stock_updated_mt' => round((float) ($row->stock_updated_mt ?? 0), 2),
                'in_progress_gross_mt' => round((float) ($row->in_progress_gross_mt ?? 0), 2),
            ];
        }

        return $map;
    }

    private function formatReportDate(mixed $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return (string) $value;
    }

    /**
     * @param  array{dispatch_trips: int, dispatch_qty: float}  $dispatch
     * @param  array{received_trips: int, received_qty: float, stock_updated_mt: float, in_progress_gross_mt: float}  $received
     * @return array<string, int|float>
     */
    private function mergeMetrics(array $dispatch, array $received): array
    {
        $dispatchTrips = $dispatch['dispatch_trips'];
        $dispatchQty = $dispatch['dispatch_qty'];
        $receivedTrips = $received['received_trips'];
        $receivedQty = $received['received_qty'];

        $inTransitTrips = $dispatchTrips - $receivedTrips;
        $inTransitQty = round($dispatchQty - $receivedQty, 2);

        return [
            'dispatch_trips' => $dispatchTrips,
            'dispatch_qty' => $dispatchQty,
            'received_trips' => $receivedTrips,
            'received_qty' => $receivedQty,
            'in_transit_trips' => $inTransitTrips,
            'in_transit_qty' => $inTransitQty,
            'stock_updated_mt' => $received['stock_updated_mt'],
            'in_progress_gross_mt' => $received['in_progress_gross_mt'],
        ];
    }

    /**
     * @return array<string, int|float>
     */
    private function emptyMetrics(): array
    {
        return [
            'dispatch_trips' => 0,
            'dispatch_qty' => 0.0,
            'received_trips' => 0,
            'received_qty' => 0.0,
            'in_transit_trips' => 0,
            'in_transit_qty' => 0.0,
            'stock_updated_mt' => 0.0,
            'in_progress_gross_mt' => 0.0,
        ];
    }

    /**
     * @param  array<string, int|float>  $a
     * @param  array<string, int|float>  $b
     * @return array<string, int|float>
     */
    private function addMetrics(array $a, array $b): array
    {
        return [
            'dispatch_trips' => (int) $a['dispatch_trips'] + (int) ($b['dispatch_trips'] ?? 0),
            'dispatch_qty' => round((float) $a['dispatch_qty'] + (float) ($b['dispatch_qty'] ?? 0), 2),
            'received_trips' => (int) $a['received_trips'] + (int) ($b['received_trips'] ?? 0),
            'received_qty' => round((float) $a['received_qty'] + (float) ($b['received_qty'] ?? 0), 2),
            'in_transit_trips' => (int) $a['in_transit_trips'] + (int) ($b['in_transit_trips'] ?? 0),
            'in_transit_qty' => round((float) $a['in_transit_qty'] + (float) ($b['in_transit_qty'] ?? 0), 2),
            'stock_updated_mt' => round((float) $a['stock_updated_mt'] + (float) ($b['stock_updated_mt'] ?? 0), 2),
            'in_progress_gross_mt' => round((float) $a['in_progress_gross_mt'] + (float) ($b['in_progress_gross_mt'] ?? 0), 2),
        ];
    }

    /**
     * @param  array<string, int|float>  $metrics
     * @return array<string, int|float>
     */
    private function stripShiftKey(array $metrics): array
    {
        unset($metrics['shift']);

        return $metrics;
    }
}
