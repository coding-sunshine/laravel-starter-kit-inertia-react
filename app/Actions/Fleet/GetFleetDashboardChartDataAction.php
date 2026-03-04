<?php

declare(strict_types=1);

namespace App\Actions\Fleet;

use App\Models\Fleet\AiAnalysisResult;
use App\Models\Fleet\Alert;
use App\Models\Fleet\Driver;
use App\Models\Fleet\FuelTransaction;
use App\Models\Fleet\InsurancePolicy;
use App\Models\Fleet\ServiceSchedule;
use App\Models\Fleet\Trip;
use App\Models\Fleet\Vehicle;
use App\Models\Fleet\WorkOrder;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

/**
 * Compute chart data, KPI trends, sparklines, and operational props for the fleet dashboard.
 *
 * @return array<string, mixed>
 */
final readonly class GetFleetDashboardChartDataAction
{
    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $now = Date::now();
        $thisWeekStart = $now->copy()->subDays(7)->startOfDay();
        $lastWeekStart = $now->copy()->subDays(14)->startOfDay();

        return [
            'kpiTrends' => $this->computeKpiTrends($thisWeekStart, $lastWeekStart, $now),
            'kpiSparklines' => $this->computeKpiSparklines($now),
            'chartFleetActivity' => $this->computeFleetActivity($now),
            'chartCostBreakdown' => $this->computeCostBreakdown($now),
            'chartFuelCostTrend' => $this->computeFuelCostTrend($now),
            'chartDriverSafetyDistribution' => $this->computeDriverSafetyDistribution(),
            'aiPredictions' => $this->computeAiPredictions(),
            'upcomingMaintenance' => $this->computeUpcomingMaintenance($now),
        ];
    }

    /**
     * @return array{vehicles: array{current: int, previous: int, change: float, direction: string}, trips: array{current: int, previous: int, change: float, direction: string}, work_orders: array{current: int, previous: int, change: float, direction: string}, alerts: array{current: int, previous: int, change: float, direction: string}}
     */
    private function computeKpiTrends(CarbonInterface $thisWeekStart, CarbonInterface $lastWeekStart, CarbonInterface $now): array
    {
        return [
            'vehicles' => $this->trendForModel(Vehicle::class, 'created_at', $thisWeekStart, $lastWeekStart, $now),
            'trips' => $this->trendForModel(Trip::class, 'started_at', $thisWeekStart, $lastWeekStart, $now),
            'work_orders' => $this->trendForModel(WorkOrder::class, 'created_at', $thisWeekStart, $lastWeekStart, $now),
            'alerts' => $this->trendForModel(Alert::class, 'created_at', $thisWeekStart, $lastWeekStart, $now),
        ];
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     * @return array{current: int, previous: int, change: float, direction: string}
     */
    private function trendForModel(string $model, string $dateColumn, CarbonInterface $thisWeekStart, CarbonInterface $lastWeekStart, CarbonInterface $now): array
    {
        $current = $model::query()
            ->where($dateColumn, '>=', $thisWeekStart)
            ->where($dateColumn, '<=', $now)
            ->count();

        $previous = $model::query()
            ->where($dateColumn, '>=', $lastWeekStart)
            ->where($dateColumn, '<', $thisWeekStart)
            ->count();

        $change = $previous > 0
            ? round((($current - $previous) / $previous) * 100, 1)
            : ($current > 0 ? 100.0 : 0.0);

        $direction = $current > $previous ? 'up' : ($current < $previous ? 'down' : 'flat');

        return [
            'current' => $current,
            'previous' => $previous,
            'change' => $change,
            'direction' => $direction,
        ];
    }

    /**
     * @return array{vehicles: list<int>, trips: list<int>, work_orders: list<int>, alerts: list<int>}
     */
    private function computeKpiSparklines(CarbonInterface $now): array
    {
        $sparklines = ['vehicles' => [], 'trips' => [], 'work_orders' => [], 'alerts' => []];

        for ($i = 6; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i)->toDateString();

            $sparklines['vehicles'][] = Vehicle::query()->whereDate('created_at', $date)->count();
            $sparklines['trips'][] = Trip::query()->whereDate('started_at', $date)->count();
            $sparklines['work_orders'][] = WorkOrder::query()->whereDate('created_at', $date)->count();
            $sparklines['alerts'][] = Alert::query()->whereDate('created_at', $date)->count();
        }

        return $sparklines;
    }

    /**
     * @return list<array{date: string, label: string, trips: int, work_orders: int}>
     */
    private function computeFleetActivity(CarbonInterface $now): array
    {
        $activity = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $dateString = $date->toDateString();

            $activity[] = [
                'date' => $dateString,
                'label' => $date->format('M j'),
                'trips' => Trip::query()->whereDate('started_at', $dateString)->count(),
                'work_orders' => WorkOrder::query()->whereDate('created_at', $dateString)->count(),
            ];
        }

        return $activity;
    }

    /**
     * @return list<array{name: string, value: float}>
     */
    private function computeCostBreakdown(CarbonInterface $now): array
    {
        $thirtyDaysAgo = $now->copy()->subDays(30);

        $fuelCost = (float) FuelTransaction::query()
            ->where('transaction_timestamp', '>=', $thirtyDaysAgo)
            ->sum('total_cost');

        $maintenanceCost = (float) WorkOrder::query()
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->whereIn('status', ['completed', 'in_progress'])
            ->sum('total_cost');

        $insuranceCost = (float) InsurancePolicy::query()
            ->where('status', 'active')
            ->sum('premium_amount');

        return [
            ['name' => 'Fuel', 'value' => round($fuelCost, 2)],
            ['name' => 'Maintenance', 'value' => round($maintenanceCost, 2)],
            ['name' => 'Insurance', 'value' => round($insuranceCost, 2)],
        ];
    }

    /**
     * @return list<array{date: string, label: string, cost: float}>
     */
    private function computeFuelCostTrend(CarbonInterface $now): array
    {
        $trend = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $dateString = $date->toDateString();

            $cost = (float) FuelTransaction::query()
                ->whereDate('transaction_timestamp', $dateString)
                ->sum('total_cost');

            $trend[] = [
                'date' => $dateString,
                'label' => $date->format('M j'),
                'cost' => round($cost, 2),
            ];
        }

        return $trend;
    }

    /**
     * @return list<array{name: string, value: int}>
     */
    private function computeDriverSafetyDistribution(): array
    {
        $excellent = Driver::query()->where('safety_score', '>=', 90)->count();
        $good = Driver::query()->where('safety_score', '>=', 70)->where('safety_score', '<', 90)->count();
        $needsAttention = Driver::query()->where('safety_score', '<', 70)->count();

        return [
            ['name' => 'Excellent (90+)', 'value' => $excellent],
            ['name' => 'Good (70-89)', 'value' => $good],
            ['name' => 'Needs Attention (<70)', 'value' => $needsAttention],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function computeAiPredictions(): array
    {
        return AiAnalysisResult::query()
            ->latest()
            ->limit(5)
            ->get(['id', 'priority', 'primary_finding', 'analysis_type', 'entity_type', 'entity_id'])
            ->map(fn (AiAnalysisResult $result): array => [
                'id' => $result->id,
                'priority' => $result->priority,
                'primary_finding' => $result->primary_finding,
                'analysis_type' => $result->analysis_type,
                'entity_type' => $result->entity_type,
                'entity_id' => $result->entity_id,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id: int, service_type: string, next_service_due_date: string, vehicle_name: string}>
     */
    private function computeUpcomingMaintenance(CarbonInterface $now): array
    {
        return ServiceSchedule::query()
            ->with('vehicle:id,registration,make,model')
            ->where('is_active', true)
            ->whereNotNull('next_service_due_date')
            ->where('next_service_due_date', '>=', $now->toDateString())
            ->where('next_service_due_date', '<=', $now->copy()->addDays(14)->toDateString())
            ->oldest('next_service_due_date')
            ->limit(5)
            ->get()
            ->map(fn (ServiceSchedule $schedule): array => [
                'id' => $schedule->id,
                'service_type' => $schedule->service_type,
                'next_service_due_date' => $schedule->next_service_due_date?->toDateString() ?? '',
                'vehicle_name' => $schedule->vehicle
                    ? mb_trim($schedule->vehicle->make.' '.$schedule->vehicle->model.' ('.$schedule->vehicle->registration.')')
                    : 'Unknown',
            ])
            ->values()
            ->all();
    }
}
