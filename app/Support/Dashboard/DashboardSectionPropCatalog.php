<?php

declare(strict_types=1);

namespace App\Support\Dashboard;

use stdClass;

/**
 * Maps dashboard tab IDs to Inertia prop keys built for that tab (excluding globals).
 *
 * @see DashboardWidgetPermissions::orderedDashboardSectionIds()
 */
final class DashboardSectionPropCatalog
{
    /**
     * @return list<string>
     */
    public static function propKeysForSection(string $sectionId): array
    {
        return match ($sectionId) {
            'executive-overview' => [
                'executiveYesterday',
                'operatorRake',
                'penaltyPredictions',
                'sidingStocks',
                'penaltyBySiding',
                'powerPlantDispatch',
                'penaltySummary',
                'activeRakePipeline',
                'riskScores',
                'alerts',
                'predictedVsActualPenalty',
                'yesterdayPredictedPenalties',
                'overloadPatterns',
                'dateWiseDispatch',
                'dispatchSummaryByPeriod',
                'sidingWiseMonthly',
                'sidingRadar',
            ],
            'siding-overview' => [
                'sidingPerformance',
                'penaltyTrendDaily',
                'powerPlantDispatch',
            ],
            'operations' => [
                'coalTransportReport',
                'dailyRakeDetails',
                'truckReceiptTrend',
                'shiftWiseVehicleReceipt',
                'liveRakeStatus',
            ],
            'penalty-control' => [
                'penaltyByType',
                'penaltyBySiding',
                'penaltyControlRrCoverage',
                'executiveYesterday',
            ],
            'rake-performance', 'loader-overload' => [],
            'power-plant' => [
                'powerPlantDispatch',
            ],
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    public static function allDeferredPropKeys(): array
    {
        $seen = [];
        foreach (DashboardWidgetPermissions::orderedDashboardSectionIds() as $sectionId) {
            foreach (self::propKeysForSection($sectionId) as $key) {
                $seen[$key] = true;
            }
        }

        return array_keys($seen);
    }

    /**
     * Safe JSON/Inertia defaults for props omitted on initial or lazy loads.
     *
     * @return array<string, mixed>
     */
    public static function defaultDeferredProps(): array
    {
        $defaults = [];
        foreach (self::allDeferredPropKeys() as $key) {
            $defaults[$key] = self::defaultValueForKey($key);
        }

        return $defaults;
    }

    /**
     * @return array<int, string>
     */
    public static function allowedSectionIds(): array
    {
        return DashboardWidgetPermissions::orderedDashboardSectionIds();
    }

    private static function defaultValueForKey(string $key): mixed
    {
        return match ($key) {
            'penaltyTrendDaily' => [
                'series' => [],
                'points' => [],
            ],
            'predictedVsActualPenalty' => [
                'predicted' => 0,
                'actual' => 0,
                'bySiding' => [],
            ],
            'dateWiseDispatch' => [
                'sidingNames' => new stdClass,
                'dates' => [],
            ],
            'dispatchSummaryByPeriod' => [
                'default_period' => 'today',
                'periods' => [
                    'today' => ['received_mt' => 0.0, 'dispatched_mt' => 0.0, 'from' => '', 'to' => ''],
                    'yesterday' => ['received_mt' => 0.0, 'dispatched_mt' => 0.0, 'from' => '', 'to' => ''],
                    'month' => ['received_mt' => 0.0, 'dispatched_mt' => 0.0, 'from' => '', 'to' => ''],
                    'last_month' => ['received_mt' => 0.0, 'dispatched_mt' => 0.0, 'from' => '', 'to' => ''],
                    'fy' => ['received_mt' => 0.0, 'dispatched_mt' => 0.0, 'from' => '', 'to' => ''],
                ],
            ],
            'sidingRadar' => [
                'sidings' => [],
            ],
            'rakePerformance' => [],
            'loaderOverloadTrends' => [
                'loaders' => [],
                'monthly' => [],
            ],
            'coalTransportReport', 'dailyRakeDetails', 'executiveYesterday' => null,
            'penaltyByType', 'penaltyBySiding', 'liveRakeStatus', 'truckReceiptTrend',
            'shiftWiseVehicleReceipt', 'powerPlantDispatch', 'sidingPerformance',
            'yesterdayPredictedPenalties', 'penaltyPredictions', 'overloadPatterns',
            'sidingWiseMonthly' => [],
            'penaltyControlRrCoverage' => [
                'total_rakes' => 0,
                'rakes_with_rr' => 0,
                'rakes_without_rr' => 0,
            ],
            'operatorRake', 'penaltySummary', 'activeRakePipeline' => null,
            'riskScores' => new stdClass,
            'alerts' => [],
            'sidingStocks' => new stdClass,
            default => [],
        };
    }
}
