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
                    'vw.wo_no as vwo_wo_no',
                    'vw.tyres as vwo_tyres',
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
                    'vwo.vwo_wo_no',
                    'vwo.vwo_tyres',
                ])
                ->orderBy('siding_vehicle_dispatches.id');

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
            $fallbackEntries = $this->fallbackEntriesByTruckDate($rows);
            $tripSequences = [];

            foreach ($rows as $row) {
                $issuedOnDate = $this->normalizeIssuedOn($row->issued_on)?->format('Y-m-d');

                // Only ~14% of daily vehicle entries carry an e-challan number, so the
                // challan join leaves weighbridge and gross weight blank on most
                // dispatches. Fall back to the same truck's entry that day.
                $fallback = $row->gross_wt === null && $row->wb_no === null
                    ? ($fallbackEntries[$this->truckDateKey($row->siding_id, $row->truck_regd_no, $issuedOnDate)] ?? null)
                    : null;

                $grossWt = $row->gross_wt ?? $fallback?->gross_wt;
                $grossWt = $grossWt !== null ? (float) $grossWt : null;

                $tareFromDve = $row->tare_wt ?? $fallback?->tare_wt;
                $tareFromDve = $tareFromDve !== null ? (float) $tareFromDve : null;
                $tareFromWorkorder = $row->vwo_tare_weight !== null ? (float) $row->vwo_tare_weight : null;
                $tareWt = $tareFromDve ?? $tareFromWorkorder;

                // Most entries never record a gate arrival; use the dispatch issue
                // time so the DPR shows the trip date instead of N/A.
                $reachedAtRaw = $row->reached_at ?? $fallback?->reached_at ?? $row->issued_on;
                $wbNo = $row->wb_no ?? $fallback?->wb_no;
                $tripIdNo = $row->trip_id_no ?? $fallback?->trip_id_no;

                $dveTransport = $row->transport_name ?? $fallback?->transport_name;
                $transportName = ($dveTransport !== null && $dveTransport !== '')
                    ? $dveTransport
                    : ($row->vwo_transport_name !== null && $row->vwo_transport_name !== ''
                        ? $row->vwo_transport_name
                        : null);

                $mineralWt = $row->mineral_weight !== null ? (float) $row->mineral_weight : null;

                // A workorder tare is a different weighing event from the gate gross,
                // so subtracting them yields a bogus net. Only net out a same-source pair.
                $netWt = null;
                if ($grossWt !== null && $tareFromDve !== null) {
                    $netWt = $grossWt - $tareFromDve;
                }

                $coalTonVariation = null;
                if ($netWt !== null && $mineralWt !== null) {
                    $coalTonVariation = $netWt - $mineralWt;
                }

                $issuedOn = $this->normalizeIssuedOn($row->issued_on);
                $reachedAt = $reachedAtRaw ? Carbon::parse($reachedAtRaw) : null;
                $timeTakenTrip = null;
                if ($issuedOn !== null && $reachedAt !== null && $reachedAt->greaterThanOrEqualTo($issuedOn)) {
                    $timeTakenTrip = (string) $issuedOn->diffInMinutes($reachedAt);
                }

                $eChallanNo = (string) $row->e_challan_no;

                $dispatchId = (int) $row->dispatch_id;

                // TRIPS is the nth run of this truck on this day, not the pass serial.
                $tripKey = $this->truckDateKey($row->siding_id, $row->truck_regd_no, $issuedOnDate);
                $tripSequences[$tripKey] = ($tripSequences[$tripKey] ?? 0) + 1;

                $dispatchReportData = [
                    'vehicle_dispatch_id' => $dispatchId,
                    'siding_id' => $row->siding_id,
                    'e_challan_no' => $eChallanNo,
                    'ref_no' => $row->ref_no,
                    'issued_on' => $issuedOn?->format('Y-m-d'),
                    'truck_no' => $row->truck_regd_no,
                    'shift' => $row->shift,
                    'date' => $issuedOn?->format('Y-m-d'),
                    'trips' => $tripSequences[$tripKey],
                    'wo_no' => $row->vwo_wo_no,
                    'transport_name' => $transportName,
                    'mineral_wt' => $mineralWt,
                    'gross_wt_siding_rec_wt' => $grossWt,
                    'tare_wt' => $tareWt,
                    'net_wt_siding_rec_wt' => $netWt,
                    'tyres' => $row->vwo_tyres,
                    'coal_ton_variation' => $coalTonVariation,
                    'reached_datetime' => $reachedAtRaw,
                    'time_taken_trip' => $timeTakenTrip,
                    'remarks' => null,
                    'wb' => $wbNo,
                    'trip_id_no' => $tripIdNo,
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

    private function truckDateKey(mixed $sidingId, mixed $truckNo, ?string $date): string
    {
        return implode('|', [(string) $sidingId, mb_strtoupper(mb_trim((string) $truckNo)), (string) $date]);
    }

    /**
     * Latest daily vehicle entry per siding + truck + date for the dispatches in scope,
     * used when the e-challan join finds nothing.
     *
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     * @return array<string, object>
     */
    private function fallbackEntriesByTruckDate($rows): array
    {
        $sidingIds = [];
        $truckNos = [];
        $dates = [];

        foreach ($rows as $row) {
            if ($row->gross_wt !== null && $row->wb_no !== null) {
                continue;
            }

            $date = $this->normalizeIssuedOn($row->issued_on)?->format('Y-m-d');
            $truckNo = mb_trim((string) $row->truck_regd_no);

            if ($date === null || $truckNo === '') {
                continue;
            }

            $sidingIds[(string) $row->siding_id] = $row->siding_id;
            $truckNos[$truckNo] = $truckNo;
            $dates[$date] = $date;
        }

        if ($truckNos === []) {
            return [];
        }

        $entries = DB::table('daily_vehicle_entries')
            ->whereIn('siding_id', array_values($sidingIds))
            ->whereIn('vehicle_no', array_values($truckNos))
            // `entry_date` is a date on Postgres but a datetime string on sqlite,
            // so bound the range with whereDate and match the exact day in PHP.
            ->whereDate('entry_date', '>=', min($dates))
            ->whereDate('entry_date', '<=', max($dates))
            ->orderBy('id')
            ->get([
                'siding_id',
                'vehicle_no',
                'entry_date',
                'transport_name',
                'gross_wt',
                'tare_wt',
                'reached_at',
                'wb_no',
                'trip_id_no',
            ]);

        $byKey = [];

        foreach ($entries as $entry) {
            $key = $this->truckDateKey(
                $entry->siding_id,
                $entry->vehicle_no,
                mb_substr((string) $entry->entry_date, 0, 10)
            );
            $byKey[$key] = $entry;
        }

        return $byKey;
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
