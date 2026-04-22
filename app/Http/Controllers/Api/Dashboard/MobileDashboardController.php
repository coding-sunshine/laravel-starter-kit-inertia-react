<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dashboard\ExecutiveDashboardController;
use App\Models\PenaltyType;
use App\Support\Dashboard\DashboardFilterResolver;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MobileDashboardController extends Controller
{
    public function __construct(
        private readonly DashboardFilterResolver $filters,
        private readonly ExecutiveDashboardController $dashboard,
    ) {}

    /**
     * Lists for dashboard filter pickers (power plants, loaders, shifts, penalty types).
     * Optional query: siding_ids (comma-separated) or siding_id — scopes power plants and loaders; shifts and penalty types are global.
     */
    public function filterOptions(Request $request): JsonResponse
    {
        $resolved = $this->filters->resolve($request);
        $sidingIds = $resolved['filteredSidingIds'];
        $section = $this->resolveSection($request);
        $options = $this->resolveFilterOptionsForSection($section, $sidingIds);

        $penaltyTypes = PenaltyType::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn (PenaltyType $pt): array => [
                'id' => $pt->id,
                'code' => $pt->code,
                'name' => $pt->name,
            ])
            ->values()
            ->all();

        return response()->json([
            'siding_ids' => array_values($sidingIds),
            'meta' => [
                'section' => $section,
                'executive_local_filters' => null,
            ],
            'data' => [
                'power_plants' => $options['power_plants'],
                'loaders' => $options['loaders'],
                'shifts' => $options['shifts'],
                'penalty_types' => $options['penalty_types'] ? $penaltyTypes : [],
            ],
        ]);
    }

    /**
     * Mobile-only KPIs for Admin/Superadmin dashboard (today only, IST).
     *
     * - Date is always today (`filters.date`); no period/from/to query params.
     * - Superadmin: optional `siding_ids` / `siding_id` to narrow; default = all sidings.
     * - Admin (and others): scoped to `users.siding_id` when set; else pivot-assigned sidings. Request siding params only intersect.
     */
    public function adminKpis(Request $request): JsonResponse
    {
        $sidingScope = $this->filters->resolveAdminKpiSidings($request);
        $sidingIds = $sidingScope['filteredSidingIds'];

        $tz = config('app.timezone', 'UTC');
        $from = now($tz)->startOfDay();
        $to = now($tz)->endOfDay();

        $filterContext = [
            'period' => 'today',
            'power_plant' => null,
            'rake_number' => null,
            'loader_id' => null,
            'shift' => null,
            'penalty_type_id' => null,
        ];

        $kpis = $this->dashboard->buildKpis($sidingIds, $from, $to, $filterContext);
        $activeRakes = $this->dashboard->buildLiveRakeStatus($sidingIds, $filterContext, $from, $to);
        $sidingStocks = $this->dashboard->buildSidingStocks($sidingIds, $from, $to);

        $coalBalanceMt = 0.0;
        foreach ($sidingStocks as $stock) {
            $coalBalanceMt += (float) ($stock['closing_balance_mt'] ?? 0);
        }

        return response()->json([
            'filters' => [
                'date' => $from->toDateString(),
                'siding_ids' => array_values($sidingIds),
            ],
            'data' => [
                'active_rakes' => count($activeRakes),
                'coal_dispatched_mt' => (float) ($kpis['coalDispatchedToday'] ?? 0),
                'coal_balance_mt' => $coalBalanceMt,
                'penalty_risk' => (float) ($kpis['predictedPenaltyRisk'] ?? 0),
            ],
        ]);
    }

    public function executive(Request $request): JsonResponse
    {
        $resolved = $this->filters->resolve($request);
        $anchorDate = $this->parseExecutiveYesterdayDate($request);
        $customRanges = $this->parseExecutiveCustomRanges($request, $anchorDate);
        $payload = $this->dashboard->buildExecutiveYesterdayData($resolved['allSidingIds'], $anchorDate, $customRanges);

        return response()->json($this->buildExecutiveApiPayload($request, $payload));
    }

    public function executiveCustomRange(Request $request): JsonResponse
    {
        $resolved = $this->filters->resolve($request);
        $anchorDate = $this->parseExecutiveYesterdayDate($request);
        $customRanges = $this->parseExecutiveCustomRanges($request, $anchorDate);
        $scopeRaw = $request->query('executive_apply_scope');

        if (! is_string($scopeRaw) || $scopeRaw === '') {
            return response()->json(
                $this->dashboard->buildExecutiveYesterdayData($resolved['allSidingIds'], $anchorDate, $customRanges),
            );
        }

        $scopes = array_values(array_filter(array_map('trim', explode(',', $scopeRaw))));
        $allowed = ['road', 'rail', 'ob', 'coal'];
        $scopes = array_values(array_intersect($scopes, $allowed));
        if ($scopes === []) {
            abort(422, 'Invalid executive_apply_scope.');
        }

        $payload = $this->dashboard->buildExecutiveYesterdayData($resolved['allSidingIds'], $anchorDate, $customRanges);
        $allCustomRanges = is_array($payload['customRanges'] ?? null) ? $payload['customRanges'] : [];
        $scopeToRangeKey = [
            'road' => 'roadDispatch',
            'rail' => 'railDispatch',
            'ob' => 'obProduction',
            'coal' => 'coalProduction',
        ];
        $scopedCustomRanges = [];
        foreach ($scopes as $scope) {
            $rangeKey = $scopeToRangeKey[$scope] ?? null;
            if ($rangeKey !== null && array_key_exists($rangeKey, $allCustomRanges)) {
                $scopedCustomRanges[$rangeKey] = $allCustomRanges[$rangeKey];
            }
        }

        return response()->json([
            'customRanges' => $scopedCustomRanges,
        ]);
    }

    /**
     * Same widgets as web "Siding overview" section.
     */
    public function sidingOverview(Request $request): JsonResponse
    {
        $resolved = $this->filters->resolve($request);

        $data = [
            'kpis' => $this->dashboard->buildKpis($resolved['filteredSidingIds'], $resolved['from'], $resolved['to'], $resolved['filterContext']),
            'sidingStocks' => $this->dashboard->buildSidingStocks($resolved['filteredSidingIds'], $resolved['from'], $resolved['to']),
            'sidingPerformance' => $this->dashboard->buildSidingPerformance($resolved['filteredSidingIds'], $resolved['from'], $resolved['to'], $resolved['filterContext']),
            'penaltyTrendDaily' => $this->dashboard->buildPenaltyTrendDaily($resolved['filteredSidingIds'], $resolved['from'], $resolved['to'], $resolved['filterContext']),
            'powerPlantDispatch' => $this->dashboard->buildPowerPlantDispatch(
                $resolved['filteredSidingIds'],
                $resolved['from'],
                $resolved['to'],
                $resolved['filterContext'],
            ),
        ];

        return response()->json([
            'filters' => $this->serializeFilters($resolved),
            'data' => $data,
        ]);
    }

    /**
     * Backward-compatible alias for existing mobile clients.
     */
    public function executiveOverview(Request $request): JsonResponse
    {
        return $this->sidingOverview($request);
    }

    public function operations(Request $request): JsonResponse
    {
        $resolved = $this->filters->resolve($request);

        $data = [
            'coalTransportReport' => $this->dashboard->buildCoalTransportReport(
                $resolved['filteredSidingIds'],
                $resolved['coalTransportDate'],
                $resolved['filterContext']['shift'] ?? null,
            ),
            'truckReceiptTrend' => $this->dashboard->buildTruckReceiptTrend($resolved['filteredSidingIds'], $resolved['filterContext']),
            'shiftWiseVehicleReceipt' => $this->dashboard->buildShiftWiseVehicleReceipt(
                $resolved['filteredSidingIds'],
                $resolved['filterContext']['shift'] ?? null,
            ),
            'dailyRakeDetails' => $this->dashboard->buildDailyRakeDetails($resolved['filteredSidingIds'], $resolved['dailyRakeDate']),
            'liveRakeStatus' => $this->dashboard->buildLiveRakeStatus(
                $resolved['filteredSidingIds'],
                [],
                $resolved['from'],
                $resolved['to'],
            ),
        ];

        return response()->json([
            'filters' => $this->serializeFilters($resolved),
            'data' => $data,
        ]);
    }

    /**
     * Same widgets as web "Penalty control" section (no executive-only or unused chart bundles).
     */
    public function penaltyControl(Request $request): JsonResponse
    {
        $resolved = $this->filters->resolve($request);

        $data = [
            'penaltyByType' => $this->dashboard->buildPenaltyByType($resolved['filteredSidingIds'], $resolved['from'], $resolved['to'], $resolved['filterContext']),
            'yesterdayPredictedPenalties' => $this->dashboard->buildYesterdayPredictedPenalties($resolved['allSidingIds']),
            'penaltyBySiding' => $this->dashboard->buildPenaltyBySiding($resolved['filteredSidingIds'], $resolved['from'], $resolved['to'], $resolved['filterContext']),
            'predictedVsActualPenalty' => $this->dashboard->buildPredictedVsActualPenalty($resolved['filteredSidingIds'], $resolved['from'], $resolved['to'], $resolved['filterContext']),
        ];

        return response()->json([
            'filters' => $this->serializeFilters($resolved),
            'data' => $data,
        ]);
    }

    public function rakePerformance(Request $request): JsonResponse
    {
        $resolved = $this->filters->resolve($request);

        $data = [
            'rakePerformance' => $this->dashboard->buildRakePerformance($resolved['filteredSidingIds'], $resolved['from'], $resolved['to'], $resolved['filterContext']),
        ];

        return response()->json([
            'filters' => $this->serializeFilters($resolved),
            'data' => $data,
        ]);
    }

    public function loaderOverload(Request $request): JsonResponse
    {
        $resolved = $this->filters->resolve($request);

        return response()->json([
            'filters' => $this->serializeFilters($resolved),
            'data' => [
                'loaderOverloadTrends' => $this->dashboard->buildLoaderOverloadTrends(
                    $resolved['filteredSidingIds'],
                    $resolved['from'],
                    $resolved['to'],
                    $resolved['filterContext'],
                ),
            ],
        ]);
    }

    public function powerPlant(Request $request): JsonResponse
    {
        $resolved = $this->filters->resolve($request);

        $dispatch = $this->dashboard->buildPowerPlantDispatch(
            $resolved['filteredSidingIds'],
            $resolved['from'],
            $resolved['to'],
            $resolved['filterContext'],
        );

        return response()->json([
            'filters' => $this->serializeFilters($resolved),
            'data' => [
                'powerPlantDispatch' => $dispatch,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $resolved
     * @return array<string, mixed>
     */
    private function serializeFilters(array $resolved): array
    {
        return [
            'period' => $resolved['period'],
            'from' => $resolved['from']->toDateString(),
            'to' => $resolved['to']->toDateString(),
            'siding_ids' => array_values($resolved['filteredSidingIds']),
            'power_plant' => $resolved['powerPlant'],
            'rake_number' => $resolved['rakeNumber'],
            'loader_id' => $resolved['loaderId'],
            'loader_operator' => $resolved['loaderOperatorName'],
            'underload_threshold' => $resolved['underloadThresholdPercent'],
            'shift' => $resolved['shift'],
            'penalty_type' => $resolved['penaltyTypeId'],
            'daily_rake_date' => $resolved['dailyRakeDate']->toDateString(),
            'coal_transport_date' => $resolved['coalTransportDate']->toDateString(),
            'section' => $resolved['section'],
        ];
    }

    private function resolveSection(Request $request): string
    {
        $section = (string) ($request->query('section') ?? $request->input('section') ?? 'executive-overview');
        $allowed = [
            'executive-overview',
            'siding-overview',
            'operations',
            'penalty-control',
            'rake-performance',
            'loader-overload',
            'power-plant',
        ];

        if (! in_array($section, $allowed, true)) {
            return 'executive-overview';
        }

        return $section;
    }

    /**
     * @return array{power_plants: array<int, mixed>, loaders: array<int, mixed>, shifts: array<int, mixed>, penalty_types: bool}
     */
    private function resolveFilterOptionsForSection(string $section, array $sidingIds): array
    {
        $opts = $this->dashboard->buildFilterOptions($sidingIds);
        $sectionFilterKeys = [
            'executive-overview' => ['power_plant', 'rake_number', 'penalty_type'],
            'siding-overview' => ['power_plant', 'rake_number', 'penalty_type'],
            'operations' => ['shift', 'daily_rake_date', 'coal_transport_date'],
            'penalty-control' => ['penalty_type'],
            'rake-performance' => ['rake_number', 'power_plant', 'rake_penalty_scope'],
            'loader-overload' => ['loader_id'],
            'power-plant' => ['power_plant'],
        ];
        $keys = $sectionFilterKeys[$section] ?? [];

        return [
            'power_plants' => in_array('power_plant', $keys, true) ? $opts['powerPlants'] : [],
            'loaders' => in_array('loader_id', $keys, true) ? $opts['loaders'] : [],
            'shifts' => in_array('shift', $keys, true) ? $opts['shifts'] : [],
            'penalty_types' => in_array('penalty_type', $keys, true),
        ];
    }

    private function parseExecutiveYesterdayDate(Request $request): CarbonInterface
    {
        $tz = config('app.timezone', 'UTC');
        $value = $request->query('executive_yesterday_date') ?? $request->input('executive_yesterday_date');
        if ($value === null || $value === '') {
            return now($tz)->startOfDay();
        }

        return Carbon::parse((string) $value, $tz)->startOfDay();
    }

    /**
     * @return array{
     *     roadDispatch: array{from: string, to: string},
     *     railDispatch: array{from: string, to: string},
     *     obProduction: array{from: string, to: string},
     *     coalProduction: array{from: string, to: string}
     * }
     */
    private function parseExecutiveCustomRanges(Request $request, CarbonInterface $anchorDate): array
    {
        $tz = config('app.timezone', 'UTC');
        $anchor = Carbon::parse($anchorDate, $tz)->startOfDay();
        $defaultDay = $anchor->copy()->subDay();
        $defaultFrom = $defaultDay->copy()->startOfDay();
        $defaultTo = $defaultDay->copy()->endOfDay();
        $sharedFrom = $this->parseRequestDate($request, 'executive_from', $tz);
        $sharedTo = $this->parseRequestDate($request, 'executive_to', $tz);
        $roadRailDefaultFrom = $sharedFrom ?? $defaultFrom;
        $roadRailDefaultTo = $sharedTo ?? $defaultTo;

        $road = $this->parseRange($request, 'executive_road_from', 'executive_road_to', $roadRailDefaultFrom, $roadRailDefaultTo, $tz);
        $rail = $this->parseRange($request, 'executive_rail_from', 'executive_rail_to', $roadRailDefaultFrom, $roadRailDefaultTo, $tz);
        $ob = $this->parseRange($request, 'executive_ob_from', 'executive_ob_to', $defaultFrom, $defaultTo, $tz);
        $coal = $this->parseRange($request, 'executive_coal_from', 'executive_coal_to', $defaultFrom, $defaultTo, $tz);

        return [
            'roadDispatch' => ['from' => $road['from']->toDateString(), 'to' => $road['to']->toDateString()],
            'railDispatch' => ['from' => $rail['from']->toDateString(), 'to' => $rail['to']->toDateString()],
            'obProduction' => ['from' => $ob['from']->toDateString(), 'to' => $ob['to']->toDateString()],
            'coalProduction' => ['from' => $coal['from']->toDateString(), 'to' => $coal['to']->toDateString()],
        ];
    }

    /**
     * @return array{from: Carbon, to: Carbon}
     */
    private function parseRange(
        Request $request,
        string $fromKey,
        string $toKey,
        Carbon $defaultFrom,
        Carbon $defaultTo,
        string $tz,
    ): array {
        $from = $this->parseRequestDate($request, $fromKey, $tz) ?? $defaultFrom->copy();
        $to = $this->parseRequestDate($request, $toKey, $tz) ?? $defaultTo->copy();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return ['from' => $from->copy()->startOfDay(), 'to' => $to->copy()->endOfDay()];
    }

    private function parseRequestDate(Request $request, string $key, string $tz): ?Carbon
    {
        $value = $request->query($key) ?? $request->input($key);
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof CarbonInterface) {
            return Carbon::parse($value->format('Y-m-d'), $tz)->startOfDay();
        }

        return Carbon::parse((string) $value, $tz)->startOfDay();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function buildExecutiveApiPayload(Request $request, array $payload): array
    {
        $graphFilters = [
            'road' => [
                'period' => $this->readGraphEnum($request, 'road_period', ['yesterday', 'today', 'month', 'fy'], 'month'),
                'metric' => $this->readGraphEnum($request, 'road_metric', ['count', 'qty'], 'qty'),
            ],
            'rail' => [
                'period' => $this->readGraphEnum($request, 'rail_period', ['yesterday', 'today', 'month', 'fy'], 'month'),
                'metric' => $this->readGraphEnum($request, 'rail_metric', ['count', 'qty'], 'qty'),
            ],
            'production' => [
                'period' => $this->readGraphEnum($request, 'production_period', ['yesterday', 'today', 'month', 'fy'], 'month'),
                'metric' => $this->readGraphEnum($request, 'production_metric', ['trips', 'qty'], 'qty'),
            ],
            'penalty' => [
                'period' => $this->readGraphEnum($request, 'penalty_period', ['yesterday', 'today', 'month', 'fy'], 'month'),
            ],
            'power_plant' => [
                'period' => $this->readGraphEnum($request, 'power_plant_period', ['yesterday', 'today', 'month', 'fy'], 'month'),
                'metric' => $this->readGraphEnum($request, 'power_plant_metric', ['rakes', 'qty'], 'qty'),
            ],
        ];

        $tables = [
            'anchorDate' => $payload['anchorDate'] ?? null,
            'fyLabel' => $payload['fyLabel'] ?? null,
            'periods' => $payload['periods'] ?? [],
            'roadDispatch' => $payload['roadDispatch'] ?? [],
            'railDispatch' => $payload['railDispatch'] ?? [],
            'obProduction' => $payload['obProduction'] ?? [],
            'coalProduction' => $payload['coalProduction'] ?? [],
            'customRanges' => $payload['customRanges'] ?? [],
            'fySummary' => $payload['fySummary'] ?? [],
        ];

        $graphs = [
            'roadDispatchByPeriod' => $payload['roadDispatch'] ?? [],
            'railDispatchByPeriod' => $payload['railDispatch'] ?? [],
            'productionByPeriod' => [
                'obProduction' => $payload['obProduction'] ?? [],
                'coalProduction' => $payload['coalProduction'] ?? [],
            ],
            'penaltyBySidingByPeriod' => $payload['penaltyBySidingByPeriod'] ?? [],
            'powerPlantDispatchByPeriod' => $payload['powerPlantDispatchByPeriod'] ?? [],
            'fyCharts' => $payload['fyCharts'] ?? [],
        ];

        return [
            'meta' => [
                'defaults' => [
                    'period' => 'month',
                    'view_mode' => 'charts',
                ],
                'graph_filters' => $graphFilters,
            ],
            'data' => [
                'tables' => $tables,
                'graphs' => $graphs,
            ],
        ];
    }

    /**
     * @param  list<string>  $allowed
     */
    private function readGraphEnum(Request $request, string $key, array $allowed, string $default): string
    {
        $value = (string) ($request->query($key) ?? $request->input($key) ?? '');
        if ($value === '' || ! in_array($value, $allowed, true)) {
            return $default;
        }

        return $value;
    }
}
