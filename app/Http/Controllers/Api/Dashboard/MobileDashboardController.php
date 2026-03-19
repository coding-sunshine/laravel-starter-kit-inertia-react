<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dashboard\ExecutiveDashboardController;
use App\Models\PenaltyType;
use App\Support\Dashboard\DashboardFilterResolver;
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

        $opts = $this->dashboard->buildFilterOptions($sidingIds);

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
            'data' => [
                'power_plants' => $opts['powerPlants'],
                'loaders' => $opts['loaders'],
                'shifts' => $opts['shifts'],
                'penalty_types' => $penaltyTypes,
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
        $activeRakes = $this->dashboard->buildLiveRakeStatus($sidingIds, $filterContext);
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

    /**
     * Same widgets as web "Executive overview" section, plus KPI strip and coal-stock cards (shown above all sections on web).
     */
    public function executiveOverview(Request $request): JsonResponse
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
            'liveRakeStatus' => $this->dashboard->buildLiveRakeStatus($resolved['filteredSidingIds'], $resolved['filterContext']),
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
            'shift' => $resolved['shift'],
            'penalty_type' => $resolved['penaltyTypeId'],
            'daily_rake_date' => $resolved['dailyRakeDate']->toDateString(),
            'coal_transport_date' => $resolved['coalTransportDate']->toDateString(),
            'section' => $resolved['section'],
        ];
    }
}
