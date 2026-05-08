<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AppliedPenalty;
use App\Models\DailyVehicleEntry;
use App\Models\Indent;
use App\Models\PowerPlant;
use App\Models\Rake;
use App\Models\RakeCharge;
use App\Models\RakeWagonWeighment;
use App\Models\RrDocument;
use App\Models\RrPenaltySnapshot;
use App\Models\Txr;
use App\Models\WagonLoading;
use App\Models\WagonUnfitLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
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
        'rr_summary',
        'penalty_register',
    ];

    /**
     * Coal Logestic Core reports (same generate endpoint as operational grid reports).
     *
     * @var list<string>
     */
    public const array COAL_LOGESTIC_CORE_REPORT_KEYS = [
        'rail_dispatch_dpr',
        'penalty_report',
        'overloading_report',
        'underloading_report',
        'loader_performance_report',
        'operator_performance_report',
        'siding_dispatch_report',
        'power_plant_dispatch_report',
    ];

    /**
     * Coal Logestic Advance reports (same generate endpoint as Core / operational grid).
     *
     * @var list<string>
     */
    public const array COAL_LOGESTIC_ADVANCE_REPORT_KEYS = [
        'weighment_analysis_report',
        'loader_vs_weighment_report',
        'weighment_summary_report',
        'rr_charges_report',
        'rr_wagon_details_report',
        'weighment_vs_rr_report',
        'auto_dpr_report',
    ];

    public const array REPORT_KEYS = [
        'siding_coal_receipt' => ['name' => 'Siding Coal Receipt', 'description' => 'Shift-wise receipt report'],
        'rake_indent' => ['name' => 'Rake Indent', 'description' => 'Indent history report'],
        'txr' => ['name' => 'Rake Placement TXR', 'description' => 'TXR performance report'],
        'unfit_wagon' => ['name' => 'Unfit Wagon Details', 'description' => 'Unfit wagon log'],
        'wagon_loading' => ['name' => 'Wagon Loading Data', 'description' => 'Loader-wise loading report'],
        'weighment' => ['name' => 'Inmotion Weighment', 'description' => 'Weighment data report'],
        'loader_vs_weighment' => ['name' => 'Loader Weighment Comparison', 'description' => 'Overload analysis report'],
        'rr_summary' => ['name' => 'Railway Receipt RR', 'description' => 'RR summary report'],
        'penalty_register' => ['name' => 'Penalty Register', 'description' => 'Penalty breakdown report'],
        'penalty_register_rr_snapshot' => ['name' => 'Penalty Register (RR Snapshot)', 'description' => 'Penalty register from RR penalty snapshots'],
        'penalty_register_applied' => ['name' => 'Penalty Register (Applied)', 'description' => 'Penalty register from applied penalties'],
        'daily_operations' => ['name' => 'Daily Operations Summary', 'description' => 'Stock, rakes, alerts overview'],
        'demurrage_analysis' => ['name' => 'Demurrage Analysis', 'description' => 'Demurrage charges by month'],
        'financial_impact' => ['name' => 'Financial Impact', 'description' => 'Revenue impact and savings'],
        'rake_lifecycle' => ['name' => 'Rake Lifecycle', 'description' => 'Rake processing timeline'],
        'indent_fulfillment' => ['name' => 'Indent Fulfillment', 'description' => 'Indent allocation progress'],
        'rail_dispatch_dpr' => ['name' => 'Rail Dispatch DPR', 'description' => 'Rail dispatch daily report by RR leg including diversions'],
        'penalty_report' => ['name' => 'Penalty Report', 'description' => 'Penalty lines with pre/post RR filter (Coal Logestic Core)'],
        'overloading_report' => ['name' => 'Overloading Report', 'description' => 'Wagon weighment overload lines with loader context (Coal Logestic Core)'],
        'underloading_report' => ['name' => 'Underloading Report', 'description' => 'Wagon weighment underload vs CC threshold % (Coal Logestic Core)'],
        'loader_performance_report' => ['name' => 'Loader Performance', 'description' => 'Per-loader loading accuracy from wagon_loading (Coal Logestic Core)'],
        'operator_performance_report' => ['name' => 'Operator Performance', 'description' => 'Per-operator and loader aggregates from wagon_loading: overload / underload cases and accuracy % (Coal Logestic Core)'],
        'siding_dispatch_report' => ['name' => 'Siding Dispatch', 'description' => 'Daily siding KPIs from rakes, RR penalty snapshots, and indent targets (Coal Logestic Core)'],
        'power_plant_dispatch_report' => ['name' => 'Power Plant Dispatch', 'description' => 'Daily dispatch by destination plant and source siding from rakes and latest weighment net (Coal Logestic Core)'],
        'weighment_analysis_report' => ['name' => 'Weighment Analysis', 'description' => 'Per-wagon weighment lines from the latest rake weighment (Coal Logestic Advance)'],
        'loader_vs_weighment_report' => ['name' => 'Loader vs Weighment', 'description' => 'Loader quantities vs latest in-motion net per wagon (Coal Logestic Advance)'],
        'weighment_summary_report' => ['name' => 'Weighment Summary', 'description' => 'Per-rake totals from the latest rake weighment (Coal Logestic Advance)'],
        'weighment_vs_rr_report' => ['name' => 'Weighment vs RR', 'description' => 'Per-rake in-motion net vs summed RR chargeable weight, overloads, and penalty impact (Coal Logestic Advance)'],
        'rr_charges_report' => ['name' => 'RR Charges', 'description' => 'Per-RR freight and weights from wagon snapshots and rake charges (Coal Logestic Advance)'],
        'rr_wagon_details_report' => ['name' => 'RR Wagon Details', 'description' => 'Per-wagon RR PDF weights, chargeable allocation, and rates (Coal Logestic Advance)'],
        'auto_dpr_report' => ['name' => 'Auto DPR Report', 'description' => 'Per-rake dispatch summary from RR wagon snapshots, chargeable weights, and actual rake charges (Coal Logestic Advance)'],
    ];

    /** Default matches rake-performance modal (% of CC, shortfall from weighment under_load_mt). */
    private const float DEFAULT_UNDERLOAD_THRESHOLD_PERCENT = 1.0;

    /**
     * Loader / operator performance: loads at or above this fraction of effective CC count as accurate (not underload).
     * Underload = eligible, not overloaded, and loaded_quantity_mt < CC × this value.
     */
    private const float LOADER_OPERATOR_PERFORMANCE_MIN_ACCEPTABLE_LOAD_FRACTION_OF_CC = 0.97;

    private const string PENALTY_STAGE_PRE = 'Pre-RR';

    private const string PENALTY_STAGE_POST = 'Post-RR';

    private const string OVERLOADING_REPORT_PENALTY_IMPACT = 'Overload penalty';

    private const string UNDERLOADING_REPORT_LOSS_IMPACT = 'Capacity shortfall';

    /**
     * Report keys accepted by POST /reports/generate for the grid UI (operational, core, advance).
     *
     * @return list<string>
     */
    public static function reportGenerateKeys(): array
    {
        return array_values(array_unique([
            ...self::RAKE_MANAGEMENT_REPORT_KEYS,
            ...self::COAL_LOGESTIC_CORE_REPORT_KEYS,
            ...self::COAL_LOGESTIC_ADVANCE_REPORT_KEYS,
        ]));
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, loader?: string, power_plant_id?: int, penalty_stage?: string, underload_threshold_percent?: float, loader_id?: int, loader_operator_name?: string}  $params
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
            'rr_summary' => $this->rrSummary($sidingIds, $params),
            'rail_dispatch_dpr' => $this->railDispatchDpr($sidingIds, $params),
            'penalty_register' => $this->penaltyRegister($sidingIds, $params),
            'penalty_report' => $this->penaltyReport($sidingIds, $params),
            'overloading_report' => $this->overloadingReport($sidingIds, $params),
            'underloading_report' => $this->underloadingReport($sidingIds, $params),
            'loader_performance_report' => $this->loaderPerformanceReport($sidingIds, $params),
            'operator_performance_report' => $this->operatorPerformanceReport($sidingIds, $params),
            'siding_dispatch_report' => $this->sidingDispatchReport($sidingIds, $params),
            'power_plant_dispatch_report' => $this->powerPlantDispatchReport($sidingIds, $params),
            'weighment_analysis_report' => $this->weighmentAnalysisReport($sidingIds, $params),
            'loader_vs_weighment_report' => $this->loaderVsWeighmentAdvanceReport($sidingIds, $params),
            'weighment_summary_report' => $this->weighmentSummaryReport($sidingIds, $params),
            'weighment_vs_rr_report' => $this->weighmentVsRrReport($sidingIds, $params),
            'rr_charges_report' => $this->rrChargesReport($sidingIds, $params),
            'rr_wagon_details_report' => $this->rrWagonDetailsReport($sidingIds, $params),
            'auto_dpr_report' => $this->autoDprReport($sidingIds, $params),
            'penalty_register_rr_snapshot' => $this->penaltyRegisterRrSnapshot($sidingIds, $params),
            'penalty_register_applied' => $this->penaltyRegisterApplied($sidingIds, $params),
            'daily_operations', 'demurrage_analysis', 'financial_impact', 'rake_lifecycle', 'indent_fulfillment' => $this->delegateToGenerateReports($key, $sidingIds, $params),
            default => [],
        };
    }

    /**
     * Paginated rows for the /reports UI (per_page capped at 60 in the controller).
     *
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, loader?: string, power_plant_id?: int, penalty_stage?: string, underload_threshold_percent?: float, loader_id?: int, loader_operator_name?: string}  $params
     * @return array{data: array<int, array<string, mixed>>, meta: array{current_page: int, per_page: int, total: int, last_page: int}}
     */
    public function handlePaginated(string $key, array $sidingIds, array $params, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(60, $perPage));
        $offset = ($page - 1) * $perPage;

        if ($key === 'penalty_register') {
            $chunk = $this->penaltyRegisterPaginatedSlice($sidingIds, $params, $offset, $perPage, false);
            $total = $chunk['total'];
            $data = $chunk['data'];
        } elseif ($key === 'penalty_report') {
            $chunk = $this->penaltyRegisterPaginatedSlice($sidingIds, $params, $offset, $perPage, true);
            $total = $chunk['total'];
            $data = $chunk['data'];
        } else {
            $total = match ($key) {
                'siding_coal_receipt' => $this->sidingCoalReceiptCount($sidingIds, $params),
                'rake_indent' => $this->rakeIndentCount($sidingIds, $params),
                'txr' => $this->txrReportCount($sidingIds, $params),
                'unfit_wagon' => $this->unfitWagonCount($sidingIds, $params),
                'wagon_loading' => $this->wagonLoadingCount($sidingIds, $params),
                'weighment' => $this->weighmentReportCount($sidingIds, $params),
                'loader_vs_weighment' => $this->loaderVsWeighmentCount($sidingIds, $params),
                'rr_summary' => $this->rrSummaryCount($sidingIds, $params),
                'rail_dispatch_dpr' => $this->railDispatchDprCount($sidingIds, $params),
                'overloading_report' => $this->overloadingReportCount($sidingIds, $params),
                'underloading_report' => $this->underloadingReportCount($sidingIds, $params),
                'loader_performance_report' => $this->loaderPerformanceReportCount($sidingIds, $params),
                'operator_performance_report' => $this->operatorPerformanceReportCount($sidingIds, $params),
                'siding_dispatch_report' => $this->sidingDispatchReportCount($sidingIds, $params),
                'power_plant_dispatch_report' => $this->powerPlantDispatchReportCount($sidingIds, $params),
                'weighment_analysis_report' => $this->weighmentAnalysisReportCount($sidingIds, $params),
                'loader_vs_weighment_report' => $this->loaderVsWeighmentAdvanceReportCount($sidingIds, $params),
                'weighment_summary_report' => $this->weighmentSummaryReportCount($sidingIds, $params),
                'weighment_vs_rr_report' => $this->weighmentVsRrReportCount($sidingIds, $params),
                'rr_charges_report' => $this->rrChargesReportCount($sidingIds, $params),
                'rr_wagon_details_report' => $this->rrWagonDetailsReportCount($sidingIds, $params),
                'auto_dpr_report' => $this->autoDprReportCount($sidingIds, $params),
                default => 0,
            };

            $gridParams = array_merge($params, [
                'grid_pagination' => true,
                'grid_offset' => $offset,
                'grid_limit' => $perPage,
            ]);
            $data = $this->handle($key, $sidingIds, $gridParams);
        }

        $lastPage = $total === 0 ? 1 : max(1, (int) ceil($total / $perPage));

        return [
            'data' => $data,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
            ],
        ];
    }

    private static function normalizeRrWagonSnapshotWagonKey(string $wagonNumber): ?string
    {
        $t = mb_trim($wagonNumber);
        if ($t === '') {
            return null;
        }

        return mb_strtoupper($t);
    }

    private function formatLoaderPerformanceAccuracyPercent(float $percentRoundedToOneDecimal): string
    {
        if (abs($percentRoundedToOneDecimal - round($percentRoundedToOneDecimal)) < 0.0001) {
            return sprintf('%d%%', (int) round($percentRoundedToOneDecimal));
        }

        return sprintf('%.1f%%', $percentRoundedToOneDecimal);
    }

    /** Normalized wagon_loading operator bucket for GROUP BY (empty name → ''). */
    private function operatorPerformanceSqlOperatorGroupKeyExpr(string $wlAlias = 'wl'): string
    {
        $col = "{$wlAlias}.loader_operator_name";

        return match (DB::connection()->getDriverName()) {
            'pgsql' => 'CASE WHEN '.$col.' IS NULL OR trim(both from '.$col.") = '' THEN '' ELSE trim(both from {$col}) END",
            default => 'CASE WHEN '.$col.' IS NULL OR TRIM('.$col.") = '' THEN '' ELSE TRIM({$col}) END",
        };
    }

    private function formatOptionalAverageLoadingMinutes(?float $minutes): string
    {
        if ($minutes === null || $minutes < 0 || ! is_finite($minutes)) {
            return '—';
        }

        $rounded = round($minutes, 1);
        if (abs($rounded - round($rounded)) < 0.0001) {
            return sprintf('%d min', (int) round($rounded));
        }

        return sprintf('%.1f min', $rounded);
    }

    /** Calendar date bucket for `rakes.loading_date`. */
    private function sqlRakeLoadingCalendarDate(string $rakeAlias = 'r'): string
    {
        $column = "{$rakeAlias}.loading_date";

        return match (DB::getDriverName()) {
            'pgsql' => "({$column})::date",
            'sqlite' => "DATE({$column})",
            default => "DATE({$column})",
        };
    }

    /** `COALESCE(r.loaded_weight_mt, sum net from latest rake weighment)` SQL fragment (aliases `r`, `wn`). */
    private function sqlCoalesceLoadedOrWeighNetMt(): string
    {
        return match (DB::getDriverName()) {
            'pgsql' => 'COALESCE(r.loaded_weight_mt::double precision, COALESCE(wn.net_mt, 0::double precision))',
            'sqlite' => 'COALESCE(CAST(r.loaded_weight_mt AS REAL), COALESCE(wn.net_mt, 0))',
            default => 'COALESCE(r.loaded_weight_mt, COALESCE(wn.net_mt, 0))',
        };
    }

    /**
     * Latest `rake_weighments` row per rake: `total_net_weight_mt`, else `total_gross_weight_mt - total_tare_weight_mt`, else sum of wagon `net_weight_mt` (alias `wn`).
     * Aliases: `rw` (joined latest header), `wn` (left join subquery sum by rake).
     */
    private function sqlCoalesceWeighmentHeaderOrGrossMinusTareOrWagonNetMt(): string
    {
        $rw = 'rw';
        $wn = 'wn';
        $grossMinusTare = match (DB::getDriverName()) {
            'pgsql' => "CASE WHEN {$rw}.total_gross_weight_mt IS NOT NULL AND {$rw}.total_tare_weight_mt IS NOT NULL "
                ."THEN ({$rw}.total_gross_weight_mt::double precision - {$rw}.total_tare_weight_mt::double precision) ELSE NULL END",
            'sqlite' => "CASE WHEN {$rw}.total_gross_weight_mt IS NOT NULL AND {$rw}.total_tare_weight_mt IS NOT NULL "
                .'THEN (CAST('.$rw.'.total_gross_weight_mt AS REAL) - CAST('.$rw.'.total_tare_weight_mt AS REAL)) ELSE NULL END',
            default => "CASE WHEN {$rw}.total_gross_weight_mt IS NOT NULL AND {$rw}.total_tare_weight_mt IS NOT NULL "
                ."THEN ({$rw}.total_gross_weight_mt - {$rw}.total_tare_weight_mt) ELSE NULL END",
        };

        return match (DB::getDriverName()) {
            'pgsql' => 'COALESCE('.$rw.'.total_net_weight_mt::double precision, ('.$grossMinusTare.'), COALESCE('.$wn.'.net_mt, 0::double precision))',
            'sqlite' => 'COALESCE(CAST('.$rw.'.total_net_weight_mt AS REAL), ('.$grossMinusTare.'), COALESCE(CAST('.$wn.'.net_mt AS REAL), 0))',
            default => 'COALESCE('.$rw.'.total_net_weight_mt, ('.$grossMinusTare.'), COALESCE('.$wn.'.net_mt, 0))',
        };
    }

    /**
     * Net weight for one `rake_wagon_weighments` row: `net_weight_mt`, else `actual_gross_mt - actual_tare_mt`.
     */
    private function sqlRakeWagonLineNetWeightMt(string $alias = 'rww'): string
    {
        $net = "{$alias}.net_weight_mt";
        $gross = "{$alias}.actual_gross_mt";
        $tare = "{$alias}.actual_tare_mt";
        $grossMinusTare = match (DB::getDriverName()) {
            'pgsql' => "CASE WHEN {$gross} IS NOT NULL AND {$tare} IS NOT NULL "
                ."THEN ({$gross}::double precision - {$tare}::double precision) ELSE NULL END",
            'sqlite' => "CASE WHEN {$gross} IS NOT NULL AND {$tare} IS NOT NULL "
                ."THEN (CAST({$gross} AS REAL) - CAST({$tare} AS REAL)) ELSE NULL END",
            default => "CASE WHEN {$gross} IS NOT NULL AND {$tare} IS NOT NULL "
                ."THEN ({$gross} - {$tare}) ELSE NULL END",
        };

        return match (DB::getDriverName()) {
            'pgsql' => 'COALESCE('.$net.'::double precision, ('.$grossMinusTare.'))',
            'sqlite' => 'COALESCE(CAST('.$net.' AS REAL), ('.$grossMinusTare.'))',
            default => 'COALESCE('.$net.', ('.$grossMinusTare.'))',
        };
    }

    /**
     * Header net on `rake_weighments`: `total_net_weight_mt`, else `total_gross_weight_mt - total_tare_weight_mt`.
     */
    private function sqlRakeWeighmentHeaderNetMt(string $rwAlias = 'rw'): string
    {
        $net = "{$rwAlias}.total_net_weight_mt";
        $gross = "{$rwAlias}.total_gross_weight_mt";
        $tare = "{$rwAlias}.total_tare_weight_mt";
        $grossMinusTare = match (DB::getDriverName()) {
            'pgsql' => "CASE WHEN {$gross} IS NOT NULL AND {$tare} IS NOT NULL "
                ."THEN ({$gross}::double precision - {$tare}::double precision) ELSE NULL END",
            'sqlite' => "CASE WHEN {$gross} IS NOT NULL AND {$tare} IS NOT NULL "
                ."THEN (CAST({$gross} AS REAL) - CAST({$tare} AS REAL)) ELSE NULL END",
            default => "CASE WHEN {$gross} IS NOT NULL AND {$tare} IS NOT NULL "
                ."THEN ({$gross} - {$tare}) ELSE NULL END",
        };

        return match (DB::getDriverName()) {
            'pgsql' => 'COALESCE('.$net.'::double precision, ('.$grossMinusTare.'))',
            'sqlite' => 'COALESCE(CAST('.$net.' AS REAL), ('.$grossMinusTare.'))',
            default => 'COALESCE('.$net.', ('.$grossMinusTare.'))',
        };
    }

    /** Net/load for one `rr_wagon_snapshots` row: `loaded_weight_mt`, else `gross_weight_mt - tare_weight_mt`. */
    private function sqlRrWagonSnapshotLineNetMt(string $alias = 'rws'): string
    {
        $loaded = "{$alias}.loaded_weight_mt";
        $gross = "{$alias}.gross_weight_mt";
        $tare = "{$alias}.tare_weight_mt";
        $grossMinusTare = match (DB::getDriverName()) {
            'pgsql' => "CASE WHEN {$gross} IS NOT NULL AND {$tare} IS NOT NULL "
                ."THEN ({$gross}::double precision - {$tare}::double precision) ELSE NULL END",
            'sqlite' => "CASE WHEN {$gross} IS NOT NULL AND {$tare} IS NOT NULL "
                ."THEN (CAST({$gross} AS REAL) - CAST({$tare} AS REAL)) ELSE NULL END",
            default => "CASE WHEN {$gross} IS NOT NULL AND {$tare} IS NOT NULL "
                ."THEN ({$gross} - {$tare}) ELSE NULL END",
        };

        return match (DB::getDriverName()) {
            'pgsql' => 'COALESCE('.$loaded.'::double precision, ('.$grossMinusTare.'))',
            'sqlite' => 'COALESCE(CAST('.$loaded.' AS REAL), ('.$grossMinusTare.'))',
            default => 'COALESCE('.$loaded.', ('.$grossMinusTare.'))',
        };
    }

    /**
     * Shortfall vs PCC / permissible on one `rr_wagon_snapshots` row (0 when not applicable).
     */
    private function sqlRrWagonSnapshotLineUnderloadMt(string $alias = 'rws'): string
    {
        $lineNet = '('.$this->sqlRrWagonSnapshotLineNetMt($alias).')';
        $pcc = "{$alias}.pcc_weight_mt";
        $perm = "{$alias}.permissible_weight_mt";
        $cap = match (DB::getDriverName()) {
            'pgsql' => "COALESCE({$pcc}, {$perm})",
            'sqlite' => "COALESCE(CAST({$pcc} AS REAL), CAST({$perm} AS REAL))",
            default => "COALESCE({$pcc}, {$perm})",
        };

        return match (DB::getDriverName()) {
            'pgsql' => "CASE WHEN {$cap} IS NOT NULL AND {$lineNet} IS NOT NULL AND {$cap}::double precision > {$lineNet}::double precision "
                ."THEN ({$cap}::double precision - {$lineNet}::double precision) ELSE 0 END",
            'sqlite' => "CASE WHEN {$cap} IS NOT NULL AND {$lineNet} IS NOT NULL AND {$cap} > {$lineNet} "
                ."THEN ({$cap} - {$lineNet}) ELSE 0 END",
            default => "CASE WHEN {$cap} IS NOT NULL AND {$lineNet} IS NOT NULL AND {$cap} > {$lineNet} "
                ."THEN ({$cap} - {$lineNet}) ELSE 0 END",
        };
    }

    /** AVG loading interval minutes where both timestamps exist (alias `r`). */
    private function sqlAvgLoadingMinutesAgg(): string
    {
        $driver = DB::getDriverName();
        $start = 'r.loading_start_time';
        $end = 'r.loading_end_time';

        return match ($driver) {
            'pgsql' => "AVG(CASE WHEN {$start} IS NOT NULL AND {$end} IS NOT NULL THEN EXTRACT(EPOCH FROM ({$end}::timestamp - {$start}::timestamp)) / 60.0 END)",
            'sqlite' => "AVG(CASE WHEN {$start} IS NOT NULL AND {$end} IS NOT NULL THEN (julianday({$end}) - julianday({$start})) * 1440.0 END)",
            default => "AVG(CASE WHEN {$start} IS NOT NULL AND {$end} IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, {$start}, {$end}) END)",
        };
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string}  $params
     */
    private function buildSidingDispatchGroupedSubquery(array $sidingIds, array $params): QueryBuilder
    {
        $latestRwId = DB::table('rake_weighments')
            ->selectRaw('rake_id, MAX(id) as max_rw_id')
            ->groupBy('rake_id');

        $weighNetByRake = DB::table('rake_wagon_weighments as rww')
            ->join('rake_weighments as rw', 'rw.id', '=', 'rww.rake_weighment_id')
            ->joinSub($latestRwId, 'lrw', function ($join): void {
                $join->on('lrw.max_rw_id', '=', 'rw.id')->on('lrw.rake_id', '=', 'rw.rake_id');
            })
            ->groupBy('rw.rake_id')
            ->selectRaw('rw.rake_id as rake_id, SUM(COALESCE(rww.net_weight_mt, 0)) as net_mt');

        $penaltyByRake = DB::table('rr_penalty_snapshots')
            ->whereNotNull('rake_id')
            ->groupBy('rake_id')
            ->selectRaw('rake_id, SUM(COALESCE(amount, 0)) as penalty_sum');

        $dispatchDay = $this->sqlRakeLoadingCalendarDate('r');
        $coalMt = $this->sqlCoalesceLoadedOrWeighNetMt();

        $effActual = '(CASE WHEN r.indent_id IS NOT NULL AND COALESCE(i.target_quantity_mt, 0) > 0 THEN '.$coalMt.' ELSE 0 END)';
        $effTarget = '(CASE WHEN r.indent_id IS NOT NULL AND COALESCE(i.target_quantity_mt, 0) > 0 THEN COALESCE(i.target_quantity_mt, 0) ELSE 0 END)';

        $avgLoadingSql = $this->sqlAvgLoadingMinutesAgg();

        $q = DB::table('rakes as r')
            ->join('sidings as s', 's.id', '=', 'r.siding_id')
            ->leftJoinSub($weighNetByRake, 'wn', 'wn.rake_id', '=', 'r.id')
            ->leftJoinSub($penaltyByRake, 'rp', 'rp.rake_id', '=', 'r.id')
            ->leftJoin('indents as i', function ($join): void {
                $join->on('i.id', '=', 'r.indent_id')
                    ->whereNull('i.deleted_at');
            })
            ->whereNull('r.deleted_at')
            ->whereIn('r.siding_id', $sidingIds)
            ->whereNotNull('r.loading_date');

        if (! empty($params['siding_id'])) {
            $q->where('r.siding_id', '=', (int) $params['siding_id']);
        }

        if (! empty($params['date_from'])) {
            $q->whereDate('r.loading_date', '>=', $params['date_from']);
        }

        if (! empty($params['date_to'])) {
            $q->whereDate('r.loading_date', '<=', $params['date_to']);
        }

        return $q
            ->groupByRaw($dispatchDay.', r.siding_id, s.name')
            ->selectRaw(
                $dispatchDay.' as dispatch_day,'.
                ' r.siding_id as siding_id,'.
                ' s.name as siding_name,'.
                ' COUNT(DISTINCT r.id) as total_rakes,'.
                ' SUM('.$coalMt.') as total_coal_mt,'.
                ' SUM(COALESCE(rp.penalty_sum, 0)) as total_penalty,'.
                ' '.$avgLoadingSql.' as avg_loading_minutes,'.
                ' SUM('.$effActual.') as efficiency_actual_mt,'.
                ' SUM('.$effTarget.') as efficiency_target_mt'
            );
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string}  $params
     */
    private function sidingDispatchReportCount(array $sidingIds, array $params): int
    {
        if ($sidingIds === []) {
            return 0;
        }

        return (int) DB::query()->fromSub(
            $this->buildSidingDispatchGroupedSubquery($sidingIds, $params),
            'dispatch_agg',
        )->count();
    }

    /**
     * Coal Logestic Core — date × siding from `rakes.loading_date`; coal uses `loaded_weight_mt` or latest weighment net;
     * penalties from `rr_penalty_snapshots`; efficiency = 100 × Σ actual / Σ indent `target_quantity_mt` (indented rakes only).
     *
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, grid_pagination?: bool, grid_offset?: int, grid_limit?: int, no_limit?: bool, limit?: int|null}  $params
     * @return array<int, array<string, mixed>>
     */
    private function sidingDispatchReport(array $sidingIds, array $params): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $grouped = $this->buildSidingDispatchGroupedSubquery($sidingIds, $params);

        $query = DB::query()->fromSub($grouped, 'dispatch_agg')
            ->orderByDesc('dispatch_day')
            ->orderBy('siding_name');

        $this->applyLegacyLimitOrGridPagination($query, $params);

        /** @var Collection<int, object> $rows */
        $rows = $query->get();

        return $rows->map(function (object $r): array {
            $dateRaw = $r->dispatch_day ?? '';
            $dateOut = '';
            if ($dateRaw !== '') {
                $dateOut = Carbon::parse((string) $dateRaw)->toDateString();
            }

            $totalCoal = isset($r->total_coal_mt) ? round((float) $r->total_coal_mt, 2) : null;
            $totalPenalty = isset($r->total_penalty) ? round((float) $r->total_penalty, 2) : null;
            $avgMinutes = isset($r->avg_loading_minutes) && $r->avg_loading_minutes !== null
                ? (float) $r->avg_loading_minutes
                : null;

            $effActual = isset($r->efficiency_actual_mt) ? (float) $r->efficiency_actual_mt : 0.0;
            $effTarget = isset($r->efficiency_target_mt) ? (float) $r->efficiency_target_mt : 0.0;
            $efficiencyDisplay = ($effTarget > 0)
                ? $this->formatLoaderPerformanceAccuracyPercent(round(100.0 * $effActual / $effTarget, 1))
                : '—';

            return [
                'Date' => $dateOut,
                'Siding' => isset($r->siding_name) ? (string) $r->siding_name : '',
                'Total Rakes' => (int) ($r->total_rakes ?? 0),
                'Total Coal (MT)' => $totalCoal,
                'Total Penalty' => $totalPenalty,
                'Avg Loading Time' => $this->formatOptionalAverageLoadingMinutes($avgMinutes),
                'Efficiency %' => $efficiencyDisplay,
            ];
        })->values()->all();
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, power_plant_id?: int}  $params
     */
    private function buildPowerPlantDispatchGroupedSubquery(array $sidingIds, array $params): QueryBuilder
    {
        $latestRwId = DB::table('rake_weighments')
            ->selectRaw('rake_id, MAX(id) as max_rw_id')
            ->groupBy('rake_id');

        $weighNetByRake = DB::table('rake_wagon_weighments as rww')
            ->join('rake_weighments as rw2', 'rw2.id', '=', 'rww.rake_weighment_id')
            ->joinSub($latestRwId, 'lrw2', function ($join): void {
                $join->on('lrw2.max_rw_id', '=', 'rw2.id')->on('lrw2.rake_id', '=', 'rw2.rake_id');
            })
            ->groupBy('rw2.rake_id')
            ->selectRaw('rw2.rake_id as rake_id, SUM(COALESCE(rww.net_weight_mt, 0)) as net_mt');

        $primaryRrPerRake = DB::table('rr_documents')
            ->selectRaw('rake_id, MIN(id) as rr_id')
            ->whereNotNull('to_station_code')
            ->where('to_station_code', '!=', '')
            ->groupBy('rake_id');

        $dispatchDay = $this->sqlRakeLoadingCalendarDate('r');
        $coalMt = $this->sqlCoalesceWeighmentHeaderOrGrossMinusTareOrWagonNetMt();

        $q = DB::table('rakes as r')
            ->join('sidings as s', 's.id', '=', 'r.siding_id')
            ->leftJoinSub($primaryRrPerRake, 'pri', 'pri.rake_id', '=', 'r.id')
            ->leftJoin('rr_documents as rr', 'rr.id', '=', 'pri.rr_id')
            ->leftJoin('power_plants as pp', 'pp.code', '=', 'rr.to_station_code')
            ->leftJoinSub($latestRwId, 'lrw', function ($join): void {
                $join->on('lrw.rake_id', '=', 'r.id');
            })
            ->leftJoin('rake_weighments as rw', 'rw.id', '=', 'lrw.max_rw_id')
            ->leftJoinSub($weighNetByRake, 'wn', 'wn.rake_id', '=', 'r.id')
            ->whereNull('r.deleted_at')
            ->whereIn('r.siding_id', $sidingIds)
            ->whereNotNull('r.loading_date');

        if (! empty($params['siding_id'])) {
            $q->where('r.siding_id', '=', (int) $params['siding_id']);
        }

        if (! empty($params['date_from'])) {
            $q->whereDate('r.loading_date', '>=', $params['date_from']);
        }

        if (! empty($params['date_to'])) {
            $q->whereDate('r.loading_date', '<=', $params['date_to']);
        }

        if (! empty($params['power_plant_id'])) {
            $q->where('pp.id', '=', (int) $params['power_plant_id']);
        }

        $bucketPlantId = match (DB::getDriverName()) {
            'pgsql' => 'COALESCE(pp.id, 0)',
            'sqlite' => 'COALESCE(CAST(pp.id AS INTEGER), 0)',
            default => 'COALESCE(pp.id, 0)',
        };
        $bucketToCode = "COALESCE(rr.to_station_code, '')";

        return $q
            ->groupByRaw($dispatchDay.', r.siding_id, s.id, s.name, '.$bucketPlantId.', '.$bucketToCode)
            ->selectRaw(
                $dispatchDay.' as dispatch_day,'.
                ' r.siding_id as siding_id,'.
                ' s.name as source_siding_name,'.
                ' MAX(COALESCE(pp.name, rr.to_station_code, \'\')) as power_plant_label,'.
                ' COUNT(DISTINCT r.id) as total_rakes,'.
                ' SUM('.$coalMt.') as total_coal_mt'
            );
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, power_plant_id?: int}  $params
     */
    private function powerPlantDispatchReportCount(array $sidingIds, array $params): int
    {
        if ($sidingIds === []) {
            return 0;
        }

        return (int) DB::query()->fromSub(
            $this->buildPowerPlantDispatchGroupedSubquery($sidingIds, $params),
            'pp_dispatch_agg',
        )->count();
    }

    /**
     * Coal Logestic Core — by `rakes.loading_date`, destination from primary RR `to_station_code` → `power_plants`;
     * coal from latest weighment header net or gross−tare or wagon net sum; transit time not tracked (N/A).
     *
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, power_plant_id?: int, grid_pagination?: bool, grid_offset?: int, grid_limit?: int, no_limit?: bool, limit?: int|null}  $params
     * @return array<int, array<string, mixed>>
     */
    private function powerPlantDispatchReport(array $sidingIds, array $params): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $grouped = $this->buildPowerPlantDispatchGroupedSubquery($sidingIds, $params);

        $query = DB::query()->fromSub($grouped, 'pp_dispatch_agg')
            ->orderByDesc('dispatch_day')
            ->orderBy('power_plant_label')
            ->orderBy('source_siding_name');

        $this->applyLegacyLimitOrGridPagination($query, $params);

        /** @var Collection<int, object> $rows */
        $rows = $query->get();

        return $rows->map(function (object $r): array {
            $dateRaw = $r->dispatch_day ?? '';
            $dateOut = '';
            if ($dateRaw !== '') {
                $dateOut = Carbon::parse((string) $dateRaw)->toDateString();
            }

            $plantLabel = isset($r->power_plant_label) ? mb_trim((string) $r->power_plant_label) : '';
            if ($plantLabel === '') {
                $plantLabel = '—';
            }

            $totalCoal = isset($r->total_coal_mt) ? round((float) $r->total_coal_mt, 2) : null;

            return [
                'Date' => $dateOut,
                'Power Plant' => $plantLabel,
                'No of Rakes' => (int) ($r->total_rakes ?? 0),
                'Total Coal (MT)' => $totalCoal,
                'Source Siding' => isset($r->source_siding_name) ? (string) $r->source_siding_name : '',
                'Transit Time' => 'N/A',
                'Remarks' => '',
            ];
        })->values()->all();
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string}  $params
     */
    private function buildWeighmentAnalysisBaseQuery(array $sidingIds, array $params): QueryBuilder
    {
        if ($sidingIds === []) {
            return DB::table('rake_wagon_weighments as rww')->whereRaw('1 = 0');
        }

        $latestRwId = DB::table('rake_weighments')
            ->selectRaw('rake_id, MAX(id) as max_rw_id')
            ->groupBy('rake_id');

        $q = DB::table('rake_wagon_weighments as rww')
            ->join('rake_weighments as rw', 'rw.id', '=', 'rww.rake_weighment_id')
            ->joinSub($latestRwId, 'lrw', function ($join): void {
                $join->on('lrw.max_rw_id', '=', 'rw.id')->on('lrw.rake_id', '=', 'rw.rake_id');
            })
            ->join('rakes as r', 'r.id', '=', 'rw.rake_id')
            ->leftJoin('wagons as w', 'w.id', '=', 'rww.wagon_id')
            ->whereNull('r.deleted_at')
            ->whereIn('r.siding_id', $sidingIds);

        if (! empty($params['siding_id'])) {
            $q->where('r.siding_id', '=', (int) $params['siding_id']);
        }

        if (! empty($params['date_from'])) {
            $q->whereDate('r.loading_date', '>=', $params['date_from']);
        }

        if (! empty($params['date_to'])) {
            $q->whereDate('r.loading_date', '<=', $params['date_to']);
        }

        if (! empty($params['rake_number'])) {
            $needle = mb_trim((string) $params['rake_number']);
            if ($needle !== '') {
                $q->where('r.rake_number', 'like', '%'.$needle.'%');
            }
        }

        return $q;
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string}  $params
     */
    private function weighmentAnalysisReportCount(array $sidingIds, array $params): int
    {
        return (int) $this->buildWeighmentAnalysisBaseQuery($sidingIds, $params)->count('rww.id');
    }

    /**
     * Coal Logestic Advance — per-wagon lines from latest `rake_weighments` row per rake.
     *
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, grid_pagination?: bool, grid_offset?: int, grid_limit?: int, no_limit?: bool, limit?: int|null}  $params
     * @return array<int, array<string, mixed>>
     */
    private function weighmentAnalysisReport(array $sidingIds, array $params): array
    {
        $query = $this->buildWeighmentAnalysisBaseQuery($sidingIds, $params)
            ->select([
                'rww.id as rww_id',
                'r.rake_number',
                'rww.wagon_number as rww_wagon_number',
                'w.wagon_number as w_wagon_number',
                'rww.wagon_type',
                'rww.cc_capacity_mt',
                'rww.printed_tare_mt',
                'rww.actual_tare_mt',
                'rww.actual_gross_mt',
                'rww.net_weight_mt',
                'rww.under_load_mt',
                'rww.over_load_mt',
                'r.loading_date',
                'rww.wagon_sequence',
            ])
            ->orderByDesc('r.loading_date')
            ->orderBy('r.rake_number')
            ->orderByRaw('COALESCE(rww.wagon_sequence, 999999) asc')
            ->orderBy('rww.id');

        $this->applyLegacyLimitOrGridPagination($query, $params);

        /** @var Collection<int, object> $rows */
        $rows = $query->get();

        return $rows->map(function (object $r): array {
            $wagonNo = $r->rww_wagon_number;
            if ($wagonNo === null || $wagonNo === '') {
                $wagonNo = $r->w_wagon_number ?? '';
            }

            $cc = isset($r->cc_capacity_mt) && $r->cc_capacity_mt !== null ? (float) $r->cc_capacity_mt : null;
            $gross = isset($r->actual_gross_mt) && $r->actual_gross_mt !== null ? (float) $r->actual_gross_mt : null;

            $tareActual = isset($r->actual_tare_mt) && $r->actual_tare_mt !== null ? (float) $r->actual_tare_mt : null;
            $tarePrinted = isset($r->printed_tare_mt) && $r->printed_tare_mt !== null ? (float) $r->printed_tare_mt : null;
            $tareOut = $tareActual ?? $tarePrinted;

            $net = isset($r->net_weight_mt) && $r->net_weight_mt !== null ? (float) $r->net_weight_mt : null;
            if ($net === null && $gross !== null && $tareActual !== null) {
                $net = $gross - $tareActual;
            }

            $underload = isset($r->under_load_mt) && $r->under_load_mt !== null ? round((float) $r->under_load_mt, 2) : null;
            $overload = isset($r->over_load_mt) && $r->over_load_mt !== null ? round((float) $r->over_load_mt, 2) : null;

            return [
                'Rake No' => isset($r->rake_number) ? (string) $r->rake_number : '',
                'Wagon No' => (string) $wagonNo,
                'Wagon Type' => isset($r->wagon_type) ? (string) $r->wagon_type : '',
                'CC Capacity (MT)' => $cc !== null ? round($cc, 2) : null,
                'Tare Weight (MT)' => $tareOut !== null ? round($tareOut, 2) : null,
                'Gross Weight (MT)' => $gross !== null ? round($gross, 2) : null,
                'Net Weight (MT)' => $net !== null ? round($net, 2) : null,
                'Underload (MT)' => $underload,
                'Overload (MT)' => $overload,
                'Deviation %' => $this->formatWeighmentAnalysisDeviationPercent($cc, $net),
                '_rww_id' => (int) $r->rww_id,
            ];
        })->values()->all();
    }

    private function formatWeighmentAnalysisDeviationPercent(?float $cc, ?float $net): string
    {
        if ($cc === null || $cc <= 0 || $net === null || ! is_finite($net)) {
            return '—';
        }

        $pct = 100.0 * ($net - $cc) / $cc;
        $rounded = round($pct, 1);
        if (abs($rounded - round($rounded)) < 0.0001) {
            return sprintf('%d%%', (int) round($rounded));
        }

        return sprintf('%.1f%%', $rounded);
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string}  $params
     */
    private function buildWeighmentSummaryBaseQuery(array $sidingIds, array $params): QueryBuilder
    {
        if ($sidingIds === []) {
            return DB::table('rakes as r')->whereRaw('1 = 0');
        }

        $latestRwId = DB::table('rake_weighments')
            ->selectRaw('rake_id, MAX(id) as max_rw_id')
            ->groupBy('rake_id');

        $lineNet = $this->sqlRakeWagonLineNetWeightMt('rww');

        $wagonAgg = DB::table('rake_wagon_weighments as rww')
            ->join('rake_weighments as rw_agg', 'rw_agg.id', '=', 'rww.rake_weighment_id')
            ->joinSub($latestRwId, 'lrw_agg', function ($join): void {
                $join->on('lrw_agg.max_rw_id', '=', 'rw_agg.id')->on('lrw_agg.rake_id', '=', 'rw_agg.rake_id');
            })
            ->groupBy('rw_agg.rake_id')
            ->selectRaw(
                'rw_agg.rake_id as rake_id, COUNT(rww.id) as wagon_line_count, SUM(rww.cc_capacity_mt) as sum_cc,'.
                ' SUM(rww.actual_gross_mt) as sum_gross, SUM('.$lineNet.') as sum_net_lines,'.
                ' SUM(rww.under_load_mt) as sum_under, SUM(rww.over_load_mt) as sum_over'
            );

        $headerNet = $this->sqlRakeWeighmentHeaderNetMt('rw');

        $q = DB::table('rakes as r')
            ->joinSub($latestRwId, 'lrw', function ($join): void {
                $join->on('lrw.rake_id', '=', 'r.id');
            })
            ->join('rake_weighments as rw', 'rw.id', '=', 'lrw.max_rw_id')
            ->leftJoinSub($wagonAgg, 'wa', 'wa.rake_id', '=', 'r.id')
            ->whereNull('r.deleted_at')
            ->whereIn('r.siding_id', $sidingIds)
            ->selectRaw(
                'r.id as rake_id, r.rake_number, r.loading_date,'.
                ' COALESCE(wa.wagon_line_count, 0) as total_wagons,'.
                ' COALESCE(wa.sum_cc, rw.total_cc_weight_mt) as total_cc_mt,'.
                ' COALESCE(wa.sum_gross, rw.total_gross_weight_mt) as total_gross_mt,'.
                ' COALESCE(wa.sum_net_lines, ('.$headerNet.')) as total_net_mt,'.
                ' COALESCE(wa.sum_under, rw.total_under_load_mt) as total_under_mt,'.
                ' COALESCE(wa.sum_over, rw.total_over_load_mt) as total_over_mt'
            );

        if (! empty($params['siding_id'])) {
            $q->where('r.siding_id', '=', (int) $params['siding_id']);
        }

        if (! empty($params['date_from'])) {
            $q->whereDate('r.loading_date', '>=', $params['date_from']);
        }

        if (! empty($params['date_to'])) {
            $q->whereDate('r.loading_date', '<=', $params['date_to']);
        }

        if (! empty($params['rake_number'])) {
            $needle = mb_trim((string) $params['rake_number']);
            if ($needle !== '') {
                $q->where('r.rake_number', 'like', '%'.$needle.'%');
            }
        }

        return $q;
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string}  $params
     */
    private function weighmentSummaryReportCount(array $sidingIds, array $params): int
    {
        return (int) $this->buildWeighmentSummaryBaseQuery($sidingIds, $params)->count('r.id');
    }

    /**
     * Coal Logestic Advance — one row per rake: totals from wagon lines on the latest `rake_weighments`
     * row, falling back to header totals when there are no lines.
     *
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, grid_pagination?: bool, grid_offset?: int, grid_limit?: int, no_limit?: bool, limit?: int|null}  $params
     * @return array<int, array<string, mixed>>
     */
    private function weighmentSummaryReport(array $sidingIds, array $params): array
    {
        $query = $this->buildWeighmentSummaryBaseQuery($sidingIds, $params)
            ->orderByDesc('r.loading_date')
            ->orderBy('r.rake_number');

        $this->applyLegacyLimitOrGridPagination($query, $params);

        /** @var Collection<int, object> $rows */
        $rows = $query->get();

        return $rows->map(function (object $r): array {
            $round = fn ($v): ?float => $v !== null && $v !== '' && is_finite((float) $v) ? round((float) $v, 2) : null;

            return [
                'Rake No' => isset($r->rake_number) ? (string) $r->rake_number : '',
                'Total Wagons' => (int) ($r->total_wagons ?? 0),
                'Total CC (MT)' => $round($r->total_cc_mt ?? null),
                'Total Gross (MT)' => $round($r->total_gross_mt ?? null),
                'Total Net (MT)' => $round($r->total_net_mt ?? null),
                'Total Underload (MT)' => $round($r->total_under_mt ?? null),
                'Total Overload (MT)' => $round($r->total_over_mt ?? null),
                '_rake_id' => (int) $r->rake_id,
            ];
        })->values()->all();
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string}  $params
     */
    private function applyWeighmentVsRrRakeFiltersOnAlias(QueryBuilder $q, string $rAliasWithoutDot, array $params): void
    {
        if (! empty($params['siding_id'])) {
            $q->where($rAliasWithoutDot.'.siding_id', '=', (int) $params['siding_id']);
        }

        if (! empty($params['date_from'])) {
            $q->whereDate($rAliasWithoutDot.'.loading_date', '>=', $params['date_from']);
        }

        if (! empty($params['date_to'])) {
            $q->whereDate($rAliasWithoutDot.'.loading_date', '<=', $params['date_to']);
        }

        if (! empty($params['rake_number'])) {
            $needle = mb_trim((string) $params['rake_number']);
            if ($needle !== '') {
                $q->where($rAliasWithoutDot.'.rake_number', 'like', '%'.$needle.'%');
            }
        }
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string}  $params
     */
    private function buildWeighmentVsRrReportBaseQuery(array $sidingIds, array $params): QueryBuilder
    {
        if ($sidingIds === []) {
            return DB::table('rakes as r')->whereRaw('1 = 0');
        }

        $wmSub = $this->buildWeighmentSummaryBaseQuery($sidingIds, $params);

        $rrChargeable = DB::table('rr_documents as rr')->join('rakes as rk', function ($join): void {
            $join->on('rk.id', '=', 'rr.rake_id')->whereNull('rk.deleted_at');
        })
            ->whereNotNull('rr.rake_id')
            ->whereIn('rk.siding_id', $sidingIds);
        $this->applyWeighmentVsRrRakeFiltersOnAlias($rrChargeable, 'rk', $params);
        $rrChargeable = $rrChargeable
            ->groupBy('rr.rake_id')
            ->selectRaw('rr.rake_id as rake_id, SUM(COALESCE(rr.rr_weight_mt, 0)) as rr_chargeable_mt');

        $rrOverload = DB::table('rr_wagon_snapshots as rws')
            ->join('rr_documents as rr2', 'rr2.id', '=', 'rws.rr_document_id')
            ->join('rakes as rk2', function ($join): void {
                $join->on('rk2.id', '=', 'rr2.rake_id')->whereNull('rk2.deleted_at');
            })
            ->whereNotNull('rr2.rake_id')
            ->whereIn('rk2.siding_id', $sidingIds);
        $this->applyWeighmentVsRrRakeFiltersOnAlias($rrOverload, 'rk2', $params);
        $rrOverload = $rrOverload->groupBy('rr2.rake_id')
            ->selectRaw('rr2.rake_id as rake_id, SUM(COALESCE(rws.overload_weight_mt, 0)) as rr_overload_mt');

        $penSnapAgg = DB::table('rr_penalty_snapshots as rps')
            ->join('rakes as rkps', function ($join): void {
                $join->on('rkps.id', '=', 'rps.rake_id')->whereNull('rkps.deleted_at');
            })
            ->whereNotNull('rps.rake_id')
            ->whereIn('rkps.siding_id', $sidingIds);
        $this->applyWeighmentVsRrRakeFiltersOnAlias($penSnapAgg, 'rkps', $params);
        $penSnapAgg = $penSnapAgg->groupBy('rps.rake_id')
            ->selectRaw('rps.rake_id as rake_id, SUM(COALESCE(rps.amount, 0)) as penalty_snap_total');

        $penChargeAgg = DB::table('rake_charges as rch')->join('rakes as rkch', function ($join): void {
            $join->on('rkch.id', '=', 'rch.rake_id')->whereNull('rkch.deleted_at');
        })
            ->where('rch.charge_type', '=', 'PENALTY')
            ->where('rch.is_actual_charges', '=', true)
            ->whereIn('rkch.siding_id', $sidingIds);
        $this->applyWeighmentVsRrRakeFiltersOnAlias($penChargeAgg, 'rkch', $params);
        $penChargeAgg = $penChargeAgg->groupBy('rch.rake_id')
            ->selectRaw('rch.rake_id as rake_id, SUM(COALESCE(rch.amount, 0)) as penalty_charge_total');

        $q = DB::table('rakes as r')
            ->whereNull('r.deleted_at')
            ->whereIn('r.siding_id', $sidingIds);
        $this->applyWeighmentVsRrRakeFiltersOnAlias($q, 'r', $params);

        return $q
            ->leftJoinSub($wmSub, 'wm', 'wm.rake_id', '=', 'r.id')
            ->leftJoinSub($rrChargeable, 'rrc', 'rrc.rake_id', '=', 'r.id')
            ->leftJoinSub($rrOverload, 'rro', 'rro.rake_id', '=', 'r.id')
            ->leftJoinSub($penSnapAgg, 'psa', 'psa.rake_id', '=', 'r.id')
            ->leftJoinSub($penChargeAgg, 'pca', 'pca.rake_id', '=', 'r.id')
            ->where(function ($w): void {
                $w->whereNotNull('wm.rake_id')
                    ->orWhereNotNull('rrc.rake_id')
                    ->orWhereNotNull('rro.rake_id');
            })
            ->selectRaw(
                'r.id as rake_id, r.rake_number as rake_number, r.loading_date,'.
                ' wm.total_net_mt as weighment_total_mt,'.
                ' wm.total_over_mt as overload_weighment_mt,'.
                ' rrc.rr_chargeable_mt as rr_chargeable_mt,'.
                ' rro.rr_overload_mt as rr_overload_mt,'.
                ' psa.penalty_snap_total as penalty_snap_total,'.
                ' pca.penalty_charge_total as penalty_charge_total'
            );
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string}  $params
     */
    private function weighmentVsRrReportCount(array $sidingIds, array $params): int
    {
        return (int) $this->buildWeighmentVsRrReportBaseQuery($sidingIds, $params)->count('r.id');
    }

    /**
     * Coal Logestic Advance — one row per rake: latest weighment net vs summed RR chargeable weight.
     *
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, grid_pagination?: bool, grid_offset?: int, grid_limit?: int, no_limit?: bool, limit?: int|null}  $params
     * @return array<int, array<string, mixed>>
     */
    private function weighmentVsRrReport(array $sidingIds, array $params): array
    {
        $query = $this->buildWeighmentVsRrReportBaseQuery($sidingIds, $params)
            ->orderByDesc('r.loading_date')
            ->orderBy('r.rake_number');

        $this->applyLegacyLimitOrGridPagination($query, $params);

        /** @var Collection<int, object> $rows */
        $rows = $query->get();

        return $rows->map(function (object $row): array {
            $roundWt = fn (?float $v): ?float => $v !== null && is_finite($v) ? round($v, 2) : null;

            $wmTotal = isset($row->weighment_total_mt) && $row->weighment_total_mt !== null ? (float) $row->weighment_total_mt : null;
            $rrChg = isset($row->rr_chargeable_mt) && $row->rr_chargeable_mt !== null ? (float) $row->rr_chargeable_mt : null;

            $difference = null;
            if ($wmTotal !== null && is_finite($wmTotal) && $rrChg !== null && is_finite($rrChg)) {
                $difference = round($wmTotal - $rrChg, 2);
            }

            $snapPen = isset($row->penalty_snap_total) && $row->penalty_snap_total !== null ? (float) $row->penalty_snap_total : null;
            $chgPen = isset($row->penalty_charge_total) && $row->penalty_charge_total !== null ? (float) $row->penalty_charge_total : null;

            $penaltyImpact = null;
            if ($snapPen !== null && is_finite($snapPen) && abs($snapPen) > 1e-6) {
                $penaltyImpact = round($snapPen, 2);
            } elseif ($chgPen !== null && is_finite($chgPen) && abs($chgPen) > 1e-6) {
                $penaltyImpact = round($chgPen, 2);
            }

            $rrOverload = isset($row->rr_overload_mt) && $row->rr_overload_mt !== null ? (float) $row->rr_overload_mt : null;

            return [
                'Rake No' => isset($row->rake_number) ? (string) $row->rake_number : '',
                'Weighment Total (MT)' => $roundWt($wmTotal),
                'RR Chargeable Weight (MT)' => $roundWt($rrChg),
                'Difference (MT)' => $difference,
                'Overload (Weighment)' => $roundWt(isset($row->overload_weighment_mt) && $row->overload_weighment_mt !== null ? (float) $row->overload_weighment_mt : null),
                'Overload (RR)' => $roundWt($rrOverload),
                'Penalty Impact' => $penaltyImpact,
                '_rake_id' => (int) $row->rake_id,
            ];
        })->values()->all();
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, power_plant_id?: int}  $params
     */
    private function buildAutoDprReportBaseQuery(array $sidingIds, array $params): QueryBuilder
    {
        if ($sidingIds === []) {
            return DB::table('rakes as r')->whereRaw('1 = 0');
        }

        $lineNet = $this->sqlRrWagonSnapshotLineNetMt('rws');
        $underLine = $this->sqlRrWagonSnapshotLineUnderloadMt('rws');

        $snapshotAgg = DB::table('rr_wagon_snapshots as rws')
            ->join('rr_documents as rrd', 'rrd.id', '=', 'rws.rr_document_id')
            ->join('rakes as rks', function ($join): void {
                $join->on('rks.id', '=', 'rrd.rake_id')->whereNull('rks.deleted_at');
            })
            ->whereNotNull('rrd.rake_id')
            ->whereIn('rks.siding_id', $sidingIds);
        $this->applyWeighmentVsRrRakeFiltersOnAlias($snapshotAgg, 'rks', $params);
        $snapshotAgg = $snapshotAgg->groupBy('rrd.rake_id')->selectRaw(
            'rrd.rake_id as rake_id,'.
            ' COUNT(rws.id) as snapshot_wagon_count,'.
            ' SUM(COALESCE(('.$lineNet.'), 0)) as total_snapshot_net_mt,'.
            ' SUM(COALESCE(rws.overload_weight_mt, 0)) as total_snapshot_overload_mt,'.
            ' SUM(('.$underLine.')) as total_snapshot_underload_mt'
        );

        $rrChargeable = DB::table('rr_documents as rr')->join('rakes as rk', function ($join): void {
            $join->on('rk.id', '=', 'rr.rake_id')->whereNull('rk.deleted_at');
        })
            ->whereNotNull('rr.rake_id')
            ->whereIn('rk.siding_id', $sidingIds);
        $this->applyWeighmentVsRrRakeFiltersOnAlias($rrChargeable, 'rk', $params);
        $rrChargeable = $rrChargeable
            ->groupBy('rr.rake_id')
            ->selectRaw('rr.rake_id as rake_id, SUM(COALESCE(rr.rr_weight_mt, 0)) as rr_chargeable_mt');

        $chargesByRake = DB::table('rake_charges as rch')->join('rakes as rkch', function ($join): void {
            $join->on('rkch.id', '=', 'rch.rake_id')->whereNull('rkch.deleted_at');
        })
            ->where('rch.is_actual_charges', '=', true)
            ->whereIn('rkch.siding_id', $sidingIds);
        $this->applyWeighmentVsRrRakeFiltersOnAlias($chargesByRake, 'rkch', $params);
        $chargesByRake = $chargesByRake->groupBy('rch.rake_id')->selectRaw(
            'rch.rake_id as rake_id,'.
            ' SUM(CASE WHEN rch.charge_type = \'FREIGHT\' THEN rch.amount ELSE 0 END) as amt_freight,'.
            ' SUM(CASE WHEN rch.charge_type = \'GST\' THEN rch.amount ELSE 0 END) as amt_gst,'.
            ' SUM(rch.amount) as amt_total'
        );

        $primaryDoc = DB::table('rr_documents')
            ->whereNull('diverrt_destination_id')
            ->whereNotNull('rake_id')
            ->groupBy('rake_id')
            ->selectRaw('rake_id, MIN(id) as primary_rr_document_id');

        $q = DB::table('rakes as r')
            ->join('sidings as s', 's.id', '=', 'r.siding_id')
            ->whereNull('r.deleted_at')
            ->whereIn('r.siding_id', $sidingIds)
            ->whereExists(function ($sub): void {
                $sub->selectRaw('1')
                    ->from('rr_documents as rrx')
                    ->whereColumn('rrx.rake_id', 'r.id');
            });
        $this->applyWeighmentVsRrRakeFiltersOnAlias($q, 'r', $params);

        if (! empty($params['power_plant_id'])) {
            $plantCode = PowerPlant::query()->whereKey((int) $params['power_plant_id'])->value('code');
            if (is_string($plantCode) && $plantCode !== '') {
                $q->whereExists(function ($sub) use ($plantCode): void {
                    $sub->selectRaw('1')
                        ->from('rr_documents as rrf')
                        ->whereColumn('rrf.rake_id', 'r.id')
                        ->where('rrf.to_station_code', '=', $plantCode);
                });
            }
        }

        return $q
            ->leftJoinSub($snapshotAgg, 'sa', 'sa.rake_id', '=', 'r.id')
            ->leftJoinSub($rrChargeable, 'rrc', 'rrc.rake_id', '=', 'r.id')
            ->leftJoinSub($chargesByRake, 'ch', 'ch.rake_id', '=', 'r.id')
            ->leftJoinSub($primaryDoc, 'prd', 'prd.rake_id', '=', 'r.id')
            ->leftJoin('rr_documents as rr_pri', 'rr_pri.id', '=', 'prd.primary_rr_document_id')
            ->leftJoin('power_plants as pp', 'pp.code', '=', 'rr_pri.to_station_code')
            ->selectRaw(
                'r.id as rake_id,'.
                ' r.rake_number,'.
                ' r.loading_date,'.
                ' r.destination as rake_destination_text,'.
                ' r.remarks as rake_remarks,'.
                ' s.name as siding_name,'.
                ' pp.name as destination_plant_name,'.
                ' rr_pri.to_station_code as primary_to_station_code,'.
                ' COALESCE(sa.snapshot_wagon_count, 0) as snapshot_wagon_count,'.
                ' sa.total_snapshot_net_mt,'.
                ' rrc.rr_chargeable_mt,'.
                ' sa.total_snapshot_overload_mt,'.
                ' sa.total_snapshot_underload_mt,'.
                ' ch.amt_freight,'.
                ' ch.amt_gst,'.
                ' ch.amt_total'
            );
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, power_plant_id?: int}  $params
     */
    private function autoDprReportCount(array $sidingIds, array $params): int
    {
        return (int) $this->buildAutoDprReportBaseQuery($sidingIds, $params)->count('r.id');
    }

    /**
     * Coal Logestic Advance — per-rake DPR-style line built from RR PDF snapshots + rake charges (+ penalty snapshot remarks).
     *
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, power_plant_id?: int, grid_pagination?: bool, grid_offset?: int, grid_limit?: int, no_limit?: bool, limit?: int|null}  $params
     * @return array<int, array<string, mixed>>
     */
    private function autoDprReport(array $sidingIds, array $params): array
    {
        $query = $this->buildAutoDprReportBaseQuery($sidingIds, $params)
            ->orderByDesc('r.loading_date')
            ->orderBy('r.rake_number');

        $this->applyLegacyLimitOrGridPagination($query, $params);

        /** @var Collection<int, object> $rows */
        $rows = $query->get();

        $rakeIds = [];
        foreach ($rows as $row) {
            if (property_exists($row, 'rake_id') && $row->rake_id !== null) {
                $rakeIds[] = (int) $row->rake_id;
            }
        }
        $rakeIds = array_values(array_unique($rakeIds));
        $penRemarkByRake = $this->autoDprReportPenaltyRemarkMapByRakeId($rakeIds);

        return $rows->map(function (object $row) use ($penRemarkByRake): array {
            $round2 = fn ($v): ?float => $v !== null && is_finite((float) $v) ? round((float) $v, 2) : null;
            $roundMoney = fn ($v): float => round((float) ($v ?? 0), 2);

            $rk = (int) $row->rake_id;
            $destText = property_exists($row, 'rake_destination_text') && $row->rake_destination_text !== null
                ? mb_trim((string) $row->rake_destination_text) : '';
            $plant = property_exists($row, 'destination_plant_name') && $row->destination_plant_name !== null
                ? mb_trim((string) $row->destination_plant_name) : '';
            $toCode = property_exists($row, 'primary_to_station_code') && $row->primary_to_station_code !== null
                ? mb_trim((string) $row->primary_to_station_code) : '';

            $destination = $destText !== '' ? $destText : ($plant !== '' ? $plant : $toCode);

            $rakeRem = property_exists($row, 'rake_remarks') && $row->rake_remarks !== null
                ? mb_trim((string) $row->rake_remarks) : '';
            $penStr = $penRemarkByRake[$rk] ?? '';
            $remarkParts = array_values(array_filter([$rakeRem, $penStr], fn (string $s): bool => $s !== ''));
            $remarks = implode('; ', $remarkParts);

            $day = property_exists($row, 'loading_date') && $row->loading_date !== null
                ? (string) $row->loading_date
                : '';

            return [
                'Date' => $day,
                'Rake No' => property_exists($row, 'rake_number') && $row->rake_number !== null ? (string) $row->rake_number : '',
                'Siding' => property_exists($row, 'siding_name') && $row->siding_name !== null ? (string) $row->siding_name : '',
                'Destination' => $destination,
                'Total Wagons' => (int) ($row->snapshot_wagon_count ?? 0),
                'Total Net Weight (MT)' => $round2(
                    isset($row->total_snapshot_net_mt) && $row->total_snapshot_net_mt !== null ? (float) $row->total_snapshot_net_mt : null,
                ),
                'Chargeable Weight (MT)' => $round2(
                    isset($row->rr_chargeable_mt) && $row->rr_chargeable_mt !== null ? (float) $row->rr_chargeable_mt : null,
                ),
                'Overload (MT)' => $round2(
                    isset($row->total_snapshot_overload_mt) && $row->total_snapshot_overload_mt !== null
                        ? (float) $row->total_snapshot_overload_mt : null,
                ),
                'Underload (MT)' => $round2(
                    isset($row->total_snapshot_underload_mt) && $row->total_snapshot_underload_mt !== null
                        ? (float) $row->total_snapshot_underload_mt : null,
                ),
                'Freight' => $roundMoney($row->amt_freight ?? null),
                'GST' => $roundMoney($row->amt_gst ?? null),
                'Total Amount' => $roundMoney($row->amt_total ?? null),
                'Remarks' => $remarks,
                '_rake_id' => $rk,
            ];
        })->values()->all();
    }

    /**
     * @param  array<int>  $rakeIds
     * @return array<int, string>
     */
    private function autoDprReportPenaltyRemarkMapByRakeId(array $rakeIds): array
    {
        if ($rakeIds === []) {
            return [];
        }

        $rows = DB::table('rr_penalty_snapshots')
            ->whereIn('rake_id', $rakeIds)
            ->orderBy('penalty_code')
            ->get(['rake_id', 'penalty_code', 'amount']);

        $grouped = [];
        foreach ($rows as $r) {
            $rid = (int) $r->rake_id;
            $amt = round((float) $r->amount, 2);
            $grouped[$rid][] = mb_trim((string) $r->penalty_code).': '.$amt;
        }

        $out = [];
        foreach ($grouped as $rid => $parts) {
            $out[$rid] = implode(', ', $parts);
        }

        return $out;
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string}  $params
     */
    private function buildRrChargesReportBaseQuery(array $sidingIds, array $params): QueryBuilder
    {
        if ($sidingIds === []) {
            return DB::table('rr_documents as rr')->whereRaw('1 = 0');
        }

        $lineNet = $this->sqlRrWagonSnapshotLineNetMt('rws');

        $snapshotSum = DB::table('rr_wagon_snapshots as rws')
            ->groupBy('rws.rr_document_id')
            ->selectRaw('rws.rr_document_id as rr_document_id, SUM('.$lineNet.') as snapshot_net_sum_mt');

        $chargesPivot = DB::table('rake_charges')
            ->where('is_actual_charges', true)
            ->selectRaw(
                'rake_id, diverrt_destination_id,'.
                ' SUM(CASE WHEN charge_type = \'FREIGHT\' THEN amount ELSE 0 END) AS basic_freight,'.
                ' SUM(CASE WHEN charge_type = \'OTHER_CHARGE\' THEN amount ELSE 0 END) AS other_charges,'.
                ' SUM(CASE WHEN charge_type = \'GST\' THEN amount ELSE 0 END) AS gst'
            )
            ->groupBy('rake_id', 'diverrt_destination_id');

        $q = DB::table('rr_documents as rr')
            ->join('rakes as r', function ($join): void {
                $join->on('r.id', '=', 'rr.rake_id')->whereNull('r.deleted_at');
            })
            ->leftJoinSub($snapshotSum, 'sn', 'sn.rr_document_id', '=', 'rr.id')
            ->leftJoinSub($chargesPivot, 'ch', function ($join): void {
                $join->on('ch.rake_id', '=', 'rr.rake_id')
                    ->where(function ($w): void {
                        $w->where(function ($n): void {
                            $n->whereNull('rr.diverrt_destination_id')
                                ->whereNull('ch.diverrt_destination_id');
                        })->orWhereColumn('rr.diverrt_destination_id', 'ch.diverrt_destination_id');
                    });
            })
            ->whereNotNull('rr.rake_id')
            ->whereIn('r.siding_id', $sidingIds)
            ->selectRaw(
                'rr.id AS rr_document_id, rr.rr_number, r.rake_number,'.
                ' rr.from_station_code, rr.to_station_code, rr.distance_km,'.
                ' rr.rr_weight_mt, sn.snapshot_net_sum_mt,'.
                ' ch.basic_freight, ch.other_charges, ch.gst'
            );

        if (! empty($params['siding_id'])) {
            $q->where('r.siding_id', '=', (int) $params['siding_id']);
        }

        if (! empty($params['date_from'])) {
            $q->whereDate('r.loading_date', '>=', $params['date_from']);
        }

        if (! empty($params['date_to'])) {
            $q->whereDate('r.loading_date', '<=', $params['date_to']);
        }

        if (! empty($params['rake_number'])) {
            $needle = mb_trim((string) $params['rake_number']);
            if ($needle !== '') {
                $q->where('r.rake_number', 'like', '%'.$needle.'%');
            }
        }

        return $q;
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string}  $params
     */
    private function rrChargesReportCount(array $sidingIds, array $params): int
    {
        return (int) $this->buildRrChargesReportBaseQuery($sidingIds, $params)->count('rr.id');
    }

    /**
     * Coal Logestic Advance — RR leg with summed wagon snapshot net, chargeable RR weight,
     * and categorized actual rake charges (freight / other / GST).
     *
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, grid_pagination?: bool, grid_offset?: int, grid_limit?: int, no_limit?: bool, limit?: int|null}  $params
     * @return array<int, array<string, mixed>>
     */
    private function rrChargesReport(array $sidingIds, array $params): array
    {
        $query = $this->buildRrChargesReportBaseQuery($sidingIds, $params)
            ->orderByDesc('rr.rr_received_date')
            ->orderByDesc('rr.id');

        $this->applyLegacyLimitOrGridPagination($query, $params);

        /** @var Collection<int, object> $rows */
        $rows = $query->get();

        $roundWt = fn ($v): ?float => $v !== null && $v !== '' && is_finite((float) $v) ? round((float) $v, 2) : null;
        $roundMoney = fn ($v): ?float => $v !== null && $v !== '' && is_finite((float) $v) ? round((float) $v, 2) : null;

        return $rows->map(function (object $row) use ($roundMoney, $roundWt): array {
            $basicRaw = $row->basic_freight ?? null;
            $otherRaw = $row->other_charges ?? null;
            $gstRaw = $row->gst ?? null;
            $hasChargeRow = $basicRaw !== null || $otherRaw !== null || $gstRaw !== null;

            $basic = $roundMoney($basicRaw !== null ? $basicRaw : null);
            $other = $roundMoney($otherRaw !== null ? $otherRaw : null);
            $gst = $roundMoney($gstRaw !== null ? $gstRaw : null);

            $totalFreight = null;
            if ($hasChargeRow) {
                $totalFreight = round(
                    (float) ($basicRaw ?? 0) + (float) ($otherRaw ?? 0) + (float) ($gstRaw ?? 0),
                    2,
                );
            }

            $fromStation = isset($row->from_station_code) ? mb_trim((string) $row->from_station_code) : '';
            $toStation = isset($row->to_station_code) ? mb_trim((string) $row->to_station_code) : '';

            return [
                'RR No' => isset($row->rr_number) ? (string) $row->rr_number : '',
                'Rake No' => isset($row->rake_number) ? (string) $row->rake_number : '',
                'From Station' => $fromStation,
                'To Station' => $toStation,
                'Distance (KM)' => $roundWt($row->distance_km ?? null),
                'Total Weight (MT)' => $roundWt($row->snapshot_net_sum_mt ?? null),
                'Chargeable Weight (MT)' => $roundWt($row->rr_weight_mt ?? null),
                'Basic Freight' => $basic,
                'Other Charges' => $other,
                'GST' => $gst,
                'Total Freight' => $totalFreight,
                '_rr_document_id' => (int) $row->rr_document_id,
            ];
        })->values()->all();
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string}  $params
     */
    private function buildRrWagonDetailsReportBaseQuery(array $sidingIds, array $params): QueryBuilder
    {
        if ($sidingIds === []) {
            return DB::table('rr_wagon_snapshots as rws')->whereRaw('1 = 0');
        }

        $lineNetAgg = $this->sqlRrWagonSnapshotLineNetMt('rws2');

        $docNetSum = DB::table('rr_wagon_snapshots as rws2')
            ->groupBy('rws2.rr_document_id')
            ->selectRaw(
                'rws2.rr_document_id AS rr_document_id,'.
                ' SUM(COALESCE(('.$lineNetAgg.'), 0)) AS doc_sum_net_mt'
            );

        $lineNetRow = $this->sqlRrWagonSnapshotLineNetMt('rws');

        $q = DB::table('rr_wagon_snapshots as rws')
            ->join('rr_documents as rr', 'rr.id', '=', 'rws.rr_document_id')
            ->join('rakes as r', 'r.id', '=', 'rr.rake_id')
            ->leftJoinSub($docNetSum, 'dsl', 'dsl.rr_document_id', '=', 'rws.rr_document_id')
            ->whereNull('r.deleted_at')
            ->whereNotNull('rr.rake_id')
            ->whereIn('r.siding_id', $sidingIds)
            ->select([
                'rws.id as rws_id',
                'rws.wagon_sequence as wagon_sequence',
                'rws.wagon_number as wagon_number',
                'rws.pcc_weight_mt',
                'rws.permissible_weight_mt',
                'rws.overload_weight_mt',
                'r.rake_number',
                'rr.rate',
                'rr.id as rr_document_id',
                'rr.rr_weight_mt',
                'dsl.doc_sum_net_mt',
                DB::raw('('.$lineNetRow.') as row_net_mt'),
            ]);

        if (! empty($params['siding_id'])) {
            $q->where('r.siding_id', '=', (int) $params['siding_id']);
        }

        if (! empty($params['date_from'])) {
            $q->whereDate('r.loading_date', '>=', $params['date_from']);
        }

        if (! empty($params['date_to'])) {
            $q->whereDate('r.loading_date', '<=', $params['date_to']);
        }

        if (! empty($params['rake_number'])) {
            $needle = mb_trim((string) $params['rake_number']);
            if ($needle !== '') {
                $q->where('r.rake_number', 'like', '%'.$needle.'%');
            }
        }

        return $q;
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string}  $params
     */
    private function rrWagonDetailsReportCount(array $sidingIds, array $params): int
    {
        return (int) $this->buildRrWagonDetailsReportBaseQuery($sidingIds, $params)->count('rws.id');
    }

    /**
     * Coal Logestic Advance — wagon lines from `rr_wagon_snapshots` joined to rake + RR (weights, chargeable allocation, rates).
     *
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, grid_pagination?: bool, grid_offset?: int, grid_limit?: int, no_limit?: bool, limit?: int|null}  $params
     * @return array<int, array<string, mixed>>
     */
    private function rrWagonDetailsReport(array $sidingIds, array $params): array
    {
        $query = $this->buildRrWagonDetailsReportBaseQuery($sidingIds, $params)
            ->orderByDesc('r.loading_date')
            ->orderByDesc('rr.id')
            ->orderByRaw('COALESCE(rws.wagon_sequence, 999999)')
            ->orderBy('rws.id');

        $this->applyLegacyLimitOrGridPagination($query, $params);

        /** @var Collection<int, object> $rows */
        $rows = $query->get();

        $docIds = $rows->pluck('rr_document_id')->filter()->unique()->values()->all();
        /** @var Collection<int, Collection<int, RrPenaltySnapshot>> $penaltiesGrouped */
        $penaltiesGrouped = Collection::make();
        if ($docIds !== []) {
            /** @var Collection<int, RrPenaltySnapshot> $snapshots */
            $snapshots = RrPenaltySnapshot::query()
                ->whereIn('rr_document_id', $docIds)
                ->whereNotNull('rr_document_id')
                ->whereIn('penalty_code', ['POL1', 'POLA', 'PLO', 'PCLA', 'ENHC'])
                ->get();
            /** @phpstan-ignore argument.type ($snapshots grouped by rr_document_id) */
            $penaltiesGrouped = $snapshots->groupBy(fn (RrPenaltySnapshot $p): int => (int) ($p->rr_document_id ?? 0));
        }

        /** @var array<int, array{wagon: array<string, array<string, float>>, sequence: array<int, array<string, float>>, broad: array<string, float>}> $mapCache */
        $mapCache = [];

        $roundWt = fn ($v): ?float => $v !== null && $v !== '' && is_finite((float) $v) ? round((float) $v, 2) : null;
        $roundRate = fn ($v): ?float => $v !== null && $v !== '' && is_finite((float) $v) ? round((float) $v, 4) : null;

        return $rows->map(function (object $row) use ($penaltiesGrouped, &$mapCache, $roundWt, $roundRate): array {
            $docId = (int) ($row->rr_document_id ?? 0);

            if ($docId > 0 && ! isset($mapCache[$docId])) {
                /** @var Collection<int, RrPenaltySnapshot>|null $forDoc */
                $forDoc = $penaltiesGrouped->get($docId);
                $mapCache[$docId] = $this->punitiveRateMapsForRrSnapshots(
                    $forDoc instanceof Collection ? $forDoc : collect()
                );
            }

            /** @var array{wagon: array<string, array<string, float>>, sequence: array<int, array<string, float>>, broad: array<string, float>} $maps */
            $maps = $docId > 0
                ? ($mapCache[$docId] ?? ['wagon' => [], 'sequence' => [], 'broad' => []])
                : ['wagon' => [], 'sequence' => [], 'broad' => []];

            $wagonSeq = null;
            if (property_exists($row, 'wagon_sequence') && $row->wagon_sequence !== null) {
                $wagonSeq = (int) $row->wagon_sequence;
            }

            $punitive = $docId > 0
                ? $this->punitiveRatePickFromMaps(
                    $maps,
                    property_exists($row, 'wagon_number') ? (string) $row->wagon_number : null,
                    $wagonSeq,
                )
                : null;

            $wagonNoUi = property_exists($row, 'wagon_number') && $row->wagon_number !== null && $row->wagon_number !== ''
                ? mb_trim((string) $row->wagon_number)
                : '';

            return [
                'Rake No' => property_exists($row, 'rake_number') && $row->rake_number !== null ? (string) $row->rake_number : '',
                'Wagon No' => $wagonNoUi,
                'CC Capacity (MT)' => $roundWt($row->pcc_weight_mt ?? null),
                'Actual Weight (MT)' => $roundWt($row->row_net_mt ?? null),
                'Permissible Weight (MT)' => $roundWt($row->permissible_weight_mt ?? null),
                'Chargeable Weight (MT)' => $roundWt($this->rrWagonDetailsChargeableWeightMt($row)),
                'Overload (MT)' => $roundWt($row->overload_weight_mt ?? null),
                'Normal Rate' => $roundRate($row->rate ?? null),
                'Punitive Rate' => $roundRate($punitive),
                '_rws_id' => (int) $row->rws_id,
            ];
        })->values()->all();
    }

    private function rrWagonDetailsChargeableWeightMt(object $row): ?float
    {
        $docWt = isset($row->rr_weight_mt) && $row->rr_weight_mt !== null ? (float) $row->rr_weight_mt : null;
        $docSumParsed = isset($row->doc_sum_net_mt) && $row->doc_sum_net_mt !== null ? (float) $row->doc_sum_net_mt : 0.0;
        $rowNet = isset($row->row_net_mt) && $row->row_net_mt !== null ? (float) $row->row_net_mt : null;
        $pcc = isset($row->pcc_weight_mt) && $row->pcc_weight_mt !== null ? (float) $row->pcc_weight_mt : null;
        $perm = isset($row->permissible_weight_mt) && $row->permissible_weight_mt !== null ? (float) $row->permissible_weight_mt : null;

        if ($docWt !== null && $docSumParsed > 0.0) {
            $numerator = $rowNet !== null ? max(0.0, $rowNet) : 0.0;

            return round($docWt * $numerator / $docSumParsed, 2);
        }

        if ($docWt !== null && ($docSumParsed <= 0.0) && ($pcc !== null || $perm !== null)) {
            return round((float) ($pcc ?? $perm), 2);
        }

        if ($pcc !== null) {
            return round($pcc, 2);
        }

        if ($perm !== null) {
            return round($perm, 2);
        }

        return null;
    }

    /**
     * Group penalty snapshot rows → maps by wagon_number, wagon_sequence, or whole-RR buckets.
     *
     * @param  Collection<int, RrPenaltySnapshot>  $snapshotsForDoc
     * @return array{wagon: array<string, array<string, float>>, sequence: array<int, array<string, float>>, broad: array<string, float>}
     */
    private function punitiveRateMapsForRrSnapshots(Collection $snapshotsForDoc): array
    {
        /** @var array<string, array<string, float>> $wagon */
        $wagon = [];
        /** @var array<int, array<string, float>> $sequence */
        $sequence = [];
        /** @var array<string, float> $broad */
        $broad = [];

        foreach ($snapshotsForDoc as $snap) {
            if (! $snap instanceof RrPenaltySnapshot) {
                continue;
            }
            $rate = $this->extractPunitiveRateFromPenaltySnapshotMeta($snap->meta ?? null);
            if ($rate === null) {
                continue;
            }

            $code = $snap->penalty_code !== null && $snap->penalty_code !== ''
                ? mb_strtoupper(mb_trim((string) $snap->penalty_code))
                : '';

            $wn = self::normalizeRrWagonSnapshotWagonKey((string) ($snap->wagon_number ?? ''));
            $seqRaw = $snap->wagon_sequence;

            if ($wn !== null) {
                if (! isset($wagon[$wn])) {
                    $wagon[$wn] = [];
                }

                $wagon[$wn][$code !== '' ? $code : '__'] = (float) $rate;

                continue;
            }

            if ($seqRaw !== null && (string) $seqRaw !== '') {
                $sq = (int) $seqRaw;
                if (! isset($sequence[$sq])) {
                    $sequence[$sq] = [];
                }
                $sequence[$sq][$code !== '' ? $code : '__'] = (float) $rate;

                continue;
            }

            if ($code !== '') {
                $broad[$code] = (float) $rate;
            }
        }

        return [
            'wagon' => $wagon,
            'sequence' => $sequence,
            'broad' => $broad,
        ];
    }

    /**
     * @param  array{wagon: array<string, array<string, float>>, sequence: array<int, array<string, float>>, broad: array<string, float>}  $maps
     */
    private function punitiveRatePickFromMaps(array $maps, ?string $wagonNumber, ?int $wagonSeq): ?float
    {
        $wnKey = self::normalizeRrWagonSnapshotWagonKey((string) ($wagonNumber ?? ''));

        if ($wnKey !== null && isset($maps['wagon'][$wnKey])) {
            return $this->pickPreferredOverloadPenaltyCodeRates($maps['wagon'][$wnKey]);
        }

        if ($wagonSeq !== null && isset($maps['sequence'][$wagonSeq])) {
            return $this->pickPreferredOverloadPenaltyCodeRates($maps['sequence'][$wagonSeq]);
        }

        return $this->pickPreferredOverloadPenaltyCodeRates($maps['broad']);
    }

    /** @param  array<string, float>  $codeRates */
    private function pickPreferredOverloadPenaltyCodeRates(array $codeRates): ?float
    {
        if ($codeRates === []) {
            return null;
        }

        foreach (['POL1', 'POLA', 'PLO', 'PCLA', 'ENHC'] as $code) {
            if (isset($codeRates[$code]) && is_finite($codeRates[$code])) {
                return (float) $codeRates[$code];
            }
        }

        foreach ($codeRates as $rate) {
            if (is_finite($rate)) {
                return (float) $rate;
            }
        }

        return null;
    }

    private function extractPunitiveRateFromPenaltySnapshotMeta(?array $meta): ?float
    {
        if ($meta === []) {
            return null;
        }

        foreach ([
            'punitive_rate',
            'punitive_freight_rate',
            'overload_punitive_rate',
            'punitiveRate',
        ] as $key) {
            if (isset($meta[$key]) && is_numeric($meta[$key])) {
                return (float) $meta[$key];
            }
        }

        return null;
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string}  $params
     */
    private function buildLoaderVsWeighmentAdvanceBaseQuery(array $sidingIds, array $params): QueryBuilder
    {
        if ($sidingIds === []) {
            return DB::table('wagon_loading as wl')->whereRaw('1 = 0');
        }

        $latestRwId = DB::table('rake_weighments')
            ->selectRaw('rake_id, MAX(id) as max_rw_id')
            ->groupBy('rake_id');

        $q = DB::table('wagon_loading as wl')
            ->join('rakes as r', 'r.id', '=', 'wl.rake_id')
            ->leftJoin('wagons as w', 'w.id', '=', 'wl.wagon_id')
            ->leftJoin('loaders as l', 'l.id', '=', 'wl.loader_id')
            ->leftJoinSub($latestRwId, 'lrw', function ($join): void {
                $join->on('lrw.rake_id', '=', 'wl.rake_id');
            })
            ->leftJoin('rake_weighments as rw', 'rw.id', '=', 'lrw.max_rw_id')
            ->leftJoin('rake_wagon_weighments as rww', function ($join): void {
                $join->on('rww.rake_weighment_id', '=', 'rw.id')
                    ->on('rww.wagon_id', '=', 'wl.wagon_id');
            })
            ->whereNull('r.deleted_at')
            ->whereIn('r.siding_id', $sidingIds);

        if (! empty($params['siding_id'])) {
            $q->where('r.siding_id', '=', (int) $params['siding_id']);
        }

        if (! empty($params['date_from'])) {
            $q->whereDate('r.loading_date', '>=', $params['date_from']);
        }

        if (! empty($params['date_to'])) {
            $q->whereDate('r.loading_date', '<=', $params['date_to']);
        }

        if (! empty($params['rake_number'])) {
            $needle = mb_trim((string) $params['rake_number']);
            if ($needle !== '') {
                $q->where('r.rake_number', 'like', '%'.$needle.'%');
            }
        }

        return $q;
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string}  $params
     */
    private function loaderVsWeighmentAdvanceReportCount(array $sidingIds, array $params): int
    {
        return (int) $this->buildLoaderVsWeighmentAdvanceBaseQuery($sidingIds, $params)->count('wl.id');
    }

    /**
     * Coal Logestic Advance — `wagon_loading` vs `rake_wagon_weighments` on the latest `rake_weighments` row per rake.
     *
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, grid_pagination?: bool, grid_offset?: int, grid_limit?: int, no_limit?: bool, limit?: int|null}  $params
     * @return array<int, array<string, mixed>>
     */
    private function loaderVsWeighmentAdvanceReport(array $sidingIds, array $params): array
    {
        $query = $this->buildLoaderVsWeighmentAdvanceBaseQuery($sidingIds, $params)
            ->select([
                'wl.id as wl_id',
                'r.rake_number',
                'rww.wagon_number as rww_wagon_number',
                'w.wagon_number as w_wagon_number',
                'wl.loaded_quantity_mt',
                'rww.net_weight_mt',
                'rww.actual_gross_mt',
                'rww.actual_tare_mt',
                'l.code as loader_code',
                'l.loader_name',
                'l.id as loader_table_id',
                'wl.loading_time',
            ])
            ->orderByDesc('r.loading_date')
            ->orderBy('r.rake_number')
            ->orderByDesc('wl.loading_time')
            ->orderBy('wl.id');

        $this->applyLegacyLimitOrGridPagination($query, $params);

        /** @var Collection<int, object> $rows */
        $rows = $query->get();

        return $rows->map(function (object $r): array {
            $wagonNo = $r->rww_wagon_number;
            if ($wagonNo === null || $wagonNo === '') {
                $wagonNo = $r->w_wagon_number ?? '';
            }

            $loaderQty = isset($r->loaded_quantity_mt) && $r->loaded_quantity_mt !== null
                ? (float) $r->loaded_quantity_mt
                : null;

            $net = isset($r->net_weight_mt) && $r->net_weight_mt !== null ? (float) $r->net_weight_mt : null;
            $gross = isset($r->actual_gross_mt) && $r->actual_gross_mt !== null ? (float) $r->actual_gross_mt : null;
            $tareActual = isset($r->actual_tare_mt) && $r->actual_tare_mt !== null ? (float) $r->actual_tare_mt : null;
            if ($net === null && $gross !== null && $tareActual !== null) {
                $net = $gross - $tareActual;
            }

            $weighmentQty = $net;

            $difference = ($loaderQty !== null && $weighmentQty !== null && is_finite($loaderQty) && is_finite($weighmentQty))
                ? round($loaderQty - $weighmentQty, 2)
                : null;

            $overloadFlag = '—';
            $underloadFlag = '—';
            if ($difference !== null) {
                if ($difference > 0) {
                    $overloadFlag = 'Yes';
                    $underloadFlag = 'No';
                } elseif ($difference < 0) {
                    $overloadFlag = 'No';
                    $underloadFlag = 'Yes';
                } else {
                    $overloadFlag = 'No';
                    $underloadFlag = 'No';
                }
            }

            $loaderIdOut = '';
            if (isset($r->loader_code) && $r->loader_code !== null && $r->loader_code !== '') {
                $loaderIdOut = (string) $r->loader_code;
            } elseif (isset($r->loader_name) && $r->loader_name !== null && $r->loader_name !== '') {
                $loaderIdOut = (string) $r->loader_name;
            } elseif (isset($r->loader_table_id) && $r->loader_table_id !== null) {
                $loaderIdOut = (string) $r->loader_table_id;
            }

            return [
                'Rake No' => isset($r->rake_number) ? (string) $r->rake_number : '',
                'Wagon No' => (string) $wagonNo,
                'Loader Qty (MT)' => $loaderQty !== null ? round($loaderQty, 2) : null,
                'Weighment Qty (MT)' => $weighmentQty !== null ? round($weighmentQty, 2) : null,
                'Difference (MT)' => $difference,
                'Overload Flag' => $overloadFlag,
                'Underload Flag' => $underloadFlag,
                'Loader ID' => $loaderIdOut,
                '_wl_id' => (int) $r->wl_id,
            ];
        })->values()->all();
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, loader?: string, grid_pagination?: bool, grid_offset?: int, grid_limit?: int, limit?: int|null, no_limit?: bool}  $params
     * @param  EloquentBuilder<\Illuminate\Database\Eloquent\Model>|QueryBuilder  $query
     */
    private function applyLegacyLimitOrGridPagination(EloquentBuilder|QueryBuilder $query, array $params): void
    {
        if (! empty($params['grid_pagination'])) {
            $query
                ->offset(max(0, (int) ($params['grid_offset'] ?? 0)))
                ->limit(max(1, (int) ($params['grid_limit'] ?? 60)));

            return;
        }

        $limit = $this->resolveLimit($params);
        if ($limit !== null) {
            $query->limit($limit);
        }
    }

    /**
     * Same matching as the `loader` filter in {@see self::wagonLoading()}: numeric `loader_id` or substring on `loader_name` / `code` on joined `wagon_loading`/`loaders` (`wl`/`l`).
     *
     * @param  array{loader?: string}  $params
     */
    private function applyOverloadUnderloadReportLoaderFilter(QueryBuilder $q, array $params): void
    {
        if (empty($params['loader'])) {
            return;
        }

        $loaderFilter = mb_trim((string) $params['loader']);
        if ($loaderFilter === '') {
            return;
        }

        $q->where(function ($sub) use ($loaderFilter): void {
            if (is_numeric($loaderFilter)) {
                $sub->where('wl.loader_id', (int) $loaderFilter)
                    ->orWhere('l.loader_name', 'like', '%'.$loaderFilter.'%')
                    ->orWhere('l.code', 'like', '%'.$loaderFilter.'%');
            } else {
                $sub->where(function ($inner) use ($loaderFilter): void {
                    $inner->where('l.loader_name', 'like', '%'.$loaderFilter.'%')
                        ->orWhere('l.code', 'like', '%'.$loaderFilter.'%');
                });
            }
        });
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string}  $params
     */
    private function sidingCoalReceiptCount(array $sidingIds, array $params): int
    {
        $query = $this->buildSidingCoalReceiptGroupedQuery($sidingIds, $params);

        return (int) DB::query()->fromSub($query, 'siding_coal_receipt_agg')->count();
    }

    /**
     * Grouped aggregate query before offset/limit (shared by count + rows).
     *
     * @param  array{siding_id?: int, date_from?: string, date_to?: string}  $params
     */
    private function buildSidingCoalReceiptGroupedQuery(array $sidingIds, array $params): QueryBuilder
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

        return $query;
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, loader?: string}  $params
     * @return array<int, array<string, mixed>>
     */
    private function sidingCoalReceipt(array $sidingIds, array $params): array
    {
        $query = $this->buildSidingCoalReceiptGroupedQuery($sidingIds, $params);
        $this->applyLegacyLimitOrGridPagination($query, $params);

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
     * @param  array{siding_id?: int, date_from?: string, date_to?: string}  $params
     */
    private function rakeIndentCount(array $sidingIds, array $params): int
    {
        $query = Indent::query()
            ->whereIn('siding_id', $sidingIds);
        $this->applyDateFilter($query, $params, 'indent_date', 'created_at');
        if (! empty($params['siding_id'])) {
            $query->where('siding_id', $params['siding_id']);
        }

        return (int) $query->count();
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string}  $params
     */
    private function txrReportCount(array $sidingIds, array $params): int
    {
        $query = Txr::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));
        if (! empty($params['date_from'])) {
            $query->whereHas('rake', fn ($q) => $q->whereDate('loading_date', '>=', $params['date_from']));
        }
        if (! empty($params['date_to'])) {
            $query->whereHas('rake', fn ($q) => $q->whereDate('loading_date', '<=', $params['date_to']));
        }
        if (! empty($params['siding_id'])) {
            $query->whereHas('rake', fn ($q) => $q->where('siding_id', $params['siding_id']));
        }
        if (! empty($params['rake_number'])) {
            $query->whereHas('rake', fn ($q) => $q->where('rake_number', 'like', '%'.$params['rake_number'].'%'));
        }

        return (int) $query->count();
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string}  $params
     */
    private function unfitWagonCount(array $sidingIds, array $params): int
    {
        $query = WagonUnfitLog::query()
            ->whereHas('txr.rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));

        if (! empty($params['date_from'])) {
            $query->whereHas('txr.rake', fn ($q) => $q->whereDate('loading_date', '>=', $params['date_from']));
        }
        if (! empty($params['date_to'])) {
            $query->whereHas('txr.rake', fn ($q) => $q->whereDate('loading_date', '<=', $params['date_to']));
        }

        if (! empty($params['siding_id'])) {
            $query->whereHas('txr.rake', fn ($q) => $q->where('siding_id', $params['siding_id']));
        }
        if (! empty($params['rake_number'])) {
            $query->whereHas('txr.rake', fn ($q) => $q->where('rake_number', 'like', '%'.$params['rake_number'].'%'));
        }

        return (int) $query->count();
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, loader?: string}  $params
     */
    private function wagonLoadingCount(array $sidingIds, array $params): int
    {
        $query = WagonLoading::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));

        if (! empty($params['date_from'])) {
            $query->whereHas('rake', fn ($q) => $q->whereDate('loading_date', '>=', $params['date_from']));
        }
        if (! empty($params['date_to'])) {
            $query->whereHas('rake', fn ($q) => $q->whereDate('loading_date', '<=', $params['date_to']));
        }

        if (! empty($params['siding_id'])) {
            $query->whereHas('rake', fn ($q) => $q->where('siding_id', $params['siding_id']));
        }
        if (! empty($params['rake_number'])) {
            $query->whereHas('rake', fn ($q) => $q->where('rake_number', 'like', '%'.$params['rake_number'].'%'));
        }
        if (! empty($params['loader'])) {
            $loaderFilter = mb_trim((string) $params['loader']);
            $query->where(function ($q) use ($loaderFilter): void {
                if (is_numeric($loaderFilter)) {
                    $q->where('loader_id', (int) $loaderFilter)
                        ->orWhereHas('loader', function ($loaderQuery) use ($loaderFilter): void {
                            $loaderQuery
                                ->where('loader_name', 'like', '%'.$loaderFilter.'%')
                                ->orWhere('code', 'like', '%'.$loaderFilter.'%');
                        });
                } else {
                    $q->whereHas('loader', function ($loaderQuery) use ($loaderFilter): void {
                        $loaderQuery
                            ->where('loader_name', 'like', '%'.$loaderFilter.'%')
                            ->orWhere('code', 'like', '%'.$loaderFilter.'%');
                    });
                }
            });
        }

        return (int) $query->count();
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string}  $params
     */
    private function weighmentReportCount(array $sidingIds, array $params): int
    {
        $query = RakeWagonWeighment::query()
            ->whereHas('rakeWeighment.rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));

        if (! empty($params['date_from'])) {
            $query->whereHas('rakeWeighment.rake', fn ($q) => $q->whereDate('loading_date', '>=', $params['date_from']));
        }
        if (! empty($params['date_to'])) {
            $query->whereHas('rakeWeighment.rake', fn ($q) => $q->whereDate('loading_date', '<=', $params['date_to']));
        }

        if (! empty($params['siding_id'])) {
            $query->whereHas('rakeWeighment.rake', fn ($q) => $q->where('siding_id', $params['siding_id']));
        }
        if (! empty($params['rake_number'])) {
            $query->whereHas('rakeWeighment.rake', fn ($q) => $q->where('rake_number', 'like', '%'.$params['rake_number'].'%'));
        }

        return (int) $query->count();
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string}  $params
     */
    private function loaderVsWeighmentCount(array $sidingIds, array $params): int
    {
        $query = $this->buildLoaderVsWeighmentBaseQuery($sidingIds, $params);

        return (int) $query->count();
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string}  $params
     */
    private function rrSummaryCount(array $sidingIds, array $params): int
    {
        $query = RrDocument::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));
        if (! empty($params['date_from'])) {
            $query->whereHas('rake', fn ($q) => $q->whereDate('loading_date', '>=', $params['date_from']));
        }
        if (! empty($params['date_to'])) {
            $query->whereHas('rake', fn ($q) => $q->whereDate('loading_date', '<=', $params['date_to']));
        }
        if (! empty($params['siding_id'])) {
            $query->whereHas('rake', fn ($q) => $q->where('siding_id', $params['siding_id']));
        }
        if (! empty($params['rake_number'])) {
            $query->whereHas('rake', fn ($q) => $q->where('rake_number', 'like', '%'.$params['rake_number'].'%'));
        }

        return (int) $query->count();
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string}  $params
     */
    private function buildLoaderVsWeighmentBaseQuery(array $sidingIds, array $params): QueryBuilder
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
            $query->whereDate('r.loading_date', '>=', $params['date_from']);
        }
        if (! empty($params['date_to'])) {
            $query->whereDate('r.loading_date', '<=', $params['date_to']);
        }
        if (! empty($params['rake_number'])) {
            $query->where('r.rake_number', 'like', '%'.$params['rake_number'].'%');
        }

        return $query->select([
            'r.rake_number as rake_no',
            'wl.wagon_id',
            'rww.wagon_number as weighment_wagon_no',
            'wl.loaded_quantity_mt as loader_qty_mt',
            'rww.net_weight_mt as inmotion_qty_mt',
            'rww.action_taken as action_taken',
        ])->addSelect(DB::raw('s.name as siding'));
    }

    /**
     * Penalty merged rows without per-query limits (paginated grids load full merged set).
     *
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string}  $params
     */
    private function mergedPenaltyInternalRowsForGrid(array $sidingIds, array $params): Collection
    {
        [$appliedQuery, $snapshotQuery] = $this->penaltyRegisterBaseQueries($sidingIds, $params);

        $appliedInternal = $appliedQuery->latest()->get()->map(fn (AppliedPenalty $p): array => $this->mapAppliedPenaltyToInternalRow($p));
        $snapshotInternal = $snapshotQuery->latest()->get()->map(fn (RrPenaltySnapshot $p): array => $this->mapRrPenaltySnapshotToInternalRow($p));

        return $appliedInternal
            ->concat($snapshotInternal)
            ->sortByDesc(fn (array $row): ?string => $row['report_date'])
            ->values();
    }

    /**
     * Penalty merged rows with legacy export limits applied to each query source.
     *
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, limit?: int|null, no_limit?: bool}  $params
     */
    private function mergedPenaltyInternalRows(array $sidingIds, array $params): Collection
    {
        [$appliedQuery, $snapshotQuery] = $this->penaltyRegisterBaseQueries($sidingIds, $params);

        $limit = $this->resolveLimit($params);
        if ($limit !== null) {
            $appliedQuery->limit($limit);
            $snapshotQuery->limit($limit);
        }

        $appliedInternal = $appliedQuery->latest()->get()->map(fn (AppliedPenalty $p): array => $this->mapAppliedPenaltyToInternalRow($p));
        $snapshotInternal = $snapshotQuery->latest()->get()->map(fn (RrPenaltySnapshot $p): array => $this->mapRrPenaltySnapshotToInternalRow($p));

        return $appliedInternal
            ->concat($snapshotInternal)
            ->sortByDesc(fn (array $row): ?string => $row['report_date'])
            ->values();
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string}  $params
     */
    private function penaltyRegisterBaseQueries(array $sidingIds, array $params): array
    {
        $appliedQuery = AppliedPenalty::query()
            ->with(['rake.siding:id,name', 'penaltyType:id,code,name,calculation_type'])
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));

        $snapshotQuery = RrPenaltySnapshot::query()
            ->with(['rake.siding:id,name'])
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));

        if (! empty($params['date_from'])) {
            $appliedQuery->whereHas('rake', fn ($q) => $q->whereDate('loading_date', '>=', $params['date_from']));
            $snapshotQuery->whereHas('rake', fn ($q) => $q->whereDate('loading_date', '>=', $params['date_from']));
        }
        if (! empty($params['date_to'])) {
            $appliedQuery->whereHas('rake', fn ($q) => $q->whereDate('loading_date', '<=', $params['date_to']));
            $snapshotQuery->whereHas('rake', fn ($q) => $q->whereDate('loading_date', '<=', $params['date_to']));
        }

        if (! empty($params['siding_id'])) {
            $appliedQuery->whereHas('rake', fn ($q) => $q->where('siding_id', $params['siding_id']));
            $snapshotQuery->whereHas('rake', fn ($q) => $q->where('siding_id', $params['siding_id']));
        }
        if (! empty($params['rake_number'])) {
            $appliedQuery->whereHas('rake', fn ($q) => $q->where('rake_number', 'like', '%'.$params['rake_number'].'%'));
            $snapshotQuery->whereHas('rake', fn ($q) => $q->where('rake_number', 'like', '%'.$params['rake_number'].'%'));
        }

        return [$appliedQuery, $snapshotQuery];
    }

    /**
     * Normalized row for merging (includes optional overload delay fields for Coal Logestic penalty report).
     *
     * @return array{
     *     report_date: ?string,
     *     siding: ?string,
     *     rake_no: ?string,
     *     penalty_type: ?string,
     *     amount: ?float,
     *     stage_label: string,
     *     overload_qty: ?float,
     *     delay_time: ?string,
     *     remarks: string
     * }
     */
    private function mapAppliedPenaltyToInternalRow(AppliedPenalty $p): array
    {
        return [
            'report_date' => $p->rake?->created_at?->toDateString(),
            'siding' => $p->rake?->siding?->name,
            'rake_no' => $p->rake?->rake_number,
            'penalty_type' => $p->penaltyType?->code ?? $p->penaltyType?->name,
            'amount' => $p->amount !== null ? (float) $p->amount : null,
            'stage_label' => self::PENALTY_STAGE_PRE,
            'overload_qty' => $this->overloadQtyFromAppliedPenalty($p),
            'delay_time' => $this->delayDescriptionFromAppliedPenalty($p),
            'remarks' => '',
        ];
    }

    /**
     * @return array{
     *     report_date: ?string,
     *     siding: ?string,
     *     rake_no: ?string,
     *     penalty_type: ?string,
     *     amount: ?float,
     *     stage_label: string,
     *     overload_qty: ?float,
     *     delay_time: ?string,
     *     remarks: string
     * }
     */
    private function mapRrPenaltySnapshotToInternalRow(RrPenaltySnapshot $p): array
    {
        return [
            'report_date' => $p->rake?->created_at?->toDateString(),
            'siding' => $p->rake?->siding?->name,
            'rake_no' => $p->rake?->rake_number,
            'penalty_type' => $p->penalty_code,
            'amount' => $p->amount !== null ? (float) $p->amount : null,
            'stage_label' => self::PENALTY_STAGE_POST,
            'overload_qty' => $this->overloadQtyFromSnapshot($p),
            'delay_time' => $this->delayDescriptionFromSnapshot($p),
            'remarks' => '',
        ];
    }

    /**
     * @param  array{
     *     report_date: ?string,
     *     siding: ?string,
     *     rake_no: ?string,
     *     penalty_type: ?string,
     *     amount: ?float,
     *     stage_label: string,
     *     overload_qty: ?float,
     *     delay_time: ?string,
     *     remarks: string
     * }  $row
     * @return array<string, mixed>
     */
    private function mapPenaltyInternalRowToLegacy(array $row): array
    {
        return [
            'Date' => $row['report_date'],
            'Siding' => $row['siding'],
            'Rake No' => $row['rake_no'],
            'Penalty Type' => $row['penalty_type'],
            'Reason' => '',
            'Amount' => $row['amount'],
            'Stage Detected (Pre-RR/Post-RR)' => $row['stage_label'],
            'Remarks' => $row['remarks'],
        ];
    }

    /**
     * @param  array{
     *     report_date: ?string,
     *     siding: ?string,
     *     rake_no: ?string,
     *     penalty_type: ?string,
     *     amount: ?float,
     *     stage_label: string,
     *     overload_qty: ?float,
     *     delay_time: ?string,
     *     remarks: string
     * }  $row
     * @return array<string, mixed>
     */
    private function mapPenaltyInternalRowToCore(array $row): array
    {
        return [
            'Date' => $row['report_date'],
            'Siding' => $row['siding'],
            'Rake No' => $row['rake_no'],
            'Penalty Type' => $row['penalty_type'],
            'Penalty Amount' => $row['amount'],
            'Overload Qty' => $row['overload_qty'],
            'Delay Time' => $row['delay_time'],
            'Stage (Pre/Post RR)' => $row['stage_label'],
            'Remarks' => $row['remarks'],
        ];
    }

    private function overloadQtyFromAppliedPenalty(AppliedPenalty $p): ?float
    {
        if ($p->quantity === null) {
            return null;
        }

        return round((float) $p->quantity, 3);
    }

    private function delayDescriptionFromAppliedPenalty(AppliedPenalty $p): ?string
    {
        $meta = $p->meta ?? [];
        if (isset($meta['excess_minutes']) && is_numeric($meta['excess_minutes'])) {
            return sprintf('%s min', $meta['excess_minutes']);
        }
        if (isset($meta['charged_hours']) && is_numeric($meta['charged_hours'])) {
            return sprintf('%s hr', $meta['charged_hours']);
        }

        return null;
    }

    private function overloadQtyFromSnapshot(RrPenaltySnapshot $p): ?float
    {
        $meta = $p->meta ?? [];
        foreach (['overload_mt', 'overload_qty', 'quantity_mt', 'quantity'] as $key) {
            if (isset($meta[$key]) && is_numeric($meta[$key])) {
                return round((float) $meta[$key], 3);
            }
        }

        return null;
    }

    private function delayDescriptionFromSnapshot(RrPenaltySnapshot $p): ?string
    {
        $meta = $p->meta ?? [];
        foreach (['excess_minutes', 'delay_minutes', 'total_delay_minutes'] as $key) {
            if (isset($meta[$key]) && is_numeric($meta[$key])) {
                return sprintf('%s min', $meta[$key]);
            }
        }

        return null;
    }

    /**
     * Merges applied + snapshot penalties, then slices one page (full merge in memory).
     *
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, penalty_stage?: string}  $params
     * @return array{data: array<int, array<string, mixed>>, total: int}
     */
    private function penaltyRegisterPaginatedSlice(array $sidingIds, array $params, int $offset, int $perPage, bool $coreReport): array
    {
        $merged = $this->mergedPenaltyInternalRowsForGrid($sidingIds, $params);

        if ($coreReport) {
            $stageFilter = isset($params['penalty_stage']) ? (string) $params['penalty_stage'] : '';
            if ($stageFilter === 'pre_rr') {
                $merged = $merged->filter(fn (array $row): bool => $row['stage_label'] === self::PENALTY_STAGE_PRE)->values();
            } elseif ($stageFilter === 'post_rr') {
                $merged = $merged->filter(fn (array $row): bool => $row['stage_label'] === self::PENALTY_STAGE_POST)->values();
            }
        }

        $total = $merged->count();
        $pageRows = $merged->slice($offset, $perPage)->values();
        $data = $pageRows
            ->map(fn (array $row): array => $coreReport
                ? $this->mapPenaltyInternalRowToCore($row)
                : $this->mapPenaltyInternalRowToLegacy($row))
            ->values()
            ->all();

        return ['data' => $data, 'total' => $total];
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
        $query->latest('indent_date')->latest();
        $this->applyLegacyLimitOrGridPagination($query, $params);
        $rows = $query->get();

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
        if (! empty($params['date_from'])) {
            $query->whereHas('rake', fn ($q) => $q->whereDate('loading_date', '>=', $params['date_from']));
        }
        if (! empty($params['date_to'])) {
            $query->whereHas('rake', fn ($q) => $q->whereDate('loading_date', '<=', $params['date_to']));
        }
        if (! empty($params['siding_id'])) {
            $query->whereHas('rake', fn ($q) => $q->where('siding_id', $params['siding_id']));
        }
        if (! empty($params['rake_number'])) {
            $query->whereHas('rake', fn ($q) => $q->where('rake_number', 'like', '%'.$params['rake_number'].'%'));
        }
        $query->orderByDesc('inspection_time')->latest();
        $this->applyLegacyLimitOrGridPagination($query, $params);
        $rows = $query->get();

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
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, loader?: string}  $params
     * @return array<int, array<string, mixed>>
     */
    private function unfitWagon(array $sidingIds, array $params): array
    {
        $query = WagonUnfitLog::query()
            ->with(['wagon:id,wagon_number,wagon_type', 'txr.rake:id,rake_number,siding_id', 'txr.rake.siding:id,name'])
            ->whereHas('txr.rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));

        if (! empty($params['date_from'])) {
            $query->whereHas('txr.rake', fn ($q) => $q->whereDate('loading_date', '>=', $params['date_from']));
        }
        if (! empty($params['date_to'])) {
            $query->whereHas('txr.rake', fn ($q) => $q->whereDate('loading_date', '<=', $params['date_to']));
        }

        if (! empty($params['siding_id'])) {
            $query->whereHas('txr.rake', fn ($q) => $q->where('siding_id', $params['siding_id']));
        }
        if (! empty($params['rake_number'])) {
            $query->whereHas('txr.rake', fn ($q) => $q->where('rake_number', 'like', '%'.$params['rake_number'].'%'));
        }

        $query->orderByDesc('marked_at')->latest();
        $this->applyLegacyLimitOrGridPagination($query, $params);
        $rows = $query->get();

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
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, loader?: string}  $params
     * @return array<int, array<string, mixed>>
     */
    private function wagonLoading(array $sidingIds, array $params): array
    {
        $query = WagonLoading::query()
            ->with(['rake.siding:id,name,code', 'wagon:id,wagon_number'])
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));

        if (! empty($params['date_from'])) {
            $query->whereHas('rake', fn ($q) => $q->whereDate('loading_date', '>=', $params['date_from']));
        }
        if (! empty($params['date_to'])) {
            $query->whereHas('rake', fn ($q) => $q->whereDate('loading_date', '<=', $params['date_to']));
        }

        if (! empty($params['siding_id'])) {
            $query->whereHas('rake', fn ($q) => $q->where('siding_id', $params['siding_id']));
        }
        if (! empty($params['rake_number'])) {
            $query->whereHas('rake', fn ($q) => $q->where('rake_number', 'like', '%'.$params['rake_number'].'%'));
        }
        if (! empty($params['loader'])) {
            $loaderFilter = mb_trim((string) $params['loader']);
            $query->where(function ($q) use ($loaderFilter): void {
                if (is_numeric($loaderFilter)) {
                    $q->where('loader_id', (int) $loaderFilter)
                        ->orWhereHas('loader', function ($loaderQuery) use ($loaderFilter): void {
                            $loaderQuery
                                ->where('loader_name', 'like', '%'.$loaderFilter.'%')
                                ->orWhere('code', 'like', '%'.$loaderFilter.'%');
                        });
                } else {
                    $q->whereHas('loader', function ($loaderQuery) use ($loaderFilter): void {
                        $loaderQuery
                            ->where('loader_name', 'like', '%'.$loaderFilter.'%')
                            ->orWhere('code', 'like', '%'.$loaderFilter.'%');
                    });
                }
            });
        }

        $query->latest('loading_time')->latest();
        $this->applyLegacyLimitOrGridPagination($query, $params);
        $rows = $query->get();

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
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, loader?: string}  $params
     * @return array<int, array<string, mixed>>
     */
    private function weighmentReport(array $sidingIds, array $params): array
    {
        $query = RakeWagonWeighment::query()
            ->with('rakeWeighment.rake.siding:id,name')
            ->whereHas('rakeWeighment.rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));

        if (! empty($params['date_from'])) {
            $query->whereHas('rakeWeighment.rake', fn ($q) => $q->whereDate('loading_date', '>=', $params['date_from']));
        }
        if (! empty($params['date_to'])) {
            $query->whereHas('rakeWeighment.rake', fn ($q) => $q->whereDate('loading_date', '<=', $params['date_to']));
        }

        if (! empty($params['siding_id'])) {
            $query->whereHas('rakeWeighment.rake', fn ($q) => $q->where('siding_id', $params['siding_id']));
        }
        if (! empty($params['rake_number'])) {
            $query->whereHas('rakeWeighment.rake', fn ($q) => $q->where('rake_number', 'like', '%'.$params['rake_number'].'%'));
        }

        $query->orderByDesc('weighment_time')->latest();
        $this->applyLegacyLimitOrGridPagination($query, $params);
        $rows = $query->get();

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
     * Latest wagon_loading row per rake+wagon (MAX id), for loader / operator on overload lines.
     *
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, loader?: string}  $params
     */
    private function buildOverloadingReportBaseQuery(array $sidingIds, array $params): QueryBuilder
    {
        $wlLatestIds = DB::table('wagon_loading')
            ->selectRaw('rake_id, wagon_id, MAX(id) as wl_id')
            ->groupBy('rake_id', 'wagon_id');

        $q = DB::table('rake_wagon_weighments as rww')
            ->join('rake_weighments as rwm', 'rwm.id', '=', 'rww.rake_weighment_id')
            ->join('rakes as r', 'r.id', '=', 'rwm.rake_id')
            ->join('sidings as s', 's.id', '=', 'r.siding_id')
            ->leftJoin('wagons as w', 'w.id', '=', 'rww.wagon_id')
            ->leftJoinSub($wlLatestIds, 'wl_latest', function ($join): void {
                $join->on('wl_latest.rake_id', '=', 'r.id')
                    ->on('wl_latest.wagon_id', '=', 'rww.wagon_id');
            })
            ->leftJoin('wagon_loading as wl', 'wl.id', '=', 'wl_latest.wl_id')
            ->leftJoin('loaders as l', 'l.id', '=', 'wl.loader_id')
            ->whereIn('r.siding_id', $sidingIds)
            ->where('rww.over_load_mt', '>', 0);

        if (! empty($params['siding_id'])) {
            $q->where('r.siding_id', '=', $params['siding_id']);
        }

        if (! empty($params['date_from'])) {
            $q->whereDate('r.loading_date', '>=', $params['date_from']);
        }
        if (! empty($params['date_to'])) {
            $q->whereDate('r.loading_date', '<=', $params['date_to']);
        }

        if (! empty($params['rake_number'])) {
            $q->where('r.rake_number', 'like', '%'.$params['rake_number'].'%');
        }

        $this->applyOverloadUnderloadReportLoaderFilter($q, $params);

        return $q;
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, loader?: string}  $params
     */
    private function overloadingReportCount(array $sidingIds, array $params): int
    {
        return (int) $this->buildOverloadingReportBaseQuery($sidingIds, $params)->count('rww.id');
    }

    /**
     * In-motion net used as "Actual Weight (MT)".
     *
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, loader?: string}  $params
     * @return array<int, array<string, mixed>>
     */
    private function overloadingReport(array $sidingIds, array $params): array
    {
        $query = $this->buildOverloadingReportBaseQuery($sidingIds, $params)
            ->select([
                'rww.cc_capacity_mt',
                'rww.net_weight_mt',
                'rww.over_load_mt',
                'rww.weighment_time',
                'rww.action_taken',
                'rww.wagon_number as rww_wagon_number',
                'r.rake_number',
                'r.loading_date',
                's.name as siding_name',
                'w.wagon_number as w_wagon_number',
                'wl.loader_operator_name',
                'l.code as loader_code',
                'l.loader_name',
                'l.id as loader_table_id',
            ])
            ->orderByDesc('rww.weighment_time');

        $this->applyLegacyLimitOrGridPagination($query, $params);

        /** @var Collection<int, object> $rows */
        $rows = $query->get();

        return $rows->map(function (object $r): array {
            $wagonNo = $r->rww_wagon_number;
            if ($wagonNo === null || $wagonNo === '') {
                $wagonNo = $r->w_wagon_number;
            }

            $loaderId = '';
            if (isset($r->loader_code) && $r->loader_code !== null && $r->loader_code !== '') {
                $loaderId = (string) $r->loader_code;
            } elseif (isset($r->loader_name) && $r->loader_name !== null && $r->loader_name !== '') {
                $loaderId = (string) $r->loader_name;
            } elseif (isset($r->loader_table_id) && $r->loader_table_id !== null) {
                $loaderId = (string) $r->loader_table_id;
            }

            $dateOut = '';
            if (! empty($r->weighment_time)) {
                $dateOut = Carbon::parse((string) $r->weighment_time)->toDateString();
            } elseif (! empty($r->loading_date)) {
                $dateOut = Carbon::parse((string) $r->loading_date)->toDateString();
            }

            $remarks = isset($r->action_taken) && $r->action_taken !== '' && $r->action_taken !== null
                ? (string) $r->action_taken
                : '';

            return [
                'Date' => $dateOut,
                'Siding' => $r->siding_name !== null ? (string) $r->siding_name : '',
                'Rake No' => $r->rake_number !== null ? (string) $r->rake_number : '',
                'Wagon No' => $wagonNo !== null ? (string) $wagonNo : '',
                'CC Capacity (MT)' => $r->cc_capacity_mt !== null ? round((float) $r->cc_capacity_mt, 2) : null,
                'Actual Weight (MT)' => $r->net_weight_mt !== null ? round((float) $r->net_weight_mt, 2) : null,
                'Overload Qty (MT)' => $r->over_load_mt !== null ? round((float) $r->over_load_mt, 2) : null,
                'Loader ID' => $loaderId,
                'Loader Operator' => isset($r->loader_operator_name) && $r->loader_operator_name !== null
                    ? (string) $r->loader_operator_name
                    : '',
                'Penalty Impact' => self::OVERLOADING_REPORT_PENALTY_IMPACT,
                'Remarks' => $remarks,
            ];
        })->values()->all();
    }

    /**
     * Rake performance modal: shortfall % = (under_load_mt / cc) × 100, count when >= threshold; overload wagons excluded.
     *
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string}  $params
     */
    private function resolveUnderloadingThresholdPercent(array $params): float
    {
        $v = $params['underload_threshold_percent'] ?? self::DEFAULT_UNDERLOAD_THRESHOLD_PERCENT;

        return max(0.0, min(100.0, (float) $v));
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, underload_threshold_percent?: float, loader?: string}  $params
     */
    private function buildUnderloadingReportBaseQuery(array $sidingIds, array $params): QueryBuilder
    {
        $threshold = $this->resolveUnderloadingThresholdPercent($params);
        $wlLatestIds = DB::table('wagon_loading')
            ->selectRaw('rake_id, wagon_id, MAX(id) as wl_id')
            ->groupBy('rake_id', 'wagon_id');

        $ccEff = 'COALESCE(rww.cc_capacity_mt, w.pcc_weight_mt)';

        $q = DB::table('rake_wagon_weighments as rww')
            ->join('rake_weighments as rwm', 'rwm.id', '=', 'rww.rake_weighment_id')
            ->join('rakes as r', 'r.id', '=', 'rwm.rake_id')
            ->join('sidings as s', 's.id', '=', 'r.siding_id')
            ->leftJoin('wagons as w', 'w.id', '=', 'rww.wagon_id')
            ->leftJoinSub($wlLatestIds, 'wl_latest', function ($join): void {
                $join->on('wl_latest.rake_id', '=', 'r.id')
                    ->on('wl_latest.wagon_id', '=', 'rww.wagon_id');
            })
            ->leftJoin('wagon_loading as wl', 'wl.id', '=', 'wl_latest.wl_id')
            ->leftJoin('loaders as l', 'l.id', '=', 'wl.loader_id')
            ->whereIn('r.siding_id', $sidingIds)
            ->whereRaw('(rww.over_load_mt IS NULL OR rww.over_load_mt <= 0)')
            ->where('rww.under_load_mt', '>', 0)
            ->whereRaw("({$ccEff}) > 0")
            ->whereRaw("(rww.under_load_mt * 100.0 / ({$ccEff})) >= ?", [$threshold]);

        if (! empty($params['siding_id'])) {
            $q->where('r.siding_id', '=', $params['siding_id']);
        }

        if (! empty($params['date_from'])) {
            $q->whereDate('r.loading_date', '>=', $params['date_from']);
        }
        if (! empty($params['date_to'])) {
            $q->whereDate('r.loading_date', '<=', $params['date_to']);
        }

        if (! empty($params['rake_number'])) {
            $q->where('r.rake_number', 'like', '%'.$params['rake_number'].'%');
        }

        $this->applyOverloadUnderloadReportLoaderFilter($q, $params);

        return $q;
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, underload_threshold_percent?: float, loader?: string}  $params
     */
    private function underloadingReportCount(array $sidingIds, array $params): int
    {
        return (int) $this->buildUnderloadingReportBaseQuery($sidingIds, $params)->count('rww.id');
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, underload_threshold_percent?: float, loader?: string}  $params
     * @return array<int, array<string, mixed>>
     */
    private function underloadingReport(array $sidingIds, array $params): array
    {
        $ccEffSql = 'COALESCE(rww.cc_capacity_mt, w.pcc_weight_mt)';

        $query = $this->buildUnderloadingReportBaseQuery($sidingIds, $params)
            ->select([
                'rww.under_load_mt',
                'rww.net_weight_mt',
                'rww.weighment_time',
                'rww.wagon_number as rww_wagon_number',
                'r.rake_number',
                'r.loading_date',
                's.name as siding_name',
                'w.wagon_number as w_wagon_number',
                'l.code as loader_code',
                'l.loader_name',
                'l.id as loader_table_id',
            ])
            ->addSelect(DB::raw("({$ccEffSql}) as cc_display_mt"))
            ->orderByDesc('rww.weighment_time');

        $this->applyLegacyLimitOrGridPagination($query, $params);

        /** @var Collection<int, object> $rows */
        $rows = $query->get();

        return $rows->map(function (object $r): array {
            $wagonNo = $r->rww_wagon_number;
            if ($wagonNo === null || $wagonNo === '') {
                $wagonNo = $r->w_wagon_number;
            }

            $loaderId = '';
            if (isset($r->loader_code) && $r->loader_code !== null && $r->loader_code !== '') {
                $loaderId = (string) $r->loader_code;
            } elseif (isset($r->loader_name) && $r->loader_name !== null && $r->loader_name !== '') {
                $loaderId = (string) $r->loader_name;
            } elseif (isset($r->loader_table_id) && $r->loader_table_id !== null) {
                $loaderId = (string) $r->loader_table_id;
            }

            $dateOut = '';
            if (! empty($r->weighment_time)) {
                $dateOut = Carbon::parse((string) $r->weighment_time)->toDateString();
            } elseif (! empty($r->loading_date)) {
                $dateOut = Carbon::parse((string) $r->loading_date)->toDateString();
            }

            $ccVal = isset($r->cc_display_mt) && $r->cc_display_mt !== null ? round((float) $r->cc_display_mt, 2) : null;
            $underMt = isset($r->under_load_mt) && $r->under_load_mt !== null ? round((float) $r->under_load_mt, 2) : null;

            return [
                'Date' => $dateOut,
                'Siding' => $r->siding_name !== null ? (string) $r->siding_name : '',
                'Rake No' => $r->rake_number !== null ? (string) $r->rake_number : '',
                'Wagon No' => $wagonNo !== null ? (string) $wagonNo : '',
                'CC Capacity (MT)' => $ccVal,
                'Actual Weight (MT)' => isset($r->net_weight_mt) && $r->net_weight_mt !== null
                    ? round((float) $r->net_weight_mt, 2)
                    : null,
                'Underload Qty (MT)' => $underMt,
                'Loss Impact' => self::UNDERLOADING_REPORT_LOSS_IMPACT,
                'Loader ID' => $loaderId,
            ];
        })->values()->all();
    }

    /**
     * Coal Logestic Core: one row per loader (wagon_loading vs CC on wagons).
     *
     * Overload: loaded &gt; effective CC. Underload: eligible, not overloaded, and fill &lt; 97% of effective CC
     * (see {@see self::LOADER_OPERATOR_PERFORMANCE_MIN_ACCEPTABLE_LOAD_FRACTION_OF_CC}). `underload_threshold_percent` does not apply.
     *
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, loader_id?: int, loader_operator_name?: string, grid_pagination?: bool, grid_offset?: int, grid_limit?: int, no_limit?: bool, limit?: int|null}  $params
     * @return array<int, array<string, mixed>>
     */
    private function loaderPerformanceReport(array $sidingIds, array $params): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $grouped = $this->buildLoaderPerformanceGroupedSubquery($sidingIds, $params);

        $query = DB::query()->fromSub($grouped, 'loader_perf')
            ->orderBy('siding_name')
            ->orderBy('loader_name_sort');

        $this->applyLegacyLimitOrGridPagination($query, $params);

        /** @var Collection<int, object> $rows */
        $rows = $query->get();

        return $rows->map(function (object $r): array {
            $total = (int) $r->total_wagons_loaded;
            $overload = (int) $r->overload_count;
            $underload = (int) $r->underload_count;
            $accuracyFraction = $total > 0
                ? 100.0 * ($total - $overload - $underload) / $total
                : null;
            $accuracyDisplay = $accuracyFraction === null
                ? '—'
                : $this->formatLoaderPerformanceAccuracyPercent(round($accuracyFraction, 1));

            $loaderIdOut = '';
            if (isset($r->loader_code) && $r->loader_code !== null && $r->loader_code !== '') {
                $loaderIdOut = (string) $r->loader_code;
            } elseif (isset($r->loader_name) && $r->loader_name !== null && $r->loader_name !== '') {
                $loaderIdOut = (string) $r->loader_name;
            } elseif (isset($r->loader_table_id) && $r->loader_table_id !== null) {
                $loaderIdOut = (string) $r->loader_table_id;
            }

            $avgLoad = isset($r->avg_load) && $r->avg_load !== null ? round((float) $r->avg_load, 2) : null;
            $avgDev = isset($r->avg_deviation_mt) && $r->avg_deviation_mt !== null
                ? round((float) $r->avg_deviation_mt, 2)
                : null;

            return [
                'Loader' => $loaderIdOut,
                'Siding' => isset($r->siding_name) ? (string) $r->siding_name : '',
                'Wagons' => $total,
                'Overload' => $overload,
                'Underload' => $underload,
                'Avg MT' => $avgLoad,
                'Dev MT' => $avgDev,
                'Accuracy' => $accuracyDisplay,
            ];
        })->values()->all();
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, loader_id?: int, loader_operator_name?: string}  $params
     */
    private function loaderPerformanceReportCount(array $sidingIds, array $params): int
    {
        if ($sidingIds === []) {
            return 0;
        }

        $grouped = $this->buildLoaderPerformanceGroupedSubquery($sidingIds, $params);

        return (int) DB::query()->fromSub($grouped, 'c')->count();
    }

    /**
     * Coal Logestic Core: wagon_loading aggregated per operator × loader × siding (same overload / underload rules as Loader Performance).
     *
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, loader_id?: int, loader_operator_name?: string, grid_pagination?: bool, grid_offset?: int, grid_limit?: int, no_limit?: bool, limit?: int|null}  $params
     * @return array<int, array<string, mixed>>
     */
    private function operatorPerformanceReport(array $sidingIds, array $params): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $grouped = $this->buildOperatorPerformanceGroupedSubquery($sidingIds, $params);

        $query = DB::query()->fromSub($grouped, 'operator_perf')
            ->orderBy('siding_name')
            ->orderBy('operator_bucket')
            ->orderBy('loader_name_sort');

        $this->applyLegacyLimitOrGridPagination($query, $params);

        /** @var Collection<int, object> $rows */
        $rows = $query->get();

        return $rows->map(function (object $r): array {
            $total = (int) $r->total_wagons_loaded;
            $overload = (int) $r->overload_count;
            $underload = (int) $r->underload_count;
            $accuracyFraction = $total > 0
                ? 100.0 * ($total - $overload - $underload) / $total
                : null;
            $accuracyDisplay = $accuracyFraction === null
                ? '—'
                : $this->formatLoaderPerformanceAccuracyPercent(round($accuracyFraction, 1));

            $loaderIdOut = '';
            if (isset($r->loader_code) && $r->loader_code !== null && $r->loader_code !== '') {
                $loaderIdOut = (string) $r->loader_code;
            } elseif (isset($r->loader_name) && $r->loader_name !== null && $r->loader_name !== '') {
                $loaderIdOut = (string) $r->loader_name;
            } elseif (isset($r->loader_table_id) && $r->loader_table_id !== null) {
                $loaderIdOut = (string) $r->loader_table_id;
            }

            $bucket = isset($r->operator_bucket) && $r->operator_bucket !== null ? (string) $r->operator_bucket : '';

            return [
                'Operator Name' => $bucket !== '' ? $bucket : '—',
                'Loader ID' => $loaderIdOut,
                'Siding' => isset($r->siding_name) ? (string) $r->siding_name : '',
                'Total Wagons' => $total,
                'Overload Cases' => $overload,
                'Underload Cases' => $underload,
                'Accuracy %' => $accuracyDisplay,
                'Remarks' => isset($r->remarks_agg) && $r->remarks_agg !== null && $r->remarks_agg !== '' ? (string) $r->remarks_agg : '',
                '_operator_perf_key' => $bucket.'|'.$loaderIdOut.'|'.(isset($r->siding_name) ? (string) $r->siding_name : ''),
            ];
        })->values()->all();
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, loader_id?: int, loader_operator_name?: string}  $params
     */
    private function operatorPerformanceReportCount(array $sidingIds, array $params): int
    {
        if ($sidingIds === []) {
            return 0;
        }

        $grouped = $this->buildOperatorPerformanceGroupedSubquery($sidingIds, $params);

        return (int) DB::query()->fromSub($grouped, 'c')->count();
    }

    /**
     * Grouped operator + loader + siding rows from `wagon_loading`.
     *
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, loader_id?: int, loader_operator_name?: string}  $params
     */
    private function buildOperatorPerformanceGroupedSubquery(array $sidingIds, array $params): QueryBuilder
    {
        $ccEff = 'COALESCE(wl.cc_capacity_mt, w.pcc_weight_mt)';
        $eligible = '(wl.loaded_quantity_mt IS NOT NULL AND '.$ccEff.' IS NOT NULL AND '.$ccEff.' > 0)';
        $overloadClause = "{$eligible} AND wl.loaded_quantity_mt > {$ccEff}";
        $minFill = $this->loaderOperatorPerformanceMinAcceptableLoadFractionSql();
        $underloadClause = "{$eligible} AND wl.loaded_quantity_mt <= {$ccEff} AND wl.loaded_quantity_mt < ({$ccEff} * {$minFill})";

        $opKey = $this->operatorPerformanceSqlOperatorGroupKeyExpr('wl');
        $remarksAgg = $this->sidingCoalReceiptRemarksAggregateSql('wl.remarks');

        $q = DB::table('wagon_loading as wl')
            ->join('rakes as r', 'r.id', '=', 'wl.rake_id')
            ->join('wagons as w', 'w.id', '=', 'wl.wagon_id')
            ->join('loaders as l', 'l.id', '=', 'wl.loader_id')
            ->join('sidings as ls', 'ls.id', '=', 'l.siding_id')
            ->whereNull('l.deleted_at')
            ->whereNull('r.deleted_at')
            ->whereIn('r.siding_id', $sidingIds)
            ->whereNotNull('wl.loader_id');

        if (! empty($params['siding_id'])) {
            $q->where('r.siding_id', '=', (int) $params['siding_id']);
        }

        if (! empty($params['date_from'])) {
            $q->whereDate('r.loading_date', '>=', $params['date_from']);
        }
        if (! empty($params['date_to'])) {
            $q->whereDate('r.loading_date', '<=', $params['date_to']);
        }

        if (! empty($params['rake_number'])) {
            $needle = mb_trim((string) $params['rake_number']);
            if ($needle !== '') {
                $q->where('r.rake_number', 'like', '%'.$needle.'%');
            }
        }

        if (! empty($params['loader_id'])) {
            $q->where('wl.loader_id', '=', (int) $params['loader_id']);
        }

        if (! empty($params['loader_operator_name'])) {
            $op = mb_trim((string) $params['loader_operator_name']);
            if ($op !== '') {
                $q->where('wl.loader_operator_name', '=', $op);
            }
        }

        return $q
            ->groupByRaw($opKey.', l.id, ls.name, l.code, l.loader_name')
            ->selectRaw(
                $opKey.' as operator_bucket,'.
                ' l.id as loader_table_id,'.
                ' l.code as loader_code,'.
                ' l.loader_name,'.
                ' ls.name as siding_name,'.
                ' l.loader_name as loader_name_sort,'.
                ' SUM(CASE WHEN '.$eligible.' THEN 1 ELSE 0 END) as total_wagons_loaded,'.
                ' SUM(CASE WHEN '.$overloadClause.' THEN 1 ELSE 0 END) as overload_count,'.
                ' SUM(CASE WHEN '.$underloadClause.' THEN 1 ELSE 0 END) as underload_count,'.
                ' '.$remarksAgg.' as remarks_agg',
            );
    }

    /**
     * Aggregated loaders (one row per loader id).
     *
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, loader_id?: int, loader_operator_name?: string}  $params
     */
    private function buildLoaderPerformanceGroupedSubquery(array $sidingIds, array $params): QueryBuilder
    {
        $ccEff = 'COALESCE(wl.cc_capacity_mt, w.pcc_weight_mt)';
        $eligible = '(wl.loaded_quantity_mt IS NOT NULL AND '.$ccEff.' IS NOT NULL AND '.$ccEff.' > 0)';
        $overloadClause = "{$eligible} AND wl.loaded_quantity_mt > {$ccEff}";
        $minFill = $this->loaderOperatorPerformanceMinAcceptableLoadFractionSql();
        $underloadClause = "{$eligible} AND wl.loaded_quantity_mt <= {$ccEff} AND wl.loaded_quantity_mt < ({$ccEff} * {$minFill})";

        $q = DB::table('wagon_loading as wl')
            ->join('rakes as r', 'r.id', '=', 'wl.rake_id')
            ->join('wagons as w', 'w.id', '=', 'wl.wagon_id')
            ->join('loaders as l', 'l.id', '=', 'wl.loader_id')
            ->join('sidings as ls', 'ls.id', '=', 'l.siding_id')
            ->whereNull('l.deleted_at')
            ->whereIn('r.siding_id', $sidingIds)
            ->whereNotNull('wl.loader_id');

        if (! empty($params['siding_id'])) {
            $q->where('r.siding_id', '=', (int) $params['siding_id']);
        }

        if (! empty($params['date_from'])) {
            $q->whereDate('r.loading_date', '>=', $params['date_from']);
        }
        if (! empty($params['date_to'])) {
            $q->whereDate('r.loading_date', '<=', $params['date_to']);
        }

        if (! empty($params['loader_id'])) {
            $q->where('wl.loader_id', '=', (int) $params['loader_id']);
        }

        if (! empty($params['loader_operator_name'])) {
            $op = mb_trim((string) $params['loader_operator_name']);
            if ($op !== '') {
                $q->where('wl.loader_operator_name', '=', $op);
            }
        }

        return $q
            ->groupBy('l.id', 'ls.name', 'l.code', 'l.loader_name')
            ->selectRaw(
                'l.id as loader_table_id,'.
                ' l.code as loader_code,'.
                ' l.loader_name,'.
                ' ls.name as siding_name,'.
                ' l.loader_name as loader_name_sort,'.
                ' SUM(CASE WHEN '.$eligible.' THEN 1 ELSE 0 END) as total_wagons_loaded,'.
                ' SUM(CASE WHEN '.$overloadClause.' THEN 1 ELSE 0 END) as overload_count,'.
                ' SUM(CASE WHEN '.$underloadClause.' THEN 1 ELSE 0 END) as underload_count,'.
                ' AVG(CASE WHEN '.$eligible.' THEN wl.loaded_quantity_mt END) as avg_load,'.
                ' AVG(CASE WHEN '.$eligible.' THEN ABS(wl.loaded_quantity_mt - ('.$ccEff.')) END) as avg_deviation_mt',
            );
    }

    private function loaderOperatorPerformanceMinAcceptableLoadFractionSql(): string
    {
        return number_format(self::LOADER_OPERATOR_PERFORMANCE_MIN_ACCEPTABLE_LOAD_FRACTION_OF_CC, 4, '.', '');
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, loader?: string}  $params
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
            $query->whereDate('r.loading_date', '>=', $params['date_from']);
        }
        if (! empty($params['date_to'])) {
            $query->whereDate('r.loading_date', '<=', $params['date_to']);
        }
        if (! empty($params['rake_number'])) {
            $query->where('r.rake_number', 'like', '%'.$params['rake_number'].'%');
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
    private function rrSummary(array $sidingIds, array $params): array
    {
        $query = RrDocument::query()
            ->with(['rake.siding:id,name', 'rake.rakeCharges:id,rake_id,diverrt_destination_id,charge_type,amount,is_actual_charges'])
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));
        if (! empty($params['date_from'])) {
            $query->whereHas('rake', fn ($q) => $q->whereDate('loading_date', '>=', $params['date_from']));
        }
        if (! empty($params['date_to'])) {
            $query->whereHas('rake', fn ($q) => $q->whereDate('loading_date', '<=', $params['date_to']));
        }
        if (! empty($params['siding_id'])) {
            $query->whereHas('rake', fn ($q) => $q->where('siding_id', $params['siding_id']));
        }
        if (! empty($params['rake_number'])) {
            $query->whereHas('rake', fn ($q) => $q->where('rake_number', 'like', '%'.$params['rake_number'].'%'));
        }
        $query->latest('rr_received_date');
        $this->applyLegacyLimitOrGridPagination($query, $params);
        $rows = $query->get();

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
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, power_plant_id?: int}  $params
     */
    private function railDispatchDprCount(array $sidingIds, array $params): int
    {
        return (int) $this->buildRailDispatchDprDocumentsQuery($sidingIds, $params)->count();
    }

    /**
     * RR documents visible in Rail Dispatch DPR (filtering on rake.loading_date and optional power plant code).
     *
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, power_plant_id?: int}  $params
     * @return EloquentBuilder<RrDocument>
     */
    private function buildRailDispatchDprDocumentsQuery(array $sidingIds, array $params): EloquentBuilder
    {
        $query = RrDocument::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds));

        if (! empty($params['date_from'])) {
            $query->whereHas('rake', fn ($q) => $q->whereDate('loading_date', '>=', $params['date_from']));
        }
        if (! empty($params['date_to'])) {
            $query->whereHas('rake', fn ($q) => $q->whereDate('loading_date', '<=', $params['date_to']));
        }
        if (! empty($params['siding_id'])) {
            $query->whereHas('rake', fn ($q) => $q->where('siding_id', $params['siding_id']));
        }
        if (! empty($params['rake_number'])) {
            $query->whereHas('rake', fn ($q) => $q->where('rake_number', 'like', '%'.$params['rake_number'].'%'));
        }
        if (! empty($params['power_plant_id'])) {
            $plantId = (int) $params['power_plant_id'];
            $query->whereExists(function ($sub) use ($plantId): void {
                $sub->selectRaw('1')
                    ->from('power_plants as pp')
                    ->whereColumn('pp.code', 'rr_documents.to_station_code')
                    ->where('pp.id', '=', $plantId);
            });
        }

        return $query;
    }

    /**
     * Rail dispatch DPR: one row per RrDocument leg; diversion rows get remarks; highlight all legs when rake is diverted or multi-RR.
     *
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, power_plant_id?: int}  $params
     * @return array<int, array<string, mixed>>
     */
    private function railDispatchDpr(array $sidingIds, array $params): array
    {
        $query = $this->buildRailDispatchDprDocumentsQuery($sidingIds, $params);
        $query->with([
            'rake' => function ($q): void {
                $q->with(['siding:id,name'])
                    ->withCount(['diverrtDestinations', 'rrDocuments'])
                    ->with(['rakeCharges:id,rake_id,diverrt_destination_id,charge_type,amount,is_actual_charges']);
            },
            'diverrtDestination:id,location,rake_id',
        ])
            ->withCount('wagonSnapshots');
        $query->latest('rr_received_date');
        $this->applyLegacyLimitOrGridPagination($query, $params);
        $rows = $query->get();

        $codes = $rows->pluck('to_station_code')->filter()->unique()->values()->all();
        $plantsByCode = $codes === []
            ? collect()
            : PowerPlant::query()->whereIn('code', $codes)->get()->keyBy('code');

        return $rows->map(function (RrDocument $r) use ($plantsByCode): array {
            $rake = $r->rake;

            $toCode = $r->to_station_code;
            $plant = $toCode ? $plantsByCode->get($toCode) : null;
            $powerPlantLabel = $plant?->name
                ?? ($r->diverrtDestination?->location)
                ?? ($toCode ?: '');

            $chargeScope = $rake?->rakeCharges
                ?->filter(function (RakeCharge $charge) use ($r): bool {
                    return $charge->is_actual_charges
                        && (int) $charge->diverrt_destination_id === (int) $r->diverrt_destination_id;
                });

            $freight = (float) ($chargeScope?->firstWhere('charge_type', 'FREIGHT')?->amount ?? 0.0);
            $penaltyAmount = (float) ($chargeScope?->firstWhere('charge_type', 'PENALTY')?->amount ?? 0.0);
            $gstAmount = (float) ($chargeScope?->firstWhere('charge_type', 'GST')?->amount ?? 0.0);
            $otherChargesAmount = (float) ($chargeScope?->firstWhere('charge_type', 'OTHER_CHARGE')?->amount ?? 0.0);
            $total = $freight + $penaltyAmount + $gstAmount + $otherChargesAmount;

            $rowHighlight = null;
            if ($rake instanceof Rake && (
                $rake->is_diverted
                || (int) ($rake->diverrt_destinations_count ?? 0) > 0
                || (int) ($rake->rr_documents_count ?? 0) > 1
            )) {
                $rowHighlight = 'diversion';
            }

            $remarks = '';
            if ($r->diverrt_destination_id !== null) {
                $remarks = 'Diverted to '.($powerPlantLabel !== '' ? $powerPlantLabel : 'Unknown');
            }

            $fmtDt = fn ($v): ?string => $v instanceof Carbon ? $v->toAtomString() : null;

            return [
                'Loading Date' => $rake?->loading_date?->toDateString(),
                'Dispatch Time' => $fmtDt($rake?->dispatch_time),
                'Loading Start' => $fmtDt($rake?->loading_start_time),
                'Loading End' => $fmtDt($rake?->loading_end_time),
                'Rake No' => $rake?->rake_number,
                'Siding' => $rake?->siding?->name,
                'RR No' => $r->rr_number,
                'RR Date' => $r->rr_received_date?->toDateString(),
                'To Power Plant' => $powerPlantLabel !== '' ? $powerPlantLabel : $toCode,
                'Wagon Count (RR)' => $r->wagon_snapshots_count ?? 0,
                'Charged Weight (MT)' => $r->rr_weight_mt !== null ? (float) $r->rr_weight_mt : null,
                'Freight Amount' => round($freight, 2),
                'Penalty Amount' => round($penaltyAmount, 2),
                'GST Amount' => round($gstAmount, 2),
                'Other Charges Amount' => round($otherChargesAmount, 2),
                'Total Amount' => round($total, 2),
                'Remarks' => $remarks,
                '_row_highlight' => $rowHighlight,
                '_rr_document_id' => $r->id,
            ];
        })->values()->all();
    }

    /**
     * Coal Logestic Core penalty grid: same merge as penalty register with core column names + optional pre/post filter.
     *
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, penalty_stage?: string, limit?: int|null, no_limit?: bool}  $params
     * @return array<int, array<string, mixed>>
     */
    private function penaltyReport(array $sidingIds, array $params): array
    {
        $merged = $this->mergedPenaltyInternalRows($sidingIds, $params);

        $stageFilter = isset($params['penalty_stage']) ? (string) $params['penalty_stage'] : '';
        if ($stageFilter === 'pre_rr') {
            $merged = $merged->filter(fn (array $row): bool => $row['stage_label'] === self::PENALTY_STAGE_PRE)->values();
        } elseif ($stageFilter === 'post_rr') {
            $merged = $merged->filter(fn (array $row): bool => $row['stage_label'] === self::PENALTY_STAGE_POST)->values();
        }

        return $merged->map(fn (array $row): array => $this->mapPenaltyInternalRowToCore($row))->values()->all();
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, loader?: string, limit?: int|null, no_limit?: bool}  $params
     * @return array<int, array<string, mixed>>
     */
    private function penaltyRegister(array $sidingIds, array $params): array
    {
        return $this->mergedPenaltyInternalRows($sidingIds, $params)
            ->map(fn (array $row): array => $this->mapPenaltyInternalRowToLegacy($row))
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
     * @param  EloquentBuilder  $query
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
