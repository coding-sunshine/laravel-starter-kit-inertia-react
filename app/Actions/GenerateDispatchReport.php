<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DispatchReport;
use App\Models\VehicleDispatch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Log;

final readonly class GenerateDispatchReport
{
    /**
     * Whether filters include a date window used to scope DPR regeneration (single day or range).
     */
    public static function filtersDefineDateWindow(array $filters): bool
    {
        return ($filters['date'] ?? '') !== ''
            || ($filters['date_from'] ?? '') !== ''
            || ($filters['date_to'] ?? '') !== '';
    }

    /**
     * Generate DPR from siding_vehicle_dispatches, left-joining daily_vehicle_entries when present,
     * and vehicle_workorders (latest per siding + vehicle_no) for transport name / tare when DVE omits them.
     *
     * When the request includes a date window, existing `dispatch_reports` in that window (and siding scope)
     * are removed first so stale rows disappear if dispatches were deleted or challans changed.
     *
     * @param  list<int>  $sidingIds  From {@see \App\Services\SidingContext::activeSidingIds()} — one siding or all accessible.
     * @return int Number of records inserted or updated
     */
    public function handle(array $sidingIds, array $filters = []): int
    {
        if ($sidingIds === []) {
            Log::warning('DPR Generation - No siding IDs in scope; skipping');

            return 0;
        }

        return DB::transaction(function () use ($sidingIds, $filters): int {
            if (self::filtersDefineDateWindow($filters)) {
                $removed = $this->deleteReportsForDateWindow($sidingIds, $filters);
                Log::info('DPR Generation - Cleared existing reports for date scope', [
                    'removed' => $removed,
                    'siding_ids' => $sidingIds,
                ]);
            }

            $latestWorkorderSub = DB::table('vehicle_workorders as vw')
                ->joinSub(
                    DB::table('vehicle_workorders')
                        ->selectRaw('siding_id, vehicle_no, MAX(id) as max_id')
                        ->whereNotNull('vehicle_no')
                        ->where('vehicle_no', '!=', '')
                        ->groupBy('siding_id', 'vehicle_no'),
                    'vwo_max',
                    fn ($join) => $join->on('vw.id', '=', 'vwo_max.max_id')
                )
                ->select([
                    'vw.siding_id',
                    'vw.vehicle_no',
                    'vw.transport_name as vwo_transport_name',
                    'vw.tare_weight as vwo_tare_weight',
                ]);

            $query = VehicleDispatch::query()
                ->leftJoin(
                    'daily_vehicle_entries as dve',
                    fn ($join) => $join
                        ->on('dve.e_challan_no', '=', 'siding_vehicle_dispatches.pass_no')
                        ->on('dve.siding_id', '=', 'siding_vehicle_dispatches.siding_id')
                )
                ->leftJoinSub($latestWorkorderSub, 'vwo', function ($join): void {
                    $join
                        ->on('vwo.siding_id', '=', 'siding_vehicle_dispatches.siding_id')
                        ->on('vwo.vehicle_no', '=', 'siding_vehicle_dispatches.truck_regd_no');
                })
                ->whereIn('siding_vehicle_dispatches.siding_id', array_values($sidingIds))
                ->when($filters['date_from'] ?? null, fn ($q, $value) => $q->whereDate('siding_vehicle_dispatches.issued_on', '>=', (string) $value))
                ->when($filters['date_to'] ?? null, fn ($q, $value) => $q->whereDate('siding_vehicle_dispatches.issued_on', '<=', (string) $value))
                ->when(
                    ($filters['date'] ?? null) && ! ($filters['date_from'] ?? null) && ! ($filters['date_to'] ?? null),
                    fn ($q) => $q->whereDate('siding_vehicle_dispatches.issued_on', (string) $filters['date'])
                )
                ->select([
                    'siding_vehicle_dispatches.id as dispatch_id',
                    'siding_vehicle_dispatches.siding_id',
                    'siding_vehicle_dispatches.ref_no',
                    'siding_vehicle_dispatches.pass_no',
                    'siding_vehicle_dispatches.issued_on',
                    'siding_vehicle_dispatches.truck_regd_no',
                    'siding_vehicle_dispatches.shift',
                    'siding_vehicle_dispatches.serial_no',
                    'siding_vehicle_dispatches.mineral_weight',
                    DB::raw('COALESCE(dve.e_challan_no, siding_vehicle_dispatches.pass_no) as e_challan_no'),
                    'dve.transport_name',
                    'dve.gross_wt',
                    'dve.tare_wt',
                    'dve.reached_at',
                    'dve.wb_no',
                    'dve.trip_id_no',
                    'vwo.vwo_transport_name',
                    'vwo.vwo_tare_weight',
                ]);

            $rows = $query->get();

            Log::info('DPR Generation - Query results', [
                'rows_found' => $rows->count(),
                'siding_ids' => $sidingIds,
            ]);

            if ($rows->count() === 0) {
                Log::warning('DPR Generation - No vehicle dispatches matched filters');

                return 0;
            }

            $count = 0;
            $insertedData = [];

            foreach ($rows as $row) {
                $grossWt = $row->gross_wt !== null ? (float) $row->gross_wt : null;
                $tareFromDve = $row->tare_wt !== null ? (float) $row->tare_wt : null;
                $tareFromWorkorder = $row->vwo_tare_weight !== null ? (float) $row->vwo_tare_weight : null;
                $tareWt = $tareFromDve ?? $tareFromWorkorder;

                $dveTransport = $row->transport_name;
                $transportName = ($dveTransport !== null && $dveTransport !== '')
                    ? $dveTransport
                    : ($row->vwo_transport_name !== null && $row->vwo_transport_name !== ''
                        ? $row->vwo_transport_name
                        : null);

                $mineralWt = $row->mineral_weight !== null ? (float) $row->mineral_weight : null;

                $netWt = null;
                if ($grossWt !== null && $tareWt !== null) {
                    $netWt = $grossWt - $tareWt;
                }

                $coalTonVariation = null;
                if ($netWt !== null && $mineralWt !== null) {
                    $coalTonVariation = $netWt - $mineralWt;
                }

                $issuedOn = $this->normalizeIssuedOn($row->issued_on);
                $reachedAt = $row->reached_at ? Carbon::parse($row->reached_at) : null;
                $timeTakenTrip = null;
                if ($issuedOn !== null && $reachedAt !== null && $reachedAt->greaterThanOrEqualTo($issuedOn)) {
                    $timeTakenTrip = (string) $issuedOn->diffInMinutes($reachedAt);
                }

                $eChallanNo = (string) $row->e_challan_no;

                $dispatchId = (int) $row->dispatch_id;

                $dispatchReportData = [
                    'vehicle_dispatch_id' => $dispatchId,
                    'siding_id' => $row->siding_id,
                    'e_challan_no' => $eChallanNo,
                    'ref_no' => $row->ref_no,
                    'issued_on' => $issuedOn?->format('Y-m-d'),
                    'truck_no' => $row->truck_regd_no,
                    'shift' => $row->shift,
                    'date' => $issuedOn?->format('Y-m-d'),
                    'trips' => $row->serial_no,
                    'wo_no' => null,
                    'transport_name' => $transportName,
                    'mineral_wt' => $mineralWt,
                    'gross_wt_siding_rec_wt' => $grossWt,
                    'tare_wt' => $tareWt,
                    'net_wt_siding_rec_wt' => $netWt,
                    'tyres' => null,
                    'coal_ton_variation' => $coalTonVariation,
                    'reached_datetime' => $row->reached_at,
                    'time_taken_trip' => $timeTakenTrip,
                    'remarks' => null,
                    'wb' => $row->wb_no,
                    'trip_id_no' => $row->trip_id_no,
                ];

                DispatchReport::updateOrCreate(
                    ['vehicle_dispatch_id' => $dispatchId],
                    $dispatchReportData
                );

                $insertedData[] = $dispatchReportData;
                $count++;
            }

            Log::info('DPR Generation - Success', [
                'records_processed' => $count,
                'sample_data' => array_slice($insertedData, 0, 2),
            ]);

            return $count;
        });
    }

    private function normalizeIssuedOn(mixed $issuedOn): ?Carbon
    {
        if ($issuedOn === null || $issuedOn === '') {
            return null;
        }

        $timezone = config('app.timezone');
        if (is_numeric($issuedOn)) {
            return Carbon::createFromTimestamp((int) $issuedOn, $timezone);
        }

        return Carbon::parse((string) $issuedOn, $timezone)->setTimezone($timezone);
    }

    /**
     * Delete dispatch reports matching the same issued-on date logic as the vehicle dispatch query.
     *
     * @param  list<int>  $sidingIds
     */
    private function deleteReportsForDateWindow(array $sidingIds, array $filters): int
    {
        $query = DispatchReport::query()
            ->whereIn('siding_id', array_values($sidingIds))
            ->when($filters['date_from'] ?? null, fn ($q, $value) => $q->whereDate('issued_on', '>=', (string) $value))
            ->when($filters['date_to'] ?? null, fn ($q, $value) => $q->whereDate('issued_on', '<=', (string) $value))
            ->when(
                ($filters['date'] ?? null) && ! ($filters['date_from'] ?? null) && ! ($filters['date_to'] ?? null),
                fn ($q) => $q->whereDate('issued_on', (string) $filters['date'])
            );

        return $query->delete();
    }
}
