<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AppliedPenalty;
use App\Models\Indent;
use App\Models\Penalty;
use App\Models\Rake;
use App\Models\RakeWagonWeighment;
use App\Models\RrDocument;
use App\Models\RrPenaltySnapshot;
use App\Models\Txr;
use App\Models\Wagon;
use App\Models\WagonLoading;
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
        $query = DB::table('stock_ledgers as sl')
            ->leftJoin('daily_vehicle_entries as dve', 'dve.id', '=', 'sl.daily_vehicle_entry_id')
            ->leftJoin('sidings as s', 's.id', '=', 'sl.siding_id')
            ->where('sl.transaction_type', '=', 'receipt')
            ->whereIn('sl.siding_id', $sidingIds)
            // This report is meant to describe trips/vehicles/shifts, which come from daily_vehicle_entries
            ->whereNotNull('sl.daily_vehicle_entry_id');

        if (! empty($params['siding_id'])) {
            $query->where('sl.siding_id', '=', $params['siding_id']);
        }

        if (! empty($params['date_from'])) {
            $query->whereRaw('COALESCE(dve.entry_date, sl.created_at::date) >= ?', [$params['date_from']]);
        }
        if (! empty($params['date_to'])) {
            $query->whereRaw('COALESCE(dve.entry_date, sl.created_at::date) <= ?', [$params['date_to']]);
        }

        $query->selectRaw('
            s.name as siding,
            COALESCE(dve.entry_date, sl.created_at::date) as date,
            dve.shift as shift,
            dve.vehicle_no as vehicle_no,
            COUNT(DISTINCT sl.daily_vehicle_entry_id) as total_trips,
            SUM(COALESCE(sl.quantity_mt, 0)) as received_qty_mt
        ');

        $query->groupByRaw('s.name, COALESCE(dve.entry_date, sl.created_at::date), dve.shift, dve.vehicle_no');
        $query->orderByRaw('COALESCE(dve.entry_date, sl.created_at::date) desc');
        $query->orderBy('s.name');
        $query->orderBy('dve.shift');
        $query->orderBy('dve.vehicle_no');

        $limit = $this->resolveLimit($params);
        if ($limit !== null) {
            $query->limit($limit);
        }

        $rows = $query->get();

        return collect($rows)->map(fn ($r): array => [
            'siding' => $r->siding,
            'date' => (string) $r->date,
            'shift' => $r->shift !== null ? (int) $r->shift : null,
            'vehicle_no' => $r->vehicle_no,
            'total_trips' => (int) $r->total_trips,
            'received_qty_mt' => round((float) $r->received_qty_mt, 2),
        ])->values()->all();
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string}  $params
     * @return array<int, array<string, mixed>>
     */
    private function rakeIndent(array $sidingIds, array $params): array
    {
        $query = Indent::query()->with('siding:id,name')->whereIn('siding_id', $sidingIds);
        $this->applyDateFilter($query, $params, 'created_at');
        if (! empty($params['siding_id'])) {
            $query->where('siding_id', $params['siding_id']);
        }
        $rows = $query->latest()->limit(500)->get();

        return $rows->map(fn ($r): array => [
            'id' => $r->id,
            'siding' => $r->siding?->name,
            'state' => $r->state,
            'created_at' => $r->created_at->toIso8601String(),
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
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));
        $this->applyDateFilter($query, $params, 'inspection_time');
        if (! empty($params['siding_id'])) {
            $query->whereHas('rake', fn ($q) => $q->where('siding_id', $params['siding_id']));
        }
        $rows = $query->orderByDesc('inspection_time')->limit(500)->get();

        return $rows->map(fn ($r): array => [
            'rake_number' => $r->rake?->rake_number,
            'siding' => $r->rake?->siding?->name,
            'inspection_time' => $r->inspection_time?->toIso8601String(),
            'state' => $r->state,
            'unfit_wagons_count' => $r->unfit_wagons_count,
        ])->values()->all();
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string}  $params
     * @return array<int, array<string, mixed>>
     */
    private function unfitWagon(array $sidingIds, array $params): array
    {
        $query = Wagon::query()
            ->with('rake.siding:id,name')
            ->where('is_unfit', true)
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));
        if (! empty($params['siding_id'])) {
            $query->whereHas('rake', fn ($q) => $q->where('siding_id', $params['siding_id']));
        }
        $rows = $query->latest()->limit(500)->get();

        return $rows->map(fn ($r): array => [
            'wagon_number' => $r->wagon_number,
            'rake_number' => $r->rake?->rake_number,
            'siding' => $r->rake?->siding?->name,
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
            'rake_no' => $r->rakeWeighment?->rake?->rake_number,
            'wagon_no' => $r->wagon_number,
            'inmotion_gross_mt' => $r->actual_gross_mt,
            'inmotion_tare_mt' => $r->actual_tare_mt,
            'inmotion_net_mt' => $r->net_weight_mt,
            'weighment_time' => $r->weighment_time?->toIso8601String(),
            'slip_no' => $r->slip_number,
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
                'rake_no' => $r->rake_no,
                'wagon_no' => $r->weighment_wagon_no,
                'loader_qty_mt' => $loader,
                'inmotion_qty_mt' => $inmotion,
                'difference_mt' => $difference,
                'overload_underload_flag' => $flag,
                'action_taken' => $r->action_taken,
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
            ->with(['rake.siding:id,name', 'rrCharges:id,rr_document_id,charge_code,charge_name,amount', 'penaltySnapshots:id,rr_document_id,rake_id,penalty_code,amount'])
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
            $penaltyAmount = $r->penaltySnapshots->sum(fn (RrPenaltySnapshot $p): float => (float) $p->amount);
            $gstAmount = $r->rrCharges
                ->filter(fn ($c): bool => str_contains(mb_strtolower((string) $c->charge_code), 'gst') || str_contains(mb_strtolower((string) $c->charge_name), 'gst'))
                ->sum(fn ($c): float => (float) $c->amount);

            $freight = $r->freight_total !== null ? (float) $r->freight_total : 0.0;
            $total = $freight + (float) $penaltyAmount + (float) $gstAmount;

            return [
                'rake_no' => $r->rake?->rake_number,
                'rr_no' => $r->rr_number,
                'rr_date' => $r->rr_received_date?->toDateString(),
                'from_siding' => $r->rake?->siding?->name,
                'to_power_plant' => $r->to_station_code,
                'charged_weight_mt' => $r->rr_weight_mt,
                'freight_amount' => $r->freight_total,
                'penalty_amount' => round($penaltyAmount, 2),
                'gst_amount' => round($gstAmount, 2),
                'total_amount' => round($total, 2),
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
        $query = Penalty::query()
            ->with('rake.siding:id,name')
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));
        $this->applyDateFilter($query, $params, 'penalty_date');
        if (! empty($params['siding_id'])) {
            $query->whereHas('rake', fn ($q) => $q->where('siding_id', $params['siding_id']));
        }
        $limit = $this->resolveLimit($params);
        if ($limit !== null) {
            $query->limit($limit);
        }

        $rows = $query->latest('penalty_date')->get();

        return $rows->map(fn ($r): array => [
            'rake_number' => $r->rake?->rake_number,
            'siding' => $r->rake?->siding?->name,
            'penalty_type' => $r->penalty_type,
            'penalty_amount' => $r->penalty_amount,
            'penalty_status' => $r->penalty_status,
            'penalty_date' => $r->penalty_date?->toDateString(),
        ])->values()->all();
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
