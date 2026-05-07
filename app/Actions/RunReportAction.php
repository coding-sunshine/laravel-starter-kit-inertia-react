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
    ];

    private const string PENALTY_STAGE_PRE = 'Pre-RR';

    private const string PENALTY_STAGE_POST = 'Post-RR';

    private const string OVERLOADING_REPORT_PENALTY_IMPACT = 'Overload penalty';

    /**
     * Report keys accepted by POST /reports/generate for the grid UI (operational + core).
     *
     * @return list<string>
     */
    public static function reportGenerateKeys(): array
    {
        return array_values(array_unique([...self::RAKE_MANAGEMENT_REPORT_KEYS, ...self::COAL_LOGESTIC_CORE_REPORT_KEYS]));
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, loader?: string, power_plant_id?: int, penalty_stage?: string}  $params
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
            'penalty_register_rr_snapshot' => $this->penaltyRegisterRrSnapshot($sidingIds, $params),
            'penalty_register_applied' => $this->penaltyRegisterApplied($sidingIds, $params),
            'daily_operations', 'demurrage_analysis', 'financial_impact', 'rake_lifecycle', 'indent_fulfillment' => $this->delegateToGenerateReports($key, $sidingIds, $params),
            default => [],
        };
    }

    /**
     * Paginated rows for the /reports UI (per_page capped at 60 in the controller).
     *
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string, loader?: string, power_plant_id?: int, penalty_stage?: string}  $params
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
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string}  $params
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

        return $q;
    }

    /**
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string}  $params
     */
    private function overloadingReportCount(array $sidingIds, array $params): int
    {
        return (int) $this->buildOverloadingReportBaseQuery($sidingIds, $params)->count('rww.id');
    }

    /**
     * In-motion net used as "Actual Weight (MT)".
     *
     * @param  array<int>  $sidingIds
     * @param  array{siding_id?: int, date_from?: string, date_to?: string, rake_number?: string}  $params
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
