<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\DispatchReport;
use App\Models\VehicleDispatch;
use Illuminate\Support\Facades\DB;

final readonly class GenerateDispatchReport
{
    /**
     * Generate DPR by joining siding_vehicle_dispatches with daily_vehicle_entries,
     * computing derived fields, and upserting into dispatch_reports.
     * This version handles potential siding_id mismatches.
     *
     * @return int Number of records inserted or updated
     */
    public function handle(?int $sidingId = null): int
    {
        return DB::transaction(function () use ($sidingId): int {
            // First try the original query with siding_id condition
            $query = VehicleDispatch::query()
                ->join(
                    'daily_vehicle_entries as dve',
                    fn ($join) => $join
                        ->on('dve.e_challan_no', '=', 'siding_vehicle_dispatches.pass_no')
                        ->on('dve.siding_id', '=', 'siding_vehicle_dispatches.siding_id')
                )
                ->when($sidingId, fn ($q) => $q->where('siding_vehicle_dispatches.siding_id', $sidingId))
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
                    'dve.e_challan_no',
                    'dve.transport_name',
                    'dve.gross_wt',
                    'dve.tare_wt',
                    'dve.reached_at',
                    'dve.wb_no',
                    'dve.trip_id_no',
                ]);

            $rows = $query->get();
            
            \Log::info('DPR Generation - Original query results', [
                'rows_found' => $rows->count(),
                'siding_id_filter' => $sidingId,
            ]);

            // If no rows found with siding_id condition, try without it
            if ($rows->count() === 0) {
                \Log::info('DPR Generation - Trying without siding_id condition');
                
                $query = VehicleDispatch::query()
                    ->join('daily_vehicle_entries as dve', 'dve.e_challan_no', '=', 'siding_vehicle_dispatches.pass_no')
                    ->when($sidingId, fn ($q) => $q->where('siding_vehicle_dispatches.siding_id', $sidingId))
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
                        'dve.e_challan_no',
                        'dve.transport_name',
                        'dve.gross_wt',
                        'dve.tare_wt',
                        'dve.reached_at',
                        'dve.wb_no',
                        'dve.trip_id_no',
                    ]);

                $rows = $query->get();
                
                \Log::info('DPR Generation - Query without siding condition results', [
                    'rows_found' => $rows->count(),
                ]);
            }

            if ($rows->count() === 0) {
                \Log::warning('DPR Generation - No matching records found with either method');
                return 0;
            }

            $count = 0;
            $insertedData = [];

            foreach ($rows as $row) {
                $grossWt = $row->gross_wt !== null ? (float) $row->gross_wt : null;
                $tareWt = $row->tare_wt !== null ? (float) $row->tare_wt : null;
                $mineralWt = $row->mineral_weight !== null ? (float) $row->mineral_weight : null;

                $netWt = null;
                if ($grossWt !== null && $tareWt !== null) {
                    $netWt = $grossWt - $tareWt;
                }

                $coalTonVariation = null;
                if ($netWt !== null && $mineralWt !== null) {
                    $coalTonVariation = $netWt - $mineralWt;
                }

                $issuedOn = $row->issued_on ? \Carbon\Carbon::parse($row->issued_on) : null;

                $dispatchReportData = [
                    'siding_id' => $row->siding_id,
                    'e_challan_no' => $row->e_challan_no,
                    'ref_no' => $row->ref_no,
                    'issued_on' => $issuedOn?->format('Y-m-d'),
                    'truck_no' => $row->truck_regd_no,
                    'shift' => $row->shift,
                    'date' => $issuedOn?->format('Y-m-d'),
                    'trips' => $row->serial_no,
                    'wo_no' => null,
                    'transport_name' => $row->transport_name,
                    'mineral_wt' => $mineralWt,
                    'gross_wt_siding_rec_wt' => $grossWt,
                    'tare_wt' => $tareWt,
                    'net_wt_siding_rec_wt' => $netWt,
                    'tyres' => null,
                    'coal_ton_variation' => $coalTonVariation,
                    'reached_datetime' => $row->reached_at,
                    'time_taken_trip' => null,
                    'remarks' => null,
                    'wb' => $row->wb_no,
                    'trip_id_no' => $row->trip_id_no,
                ];

                DispatchReport::updateOrCreate(
                    [
                        'siding_id' => $row->siding_id,
                        'e_challan_no' => $row->e_challan_no,
                    ],
                    $dispatchReportData
                );
                
                $insertedData[] = $dispatchReportData;
                $count++;
            }

            \Log::info('DPR Generation - Success', [
                'records_processed' => $count,
                'sample_data' => array_slice($insertedData, 0, 2),
            ]);

            return $count;
        });
    }
}
