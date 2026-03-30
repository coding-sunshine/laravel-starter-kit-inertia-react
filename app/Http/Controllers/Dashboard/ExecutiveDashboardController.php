<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\DataTables\RakeDataTable;
use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\AppliedPenalty;
use App\Models\DailyVehicleEntry;
use App\Models\HistoricalMine;
use App\Models\Indent;
use App\Models\Loader;
use App\Models\Penalty;
use App\Models\PenaltyPrediction;
use App\Models\PenaltyType;
use App\Models\ProductionEntry;
use App\Models\Rake;
use App\Models\RakeWagonWeighment;
use App\Models\RakeWeighment;
use App\Models\RrPenaltySnapshot;
use App\Models\Siding;
use App\Models\SidingOpeningBalance;
use App\Models\SidingVehicleDispatch;
use App\Models\StockLedger;
use App\Models\VehicleUnload;
use App\Services\CoalTransportReport\CoalTransportReportDataBuilder;
use App\Support\Dashboard\DashboardFilterResolver;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class ExecutiveDashboardController extends Controller
{
    /** Minimum stock required for one rake (MT). */
    private const STOCK_GAUGE_RAKE_REQUIREMENT_MT = 3500;

    /**
     * Operational rakes shown on /rakes (excludes historical imports, RR snapshots, etc.).
     * Aligned with {@see RakeDataTable}.
     *
     * @var list<string>
     */
    private const OPERATIONAL_RAKE_DATA_SOURCES = ['system', 'manual'];

    public function __construct(
        private readonly DashboardFilterResolver $filters,
        private readonly CoalTransportReportDataBuilder $coalTransportReportDataBuilder,
    ) {}

    public function __invoke(Request $request): Response
    {
        $resolved = $this->filters->resolve($request);
        $executiveYesterdayDate = $this->parseExecutiveYesterdayDate($request);

        $allSidings = Siding::query()
            ->whereIn('id', $resolved['allSidingIds'])
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (Siding $s): array => ['id' => $s->id, 'name' => $s->name, 'code' => $s->code]);

        $filterOptions = $this->buildFilterOptions($resolved['filteredSidingIds']);

        // dd($this->buildSidingPerformance($filteredSidingIds, $from, $to, $filterContext));
        return Inertia::render('dashboard', [
            'sidings' => $allSidings,
            'section' => $resolved['section'],
            'filters' => [
                'period' => $resolved['period'],
                'from' => $resolved['from']->toDateString(),
                'to' => $resolved['to']->toDateString(),
                'siding_ids' => $resolved['filteredSidingIds'],
                'power_plant' => $resolved['powerPlant'],
                'rake_number' => $resolved['rakeNumber'],
                'loader_id' => $resolved['loaderId'],
                'shift' => $resolved['shift'],
                'penalty_type' => $resolved['penaltyTypeId'],
                'daily_rake_date' => $resolved['dailyRakeDate']->toDateString(),
                'coal_transport_date' => $resolved['coalTransportDate']->toDateString(),
            ],
            'filterOptions' => $filterOptions,
            'kpis' => $this->buildKpis($resolved['filteredSidingIds'], $resolved['from'], $resolved['to'], $resolved['filterContext']),
            'penaltyTrendDaily' => $this->buildPenaltyTrendDaily($resolved['filteredSidingIds'], $resolved['from'], $resolved['to'], $resolved['filterContext']),
            'penaltyByType' => $this->buildPenaltyByType($resolved['filteredSidingIds'], $resolved['from'], $resolved['to'], $resolved['filterContext']),
            'penaltyBySiding' => $this->buildPenaltyBySiding($resolved['filteredSidingIds'], $resolved['from'], $resolved['to'], $resolved['filterContext']),
            // 'notifications' => $this->buildDashboardNotifications($request),
            // 'notificationsUnreadCount' => $this->buildDashboardUnreadNotificationCount($request),
            'liveRakeStatus' => $this->buildLiveRakeStatus($resolved['filteredSidingIds'], $resolved['filterContext']),
            'dailyRakeDetails' => $this->buildDailyRakeDetails($resolved['filteredSidingIds'], $resolved['dailyRakeDate']),
            'coalTransportReport' => $this->coalTransportReportDataBuilder->buildCoalTransportReport($resolved['filteredSidingIds'], $resolved['coalTransportDate'], $resolved['filterContext']['shift'] ?? null),
            'truckReceiptTrend' => $this->buildTruckReceiptTrend($resolved['filteredSidingIds'], $resolved['filterContext']),
            'shiftWiseVehicleReceipt' => $this->buildShiftWiseVehicleReceipt($resolved['filteredSidingIds'], $resolved['filterContext']['shift'] ?? null),
            // 'stockGauge' => $this->buildStockGauge($resolved['filteredSidingIds'], $resolved['to']), // hidden for now
            'predictedVsActualPenalty' => $this->buildPredictedVsActualPenalty($resolved['filteredSidingIds'], $resolved['from'], $resolved['to'], $resolved['filterContext']),
            'yesterdayPredictedPenalties' => $this->buildYesterdayPredictedPenalties($resolved['allSidingIds']),
            'sidingWiseMonthly' => $this->buildSidingWiseMonthly($resolved['filteredSidingIds'], $resolved['from'], $resolved['to']),
            'sidingRadar' => $this->buildSidingRadar($resolved['filteredSidingIds'], $resolved['from'], $resolved['to']),
            'sidingPerformance' => $this->buildSidingPerformance($resolved['filteredSidingIds'], $resolved['from'], $resolved['to'], $resolved['filterContext']),
            'sidingStocks' => $this->buildSidingStocks($resolved['filteredSidingIds'], $resolved['from'], $resolved['to']),
            'dateWiseDispatch' => $this->buildDateWiseDispatch($resolved['filteredSidingIds'], $resolved['from'], $resolved['to']),
            'rakePerformance' => $this->buildRakePerformance($resolved['filteredSidingIds'], $resolved['from'], $resolved['to'], $resolved['filterContext']),
            'loaderOverloadTrends' => $this->buildLoaderOverloadTrends($resolved['filteredSidingIds'], $resolved['from'], $resolved['to'], $resolved['filterContext']),
            'powerPlantDispatch' => $this->buildPowerPlantDispatch($resolved['filteredSidingIds'], $resolved['from'], $resolved['to'], $resolved['filterContext']),
            // Executive Yesterday tab uses its own date picker and ignores main dashboard filters.
            'executiveYesterday' => $this->buildExecutiveYesterdayData($resolved['allSidingIds'], $executiveYesterdayDate),
        ]);
    }

    /**
     * Executive Yesterday tab data (date-wise, month-wise, FY-wise) for production and dispatch.
     *
     * @param  array<int>  $sidingIds
     * @return array{
     *     date: string,
     *     monthLabel: string,
     *     fyLabel: string,
     *     obProduction: array{dateWise: array{trips: int|null, qty: float|null}, monthWise: array{trips: int|null, qty: float|null}, fyWise: array{trips: int|null, qty: float|null}},
     *     coalProduction: array{dateWise: array{trips: int|null, qty: float|null}, monthWise: array{trips: int|null, qty: float|null}, fyWise: array{trips: int|null, qty: float|null}},
     *     coalDispatch: array{dateWise: array{trips: int|null, qty: float|null}, monthWise: array{trips: int|null, qty: float|null}, fyWise: array{trips: int|null, qty: float|null}},
     *     rakeDispatch: array{dateWise: array{trips: int|null, qty: float|null}, monthWise: array{trips: int|null, qty: float|null}, fyWise: array{trips: int|null, qty: float|null}},
     *     coalDispatchBySiding: array<int, array{sidingId: int, sidingName: string, dateWise: array{qty: float|null}, monthWise: array{qty: float}, fyWise: array{qty: float}}},
     *     rakeDispatchBySiding: array<int, array{sidingId: int, sidingName: string, dateWise: array{trips: int, qty: float}, monthWise: array{trips: int, qty: float}, fyWise: array{trips: int, qty: float}}}
     * }
     */
    public function buildExecutiveYesterdayData(array $sidingIds, CarbonInterface $anchorDate): array
    {
        $anchor = Carbon::parse($anchorDate)->startOfDay();
        $date = $anchor->toDateString();
        $monthStart = $anchor->copy()->startOfMonth()->toDateString();
        $monthEnd = $anchor->copy()->endOfMonth()->toDateString();
        $fyStart = $anchor->month >= 4
            ? $anchor->copy()->startOfDay()->setDate($anchor->year, 4, 1)
            : $anchor->copy()->startOfDay()->setDate($anchor->year - 1, 4, 1);
        $fyEnd = $fyStart->copy()->addYear()->subDay();

        // Business rule: future dates should show no data.
        if ($anchor->gt(today())) {
            $sidingMap = Siding::query()
                ->whereIn('id', $sidingIds)
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all();

            return [
                'date' => $date,
                'monthLabel' => $anchor->format('F Y'),
                'fyLabel' => sprintf('FY %d-%02d', $fyStart->year, (int) $fyStart->copy()->addYear()->format('y')),
                'obProduction' => [
                    'dateWise' => ['trips' => 0, 'qty' => 0.0],
                    'monthWise' => ['trips' => 0, 'qty' => 0.0],
                    'fyWise' => ['trips' => 0, 'qty' => 0.0],
                ],
                'coalProduction' => [
                    'dateWise' => ['trips' => 0, 'qty' => 0.0],
                    'monthWise' => ['trips' => 0, 'qty' => 0.0],
                    'fyWise' => ['trips' => 0, 'qty' => 0.0],
                ],
                'coalDispatch' => [
                    'dateWise' => ['trips' => null, 'qty' => null],
                    'monthWise' => ['trips' => 0, 'qty' => 0.0],
                    'fyWise' => ['trips' => 0, 'qty' => 0.0],
                ],
                'rakeDispatch' => [
                    'dateWise' => ['trips' => 0, 'qty' => 0.0],
                    'monthWise' => ['trips' => 0, 'qty' => 0.0],
                    'fyWise' => ['trips' => 0, 'qty' => 0.0],
                ],
                'coalDispatchBySiding' => collect($sidingMap)->map(fn (string $name, int $id): array => [
                    'sidingId' => $id,
                    'sidingName' => $name,
                    'dateWise' => ['qty' => null],
                    'monthWise' => ['qty' => 0.0],
                    'fyWise' => ['qty' => 0.0],
                ])->values()->all(),
                'rakeDispatchBySiding' => collect($sidingMap)->map(fn (string $name, int $id): array => [
                    'sidingId' => $id,
                    'sidingName' => $name,
                    'dateWise' => ['trips' => 0, 'qty' => 0.0],
                    'monthWise' => ['trips' => 0, 'qty' => 0.0],
                    'fyWise' => ['trips' => 0, 'qty' => 0.0],
                ])->values()->all(),
            ];
        }

        $sidingMap = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();

        $monthRow = HistoricalMine::query()
            ->whereBetween('month', [$monthStart, $monthEnd])
            ->selectRaw('coalesce(sum(trips_dispatched), 0) as dispatch_trips, coalesce(sum(dispatched_qty), 0) as dispatch_qty, coalesce(sum(ob_production_qty), 0) as ob_qty, coalesce(sum(coal_production_qty), 0) as coal_qty')
            ->first();

        $fyHistoricalRow = HistoricalMine::query()
            ->whereBetween('month', [$fyStart->toDateString(), $fyEnd->toDateString()])
            ->selectRaw('coalesce(sum(trips_dispatched), 0) as dispatch_trips, coalesce(sum(dispatched_qty), 0) as dispatch_qty, coalesce(sum(ob_production_qty), 0) as ob_qty, coalesce(sum(coal_production_qty), 0) as coal_qty')
            ->first();

        $productionEntryScope = ProductionEntry::query();
        if ($sidingIds !== []) {
            $productionEntryScope->where(function ($query) use ($sidingIds): void {
                $query->whereIn('siding_id', $sidingIds)
                    // Historical production imports can be mine-level (no siding_id),
                    // and should still contribute to Yesterday month/FY totals.
                    ->orWhereNull('siding_id');
            });
        }

        $obDate = (clone $productionEntryScope)
            ->where('type', ProductionEntry::TYPE_OB)
            ->whereDate('date', $date)
            ->selectRaw('count(*) as trips, coalesce(sum(qty), 0) as qty')
            ->first();

        $coalDate = (clone $productionEntryScope)
            ->where('type', ProductionEntry::TYPE_COAL)
            ->whereDate('date', $date)
            ->selectRaw('count(*) as trips, coalesce(sum(qty), 0) as qty')
            ->first();

        $obMonth = (clone $productionEntryScope)
            ->where('type', ProductionEntry::TYPE_OB)
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->selectRaw('count(*) as trips, coalesce(sum(qty), 0) as qty')
            ->first();

        $coalMonth = (clone $productionEntryScope)
            ->where('type', ProductionEntry::TYPE_COAL)
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->selectRaw('count(*) as trips, coalesce(sum(qty), 0) as qty')
            ->first();

        $obFy = (clone $productionEntryScope)
            ->where('type', ProductionEntry::TYPE_OB)
            ->whereBetween('date', [$fyStart->toDateString(), $fyEnd->toDateString()])
            ->selectRaw('count(*) as trips, coalesce(sum(qty), 0) as qty')
            ->first();

        $coalFy = (clone $productionEntryScope)
            ->where('type', ProductionEntry::TYPE_COAL)
            ->whereBetween('date', [$fyStart->toDateString(), $fyEnd->toDateString()])
            ->selectRaw('count(*) as trips, coalesce(sum(qty), 0) as qty')
            ->first();

        $rakeScope = Rake::query()
            ->whereNotNull('loading_date');
        if ($sidingIds !== []) {
            $rakeScope->whereIn('siding_id', $sidingIds);
        }

        $rakeDate = (clone $rakeScope)
            ->whereDate('loading_date', $date)
            ->selectRaw('count(*) as trips, coalesce(sum(loaded_weight_mt), 0) as qty')
            ->first();

        $rakeMonth = (clone $rakeScope)
            ->whereBetween('loading_date', [$monthStart, $monthEnd])
            ->selectRaw('count(*) as trips, coalesce(sum(loaded_weight_mt), 0) as qty')
            ->first();

        $rakeFy = (clone $rakeScope)
            ->whereBetween('loading_date', [$fyStart->toDateString(), $fyEnd->toDateString()])
            ->selectRaw('count(*) as trips, coalesce(sum(loaded_weight_mt), 0) as qty')
            ->first();

        $monthDispatchBySiding = HistoricalMine::query()
            ->whereBetween('month', [$monthStart, $monthEnd])
            ->whereIn('siding_id', array_keys($sidingMap))
            ->selectRaw('siding_id, coalesce(sum(dispatched_qty), 0) as qty')
            ->groupBy('siding_id')
            ->pluck('qty', 'siding_id')
            ->all();

        $fyDispatchBySiding = HistoricalMine::query()
            ->whereBetween('month', [$fyStart->toDateString(), $fyEnd->toDateString()])
            ->whereIn('siding_id', array_keys($sidingMap))
            ->selectRaw('siding_id, coalesce(sum(dispatched_qty), 0) as qty')
            ->groupBy('siding_id')
            ->pluck('qty', 'siding_id')
            ->all();

        $rakeDateBySiding = (clone $rakeScope)
            ->whereDate('loading_date', $date)
            ->selectRaw('siding_id, count(*) as trips, coalesce(sum(loaded_weight_mt), 0) as qty')
            ->groupBy('siding_id')
            ->get()
            ->keyBy('siding_id');

        $rakeMonthBySiding = (clone $rakeScope)
            ->whereBetween('loading_date', [$monthStart, $monthEnd])
            ->selectRaw('siding_id, count(*) as trips, coalesce(sum(loaded_weight_mt), 0) as qty')
            ->groupBy('siding_id')
            ->get()
            ->keyBy('siding_id');

        $rakeFyBySiding = (clone $rakeScope)
            ->whereBetween('loading_date', [$fyStart->toDateString(), $fyEnd->toDateString()])
            ->selectRaw('siding_id, count(*) as trips, coalesce(sum(loaded_weight_mt), 0) as qty')
            ->groupBy('siding_id')
            ->get()
            ->keyBy('siding_id');

        $monthDispatchTrips = (int) ($monthRow?->dispatch_trips ?? 0);
        $monthDispatchQty = (float) ($monthRow?->dispatch_qty ?? 0);
        $monthObQty = (float) ($monthRow?->ob_qty ?? 0);
        $monthCoalQty = (float) ($monthRow?->coal_qty ?? 0);

        $fyDispatchTrips = (int) ($fyHistoricalRow?->dispatch_trips ?? 0);
        $fyDispatchQty = (float) ($fyHistoricalRow?->dispatch_qty ?? 0);
        $fyHistoricalObQty = (float) ($fyHistoricalRow?->ob_qty ?? 0);
        $fyHistoricalCoalQty = (float) ($fyHistoricalRow?->coal_qty ?? 0);

        $fyObQty = $fyHistoricalObQty > 0 ? $fyHistoricalObQty : (float) ($obFy?->qty ?? 0);
        $fyCoalQty = $fyHistoricalCoalQty > 0 ? $fyHistoricalCoalQty : (float) ($coalFy?->qty ?? 0);

        return [
            'date' => $date,
            'monthLabel' => $anchor->format('F Y'),
            'fyLabel' => sprintf('FY %d-%02d', $fyStart->year, (int) $fyStart->copy()->addYear()->format('y')),
            'obProduction' => [
                'dateWise' => [
                    'trips' => (int) ($obDate?->trips ?? 0),
                    'qty' => round((float) ($obDate?->qty ?? 0), 2),
                ],
                'monthWise' => [
                    'trips' => (int) ($obMonth?->trips ?? 0),
                    'qty' => round($monthObQty > 0 ? $monthObQty : (float) ($obMonth?->qty ?? 0), 2),
                ],
                'fyWise' => [
                    'trips' => (int) ($obFy?->trips ?? 0),
                    'qty' => round($fyObQty, 2),
                ],
            ],
            'coalProduction' => [
                'dateWise' => [
                    'trips' => (int) ($coalDate?->trips ?? 0),
                    'qty' => round((float) ($coalDate?->qty ?? 0), 2),
                ],
                'monthWise' => [
                    'trips' => (int) ($coalMonth?->trips ?? 0),
                    'qty' => round($monthCoalQty > 0 ? $monthCoalQty : (float) ($coalMonth?->qty ?? 0), 2),
                ],
                'fyWise' => [
                    'trips' => (int) ($coalFy?->trips ?? 0),
                    'qty' => round($fyCoalQty, 2),
                ],
            ],
            'coalDispatch' => [
                'dateWise' => [
                    'trips' => null,
                    'qty' => null,
                ],
                'monthWise' => [
                    'trips' => $monthDispatchTrips,
                    'qty' => round($monthDispatchQty, 2),
                ],
                'fyWise' => [
                    'trips' => $fyDispatchTrips,
                    'qty' => round($fyDispatchQty, 2),
                ],
            ],
            'rakeDispatch' => [
                'dateWise' => [
                    'trips' => (int) ($rakeDate?->trips ?? 0),
                    'qty' => round((float) ($rakeDate?->qty ?? 0), 2),
                ],
                'monthWise' => [
                    'trips' => (int) ($rakeMonth?->trips ?? 0),
                    'qty' => round((float) ($rakeMonth?->qty ?? 0), 2),
                ],
                'fyWise' => [
                    'trips' => (int) ($rakeFy?->trips ?? 0),
                    'qty' => round((float) ($rakeFy?->qty ?? 0), 2),
                ],
            ],
            'coalDispatchBySiding' => collect($sidingMap)->map(function (string $name, int $id) use ($monthDispatchBySiding, $fyDispatchBySiding): array {
                $monthQty = (float) ($monthDispatchBySiding[$id] ?? 0);
                $fyQty = (float) ($fyDispatchBySiding[$id] ?? 0);

                return [
                    'sidingId' => $id,
                    'sidingName' => $name,
                    'dateWise' => ['qty' => null],
                    'monthWise' => ['qty' => round($monthQty, 2)],
                    'fyWise' => ['qty' => round($fyQty, 2)],
                ];
            })->values()->all(),
            'rakeDispatchBySiding' => collect($sidingMap)->map(function (string $name, int $id) use ($rakeDateBySiding, $rakeMonthBySiding, $rakeFyBySiding): array {
                $dateRow = $rakeDateBySiding->get($id);
                $monthRow = $rakeMonthBySiding->get($id);
                $fyRow = $rakeFyBySiding->get($id);

                return [
                    'sidingId' => $id,
                    'sidingName' => $name,
                    'dateWise' => [
                        'trips' => (int) ($dateRow?->trips ?? 0),
                        'qty' => round((float) ($dateRow?->qty ?? 0), 2),
                    ],
                    'monthWise' => [
                        'trips' => (int) ($monthRow?->trips ?? 0),
                        'qty' => round((float) ($monthRow?->qty ?? 0), 2),
                    ],
                    'fyWise' => [
                        'trips' => (int) ($fyRow?->trips ?? 0),
                        'qty' => round((float) ($fyRow?->qty ?? 0), 2),
                    ],
                ];
            })->values()->all(),
        ];
    }

    /**
     * Filter options for Power Plant, Loader, Shift (PDF filters).
     *
     * @param  array<int>  $sidingIds
     * @return array{powerPlants: array<int, array{value: string, label: string}>, loaders: array<int, array{id: int, name: string, siding_name: string}>, shifts: array<int, array{value: string, label: string}>}
     */
    public function buildFilterOptions(array $sidingIds): array
    {
        $powerPlants = [];
        if ($sidingIds !== []) {
            $stations = RakeWeighment::query()
                ->join('rakes', 'rake_weighments.rake_id', '=', 'rakes.id')
                ->whereIn('rakes.siding_id', $sidingIds)
                ->whereNotNull('rake_weighments.to_station')
                ->where('rake_weighments.to_station', '!=', '')
                ->distinct()
                ->pluck('rake_weighments.to_station')
                ->sort()
                ->values();
            foreach ($stations as $s) {
                $powerPlants[] = ['value' => $s, 'label' => $s];
            }
        }

        $loaders = Loader::query()
            ->whereIn('siding_id', $sidingIds)
            ->with('siding:id,name')
            ->orderBy('loader_name')
            ->get(['id', 'loader_name', 'siding_id'])
            ->map(fn (Loader $l): array => [
                'id' => $l->id,
                'name' => $l->loader_name,
                'siding_name' => $l->siding?->name ?? '',
            ])
            ->values()
            ->all();

        $shifts = [
            ['value' => '1', 'label' => 'Shift 1'],
            ['value' => '2', 'label' => 'Shift 2'],
            ['value' => '3', 'label' => 'Shift 3'],
        ];

        $penaltyTypes = PenaltyType::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn (PenaltyType $pt): array => [
                'value' => (string) $pt->id,
                'label' => $pt->code.' — '.$pt->name,
            ])
            ->values()
            ->all();

        return [
            'powerPlants' => $powerPlants,
            'loaders' => $loaders,
            'shifts' => $shifts,
            'penaltyTypes' => $penaltyTypes,
        ];
    }

    /**
     * Top KPI tiles for Executive Overview (PDF).
     *
     * KPIs for the selected date range ($from/$to). Rake-based metrics use loading_date (business date).
     *
     * @param  array<int>  $sidingIds
     * @param  array{period: string, power_plant: string|null, rake_number: string|null, loader_id: int|null, shift: string|null}  $filterContext
     * @return array{rakesDispatchedToday: int, coalDispatchedToday: float, totalPenaltyThisMonth: float, predictedPenaltyRisk: float, avgLoadingTimeMinutes: float|null, trucksReceivedToday: int}
     */
    public function buildKpis(array $sidingIds, CarbonInterface $from, CarbonInterface $to, array $filterContext = []): array
    {
        $from = Carbon::parse($from)->startOfDay();
        $to = Carbon::parse($to)->endOfDay();

        if ($sidingIds === []) {
            return [
                'rakesDispatchedToday' => 0,
                'coalDispatchedToday' => 0.0,
                'totalPenaltyThisMonth' => 0.0,
                'predictedPenaltyRisk' => 0.0,
                'avgLoadingTimeMinutes' => null,
                'trucksReceivedToday' => 0,
            ];
        }

        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();
        // Rake-based KPIs use loading_date (business date) to match Siding performance and Power plant dispatch.
        $rakeIdsInRange = Rake::query()
            ->whereIn('siding_id', $sidingIds)
            ->whereNotNull('loading_date')
            ->whereRaw($this->dateOnlyBetweenSql('loading_date', true), [$fromDate, $toDate]);
        if (! empty($filterContext['rake_number'])) {
            $rakeIdsInRange->where('rake_number', 'like', '%'.$filterContext['rake_number'].'%');
        }
        if (! empty($filterContext['power_plant'])) {
            $rakeIdsInRange->whereIn('id', RakeWeighment::query()->where('to_station', $filterContext['power_plant'])->select('rake_id'));
        }
        $rakeIdsInRange = $rakeIdsInRange->pluck('id');

        $rakesDispatchedToday = $rakeIdsInRange->count();

        $coalDispatchedToday = 0.0;
        if ($rakeIdsInRange->isNotEmpty()) {
            $coalDispatchedToday = (float) RakeWeighment::query()
                ->whereIn('rake_id', $rakeIdsInRange->all())
                ->sum('total_net_weight_mt');
            if ($coalDispatchedToday === 0.0) {
                $coalDispatchedToday = (float) Rake::query()
                    ->whereIn('id', $rakeIdsInRange->all())
                    ->sum('loaded_weight_mt');
            }
        }

        $totalPenaltyThisMonth = (float) RrPenaltySnapshot::query()
            ->join('rakes', 'rr_penalty_snapshots.rake_id', '=', 'rakes.id')
            ->whereIn('rakes.siding_id', $sidingIds)
            ->whereNotNull('rakes.loading_date')
            ->whereRaw($this->dateOnlyBetweenSql('rakes.loading_date', true), [$fromDate, $toDate])
            ->sum(DB::raw('(rr_penalty_snapshots.amount + (rr_penalty_snapshots.amount * 0.05))'));
        $predictedPenaltyRisk = (float) AppliedPenalty::query()
            ->join('rakes', 'applied_penalties.rake_id', '=', 'rakes.id')
            ->whereIn('rakes.siding_id', $sidingIds)
            ->whereNotNull('rakes.loading_date')
            ->whereRaw($this->dateOnlyBetweenSql('rakes.loading_date', true), [$fromDate, $toDate])
            ->sum(DB::raw('(applied_penalties.amount + (applied_penalties.amount * 0.05))'));

        // $predictedPenaltyRisk = (float) PenaltyPrediction::query()
        //     ->whereIn('siding_id', $sidingIds)
        //     ->whereRaw($this->dateOnlyBetweenSql('prediction_date', true), [$fromDate, $toDate])
        //     ->sum('predicted_amount_max');

        $loadingMinutes = Rake::query()
            ->whereIn('siding_id', $sidingIds)
            ->whereNotNull('loading_start_time')
            ->whereNotNull('loading_end_time')
            ->whereNotNull('loading_date')
            ->whereRaw($this->dateOnlyBetweenSql('loading_date', true), [$fromDate, $toDate])
            ->get()
            ->map(fn (Rake $r): int => (int) $r->loading_start_time->diffInMinutes($r->loading_end_time));

        $avgLoadingTimeMinutes = $loadingMinutes->isEmpty() ? null : round($loadingMinutes->avg(), 0);

        $trucksQuery = DailyVehicleEntry::query()
            ->whereIn('siding_id', $sidingIds)
            ->whereRaw($this->dateOnlyBetweenSql('COALESCE(created_at)'), [$fromDate, $toDate]);
        if (! empty($filterContext['shift'])) {
            $trucksQuery->where('shift', $filterContext['shift']);
        }
        $trucksReceivedToday = $trucksQuery->count();

        return [
            'rakesDispatchedToday' => $rakesDispatchedToday,
            'coalDispatchedToday' => round($coalDispatchedToday, 2),
            'totalPenaltyThisMonth' => $totalPenaltyThisMonth,
            'predictedPenaltyRisk' => $predictedPenaltyRisk,
            'avgLoadingTimeMinutes' => $avgLoadingTimeMinutes,
            'trucksReceivedToday' => $trucksReceivedToday,
        ];
    }

    /**
     * @param  array<int>  $sidingIds
     * @return array<string, mixed>
     */
    public function buildSummary(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return [
                'rakesByState' => [],
                'totalRakes' => 0,
                'penaltiesThisMonth' => 0,
                'indentsPending' => 0,
                'indentsAcknowledged' => 0,
                'vehiclesReceivedToday' => 0,
            ];
        }

        $rakesByState = Rake::query()
            ->whereIn('siding_id', $sidingIds)
            ->selectRaw('state, count(*) as count')
            ->groupBy('state')
            ->pluck('count', 'state')
            ->all();

        $totalRakes = (int) array_sum($rakesByState);

        $penaltiesThisMonth = AppliedPenalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $indentsPending = Indent::query()
            ->whereIn('siding_id', $sidingIds)
            ->whereIn('state', ['pending', 'submitted'])
            ->count();

        $indentsAcknowledged = Indent::query()
            ->whereIn('siding_id', $sidingIds)
            ->whereNotIn('state', ['pending', 'submitted'])
            ->count();

        $vehiclesReceivedToday = VehicleUnload::query()
            ->whereIn('siding_id', $sidingIds)
            ->whereRaw('DATE(COALESCE(unload_end_time, created_at)) = ?', [today()->toDateString()])
            ->count();

        return [
            'rakesByState' => $rakesByState,
            'totalRakes' => $totalRakes,
            'penaltiesThisMonth' => (float) $penaltiesThisMonth,
            'indentsPending' => $indentsPending,
            'indentsAcknowledged' => $indentsAcknowledged,
            'vehiclesReceivedToday' => $vehiclesReceivedToday,
        ];
    }

    /**
     * Penalty trend: date vs total amount. Uses rr_penalty_snapshots.created_at for
     * date-wise filter and grouping. Respects the selected date range (capped to 30 days).
     *
     * @param  array<int>  $sidingIds
     * @param  array{period: string, power_plant: string|null, rake_number: string|null, loader_id: int|null, shift: string|null}  $filterContext
     * @return array<int, array{date: string, total: float, label: string}>
     */
    public function buildPenaltyTrendDaily(array $sidingIds, CarbonInterface $from, CarbonInterface $to, array $filterContext = []): array
    {

        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->endOfDay();
        $diffDays = $start->diffInDays($end) + 1;

        $rakeIds = null;
        if (! empty($filterContext['rake_number']) || ! empty($filterContext['power_plant'])) {
            $rakeIds = $this->getFilteredRakeIds($sidingIds, $filterContext);
            if ($rakeIds === []) {
                return [];
            }
        }

        $tz = config('app.timezone', 'UTC');
        $driver = DB::getDriverName();

        // Long range: aggregate by month (one point per month) so chart spans full range with year in label.
        if ($diffDays > 90) {
            return $this->buildPenaltyTrendMonthly($sidingIds, $start, $end, $rakeIds, $driver, $tz);
        }

        // Short range: daily points, cap at 90 days. Label includes year (e.g. "10 Mar 2024").
        $maxDays = 90;
        if ($diffDays > $maxDays) {
            $end = $start->copy()->addDays($maxDays - 1)->endOfDay();
        }

        $days = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $days[] = [
                'date' => $cursor->toDateString(),
                'label' => $cursor->format('d M Y'),
                'total' => 0.0,
            ];
            $cursor->addDay();
        }

        if ($sidingIds === [] || $days === []) {
            return $days;
        }

        if ($driver === 'pgsql') {
            $dateSql = '(rakes.loading_date)::date';
        } else {
            $dateSql = 'DATE(rakes.loading_date)';
        }

        $startDate = $start->toDateString();
        $endDate = $end->toDateString();
        $penaltyQuery = RrPenaltySnapshot::query()
            ->join('rakes', 'rr_penalty_snapshots.rake_id', '=', 'rakes.id')
            ->whereNotNull('rakes.loading_date')
            ->whereRaw($this->dateOnlyBetweenSql('rakes.loading_date', true), [$startDate, $endDate]);
        if ($rakeIds !== null) {
            $penaltyQuery->whereIn('rr_penalty_snapshots.rake_id', $rakeIds);
        } else {
            $penaltyQuery->whereIn('rakes.siding_id', $sidingIds);
        }

        $rows = $penaltyQuery
            ->selectRaw("{$dateSql} as d, sum(rr_penalty_snapshots.amount * 1.05) as total")
            ->groupBy(DB::raw($dateSql))
            ->get();

        $byDate = [];
        foreach ($rows as $row) {
            $dateStr = $row->d instanceof DateTimeInterface
                ? Carbon::parse($row->d)->format('Y-m-d')
                : (string) $row->d;
            if (mb_strlen($dateStr) > 10) {
                $dateStr = Carbon::parse($dateStr)->format('Y-m-d');
            }
            $byDate[$dateStr] = (float) $row->total;
        }

        foreach ($days as $i => $day) {
            if (isset($byDate[$day['date']])) {
                $days[$i]['total'] = $byDate[$day['date']];
            }
        }

        return $days;
    }

    /**
     * Penalty trend by month for long ranges. One point per month, label e.g. "Mar 2024".
     *
     * @param  array<int>  $sidingIds
     * @param  array<int>|null  $rakeIds
     * @return array<int, array{date: string, label: string, total: float}>
     */
    public function buildPenaltyTrendMonthly(array $sidingIds, Carbon $start, Carbon $end, ?array $rakeIds, string $driver, string $tz): array
    {
        $months = [];
        $cursor = $start->copy()->startOfMonth();
        $endMonth = $end->copy()->startOfMonth();
        while ($cursor->lte($endMonth)) {
            $months[] = [
                'date' => $cursor->format('Y-m'),
                'label' => $cursor->format('M Y'),
                'total' => 0.0,
            ];
            $cursor->addMonth();
        }

        if ($sidingIds === [] || $months === []) {
            return $months;
        }

        if ($driver === 'pgsql') {
            $monthSql = "date_trunc('month', rakes.loading_date)::date";
        } else {
            $monthSql = "DATE_FORMAT(rakes.loading_date, '%Y-%m-01')";
        }

        $startDate = $start->toDateString();
        $endDate = $end->toDateString();
        $penaltyQuery = RrPenaltySnapshot::query()
            ->join('rakes', 'rr_penalty_snapshots.rake_id', '=', 'rakes.id')
            ->whereNotNull('rakes.loading_date')
            ->whereRaw($this->dateOnlyBetweenSql('rakes.loading_date', true), [$startDate, $endDate]);
        if ($rakeIds !== null) {
            $penaltyQuery->whereIn('rr_penalty_snapshots.rake_id', $rakeIds);
        } else {
            $penaltyQuery->whereIn('rakes.siding_id', $sidingIds);
        }

        $rows = $penaltyQuery
            ->selectRaw("{$monthSql} as m, sum(rr_penalty_snapshots.amount + rr_penalty_snapshots.amount * 0.05) as total")
            ->groupBy(DB::raw($monthSql))
            ->get();

        $byMonth = [];
        foreach ($rows as $row) {
            $key = $row->m instanceof DateTimeInterface
                ? Carbon::parse($row->m)->format('Y-m')
                : mb_substr((string) $row->m, 0, 7);
            $byMonth[$key] = (float) $row->total;
        }

        foreach ($months as $i => $month) {
            if (isset($byMonth[$month['date']])) {
                $months[$i]['total'] = $byMonth[$month['date']];
            }
        }

        return $months;
    }

    /**
     * Yesterday's predicted penalties (applied_penalties) grouped by siding and rake.
     * Limited to recent rakes (like Live rake status). Only rakes from user's accessible sidings.
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array{siding_id: int, siding_name: string, rakes: array<int, array{rake_id: int, rake_number: string, total_penalty: float, penalties: array<int, array{type_code: string, type_name: string, amount: float}>}>}>
     */
    public function buildYesterdayPredictedPenalties(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $businessDate = today()->toDateString();

        $rakes = Rake::query()
            ->with('siding:id,name')
            ->whereIn('siding_id', $sidingIds)
            ->whereNotNull('loading_date')
            ->where('loading_date', $businessDate)
            ->orderByDesc('loading_start_time')
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'rake_number', 'siding_id']);

        if ($rakes->isEmpty()) {
            return [];
        }

        $rakeIds = $rakes->pluck('id')->all();

        $penalties = AppliedPenalty::query()
            ->with('penaltyType:id,code,name')
            ->whereIn('rake_id', $rakeIds)
            ->get();

        if ($penalties->isEmpty()) {
            return [];
        }

        $penaltiesByRake = $penalties->groupBy('rake_id');

        $resultBySiding = [];

        foreach ($rakes as $rake) {
            $rakePenalties = $penaltiesByRake->get($rake->id);
            if ($rakePenalties === null || $rakePenalties->isEmpty()) {
                continue;
            }

            $penaltyRows = [];
            $totalPenalty = 0.0;

            foreach ($rakePenalties as $p) {
                $amount = (float) $p->amount;
                $totalPenalty += $amount;

                $penaltyRows[] = [
                    'type_code' => (string) ($p->penaltyType?->code ?? '—'),
                    'type_name' => (string) ($p->penaltyType?->name ?? ''),
                    'amount' => $amount,
                ];
            }

            if ($penaltyRows === []) {
                continue;
            }

            $sidingId = $rake->siding_id;
            if (! isset($resultBySiding[$sidingId])) {
                $resultBySiding[$sidingId] = [
                    'siding_id' => $sidingId,
                    'siding_name' => $rake->siding?->name ?? "Siding {$sidingId}",
                    'rakes' => [],
                ];
            }

            $resultBySiding[$sidingId]['rakes'][] = [
                'rake_id' => $rake->id,
                'rake_number' => (string) $rake->rake_number,
                'total_penalty' => round($totalPenalty, 2),
                'penalties' => $penaltyRows,
            ];
        }

        return array_values($resultBySiding);
    }

    /**
     * Siding stock from stock_ledger only (no date filter).
     * Receipts (daily_vehicle_entry_id) increase stock; dispatches (rake_id) decrease it.
     * Stock = latest ledger row's closing_balance_mt per siding as of the current request time (live).
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array{siding_id: int, opening_balance_mt: float, closing_balance_mt: float, total_rakes: int, received_mt: float, dispatched_mt: float}>
     */
    public function buildSidingStocks(array $sidingIds, CarbonInterface $from, CarbonInterface $to): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();

        // Get latest ledger per siding (correct ordering using id)
        $latestLedgerIds = StockLedger::query()
            ->whereIn('siding_id', $sidingIds)
            ->selectRaw('MAX(id) as id')
            ->groupBy('siding_id')
            ->pluck('id')
            ->all();

        $latestLedgers = StockLedger::query()
            ->whereIn('id', $latestLedgerIds)
            ->get()
            ->keyBy('siding_id');

        // Total received (positive values)
        $receivedBySiding = StockLedger::query()
            ->whereIn('siding_id', $sidingIds)
            ->where('transaction_type', 'receipt')
            ->selectRaw('siding_id, COALESCE(SUM(quantity_mt), 0) as total')
            ->groupBy('siding_id')
            ->pluck('total', 'siding_id');

        // Total dispatched (stored as negative, so sum will be negative)
        $dispatchedBySiding = StockLedger::query()
            ->whereIn('siding_id', $sidingIds)
            ->where('transaction_type', 'dispatch')
            ->selectRaw('siding_id, COALESCE(SUM(quantity_mt), 0) as total')
            ->groupBy('siding_id')
            ->pluck('total', 'siding_id');

        // Rake count in date range
        $rakeCountsBySiding = Rake::query()
            ->whereIn('siding_id', $sidingIds)
            ->whereNotNull('created_at')
            ->whereRaw($this->dateOnlyBetweenSql('created_at'), [$fromDate, $toDate])
            ->selectRaw('siding_id, COUNT(*) as cnt')
            ->groupBy('siding_id')
            ->pluck('cnt', 'siding_id');

        $result = [];

        foreach ($sidingIds as $sid) {
            $latest = $latestLedgers->get($sid);

            if ($latest) {
                $opening = (float) $latest->opening_balance_mt;
                $closing = (float) $latest->closing_balance_mt;
            } else {
                $opening = SidingOpeningBalance::getOpeningBalanceForSiding($sid);
                $closing = $opening;
            }

            $result[$sid] = [
                'siding_id' => $sid,
                'opening_balance_mt' => $opening,
                'closing_balance_mt' => $closing,
                'total_rakes' => (int) ($rakeCountsBySiding[$sid] ?? 0),
                'received_mt' => (float) ($receivedBySiding[$sid] ?? 0),
                'dispatched_mt' => abs((float) ($dispatchedBySiding[$sid] ?? 0)), // important fix
            ];
        }

        return $result;
    }

    /**
     * @param  array<int>  $sidingIds
     * @return array<int, array<string, mixed>>
     */
    public function buildActiveRakes(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $rakes = Rake::query()
            ->with('siding:id,name,code')
            ->whereIn('siding_id', $sidingIds)
            ->where('state', 'loading')
            ->whereNotNull('placement_time')
            ->whereNotNull('loading_free_minutes')
            ->get();

        $list = [];
        foreach ($rakes as $rake) {
            $start = $rake->placement_time;
            $freeMinutes = (int) $rake->loading_free_minutes;
            $end = $start->copy()->addMinutes($freeMinutes);
            $remainingMinutes = max(0, (int) now()->diffInMinutes($end, false));
            $list[] = [
                'id' => $rake->id,
                'rake_number' => $rake->rake_number,
                'siding' => $rake->siding ? ['id' => $rake->siding->id, 'name' => $rake->siding->name, 'code' => $rake->siding->code] : null,
                'placement_time' => $rake->placement_time->toIso8601String(),
                'free_time_minutes' => $freeMinutes,
                'remaining_minutes' => $remainingMinutes,
            ];
        }

        return $list;
    }

    /**
     * @param  array<int>  $sidingIds
     * @return array<int, array{id: int, type: string, title: string, severity: string, rake_id: int|null, siding_id: int|null, created_at: string}>
     */
    public function buildAlerts(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return [];
        }

        return Alert::query()
            ->active()
            ->forSidings($sidingIds)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'type', 'title', 'severity', 'rake_id', 'siding_id', 'created_at'])
            ->map(fn (Alert $a): array => [
                'id' => $a->id,
                'type' => $a->type,
                'title' => $a->title,
                'severity' => $a->severity,
                'rake_id' => $a->rake_id,
                'siding_id' => $a->siding_id,
                'created_at' => $a->created_at->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /**
     * Live rake status: operational rakes still on the siding (pending, no weighment receipt yet).
     *
     * Workflow steps on the rake page are filled in when data exists; {@see Rake::$state} alone is not reliable.
     * Business rule: once a {@see RakeWeighment} exists (receipt/slip captured), the rake is treated as dispatched
     * from the siding for this widget. {@see self::OPERATIONAL_RAKE_DATA_SOURCES} matches the main rakes list (excludes historical).
     * Risk compares {@see Rake::$loading_free_minutes} to the recorded window {@see Rake::$loading_start_time}→{@see Rake::$loading_end_time} (not “now”).
     *
     * @param  array<int>  $sidingIds
     * @param  array{power_plant: string|null, rake_number: string|null, loader_id: int|null, shift: string|null}  $filterContext
     * @return array<int, array{rake_number: string, siding_name: string, state: string, workflow_steps: array{txr_done: bool, wagon_loading_done: bool, guard_done: bool, weighment_done: bool, rr_done: bool}, time_elapsed: string, risk: string}>
     */
    public function buildLiveRakeStatus(array $sidingIds, array $filterContext = []): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $rakeQuery = Rake::query()
            ->with([
                'siding:id,name',
                'txr:id,rake_id,status,inspection_time,inspection_end_time',
                'wagons:id,rake_id,is_unfit',
                'wagonLoadings:id,rake_id,wagon_id,loaded_quantity_mt',
                'guardInspections:id,rake_id,is_approved',
                'rakeWeighments:id,rake_id,pdf_file_path,status',
                'rrDocument:id,rake_id',
            ])
            ->whereIn('siding_id', $sidingIds)
            ->where('state', 'pending')
            ->where(function ($q): void {
                $q->whereNull('data_source')
                    ->orWhereIn('data_source', self::OPERATIONAL_RAKE_DATA_SOURCES);
            })
            ->whereDoesntHave('rakeWeighments');
        if (! empty($filterContext['rake_number'])) {
            $rakeQuery->where('rake_number', 'like', '%'.$filterContext['rake_number'].'%');
        }

        // Show only the most recent few rakes on the dashboard card.
        // Full list is available on the rakes index page.
        $rakes = $rakeQuery
            ->orderByDesc('placement_time')
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'rake_number', 'siding_id', 'state', 'placement_time', 'loading_start_time', 'loading_end_time', 'loading_free_minutes']);

        $list = [];
        foreach ($rakes as $rake) {
            $loadingDuration = '—';
            if ($rake->loading_start_time !== null && $rake->loading_end_time !== null) {
                $loadMins = (int) $rake->loading_start_time->diffInMinutes($rake->loading_end_time);
                $loadingDuration = $loadMins < 60
                    ? "{$loadMins}m"
                    : sprintf('%dh %dm', (int) floor($loadMins / 60), $loadMins % 60);
            }

            // Risk uses recorded loading window only (start → end), not wall-clock "now". Operators backfill times.
            $risk = 'normal';
            if ($rake->loading_start_time !== null
                && $rake->loading_end_time !== null
                && $rake->loading_free_minutes) {
                $loadMinsForRisk = (int) $rake->loading_start_time->diffInMinutes($rake->loading_end_time);
                $free = (int) $rake->loading_free_minutes;
                if ($loadMinsForRisk >= $free) {
                    $risk = 'penalty_risk';
                } elseif ($loadMinsForRisk >= (int) ($free * 0.75)) {
                    $risk = 'attention';
                }
            }

            $list[] = [
                'rake_number' => $rake->rake_number ?? "Rake {$rake->id}",
                'siding_name' => $rake->siding?->name ?? '—',
                'state' => $rake->state ?? '—',
                'workflow_steps' => RakeDataTable::workflowStepsForRake($rake),
                'time_elapsed' => $loadingDuration,
                'risk' => $risk,
            ];
        }

        return $list;
    }

    /**
     * Daily rake details: one-day view per siding (day rakes/qty, month-to-date rakes/qty, avg).
     * Used when siding filter is applied; date defaults to yesterday.
     *
     * @param  array<int>  $sidingIds
     * @return array{date: string, rows: array<int, array{sl_no: int, siding_name: string, day_rakes: int, day_qty: float, month_rakes: int, month_qty: float, rake_day_avg: float, remarks: string}>, totals: array{day_rakes: int, day_qty: float, month_rakes: int, month_qty: float, rake_day_avg: float}}
     */
    public function buildDailyRakeDetails(array $sidingIds, CarbonInterface $date): array
    {
        $dateStr = $date->toDateString();
        $monthStart = $date->copy()->startOfMonth()->toDateString();
        $daysInMonthSoFar = max(1, (int) $date->day);

        if ($sidingIds === []) {
            return [
                'date' => $dateStr,
                'rows' => [],
                'totals' => [
                    'day_rakes' => 0,
                    'day_qty' => 0.0,
                    'month_rakes' => 0,
                    'month_qty' => 0.0,
                    'rake_day_avg' => 0.0,
                ],
            ];
        }

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $daySql = $this->dateOnlyBetweenSql('loading_date', true);
        $dayRows = Rake::query()
            ->whereIn('siding_id', $sidingIds)
            ->whereNotNull('loading_date')
            ->whereRaw($daySql, [$dateStr, $dateStr])
            ->selectRaw('siding_id, count(*) as rakes, coalesce(sum(loaded_weight_mt), 0) as qty')
            ->groupBy('siding_id')
            ->get()
            ->keyBy('siding_id');

        $monthRows = Rake::query()
            ->whereIn('siding_id', $sidingIds)
            ->whereNotNull('loading_date')
            ->whereRaw($daySql, [$monthStart, $dateStr])
            ->selectRaw('siding_id, count(*) as rakes, coalesce(sum(loaded_weight_mt), 0) as qty')
            ->groupBy('siding_id')
            ->get()
            ->keyBy('siding_id');

        $rows = [];
        $totals = ['day_rakes' => 0, 'day_qty' => 0.0, 'month_rakes' => 0, 'month_qty' => 0.0, 'rake_day_avg' => 0.0];
        $slNo = 1;
        foreach ($sidings as $siding) {
            $day = $dayRows->get($siding->id);
            $month = $monthRows->get($siding->id);
            $dayRakes = $day !== null ? (int) $day->rakes : 0;
            $dayQty = $day !== null ? (float) $day->qty : 0.0;
            $monthRakes = $month !== null ? (int) $month->rakes : 0;
            $monthQty = $month !== null ? (float) $month->qty : 0.0;
            $rakeDayAvg = $daysInMonthSoFar > 0 ? round($monthRakes / $daysInMonthSoFar, 2) : 0.0;

            $rows[] = [
                'sl_no' => $slNo++,
                'siding_name' => $siding->name,
                'day_rakes' => $dayRakes,
                'day_qty' => round($dayQty, 2),
                'month_rakes' => $monthRakes,
                'month_qty' => round($monthQty, 2),
                'rake_day_avg' => $rakeDayAvg,
                'remarks' => '',
            ];
            $totals['day_rakes'] += $dayRakes;
            $totals['day_qty'] += $dayQty;
            $totals['month_rakes'] += $monthRakes;
            $totals['month_qty'] += $monthQty;
        }
        $totals['day_qty'] = round($totals['day_qty'], 2);
        $totals['month_qty'] = round($totals['month_qty'], 2);
        $totals['rake_day_avg'] = $daysInMonthSoFar > 0 ? round($totals['month_rakes'] / $daysInMonthSoFar, 2) : 0.0;

        return [
            'date' => $dateStr,
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    /**
     * Truck receipt trend: hourly count of vehicles arrived today (from daily_vehicle_entries).
     *
     * @param  array<int>  $sidingIds
     * @param  array{power_plant: string|null, rake_number: string|null, loader_id: int|null, shift: string|null}  $filterContext
     * @return array<int, array{hour: string, label: string, count: int}>
     */
    public function buildTruckReceiptTrend(array $sidingIds, array $filterContext = []): array
    {
        $hours = [];
        for ($h = 0; $h < 24; $h++) {
            $hours[] = [
                'hour' => sprintf('%02d:00', $h),
                'label' => sprintf('%02d:00', $h),
                'count' => 0,
            ];
        }

        if ($sidingIds === []) {
            return $hours;
        }

        $todayStart = today()->startOfDay();
        $todayEnd = today()->endOfDay();

        $driver = DB::getDriverName();
        $hourSql = $driver === 'pgsql' ? 'EXTRACT(HOUR FROM reached_at)::int' : 'HOUR(reached_at)';

        $query = DailyVehicleEntry::query()
            ->whereIn('siding_id', $sidingIds)
            ->whereBetween('reached_at', [$todayStart, $todayEnd]);
        if (isset($filterContext['shift']) && $filterContext['shift'] !== null && $filterContext['shift'] !== '') {
            $query->where('shift', $filterContext['shift']);
        }
        $rows = $query
            ->selectRaw("{$hourSql} as h, count(*) as cnt")
            ->groupBy('h')
            ->get()
            ->keyBy('h');

        foreach ($rows as $h => $r) {
            $idx = (int) $h;
            if ($idx >= 0 && $idx < 24) {
                $hours[$idx]['count'] = (int) $r->cnt;
            }
        }

        return $hours;
    }

    /**
     * Shift-wise vehicle receipt by siding (today): for operations dashboard bar chart.
     * Affected by the dashboard shift filter — when set, only that shift is shown.
     *
     * @param  array<int>  $sidingIds
     * @param  string|null  $shiftFilter  '1'|'2'|'3' to show one shift, null for all
     * @return array<int, array{shift_label: string, ...array<string, int>}>
     */
    public function buildShiftWiseVehicleReceipt(array $sidingIds, ?string $shiftFilter): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $todayStart = today()->startOfDay();
        $todayEnd = today()->endOfDay();

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->keyBy('id');

        $query = DailyVehicleEntry::query()
            ->whereIn('siding_id', $sidingIds)
            ->whereBetween('reached_at', [$todayStart, $todayEnd]);
        if ($shiftFilter !== null && $shiftFilter !== '') {
            $query->where('shift', (int) $shiftFilter);
        }
        $rows = $query
            ->selectRaw('shift, siding_id, count(*) as cnt')
            ->groupBy('shift', 'siding_id')
            ->get();

        $shiftLabels = [
            1 => 'Shift 1',
            2 => 'Shift 2',
            3 => 'Shift 3',
        ];
        $shiftsOrdered = ($shiftFilter !== null && $shiftFilter !== '') ? [(int) $shiftFilter] : [1, 2, 3];
        $result = [];
        foreach ($shiftsOrdered as $shiftNum) {
            $row = ['shift_label' => $shiftLabels[$shiftNum] ?? "Shift {$shiftNum}"];
            foreach ($sidings as $siding) {
                $row[$siding->name] = 0;
            }
            $result[] = $row;
        }

        foreach ($rows as $r) {
            $shiftNum = (int) $r->shift;
            $sidingName = $sidings->get($r->siding_id)?->name;
            if ($sidingName !== null) {
                $idx = array_search($shiftNum, $shiftsOrdered, true);
                if ($idx !== false && isset($result[$idx])) {
                    $result[$idx][$sidingName] = (int) $r->cnt;
                }
            }
        }

        return $result;
    }

    /**
     * Coal Transport Report: trips and qty by shift and siding for one day.
     * Data from daily_vehicle_entries (trips) and stock_ledgers receipts (qty).
     * Affected by the dashboard shift filter — when set, only that shift row is shown; otherwise all three shifts.
     *
     * @param  array<int>  $sidingIds
     * @param  string|null  $shiftFilter  '1'|'2'|'3' to show one shift, null for all
     * @return array{date: string, sidings: array<int, array{id: int, name: string}>, rows: array<int, array{sl_no: int, shift_label: string, siding_metrics: array<int, array{siding_name: string, trips: int, qty: float}>, total_trips: int, total_qty: float}>, totals: array{siding_metrics: array<int, array{siding_name: string, trips: int, qty: float}>, total_trips: int, total_qty: float}}
     */
    public function buildCoalTransportReport(array $sidingIds, CarbonInterface $date, ?string $shiftFilter): array
    {
        return $this->coalTransportReportDataBuilder->buildCoalTransportReport($sidingIds, $date, $shiftFilter);
    }

    /**
     * Stock vs requirement gauge per siding (PDF: Stock Available, Rake Required, Status).
     * Requirement is 3800 MT per rake. Semi-circle gauge: red (below), green (at target), blue (above).
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array{siding_id: int, siding_name: string, stock_available_mt: float, rake_required_mt: float, status: string}>
     */
    public function buildStockGauge(array $sidingIds, CarbonInterface $to): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $sidingsOrdered = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $lastLedgers = StockLedger::query()
            ->whereIn('siding_id', $sidingIds)
            ->where('created_at', '<=', $to->copy()->endOfDay())
            ->orderByDesc('created_at')
            ->get()
            ->unique('siding_id');

        $result = [];
        foreach ($sidingsOrdered as $siding) {
            $sidingId = $siding->id;
            $ledger = $lastLedgers->firstWhere('siding_id', $sidingId);
            $stockMt = $ledger ? (float) $ledger->closing_balance_mt : 0.0;
            $required = (float) self::STOCK_GAUGE_RAKE_REQUIREMENT_MT;

            $status = 'no_data';
            if ($stockMt > 0) {
                $ratio = $required > 0 ? $stockMt / $required : 0;
                $status = $ratio < (1 / 3) ? 'below' : ($ratio <= 1 ? 'ready' : 'above');
            }

            $result[] = [
                'siding_id' => $sidingId,
                'siding_name' => $siding->name,
                'stock_available_mt' => round($stockMt, 2),
                'rake_required_mt' => $required,
                'status' => $status,
            ];
        }

        return $result;
    }

    /**
     * Applied vs RR snapshot penalty for period, with per-siding breakdown for grouped bar chart.
     * "Predicted" series in the UI payload uses {@see AppliedPenalty} (sum of `amount` per siding via rake `loading_date`).
     * "Actual" series uses {@see RrPenaltySnapshot} amounts (same date window on `rakes.loading_date`).
     *
     * @param  array<int>  $sidingIds
     * @param  array{power_plant: string|null, rake_number: string|null, loader_id: int|null, shift: string|null, penalty_type_id: int|null}  $filterContext
     * @return array{predicted: float, actual: float, bySiding: array<int, array{name: string, predicted: float, actual: float}>}
     */
    public function buildPredictedVsActualPenalty(array $sidingIds, CarbonInterface $from, CarbonInterface $to, array $filterContext = []): array
    {
        if ($sidingIds === []) {
            return ['predicted' => 0.0, 'actual' => 0.0, 'bySiding' => []];
        }

        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();

        $rakeIds = null;
        if (! empty($filterContext['rake_number']) || ! empty($filterContext['power_plant'])) {
            $rakeIds = $this->getFilteredRakeIds($sidingIds, $filterContext);
        }

        $appliedPenaltyBySidingQuery = function () use ($fromDate, $toDate, $filterContext): \Illuminate\Database\Eloquent\Builder {
            $q = AppliedPenalty::query()
                ->join('rakes', 'applied_penalties.rake_id', '=', 'rakes.id')
                ->whereNotNull('rakes.loading_date')
                ->whereRaw($this->dateOnlyBetweenSql('rakes.loading_date', true), [$fromDate, $toDate]);
            if (! empty($filterContext['penalty_type_id'])) {
                $q->where('applied_penalties.penalty_type_id', (int) $filterContext['penalty_type_id']);
            }

            return $q;
        };

        if ($rakeIds !== null) {
            $predictedBySiding = $rakeIds === []
                ? collect()
                : $appliedPenaltyBySidingQuery()
                    ->whereIn('rakes.id', $rakeIds)
                    ->selectRaw('rakes.siding_id, sum(applied_penalties.amount) as total')
                    ->groupBy('rakes.siding_id')
                    ->get()
                    ->keyBy('siding_id');
        } else {
            $predictedBySiding = $appliedPenaltyBySidingQuery()
                ->whereIn('rakes.siding_id', $sidingIds)
                ->selectRaw('rakes.siding_id, sum(applied_penalties.amount) as total')
                ->groupBy('rakes.siding_id')
                ->get()
                ->keyBy('siding_id');
        }

        $actualQuery = RrPenaltySnapshot::query()
            ->join('rakes', 'rr_penalty_snapshots.rake_id', '=', 'rakes.id')
            ->whereNotNull('rakes.loading_date')
            ->whereRaw($this->dateOnlyBetweenSql('rakes.loading_date', true), [$fromDate, $toDate]);

        if (! empty($filterContext['penalty_type_id'])) {
            $code = PenaltyType::query()
                ->whereKey($filterContext['penalty_type_id'])
                ->value('code');

            if ($code !== null) {
                $actualQuery->where('rr_penalty_snapshots.penalty_code', $code);
            }
        }

        if ($rakeIds !== null) {
            $actualQuery->whereIn('rakes.id', $rakeIds);
        } else {
            $actualQuery->whereIn('rakes.siding_id', $sidingIds);
        }

        $actualBySiding = $actualQuery
            ->selectRaw('rakes.siding_id, sum(rr_penalty_snapshots.amount) as total')
            ->groupBy('rakes.siding_id')
            ->get()
            ->keyBy('siding_id');

        $allSidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $bySiding = [];
        $predictedTotal = 0.0;
        $actualTotal = 0.0;
        foreach ($allSidings as $siding) {
            $pred = $predictedBySiding->get($siding->id);
            $act = $actualBySiding->get($siding->id);
            $predVal = $pred ? round((float) $pred->total, 2) : 0.0;
            $actVal = $act ? round((float) $act->total, 2) : 0.0;
            $bySiding[] = [
                'name' => (string) $siding->name,
                'predicted' => $predVal,
                'actual' => $actVal,
            ];
            $predictedTotal += $predVal;
            $actualTotal += $actVal;
        }

        return [
            'predicted' => round($predictedTotal, 2),
            'actual' => round($actualTotal, 2),
            'bySiding' => $bySiding,
        ];
    }

    /**
     * Monthly penalty trend for last 12 months (backfilled with zeros).
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array{month: string, total: float, count: int}>
     */
    public function buildPenaltyChartData(array $sidingIds): array
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key = $date->format('Y-m');
            $months[$key] = [
                'month' => $date->format('M Y'),
                'total' => 0.0,
                'count' => 0,
            ];
        }

        if ($sidingIds === []) {
            return array_values($months);
        }

        $driver = DB::getDriverName();
        $yearMonthSql = $driver === 'pgsql'
            ? 'EXTRACT(YEAR FROM penalty_date)::int as y, EXTRACT(MONTH FROM penalty_date)::int as m'
            : 'YEAR(penalty_date) as y, MONTH(penalty_date) as m';

        $rows = Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->where('penalty_date', '>=', now()->subMonths(12))
            ->selectRaw("{$yearMonthSql}, sum(penalty_amount) as total, count(*) as count")
            ->groupBy('y', 'm')
            ->get();

        foreach ($rows as $r) {
            $key = sprintf('%04d-%02d', (int) $r->y, (int) $r->m);
            if (isset($months[$key])) {
                $months[$key]['total'] = (float) $r->total;
                $months[$key]['count'] = (int) $r->count;
            }
        }

        return array_values($months);
    }

    /**
     * Penalties grouped by type (for pie chart).
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array{name: string, value: float}>
     */
    public function buildPenaltyByType(array $sidingIds, CarbonInterface $from, CarbonInterface $to, array $filterContext = []): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $rakeIds = null;
        if (! empty($filterContext['rake_number']) || ! empty($filterContext['power_plant'])) {
            $rakeIds = $this->getFilteredRakeIds($sidingIds, $filterContext);
            if ($rakeIds === []) {
                return [];
            }
        }
        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();
        $query = RrPenaltySnapshot::query()
            ->join('penalty_types', 'rr_penalty_snapshots.penalty_code', '=', 'penalty_types.code')
            ->join('rakes', 'rr_penalty_snapshots.rake_id', '=', 'rakes.id')
            ->whereNotNull('rakes.loading_date')
            ->whereRaw($this->dateOnlyBetweenSql('rakes.loading_date', true), [$fromDate, $toDate]);
        if (! empty($filterContext['penalty_type_id'])) {
            $code = PenaltyType::query()
                ->whereKey($filterContext['penalty_type_id'])
                ->value('code');

            if ($code !== null) {
                $query->where('rr_penalty_snapshots.penalty_code', $code);
            }
        }
        if ($rakeIds !== null) {
            $query->whereIn('rr_penalty_snapshots.rake_id', $rakeIds);
        } else {
            $query->whereIn('rakes.siding_id', $sidingIds);
        }

        return $query
            ->selectRaw('penalty_types.name as penalty_type_name, sum(rr_penalty_snapshots.amount) as total')
            ->groupBy('penalty_types.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r): array => [
                'name' => (string) $r->penalty_type_name,
                'value' => (float) $r->total,
            ])
            ->values()
            ->all();
    }

    /**
     * Penalties grouped by siding (for bar chart). Includes all sidings with total 0 when no data.
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array{name: string, total: float}>
     */
    public function buildPenaltyBySiding(array $sidingIds, CarbonInterface $from, CarbonInterface $to, array $filterContext = []): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $allSidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $rakeIds = null;
        if (! empty($filterContext['rake_number']) || ! empty($filterContext['power_plant'])) {
            $rakeIds = $this->getFilteredRakeIds($sidingIds, $filterContext);
        }

        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();
        $query = RrPenaltySnapshot::query()
            ->join('rakes', 'rr_penalty_snapshots.rake_id', '=', 'rakes.id')
            ->join('sidings', 'rakes.siding_id', '=', 'sidings.id')
            ->whereNotNull('rakes.loading_date')
            ->whereRaw($this->dateOnlyBetweenSql('rakes.loading_date', true), [$fromDate, $toDate]);
        if (! empty($filterContext['penalty_type_id'])) {
            $code = PenaltyType::query()
                ->whereKey($filterContext['penalty_type_id'])
                ->value('code');

            if ($code !== null) {
                $query->where('rr_penalty_snapshots.penalty_code', $code);
            }
        }
        if ($rakeIds !== null) {
            if ($rakeIds === []) {
                $totalsByName = collect();
            } else {
                $query->whereIn('rr_penalty_snapshots.rake_id', $rakeIds);
                $totalsByName = $query
                    ->selectRaw('sidings.name, sum(rr_penalty_snapshots.amount) as total')
                    ->groupBy('sidings.name')
                    ->get()
                    ->keyBy('name');
            }
        } else {
            $query->whereIn('rakes.siding_id', $sidingIds);
            $totalsByName = $query
                ->selectRaw('sidings.name, sum(rr_penalty_snapshots.amount) as total')
                ->groupBy('sidings.name')
                ->get()
                ->keyBy('name');
        }

        $result = [];
        foreach ($allSidings as $siding) {
            $name = (string) $siding->name;
            $row = $totalsByName->get($name);
            $result[] = [
                'name' => $name,
                'total' => $row ? round((float) $row->total, 2) : 0.0,
            ];
        }

        usort($result, fn ($a, $b) => $b['total'] <=> $a['total']);

        return $result;
    }

    /**
     * Cost avoidance: rakes that stayed within free time vs those that incurred penalties.
     *
     * @param  array<int>  $sidingIds
     * @return array{rakes_within_free_time: int, rakes_with_penalties: int, money_saved: float, money_lost: float}
     */
    public function buildCostAvoidance(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return ['rakes_within_free_time' => 0, 'rakes_with_penalties' => 0, 'money_saved' => 0, 'money_lost' => 0];
        }

        $thisMonth = now();
        $rakeIdsWithPenalties = Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->whereMonth('penalty_date', $thisMonth->month)
            ->whereYear('penalty_date', $thisMonth->year)
            ->distinct()
            ->pluck('rake_id');

        $totalRakesThisMonth = Rake::query()
            ->whereIn('siding_id', $sidingIds)
            ->whereNotNull('created_at')
            ->whereMonth('created_at', $thisMonth->month)
            ->whereYear('created_at', $thisMonth->year)
            ->count();

        $rakesWithPenalties = $rakeIdsWithPenalties->count();
        $rakesWithinFreeTime = max(0, $totalRakesThisMonth - $rakesWithPenalties);

        $moneyLost = (float) Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->whereMonth('penalty_date', $thisMonth->month)
            ->whereYear('penalty_date', $thisMonth->year)
            ->sum('penalty_amount');

        // Estimate money saved: average penalty per penalised rake × rakes without penalties
        $avgPenaltyPerRake = $rakesWithPenalties > 0 ? $moneyLost / $rakesWithPenalties : 0;
        $moneySaved = $rakesWithinFreeTime * $avgPenaltyPerRake;

        return [
            'rakes_within_free_time' => $rakesWithinFreeTime,
            'rakes_with_penalties' => $rakesWithPenalties,
            'money_saved' => round($moneySaved, 2),
            'money_lost' => round($moneyLost, 2),
        ];
    }

    /**
     * Financial impact summary: YTD totals, projected annual, cost per rake, worst siding.
     *
     * @param  array<int>  $sidingIds
     * @return array{ytd_total: float, projected_annual: float, cost_per_rake: float, worst_siding: string|null, trend_direction: string}
     */
    public function buildFinancialImpact(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return ['ytd_total' => 0, 'projected_annual' => 0, 'cost_per_rake' => 0, 'worst_siding' => null, 'trend_direction' => 'flat'];
        }

        $startOfYear = now()->startOfYear();

        $ytdTotal = (float) Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->where('penalty_date', '>=', $startOfYear)
            ->sum('penalty_amount');

        $monthsElapsed = max(1, now()->month);
        $projectedAnnual = round(($ytdTotal / $monthsElapsed) * 12, 2);

        $ytdRakesWithPenalties = Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->where('penalty_date', '>=', $startOfYear)
            ->distinct()
            ->count('rake_id');

        $costPerRake = $ytdRakesWithPenalties > 0 ? round($ytdTotal / $ytdRakesWithPenalties, 2) : 0;

        // Worst siding
        $worstSiding = Penalty::query()
            ->join('rakes', 'penalties.rake_id', '=', 'rakes.id')
            ->join('sidings', 'rakes.siding_id', '=', 'sidings.id')
            ->whereIn('rakes.siding_id', $sidingIds)
            ->where('penalty_date', '>=', $startOfYear)
            ->selectRaw('sidings.name, sum(penalty_amount) as total')
            ->groupBy('sidings.name')
            ->orderByDesc('total')
            ->value('name');

        // Trend: compare this month to last month
        $thisMonthTotal = (float) Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->whereMonth('penalty_date', now()->month)
            ->whereYear('penalty_date', now()->year)
            ->sum('penalty_amount');

        $lastMonthTotal = (float) Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->whereMonth('penalty_date', now()->subMonth()->month)
            ->whereYear('penalty_date', now()->subMonth()->year)
            ->sum('penalty_amount');

        $trendDirection = $thisMonthTotal > $lastMonthTotal ? 'up' : ($thisMonthTotal < $lastMonthTotal ? 'down' : 'flat');

        return [
            'ytd_total' => round($ytdTotal, 2),
            'projected_annual' => $projectedAnnual,
            'cost_per_rake' => $costPerRake,
            'worst_siding' => $worstSiding,
            'trend_direction' => $trendDirection,
        ];
    }

    /**
     * Rake distribution by state for donut chart.
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array{name: string, value: int}>
     */
    public function buildRakeStateChart(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return [];
        }

        return Rake::query()
            ->whereIn('siding_id', $sidingIds)
            ->selectRaw('state as name, count(*) as value')
            ->groupBy('state')
            ->orderByDesc('value')
            ->get()
            ->map(fn ($r): array => [
                'name' => ucfirst(str_replace('_', ' ', (string) $r->name)),
                'value' => (int) $r->value,
            ])
            ->values()
            ->all();
    }

    /**
     * Indent pipeline breakdown by state.
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array{name: string, value: int}>
     */
    public function buildIndentPipeline(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return [];
        }

        return Indent::query()
            ->whereIn('siding_id', $sidingIds)
            ->selectRaw('state as name, count(*) as value')
            ->groupBy('state')
            ->orderByDesc('value')
            ->get()
            ->map(fn ($r): array => [
                'name' => ucfirst(str_replace('_', ' ', (string) $r->name)),
                'value' => (int) $r->value,
            ])
            ->values()
            ->all();
    }

    /**
     * Penalty status breakdown (paid, disputed, waived, pending, etc.).
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array{name: string, value: float, count: int}>
     */
    public function buildPenaltyStatusBreakdown(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return [];
        }

        return Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->where('penalty_date', '>=', now()->subMonths(12))
            ->selectRaw('penalty_status as name, sum(penalty_amount) as value, count(*) as count')
            ->groupBy('penalty_status')
            ->orderByDesc('value')
            ->get()
            ->map(fn ($r): array => [
                'name' => ucfirst((string) $r->name),
                'value' => (float) $r->value,
                'count' => (int) $r->count,
            ])
            ->values()
            ->all();
    }

    /**
     * Penalties by responsible party for bar chart.
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array{name: string, value: float, count: int}>
     */
    public function buildResponsiblePartyBreakdown(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return [];
        }

        return Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->where('penalty_date', '>=', now()->subMonths(12))
            ->whereNotNull('responsible_party')
            ->selectRaw('responsible_party as name, sum(penalty_amount) as value, count(*) as count')
            ->groupBy('responsible_party')
            ->orderByDesc('value')
            ->get()
            ->map(fn ($r): array => [
                'name' => ucfirst((string) $r->name),
                'value' => (float) $r->value,
                'count' => (int) $r->count,
            ])
            ->values()
            ->all();
    }

    /**
     * Per-siding performance: rakes handled, penalties incurred, penalty rate.
     *
     * @param  array<int>  $sidingIds
     * @param  array{period: string, power_plant: string|null, rake_number: string|null, loader_id: int|null, shift: string|null}  $filterContext
     * @return array<int, array{name: string, rakes: int, penalties: int, penalty_amount: float, penalty_rate: float}>
     */
    public function buildSidingPerformance(array $sidingIds, CarbonInterface $from, CarbonInterface $to, array $filterContext = []): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $rakeIds = null;
        if (! empty($filterContext['rake_number']) || ! empty($filterContext['power_plant'])) {
            $rakeIds = $this->getFilteredRakeIds($sidingIds, $filterContext);
            if ($rakeIds === []) {
                return [];
            }
        }

        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();
        // Filter by loading_date (business date) so date selection is correct. Only rakes with loading_date set are counted.
        $rakeQuery = Rake::query()
            ->join('sidings', 'rakes.siding_id', '=', 'sidings.id')
            ->whereIn('rakes.siding_id', $sidingIds)
            ->whereNotNull('rakes.loading_date')
            ->whereRaw($this->dateOnlyBetweenSql('rakes.loading_date', true), [$fromDate, $toDate]);
        if ($rakeIds !== null) {
            $rakeQuery->whereIn('rakes.id', $rakeIds);
        }
        $rakesBySiding = $rakeQuery
            ->selectRaw('sidings.name, count(*) as total_rakes')
            ->groupBy('sidings.name')
            ->pluck('total_rakes', 'name')
            ->all();

        // Actual penalties (RR penalty snapshots) for rakes in the same period (filter by rakes.loading_date).
        $penaltyQuery = RrPenaltySnapshot::query()
            ->join('rakes', 'rr_penalty_snapshots.rake_id', '=', 'rakes.id')
            ->join('sidings', 'rakes.siding_id', '=', 'sidings.id')
            ->whereNotNull('rakes.loading_date')
            ->whereRaw($this->dateOnlyBetweenSql('rakes.loading_date', true), [$fromDate, $toDate]);
        if ($rakeIds !== null) {
            $penaltyQuery->whereIn('rakes.id', $rakeIds);
        } else {
            $penaltyQuery->whereIn('rakes.siding_id', $sidingIds);
        }
        $penaltiesBySiding = $penaltyQuery
            ->selectRaw('sidings.name, count(*) as penalty_count, count(DISTINCT rr_penalty_snapshots.rake_id) as penalised_rakes, sum(rr_penalty_snapshots.amount) as penalty_total')
            ->groupBy('sidings.name')
            ->get()
            ->keyBy('name');

        // Include all selected sidings so "All sidings" shows every siding (0 when no data in range).
        $sidingNamesById = Siding::query()
            ->whereIn('id', $sidingIds)
            ->pluck('name', 'id')
            ->all();

        $result = [];
        foreach ($sidingIds as $sid) {
            $name = $sidingNamesById[$sid] ?? "Siding {$sid}";
            $rakeCount = (int) ($rakesBySiding[$name] ?? 0);
            $penaltyData = $penaltiesBySiding->get($name);
            $penaltyCount = $penaltyData ? (int) $penaltyData->penalty_count : 0;
            $penalisedRakes = $penaltyData ? (int) $penaltyData->penalised_rakes : 0;
            $penaltyAmount = $penaltyData ? (float) $penaltyData->penalty_total : 0.0;
            $result[] = [
                'name' => (string) $name,
                'rakes' => $rakeCount,
                'penalties' => $penaltyCount,
                'penalty_amount' => round($penaltyAmount, 2),
                'penalty_rate' => $rakeCount > 0 ? round(($penalisedRakes / $rakeCount) * 100, 1) : 0,
            ];
        }

        usort($result, fn ($a, $b) => $b['penalty_amount'] <=> $a['penalty_amount']);

        return $result;
    }

    /**
     * Undisputed penalties that represent a savings opportunity.
     *
     * @param  array<int>  $sidingIds
     * @return array{potential_savings: float, undisputed_count: int}
     */
    public function buildDisputeOpportunity(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return ['potential_savings' => 0, 'undisputed_count' => 0];
        }

        $baseQuery = fn () => Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->where('penalty_date', '>=', now()->subMonths(12));

        $undisputed = $baseQuery()
            ->whereIn('penalty_status', ['incurred', 'pending'])
            ->whereNull('disputed_at')
            ->selectRaw('count(*) as count, sum(penalty_amount) as total')
            ->first();

        $undisputedCount = (int) ($undisputed->count ?? 0);
        $undisputedAmount = (float) ($undisputed->total ?? 0);

        // Apply estimated success rate
        $disputedTotal = $baseQuery()->whereIn('penalty_status', ['disputed', 'waived'])->count();
        $waivedCount = $baseQuery()->where('penalty_status', 'waived')->count();
        $successRate = $disputedTotal > 0 ? $waivedCount / $disputedTotal : 0.3;

        return [
            'potential_savings' => round($undisputedAmount * $successRate, 2),
            'undisputed_count' => $undisputedCount,
        ];
    }

    /**
     * Date-wise rail dispatch and penalty amounts broken down by siding.
     *
     * @param  array<int>  $sidingIds
     * @return array{sidingNames: array<int, string>, dates: list<array<string, mixed>>}
     */
    public function buildDateWiseDispatch(array $sidingIds, CarbonInterface $from, CarbonInterface $to): array
    {
        $sidingMap = Siding::query()
            ->whereIn('id', $sidingIds)
            ->pluck('name', 'id')
            ->all();

        $days = [];
        $cursor = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->startOfDay();
        while ($cursor->lte($end)) {
            $d = $cursor->format('Y-m-d');
            $row = ['date' => $cursor->format('d M'), 'total_dispatched' => 0, 'total_penalty' => 0.0];
            foreach ($sidingMap as $id => $name) {
                $row["dispatched_{$id}"] = 0;
                $row["penalty_{$id}"] = 0.0;
            }
            $days[$d] = $row;
            $cursor->addDay();
        }

        if ($sidingIds === [] || $days === []) {
            return ['sidingNames' => $sidingMap, 'dates' => array_values($days)];
        }

        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();
        $driver = DB::getDriverName();
        $dateSql = $driver === 'pgsql' ? 'rakes.created_at::date' : 'DATE(rakes.created_at)';

        $dispatched = Rake::query()
            ->whereIn('siding_id', $sidingIds)
            ->whereNotNull('created_at')
            ->whereRaw($this->dateOnlyBetweenSql('created_at'), [$fromDate, $toDate])
            ->selectRaw("{$dateSql} as d, siding_id, count(*) as cnt")
            ->groupBy('d', 'siding_id')
            ->get();

        foreach ($dispatched as $row) {
            $d = $row->d;
            if (isset($days[$d])) {
                $days[$d]["dispatched_{$row->siding_id}"] = (int) $row->cnt;
                $days[$d]['total_dispatched'] += (int) $row->cnt;
            }
        }

        $penDateSql = $driver === 'pgsql' ? 'applied_penalties.created_at::date' : 'DATE(applied_penalties.created_at)';

        $penalties = AppliedPenalty::query()
            ->join('rakes', 'applied_penalties.rake_id', '=', 'rakes.id')
            ->whereIn('rakes.siding_id', $sidingIds)
            ->whereRaw($this->dateOnlyBetweenSql('applied_penalties.created_at'), [$fromDate, $toDate])
            ->selectRaw("{$penDateSql} as d, rakes.siding_id, sum(applied_penalties.amount) as total")
            ->groupBy('d', 'rakes.siding_id')
            ->get();

        foreach ($penalties as $row) {
            $d = $row->d;
            if (isset($days[$d])) {
                $days[$d]["penalty_{$row->siding_id}"] = round((float) $row->total, 2);
                $days[$d]['total_penalty'] += round((float) $row->total, 2);
            }
        }

        return ['sidingNames' => $sidingMap, 'dates' => array_values($days)];
    }

    /**
     * Rake-wise performance: recent dispatched rakes with key metrics.
     *
     * @param  array<int>  $sidingIds
     * @param  array{power_plant: string|null, rake_number: string|null, loader_id: int|null, shift: string|null}  $filterContext
     * @return array<int, array<string, mixed>>
     */
    public function buildRakePerformance(array $sidingIds, CarbonInterface $from, CarbonInterface $to, array $filterContext = []): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();
        $rakeQuery = Rake::query()
            ->with('siding:id,name,code')
            ->whereIn('siding_id', $sidingIds)
            ->whereBetween('created_at', [$fromDate, $toDate]);
        if (! empty($filterContext['rake_number'])) {
            $rakeQuery->where('rake_number', 'like', '%'.$filterContext['rake_number'].'%');
        }
        if (! empty($filterContext['power_plant'])) {
            $rakeQuery->whereIn('id', RakeWeighment::query()->where('to_station', $filterContext['power_plant'])->select('rake_id'));
        }
        $rakes = $rakeQuery->orderByRaw('COALESCE(rakes.loading_date, rakes.created_at) DESC')->limit(50)->get();

        $rakeIds = $rakes->pluck('id')->all();

        $weighmentTotals = RakeWeighment::query()
            ->whereIn('rake_id', $rakeIds)
            ->selectRaw('rake_id, max(total_net_weight_mt) as net_weight, max(total_over_load_mt) as over_load, max(total_under_load_mt) as under_load')
            ->groupBy('rake_id')
            ->get()
            ->keyBy('rake_id');

        $penaltyTotals = AppliedPenalty::query()
            ->whereIn('rake_id', $rakeIds)
            ->selectRaw('rake_id, sum(amount) as total_penalty, count(*) as penalty_count')
            ->groupBy('rake_id')
            ->get()
            ->keyBy('rake_id');

        $latestWeighmentIds = RakeWeighment::query()
            ->whereIn('rake_id', $rakeIds)
            ->orderByDesc('id')
            ->get()
            ->unique('rake_id')
            ->pluck('id')
            ->all();

        $wagonOverloadsByRakeId = [];
        if ($latestWeighmentIds !== []) {
            $weighmentToRake = RakeWeighment::query()
                ->whereIn('id', $latestWeighmentIds)
                ->pluck('rake_id', 'id')
                ->all();
            $wagonRows = RakeWagonWeighment::query()
                ->whereIn('rake_weighment_id', $latestWeighmentIds)
                ->with('wagon:id,wagon_number')
                ->orderBy('wagon_sequence')
                ->get();
            foreach ($wagonRows as $row) {
                $rakeId = $weighmentToRake[$row->rake_weighment_id] ?? null;
                if ($rakeId === null) {
                    continue;
                }
                if (! isset($wagonOverloadsByRakeId[$rakeId])) {
                    $wagonOverloadsByRakeId[$rakeId] = [];
                }
                $wagonOverloadsByRakeId[$rakeId][] = [
                    'wagon_number' => $row->wagon?->wagon_number ?? (string) $row->wagon_id,
                    'over_load_mt' => round((float) ($row->over_load_mt ?? 0), 2),
                ];
            }
        }

        return $rakes->map(function (Rake $rake) use ($weighmentTotals, $penaltyTotals, $wagonOverloadsByRakeId): array {
            $w = $weighmentTotals->get($rake->id);
            $p = $penaltyTotals->get($rake->id);

            $loadingMinutes = null;
            if ($rake->loading_start_time && $rake->loading_end_time) {
                $loadingMinutes = (int) $rake->loading_start_time->diffInMinutes($rake->loading_end_time);
            }

            $displayDate = $rake->loading_date ?? $rake->created_at;

            return [
                'id' => $rake->id,
                'rake_number' => $rake->rake_number,
                'siding' => $rake->siding?->name ?? '—',
                'dispatch_date' => $displayDate ? Carbon::parse($displayDate)->format('d M Y') : '—',
                'wagon_count' => $rake->wagon_count,
                'net_weight' => $w ? round((float) $w->net_weight, 2) : null,
                'over_load' => $w ? round((float) $w->over_load, 2) : null,
                'under_load' => $w ? round((float) $w->under_load, 2) : null,
                'loading_minutes' => $loadingMinutes,
                'penalty_amount' => $p ? round((float) $p->total_penalty, 2) : 0,
                'penalty_count' => $p ? (int) $p->penalty_count : 0,
                'wagon_overloads' => $wagonOverloadsByRakeId[$rake->id] ?? [],
            ];
        })->values()->all();
    }

    /**
     * Loader-wise overloading trends (last 6 months, monthly).
     *
     * @param  array<int>  $sidingIds
     * @param  array{power_plant: string|null, rake_number: string|null, loader_id: int|null, shift: string|null}  $filterContext
     * @return array{loaders: array<int, array{id: int, name: string, siding: string}>, monthly: array<int, array<string, mixed>>}
     */
    public function buildLoaderOverloadTrends(array $sidingIds, CarbonInterface $from, CarbonInterface $to, array $filterContext = []): array
    {
        if ($sidingIds === []) {
            return ['loaders' => [], 'monthly' => []];
        }

        $loaderQuery = Loader::query()
            ->whereIn('siding_id', $sidingIds)
            ->with('siding:id,name')
            ->orderBy('loader_name');
        if (! empty($filterContext['loader_id'])) {
            $loaderQuery->where('id', $filterContext['loader_id']);
        }
        $loaders = $loaderQuery->get(['id', 'loader_name', 'siding_id']);

        if ($loaders->isEmpty()) {
            return ['loaders' => [], 'monthly' => []];
        }

        $loaderIds = $loaders->pluck('id')->all();
        $loaderMap = $loaders->mapWithKeys(fn (Loader $l): array => [$l->id => $l->loader_name])->all();

        $driver = DB::getDriverName();
        $yearMonthSql = $driver === 'pgsql'
            ? 'EXTRACT(YEAR FROM wl.loading_time)::int as y, EXTRACT(MONTH FROM wl.loading_time)::int as m'
            : 'YEAR(wl.loading_time) as y, MONTH(wl.loading_time) as m';

        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();
        $rows = DB::table('wagon_loading as wl')
            ->join('rake_wagon_weighments as rww', function ($join) {
                $join->on('wl.wagon_id', '=', 'rww.wagon_id')
                    ->on('wl.rake_id', '=', DB::raw('(SELECT rake_id FROM rake_weighments WHERE id = rww.rake_weighment_id)'));
            })
            ->whereIn('wl.loader_id', $loaderIds)
            ->whereNotNull('wl.loading_time')
            ->whereRaw($this->dateOnlyBetweenSql('wl.loading_time'), [$fromDate, $toDate])
            ->selectRaw("{$yearMonthSql}, wl.loader_id, count(*) as wagons_loaded, sum(CASE WHEN rww.over_load_mt > 0 THEN 1 ELSE 0 END) as overloaded_wagons")
            ->groupBy('y', 'm', 'wl.loader_id')
            ->get();

        $months = [];
        $cursor = Carbon::parse($from)->startOfMonth();
        $endMonth = Carbon::parse($to)->startOfMonth();
        while ($cursor->lte($endMonth)) {
            $key = $cursor->format('Y-m');
            $months[$key] = ['month' => $cursor->format('M Y')];
            foreach ($loaderMap as $id => $name) {
                $months[$key]["loader_{$id}_overload"] = 0;
                $months[$key]["loader_{$id}_total"] = 0;
            }
            $cursor->addMonth();
        }

        foreach ($rows as $r) {
            $key = sprintf('%04d-%02d', (int) $r->y, (int) $r->m);
            if (isset($months[$key])) {
                $months[$key]["loader_{$r->loader_id}_overload"] = (int) $r->overloaded_wagons;
                $months[$key]["loader_{$r->loader_id}_total"] = (int) $r->wagons_loaded;
            }
        }

        return [
            'loaders' => $loaders->map(fn (Loader $l): array => [
                'id' => $l->id,
                'name' => $l->loader_name,
                'siding' => $l->siding?->name ?? '—',
            ])->values()->all(),
            'monthly' => array_values($months),
        ];
    }

    /**
     * Power plant wise dispatch with siding breakdown.
     *
     * @param  array<int>  $sidingIds
     * @param  array{power_plant?: string|null}|array<string, mixed>  $filterContext
     * @return array<int, array{name: string, rakes: int, weight_mt: float, sidings: array<string, array{rakes: int, weight_mt: float}>}>
     */
    public function buildPowerPlantDispatch(array $sidingIds, CarbonInterface $from, CarbonInterface $to, array $filterContext = []): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $sidingNames = Siding::query()
            ->whereIn('id', $sidingIds)
            ->pluck('name', 'id')
            ->all();

        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();
        // Rakes in the selected date range by loading_date (business date).
        $rows = Rake::query()
            ->whereIn('siding_id', $sidingIds)
            ->where(function ($q): void {
                $q->whereNotNull('destination')
                    ->orWhereNotNull('destination_code');
            })
            ->whereNotNull('loading_date')
            ->whereRaw($this->dateOnlyBetweenSql('loading_date', true), [$fromDate, $toDate])
            ->selectRaw(
                "COALESCE(destination, destination_code, 'Unknown') as power_plant_name, ".
                'siding_id, count(*) as rakes, coalesce(sum(loaded_weight_mt), 0) as weight_mt'
            )
            ->when(
                ! empty($filterContext['power_plant'] ?? null),
                static function ($q) use ($filterContext): void {
                    $plant = (string) $filterContext['power_plant'];
                    $q->where(function ($inner) use ($plant): void {
                        $inner->where('destination', $plant)
                            ->orWhere('destination_code', $plant);
                    });
                }
            )
            ->groupBy('power_plant_name', 'siding_id')
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $name = (string) $row->power_plant_name;
            if (! isset($grouped[$name])) {
                $grouped[$name] = ['name' => $name, 'rakes' => 0, 'weight_mt' => 0.0, 'sidings' => []];
            }

            $sidingName = $sidingNames[$row->siding_id] ?? "Siding {$row->siding_id}";
            $grouped[$name]['rakes'] += (int) $row->rakes;
            $grouped[$name]['weight_mt'] += round((float) $row->weight_mt, 2);
            $grouped[$name]['sidings'][$sidingName] = [
                'rakes' => (int) $row->rakes,
                'weight_mt' => round((float) $row->weight_mt, 2),
            ];
        }

        $result = array_values($grouped);
        usort($result, fn (array $a, array $b): int => $b['rakes'] <=> $a['rakes']);

        return $result;
    }

    /**
     * Siding-wise monthly breakdown (rakes dispatched, penalties, overload) for stacked comparison.
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array<string, mixed>>
     */
    public function buildSidingWiseMonthly(array $sidingIds, CarbonInterface $from, CarbonInterface $to): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $sidingNames = Siding::query()
            ->whereIn('id', $sidingIds)
            ->pluck('name', 'id')
            ->all();

        $driver = DB::getDriverName();
        $yearMonthSql = $driver === 'pgsql'
            ? 'EXTRACT(YEAR FROM created_at)::int as y, EXTRACT(MONTH FROM created_at)::int as m'
            : 'YEAR(created_at) as y, MONTH(created_at) as m';

        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();
        $rakeRows = Rake::query()
            ->whereIn('siding_id', $sidingIds)
            ->whereNotNull('created_at')
            ->whereRaw($this->dateOnlyBetweenSql('created_at'), [$fromDate, $toDate])
            ->selectRaw("{$yearMonthSql}, siding_id, count(*) as cnt")
            ->groupBy('y', 'm', 'siding_id')
            ->get();

        $months = [];
        $cursor = Carbon::parse($from)->startOfMonth();
        $endMonth = Carbon::parse($to)->startOfMonth();
        while ($cursor->lte($endMonth)) {
            $key = $cursor->format('Y-m');
            $entry = ['month' => $cursor->format('M Y')];
            foreach ($sidingNames as $name) {
                $entry[$name] = 0;
            }
            $months[$key] = $entry;
            $cursor->addMonth();
        }

        foreach ($rakeRows as $r) {
            $key = sprintf('%04d-%02d', (int) $r->y, (int) $r->m);
            $name = $sidingNames[$r->siding_id] ?? null;
            if ($name && isset($months[$key])) {
                $months[$key][$name] = (int) $r->cnt;
            }
        }

        return array_values($months);
    }

    /**
     * Siding radar data for multi-dimensional comparison (normalized 0-100).
     *
     * @param  array<int>  $sidingIds
     * @return array{dimensions: array<int, array{dimension: string}>, sidingKeys: array<string>}
     */
    /**
     * Per-siding comparison with actual values for each metric.
     *
     * @param  array<int>  $sidingIds
     * @return array<string, array<int, array{name: string, rakes_dispatched: int, on_time_pct: float, vehicles: int, penalty_amount: float}>>
     */
    public function buildSidingRadar(array $sidingIds, CarbonInterface $from, CarbonInterface $to): array
    {
        if ($sidingIds === []) {
            return ['sidings' => []];
        }

        $sidingNames = Siding::query()
            ->whereIn('id', $sidingIds)
            ->pluck('name', 'id')
            ->all();

        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();
        $dispatchedBySiding = Rake::query()
            ->whereIn('siding_id', $sidingIds)
            ->whereNotNull('created_at')
            ->whereRaw($this->dateOnlyBetweenSql('created_at'), [$fromDate, $toDate])
            ->selectRaw('siding_id, count(*) as cnt')
            ->groupBy('siding_id')
            ->pluck('cnt', 'siding_id')
            ->all();

        $rakesBySiding = Rake::query()
            ->whereIn('siding_id', $sidingIds)
            ->whereRaw($this->dateOnlyBetweenSql('created_at'), [$fromDate, $toDate])
            ->selectRaw('siding_id, count(*) as cnt')
            ->groupBy('siding_id')
            ->pluck('cnt', 'siding_id')
            ->all();

        $penaltyBySiding = AppliedPenalty::query()
            ->join('rakes', 'applied_penalties.rake_id', '=', 'rakes.id')
            ->whereIn('rakes.siding_id', $sidingIds)
            ->whereRaw($this->dateOnlyBetweenSql('applied_penalties.created_at'), [$fromDate, $toDate])
            ->selectRaw('rakes.siding_id, count(DISTINCT applied_penalties.rake_id) as penalised_rakes, sum(applied_penalties.amount) as total')
            ->groupBy('rakes.siding_id')
            ->get()
            ->keyBy('siding_id');

        $vehiclesBySiding = SidingVehicleDispatch::query()
            ->whereIn('siding_id', $sidingIds)
            ->whereRaw($this->dateOnlyBetweenSql('issued_on'), [$fromDate, $toDate])
            ->selectRaw('siding_id, count(DISTINCT truck_regd_no) as cnt')
            ->groupBy('siding_id')
            ->pluck('cnt', 'siding_id')
            ->all();

        $result = [];
        foreach ($sidingIds as $sid) {
            $name = $sidingNames[$sid] ?? "Siding {$sid}";
            $dispatched = (int) ($dispatchedBySiding[$sid] ?? 0);
            $totalRakes = (int) ($rakesBySiding[$sid] ?? 0);
            $penalisedRakes = (int) ($penaltyBySiding->get($sid)?->penalised_rakes ?? 0);
            $penaltyTotal = round((float) ($penaltyBySiding->get($sid)?->total ?? 0), 2);
            $vehicles = (int) ($vehiclesBySiding[$sid] ?? 0);
            $onTimeCount = max(0, $totalRakes - $penalisedRakes);

            $result[] = [
                'name' => $name,
                'rakes_dispatched' => $dispatched,
                'on_time' => $onTimeCount,
                'vehicles' => $vehicles,
                'penalty_amount' => $penaltyTotal,
            ];
        }

        return ['sidings' => $result];
    }

    /**
     * Loader overload rate gauges for radial chart.
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array{name: string, value: float}>
     */
    public function buildLoaderGauges(array $sidingIds, CarbonInterface $from, CarbonInterface $to): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $loaders = Loader::query()
            ->whereIn('siding_id', $sidingIds)
            ->with('siding:id,name')
            ->get(['id', 'loader_name', 'siding_id']);

        if ($loaders->isEmpty()) {
            return [];
        }

        $loaderIds = $loaders->pluck('id')->all();
        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();

        $stats = DB::table('wagon_loading as wl')
            ->join('rake_wagon_weighments as rww', function ($join) {
                $join->on('wl.wagon_id', '=', 'rww.wagon_id')
                    ->on('wl.rake_id', '=', DB::raw('(SELECT rake_id FROM rake_weighments WHERE id = rww.rake_weighment_id)'));
            })
            ->whereIn('wl.loader_id', $loaderIds)
            ->whereNotNull('wl.loading_time')
            ->whereRaw($this->dateOnlyBetweenSql('wl.loading_time'), [$fromDate, $toDate])
            ->selectRaw('wl.loader_id, count(*) as total, sum(CASE WHEN rww.over_load_mt > 0 THEN 1 ELSE 0 END) as overloaded')
            ->groupBy('wl.loader_id')
            ->get()
            ->keyBy('loader_id');

        return $loaders->map(function (Loader $l) use ($stats): array {
            $s = $stats->get($l->id);
            $total = $s ? (int) $s->total : 0;
            $overloaded = $s ? (int) $s->overloaded : 0;
            $rate = $total > 0 ? round(($overloaded / $total) * 100, 1) : 0;

            return [
                'name' => $l->loader_name,
                'value' => $rate,
            ];
        })->values()->all();
    }

    private function parseExecutiveYesterdayDate(Request $request): CarbonInterface
    {
        $tz = config('app.timezone', 'UTC');
        $value = $request->query('executive_yesterday_date') ?? $request->input('executive_yesterday_date');
        if ($value === null || $value === '') {
            return now($tz)->subDay()->startOfDay();
        }

        return Carbon::parse((string) $value, $tz)->startOfDay();
    }

    /**
     * @return array<int, array{id: string, type: string, data: array<string, mixed>, read_at: string|null, created_at: string}>
     */
    private function buildDashboardNotifications(Request $request): array
    {
        $user = $request->user();
        if ($user === null || ! $user->isSuperAdmin()) {
            return [];
        }

        return $user->notifications()
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->map(fn ($n): array => [
                'id' => (string) $n->id,
                'type' => (string) $n->type,
                'data' => (array) ($n->data ?? []),
                'read_at' => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at?->toIso8601String() ?? now()->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    private function buildDashboardUnreadNotificationCount(Request $request): int
    {
        $user = $request->user();
        if ($user === null || ! $user->isSuperAdmin()) {
            return 0;
        }

        return $user->unreadNotifications()->count();
    }

    /**
     * Resolve date range from request. For GET/dashboard, from/to come from query string when period=custom.
     *
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    private function resolveDateRange(Request $request): array
    {
        $period = (string) $request->input('period', 'today');
        $tz = config('app.timezone', 'UTC');
        $to = now($tz)->endOfDay();

        $from = match ($period) {
            'today' => now($tz)->startOfDay(),
            'week' => now($tz)->startOfWeek(),
            'month' => now($tz)->startOfMonth(),
            'quarter' => now($tz)->startOfQuarter(),
            'year' => now($tz)->startOfYear(),
            'custom' => $this->parseRequestDate($request, 'from', $tz) ?? now($tz)->startOfMonth(),
            default => now($tz)->startOfMonth(),
        };

        if ($period === 'custom') {
            $parsedTo = $this->parseRequestDate($request, 'to', $tz);
            if ($parsedTo !== null) {
                $to = $parsedTo->copy()->endOfDay();
            }
        }

        return [$from, $to];
    }

    /**
     * Parse a date from request (query or input) in the given timezone.
     */
    private function parseRequestDate(Request $request, string $key, string $tz): ?Carbon
    {
        $value = $request->query($key) ?? $request->input($key);
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof CarbonInterface) {
            return Carbon::parse($value->format('Y-m-d'), $tz)->startOfDay();
        }

        return Carbon::parse($value, $tz)->startOfDay();
    }

    /**
     * Single date for Daily Rake Details section (default: yesterday).
     */
    private function parseDailyRakeDate(Request $request): CarbonInterface
    {
        $tz = config('app.timezone', 'UTC');
        $parsed = $this->parseRequestDate($request, 'daily_rake_date', $tz);

        return $parsed ?? now($tz)->subDay()->startOfDay();
    }

    /**
     * Single date for Coal Transport Report section only (default: yesterday). Does not affect Daily Rake or any other section.
     */
    private function parseCoalTransportDate(Request $request): CarbonInterface
    {
        $tz = config('app.timezone', 'UTC');
        $parsed = $this->parseRequestDate($request, 'coal_transport_date', $tz);

        return $parsed ?? now($tz)->subDay()->startOfDay();
    }

    /**
     * SQL fragment for date-only range (whole days) in app timezone. Use with bindings: [$from->toDateString(), $to->toDateString()].
     *
     * @param  bool  $columnIsPostgresDate  When true and driver is pgsql, the column is a PostgreSQL date type (calendar only). The
     *                                      AT TIME ZONE UTC → app pattern must not be used — it shifts calendar days (e.g. 19th → 18th in IST).
     */
    private function dateOnlyBetweenSql(string $column, bool $columnIsPostgresDate = false): string
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            if ($columnIsPostgresDate) {
                return "({$column})::date BETWEEN ? AND ?";
            }

            $tz = config('app.timezone', 'UTC');
            $tzEscaped = str_replace("'", "''", $tz);

            return "(({$column} AT TIME ZONE 'UTC' AT TIME ZONE '{$tzEscaped}')::date) BETWEEN ? AND ?";
        }

        return "DATE({$column}) BETWEEN ? AND ?";
    }

    /**
     * SQL fragment for rake "business date" in range: use loading_date when set, else created_at (app timezone).
     * Use with bindings: [$fromDate, $toDate]. Table prefix e.g. "rakes" for rakes.loading_date / rakes.created_at.
     */
    private function rakeBusinessDateBetweenSql(string $tablePrefix = 'rakes'): string
    {
        $driver = DB::getDriverName();
        $loadingDate = "{$tablePrefix}.loading_date";
        $createdAt = "{$tablePrefix}.created_at";

        if ($driver === 'pgsql') {
            $tz = config('app.timezone', 'UTC');
            $tzEscaped = str_replace("'", "''", $tz);
            $createdAtDate = "(({$createdAt} AT TIME ZONE 'UTC' AT TIME ZONE '{$tzEscaped}')::date)";

            return "(COALESCE({$loadingDate}, {$createdAtDate}) BETWEEN ? AND ?)";
        }

        return "COALESCE(DATE({$loadingDate}), DATE({$createdAt})) BETWEEN ? AND ?";
    }

    /**
     * Rake IDs matching siding + optional rake_number and power_plant filters (no date).
     *
     * @param  array<int>  $sidingIds
     * @return array<int>
     */
    private function getFilteredRakeIds(array $sidingIds, array $filterContext): array
    {
        $q = Rake::query()->whereIn('siding_id', $sidingIds);
        if (! empty($filterContext['rake_number'])) {
            $q->where('rake_number', 'like', '%'.$filterContext['rake_number'].'%');
        }
        if (! empty($filterContext['power_plant'])) {
            $q->whereIn('id', RakeWeighment::query()->where('to_station', $filterContext['power_plant'])->select('rake_id'));
        }

        return $q->pluck('id')->all();
    }
}
