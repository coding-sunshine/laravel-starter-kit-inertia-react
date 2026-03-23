<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AppliedPenalty;
use App\Models\DailyVehicleEntry;
use App\Models\Indent;
use App\Models\Rake;
use App\Models\RakeCharge;
use App\Models\RakeWagonWeighment;
use App\Models\RrDocument;
use App\Models\RrPenaltySnapshot;
use App\Models\Txr;
use App\Models\WagonLoading;
use App\Models\WagonUnfitLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final readonly class RunReportAction
{
    /**
     * Keys allowed on the /reports page (UI + POST /reports/generate).
     *
     * @var list<string>
     */
    public const array RAKE_MANAGEMENT_REPORT_KEYS = [
        'siding_coal_receipt',
        'rake_indent',
        'txr',
        'unfit_wagon',
        'wagon_loading',
        'weighment',
        'loader_vs_weighment',
        'rake_movement',
        'rr_summary',
        'penalty_register',
    ];

    public const array REPORT_KEYS = [
        'siding_coal_receipt' => ['name' => 'Siding Coal Receipt', 'description' => 'Shift-wise receipt report'],
        'rake_indent' => ['name' => 'Rake Indent', 'description' => 'Indent history report'],
        'txr' => ['name' => 'Rake Placement TXR', 'description' => 'TXR performance report'],
        'unfit_wagon' => ['name' => 'Unfit Wagon Details', 'description' => 'Unfit wagon log'],
        'wagon_loading' => ['name' => 'Wagon Loading Data', 'description' => 'Loader-wise loading report'],
        'weighment' => ['name' => 'Inmotion Weighment', 'description' => 'Weighment data report'],
        'loader_vs_weighment' => ['name' => 'Loader Weighment Comparison', 'description' => 'Overload analysis report'],
        'rake_movement' => ['name' => 'Rake Movement', 'description' => 'Movement delays report'],
        'rr_summary' => ['name' => 'Railway Receipt RR', 'description' => 'RR summary report'],
        'penalty_register' => ['name' => 'Penalty Register', 'description' => 'Penalty breakdown report'],
        'penalty_register_rr_snapshot' => ['name' => 'Penalty Register (RR Snapshot)', 'description' => 'Penalty register from RR penalty snapshots'],
        'penalty_register_applied' => ['name' => 'Penalty Register (Applied)', 'description' => 'Penalty register from applied penalties'],
        'daily_operations' => ['name' => 'Daily Operations Summary', 'description' => 'Stock, rakes, alerts overview'],
        'demurrage_analysis' => ['name' => 'Demurrage Analysis', 'description' => 'Demurrage charges by month'],
        'financial_impact' => ['name' => 'Financial Impact', 'description' => 'Revenue impact and savings'],
        'rake_lifecycle' => ['name' => 'Rake Lifecycle', 'description' => 'Rake processing timeline'],
        'indent_fulfillment' => ['name' => 'Indent Fulfillment', 'description' => 'Indent allocation progress'],
    ];

    /**
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string}  $params
     * @return array<int, array<string, mixed>>
     */
    public function handle(string $key, array $sidingIds, array $params = []): array
    {
        return match ($key) {
            'siding_coal_receipt' => $this->sidingCoalReceipt($sidingIds, $params),
            'rake_indent' => $this->rakeIndent($sidingIds, $params),
            'txr' => $this->txrReport($sidingIds, $params),
            'unfit_wagon' => $this->unfitWagon($sidingIds, $params),
            'wagon_loading' => $this->wagonLoading($sidingIds, $params),
            'weighment' => $this->weighmentReport($sidingIds, $params),
            'loader_vs_weighment' => $this->loaderVsWeighment($sidingIds, $params),
            'rake_movement' => $this->rakeMovement($sidingIds, $params),
            'rr_summary' => $this->rrSummary($sidingIds, $params),
            'penalty_register' => $this->penaltyRegister($sidingIds, $params),
            'penalty_register_rr_snapshot' => $this->penaltyRegisterRrSnapshot($sidingIds, $params),
            'penalty_register_applied' => $this->penaltyRegisterApplied($sidingIds, $params),
            'daily_operations', 'demurrage_analysis', 'financial_impact', 'rake_lifecycle', 'indent_fulfillment' => $this->delegateToGenerateReports($key, $sidingIds, $params),
            default => [],
        };
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string}  $params
     * @return array<int, array<string, mixed>>
     */
    private function sidingCoalReceipt(array $sidingIds, array $params): array
    {
        $remarksExpr = $this->sidingCoalReceiptRemarksAggregateSql('dve.remarks');

        $query = DB::table('daily_vehicle_entries as dve')
            ->join('sidings as s', 's.id', '=', 'dve.siding_id')
            ->whereIn('dve.siding_id', $sidingIds)
            ->where('dve.entry_type', '=', DailyVehicleEntry::ENTRY_TYPE_ROAD_DISPATCH)
            ->whereNotNull('dve.net_wt')
            ->whereNotNull('dve.reached_at');

        if (! empty($params['siding_id'])) {
            $query->where('dve.siding_id', '=', $params['siding_id']);
        }

        if (! empty($params['date_from'])) {
            $query->whereRaw('date(dve.reached_at) >= ?', [$params['date_from']]);
        }
        if (! empty($params['date_to'])) {
            $query->whereRaw('date(dve.reached_at) <= ?', [$params['date_to']]);
        }

        $query->selectRaw("
            s.name as siding_name,
            date(dve.reached_at) as receipt_date,
            dve.shift as shift_num,
            dve.vehicle_no as vehicle_no,
            count(*) as trip_count,
            sum(dve.net_wt) as qty_mt,
            min(dve.reached_at) as first_reached,
            {$remarksExpr} as remarks_agg
        ");

        $query->groupByRaw('s.name, date(dve.reached_at), dve.shift, dve.vehicle_no');
        $query->orderByRaw('date(dve.reached_at) desc');
        $query->orderBy('s.name');
        $query->orderBy('dve.shift');
        $query->orderBy('dve.vehicle_no');

        $limit = $this->resolveLimit($params);
        if ($limit !== null) {
            $query->limit($limit);
        }

        $rows = $query->get();
        $tz = config('app.timezone') ?? 'UTC';

        return collect($rows)->map(function ($r) use ($tz): array {
            $firstReached = Carbon::parse($r->first_reached)->timezone($tz);

            return [
                'Date' => (string) $r->receipt_date,
                'Shift' => $this->formatSidingCoalReceiptShift($r->shift_num !== null ? (int) $r->shift_num : null),
                'Siding (Pakur/Dumka/Kurwa)' => (string) $r->siding_name,
                'Vehicle No' => $r->vehicle_no !== null ? (string) $r->vehicle_no : '',
                'Trips Received' => (int) $r->trip_count,
                'Quantity Received (MT)' => round((float) $r->qty_mt, 2),
                'Receipt Time' => $firstReached->format('Y-m-d H:i'),
                'Remarks' => $r->remarks_agg !== null && $r->remarks_agg !== '' ? (string) $r->remarks_agg : '',
            ];
        })->values()->all();
    }

    private function sidingCoalReceiptRemarksAggregateSql(string $qualifiedColumn): string
    {
        return match (DB::connection()->getDriverName()) {
            'pgsql' => "nullif(string_agg(nullif(trim(both from {$qualifiedColumn}), ''), '; '), '')",
            default => "nullif(group_concat(nullif(trim({$qualifiedColumn}), ''), '; '), '')",
        };
    }

    private function formatSidingCoalReceiptShift(?int $shift): string
    {
        return match ($shift) {
            1 => '1st',
            2 => '2nd',
            3 => '3rd',
            default => $shift !== null ? (string) $shift : '',
        };
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string}  $params
     * @return array<int, array<string, mixed>>
     */
    private function rakeIndent(array $sidingIds, array $params): array
    {
        $query = Indent::query()
            ->with(['siding:id,name', 'createdBy:id,name'])
            ->whereIn('siding_id', $sidingIds);
        $this->applyDateFilter($query, $params, 'indent_date', 'created_at');
        if (! empty($params['siding_id'])) {
            $query->where('siding_id', $params['siding_id']);
        }
        $limit = $this->resolveLimit($params);
        if ($limit !== null) {
            $query->limit($limit);
        }
        $rows = $query->latest('indent_date')->latest()->get();

        return $rows->map(fn ($r): array => [
            'Indent Date' => $r->indent_date?->toDateString(),
            'Siding' => $r->siding?->name,
            'Available Stock (MT)' => $r->available_stock_mt !== null ? (float) $r->available_stock_mt : null,
            'Rake Target Qty (MT)' => $r->target_quantity_mt !== null ? (float) $r->target_quantity_mt : null,
            'Indent Raised By' => $r->createdBy?->name,
            'Indent Time' => $r->indent_time?->format('Y-m-d H:i'),
            'Railway Reference No' => $r->railway_reference_no,
            'Remarks' => $r->remarks,
        ])->values()->all();
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string}  $params
     * @return array<int, array<string, mixed>>
     */
    private function txrReport(array $sidingIds, array $params): array
    {
        $query = Txr::query()
            ->with('rake.siding:id,name')
            ->withCount('wagonUnfitLogs')
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));
        $this->applyDateFilter($query, $params, 'inspection_time');
        if (! empty($params['siding_id'])) {
            $query->whereHas('rake', fn ($q) => $q->where('siding_id', $params['siding_id']));
        }
        $limit = $this->resolveLimit($params);
        if ($limit !== null) {
            $query->limit($limit);
        }
        $rows = $query->orderByDesc('inspection_time')->latest()->get();

        return $rows->map(function (Txr $r): array {
            $durationMin = null;
            if ($r->inspection_time !== null && $r->inspection_end_time !== null) {
                $durationMin = $r->inspection_time->diffInMinutes($r->inspection_end_time);
            }

            return [
                'Rake No' => $r->rake?->rake_number,
                'Siding' => $r->rake?->siding?->name,
                'Rake Placement Time' => $r->rake?->placement_time?->format('Y-m-d H:i'),
                'TXR Start Time' => $r->inspection_time?->format('Y-m-d H:i'),
                'TXR End Time' => $r->inspection_end_time?->format('Y-m-d H:i'),
                'TXR Duration (Min)' => $durationMin,
                'No of Unfit Wagons' => $r->wagon_unfit_logs_count,
                'Remarks' => '',
            ];
        })->values()->all();
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string}  $params
     * @return array<int, array<string, mixed>>
     */
    private function unfitWagon(array $sidingIds, array $params): array
    {
        $query = WagonUnfitLog::query()
            ->with(['wagon:id,wagon_number,wagon_type', 'txr.rake:id,rake_number,siding_id', 'txr.rake.siding:id,name'])
            ->whereHas('txr.rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));

        $this->applyDateFilter($query, $params, 'marked_at', 'created_at');

        if (! empty($params['siding_id'])) {
            $query->whereHas('txr.rake', fn ($q) => $q->where('siding_id', $params['siding_id']));
        }

        $limit = $this->resolveLimit($params);
        if ($limit !== null) {
            $query->limit($limit);
        }

        $rows = $query->orderByDesc('marked_at')->latest()->get();

        return $rows->map(fn ($r): array => [
            'Rake No' => $r->txr?->rake?->rake_number,
            'Wagon No' => $r->wagon?->wagon_number,
            'Wagon Type' => $r->wagon?->wagon_type,
            'Reason Unfit' => $r->reason,
            'Marked By' => '',
            'Marking Method (Flag/Light)' => $r->marking_method,
            'Time' => $r->marked_at?->format('Y-m-d H:i'),
        ])->values()->all();
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string}  $params
     * @return array<int, array<string, mixed>>
     */
    private function wagonLoading(array $sidingIds, array $params): array
    {
        $query = WagonLoading::query()
            ->with(['rake.siding:id,name,code', 'wagon:id,wagon_number'])
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));

        $this->applyDateFilter($query, $params, 'loading_time', 'created_at');

        if (! empty($params['siding_id'])) {
            $query->whereHas('rake', fn ($q) => $q->where('siding_id', $params['siding_id']));
        }

        $limit = $this->resolveLimit($params);
        if ($limit !== null) {
            $query->limit($limit);
        }

        $rows = $query->latest('loading_time')->latest()->get();

        return $rows->map(fn ($r): array => [
            'rake_no' => $r->rake?->rake_number,
            'siding' => $r->rake?->siding?->name,
            'wagon_no' => $r->wagon?->wagon_number,
            'loader_id' => $r->loader_id,
            'loader_operator_name' => $r->loader_operator_name,
            'cc_capacity_mt' => $r->cc_capacity_mt,
            'loaded_qty_mt' => $r->loaded_quantity_mt,
            'loading_time' => $r->loading_time?->toIso8601String(),
            'remarks' => $r->remarks,
        ])->values()->all();
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string}  $params
     * @return array<int, array<string, mixed>>
     */
    private function weighmentReport(array $sidingIds, array $params): array
    {
        $query = RakeWagonWeighment::query()
            ->with('rakeWeighment.rake.siding:id,name')
            ->whereHas('rakeWeighment.rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));

        $this->applyDateFilter($query, $params, 'weighment_time', 'created_at');

        if (! empty($params['siding_id'])) {
            $query->whereHas('rakeWeighment.rake', fn ($q) => $q->where('siding_id', $params['siding_id']));
        }

        $limit = $this->resolveLimit($params);
        if ($limit !== null) {
            $query->limit($limit);
        }

        $rows = $query->orderByDesc('weighment_time')->latest()->get();

        return $rows->map(fn ($r): array => [
            'Rake No' => $r->rakeWeighment?->rake?->rake_number,
            'Wagon No' => $r->wagon_number,
            'Inmotion Gross (MT)' => $r->actual_gross_mt !== null ? (float) $r->actual_gross_mt : null,
            'Inmotion Tare (MT)' => $r->actual_tare_mt !== null ? (float) $r->actual_tare_mt : null,
            'Inmotion Net (MT)' => $r->net_weight_mt !== null ? (float) $r->net_weight_mt : null,
            'Weighment Time' => $r->weighment_time?->format('Y-m-d H:i'),
            'Slip No' => $r->slip_number,
        ])->values()->all();
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string}  $params
     * @return array<int, array<string, mixed>>
     */
    private function loaderVsWeighment(array $sidingIds, array $params): array
    {
        $latestWeighmentPerRake = DB::table('rake_weighments')
            ->selectRaw('MAX(id) as id, rake_id')
            ->groupBy('rake_id');

        $query = DB::table('wagon_loading as wl')
            ->join('rakes as r', 'r.id', '=', 'wl.rake_id')
            ->leftJoin('sidings as s', 's.id', '=', 'r.siding_id')
            ->leftJoinSub($latestWeighmentPerRake, 'lrw', fn ($join) => $join->on('lrw.rake_id', '=', 'wl.rake_id'))
            ->leftJoin('rake_weighments as rw', 'rw.id', '=', 'lrw.id')
            ->leftJoin('rake_wagon_weighments as rww', function ($join): void {
                $join->on('rww.rake_weighment_id', '=', 'rw.id')
                    ->on('rww.wagon_id', '=', 'wl.wagon_id');
            })
            ->whereIn('r.siding_id', $sidingIds);

        if (! empty($params['siding_id'])) {
            $query->where('r.siding_id', '=', $params['siding_id']);
        }

        if (! empty($params['date_from'])) {
            $query->where('wl.loading_time', '>=', $params['date_from']);
        }
        if (! empty($params['date_to'])) {
            $query->where('wl.loading_time', '<=', $params['date_to'].' 23:59:59');
        }

        $query->select([
            'r.rake_number as rake_no',
            'wl.wagon_id',
            'rww.wagon_number as weighment_wagon_no',
            'wl.loaded_quantity_mt as loader_qty_mt',
            'rww.net_weight_mt as inmotion_qty_mt',
            'rww.action_taken as action_taken',
        ])->addSelect(DB::raw('s.name as siding'));

        $limit = $this->resolveLimit($params);
        if ($limit !== null) {
            $query->limit($limit);
        }

        $rows = $query->orderByDesc('wl.loading_time')->get();

        return collect($rows)->map(function ($r): array {
            $loader = $r->loader_qty_mt !== null ? (float) $r->loader_qty_mt : null;
            $inmotion = $r->inmotion_qty_mt !== null ? (float) $r->inmotion_qty_mt : null;
            $difference = ($loader !== null && $inmotion !== null) ? round($loader - $inmotion, 2) : null;

            $flag = null;
            if ($difference !== null) {
                $flag = $difference > 0 ? 'OVER' : ($difference < 0 ? 'UNDER' : 'OK');
            }

            return [
                'Rake No' => $r->rake_no,
                'Wagon No' => $r->weighment_wagon_no,
                'Loader Qty (MT)' => $loader,
                'Inmotion Qty (MT)' => $inmotion,
                'Difference (MT)' => $difference,
                'Overload/Underload Flag' => $flag,
                'Action Taken' => '',
            ];
        })->values()->all();
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string}  $params
     * @return array<int, array<string, mixed>>
     */
    private function rakeMovement(array $sidingIds, array $params): array
    {
        $query = Rake::query()
            ->with('siding:id,name')
            ->whereIn('siding_id', $sidingIds)
            ->whereNotNull('placement_time')
            ->whereNotNull('dispatch_time');
        $this->applyDateFilter($query, $params, 'dispatch_time');
        if (! empty($params['siding_id'])) {
            $query->where('siding_id', $params['siding_id']);
        }
        $limit = $this->resolveLimit($params);
        if ($limit !== null) {
            $query->limit($limit);
        }

        $rows = $query->orderByDesc('dispatch_time')->get();

        return $rows->map(function ($r): array {
            $start = $r->placement_time;
            $end = $r->dispatch_time;
            $minutes = $start && $end ? $start->diffInMinutes($end) : null;

            return [
                'rake_no' => $r->rake_number,
                'loading_completion' => $end?->toIso8601String(),
                'permission_given_time' => null,
                'actual_movement_time' => $end?->toIso8601String(),
                'delay_min' => $minutes,
                'alert_generated' => null,
                'remarks' => null,
            ];
        })->values()->all();
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string}  $params
     * @return array<int, array<string, mixed>>
     */
    private function rrSummary(array $sidingIds, array $params): array
    {
        $query = RrDocument::query()
            ->with(['rake.siding:id,name', 'rake.rakeCharges:id,rake_id,diverrt_destination_id,charge_type,amount,is_actual_charges'])
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));
        $this->applyDateFilter($query, $params, 'rr_received_date');
        if (! empty($params['siding_id'])) {
            $query->whereHas('rake', fn ($q) => $q->where('siding_id', $params['siding_id']));
        }
        $limit = $this->resolveLimit($params);
        if ($limit !== null) {
            $query->limit($limit);
        }

        $rows = $query->latest('rr_received_date')->get();

        return $rows->map(function (RrDocument $r): array {
            $chargeScope = $r->rake?->rakeCharges
                ?->filter(function (RakeCharge $charge) use ($r): bool {
                    return $charge->is_actual_charges
                        && (int) $charge->diverrt_destination_id === (int) $r->diverrt_destination_id;
                });

            $freight = (float) ($chargeScope?->firstWhere('charge_type', 'FREIGHT')?->amount ?? 0.0);
            $penaltyAmount = (float) ($chargeScope?->firstWhere('charge_type', 'PENALTY')?->amount ?? 0.0);
            $gstAmount = (float) ($chargeScope?->firstWhere('charge_type', 'GST')?->amount ?? 0.0);
            $otherChargesAmount = (float) ($chargeScope?->firstWhere('charge_type', 'OTHER_CHARGE')?->amount ?? 0.0);
            $total = $freight + $penaltyAmount + $gstAmount + $otherChargesAmount;

            return [
                'Rake No' => $r->rake?->rake_number,
                'RR No' => $r->rr_number,
                'RR Date' => $r->rr_received_date?->toDateString(),
                'From Siding' => $r->rake?->siding?->name,
                'To Power Plant' => $r->to_station_code,
                'Charged Weight (MT)' => $r->rr_weight_mt !== null ? (float) $r->rr_weight_mt : null,
                'Freight Amount' => round($freight, 2),
                'Penalty Amount' => round($penaltyAmount, 2),
                'GST Amount' => round($gstAmount, 2),
                'Other Charges Amount' => round($otherChargesAmount, 2),
                'Total Amount' => round($total, 2),
            ];
        })->values()->all();
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string}  $params
     * @return array<int, array<string, mixed>>
     */
    private function penaltyRegister(array $sidingIds, array $params): array
    {
        $appliedQuery = AppliedPenalty::query()
            ->with(['rake.siding:id,name', 'penaltyType:id,code,name'])
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));

        $snapshotQuery = RrPenaltySnapshot::query()
            ->with(['rake.siding:id,name'])
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));

        // Requested source for Date: rake.created_at.
        if (! empty($params['date_from'])) {
            $appliedQuery->whereHas('rake', fn ($q) => $q->where('created_at', '>=', $params['date_from']));
            $snapshotQuery->whereHas('rake', fn ($q) => $q->where('created_at', '>=', $params['date_from']));
        }
        if (! empty($params['date_to'])) {
            $end = $params['date_to'].' 23:59:59';
            $appliedQuery->whereHas('rake', fn ($q) => $q->where('created_at', '<=', $end));
            $snapshotQuery->whereHas('rake', fn ($q) => $q->where('created_at', '<=', $end));
        }

        if (! empty($params['siding_id'])) {
            $appliedQuery->whereHas('rake', fn ($q) => $q->where('siding_id', $params['siding_id']));
            $snapshotQuery->whereHas('rake', fn ($q) => $q->where('siding_id', $params['siding_id']));
        }

        $limit = $this->resolveLimit($params);
        if ($limit !== null) {
            $appliedQuery->limit($limit);
            $snapshotQuery->limit($limit);
        }

        $appliedRows = $appliedQuery->latest()->get()->map(fn (AppliedPenalty $p): array => [
            'Date' => $p->rake?->created_at?->toDateString(),
            'Siding' => $p->rake?->siding?->name,
            'Rake No' => $p->rake?->rake_number,
            'Penalty Type' => $p->penaltyType?->code ?? $p->penaltyType?->name,
            'Reason' => '',
            'Amount' => $p->amount !== null ? (float) $p->amount : null,
            'Stage Detected (Pre-RR/Post-RR)' => 'Pre-RR',
            'Remarks' => '',
        ]);

        $snapshotRows = $snapshotQuery->latest()->get()->map(fn (RrPenaltySnapshot $p): array => [
            'Date' => $p->rake?->created_at?->toDateString(),
            'Siding' => $p->rake?->siding?->name,
            'Rake No' => $p->rake?->rake_number,
            'Penalty Type' => $p->penalty_code,
            'Reason' => '',
            'Amount' => $p->amount !== null ? (float) $p->amount : null,
            'Stage Detected (Pre-RR/Post-RR)' => 'Post-RR',
            'Remarks' => '',
        ]);

        return $appliedRows
            ->concat($snapshotRows)
            ->sortByDesc('Date')
            ->values()
            ->all();
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, limit?: int, no_limit?: bool}  $params
     * @return array<int, array<string, mixed>>
     */
    private function penaltyRegisterRrSnapshot(array $sidingIds, array $params): array
    {
        $query = RrPenaltySnapshot::query()
            ->with(['rake.siding:id,name', 'rrDocument:id,rr_received_date'])
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));

        $this->applyDateFilter($query, $params, 'created_at');

        if (! empty($params['siding_id'])) {
            $query->whereHas('rake', fn ($q) => $q->where('siding_id', $params['siding_id']));
        }

        $limit = $this->resolveLimit($params);
        if ($limit !== null) {
            $query->limit($limit);
        }

        $rows = $query->latest()->get();

        return $rows->map(fn (RrPenaltySnapshot $p): array => [
            'date' => $p->rrDocument?->rr_received_date?->toDateString() ?? $p->created_at?->toDateString(),
            'siding' => $p->rake?->siding?->name,
            'rake_no' => $p->rake?->rake_number,
            'penalty_type' => $p->penalty_code,
            'reason' => null,
            'amount' => $p->amount,
            'stage_detected' => null,
            'remarks' => null,
        ])->values()->all();
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, limit?: int, no_limit?: bool}  $params
     * @return array<int, array<string, mixed>>
     */
    private function penaltyRegisterApplied(array $sidingIds, array $params): array
    {
        $query = AppliedPenalty::query()
            ->with(['rake.siding:id,name', 'penaltyType:id,code,name'])
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));

        $this->applyDateFilter($query, $params, 'created_at');

        if (! empty($params['siding_id'])) {
            $query->whereHas('rake', fn ($q) => $q->where('siding_id', $params['siding_id']));
        }

        $limit = $this->resolveLimit($params);
        if ($limit !== null) {
            $query->limit($limit);
        }

        $rows = $query->latest()->get();

        return $rows->map(fn (AppliedPenalty $p): array => [
            'date' => $p->created_at?->toDateString(),
            'siding' => $p->rake?->siding?->name,
            'rake_no' => $p->rake?->rake_number,
            'penalty_type' => $p->penaltyType?->code ?? $p->penaltyType?->name,
            'reason' => null,
            'amount' => $p->amount,
            'stage_detected' => null,
            'remarks' => null,
        ])->values()->all();
    }

    /**
     * Delegate to the GenerateReports action for rich analytical reports.
     * Returns flattened summary rows suitable for CSV export.
     *
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string}  $params
     * @return array<int, array<string, mixed>>
     */
    private function delegateToGenerateReports(string $key, array $sidingIds, array $params): array
    {
        $generator = resolve(GenerateReports::class);
        $sidingId = $params['siding_id'] ?? ($sidingIds[0] ?? null);

        if ($sidingId === null) {
            return [];
        }

        $result = match ($key) {
            'daily_operations' => $generator->dailyOperationsSummary((int) $sidingId),
            'demurrage_analysis' => $generator->demurrageAnalysisReport((int) $sidingId),
            'financial_impact' => $generator->financialImpactReport((int) $sidingId),
            'rake_lifecycle' => $generator->rakeLifecycleReport((int) $sidingId),
            'indent_fulfillment' => $generator->indentFulfillmentReport((int) $sidingId),
            default => [],
        };

        // Wrap structured report in a single-row array for consistent CSV export
        if (! is_array($result) || ! array_is_list($result)) {
            return [$result];
        }

        return $result;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array{siding_id?: int, date_from?: string, date_to?: string}  $params
     */
    private function applyDateFilter($query, array $params, string $column, ?string $fallback = null): void
    {
        if (! empty($params['date_from'])) {
            if ($fallback !== null) {
                $query->whereRaw('COALESCE('.$column.', '.$fallback.') >= ?', [$params['date_from']]);
            } else {
                $query->where($column, '>=', $params['date_from']);
            }
        }
        if (! empty($params['date_to'])) {
            $end = $params['date_to'].' 23:59:59';
            if ($fallback !== null) {
                $query->whereRaw('COALESCE('.$column.', '.$fallback.') <= ?', [$end]);
            } else {
                $query->where($column, '<=', $end);
            }
        }
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, limit?: int, no_limit?: bool}  $params
     */
    private function resolveLimit(array $params, int $default = 500): ?int
    {
        if (! empty($params['no_limit'])) {
            return null;
        }

        if (array_key_exists('limit', $params) && $params['limit'] !== null) {
            $limit = (int) $params['limit'];

            return $limit > 0 ? $limit : $default;
        }

        return $default;
    }
}
