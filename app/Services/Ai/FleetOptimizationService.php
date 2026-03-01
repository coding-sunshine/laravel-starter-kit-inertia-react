<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Ai\Agents\FleetOptimizationAgent;
use App\Models\Fleet\CostAllocation;
use App\Models\Fleet\Trip;
use App\Models\Fleet\Vehicle;
use Laravel\Ai\Responses\StructuredAgentResponse;

final class FleetOptimizationService
{
    public function __construct(
        private readonly FleetOptimizationAgent $agent
    ) {}

    /**
     * Generate fleet optimization analysis. Returns structured result for display or storage.
     *
     * @return array{right_sizing_summary: string, replacement_timing_summary: string, fleet_mix_summary: string, what_if_scenarios: array}|null
     */
    public function analyze(int $organizationId): ?array
    {
        $context = $this->buildContext($organizationId);
        $response = $this->agent->prompt($context);

        if (! $response instanceof StructuredAgentResponse) {
            return null;
        }

        $s = $response->structured;
        $scenarios = $s['what_if_scenarios'] ?? [];
        if (! is_array($scenarios)) {
            $scenarios = [];
        }

        return [
            'right_sizing_summary' => (string) ($s['right_sizing_summary'] ?? ''),
            'replacement_timing_summary' => (string) ($s['replacement_timing_summary'] ?? ''),
            'fleet_mix_summary' => (string) ($s['fleet_mix_summary'] ?? ''),
            'what_if_scenarios' => $scenarios,
        ];
    }

    private function buildContext(int $organizationId): string
    {
        $vehicles = Vehicle::query()
            ->where('organization_id', $organizationId)
            ->get(['id', 'registration', 'make', 'model', 'fuel_type', 'vehicle_type', 'status', 'odometer_reading', 'monthly_distance_km', 'monthly_fuel_cost'])
            ->map(fn ($v) => [
                'id' => $v->id,
                'registration' => $v->registration,
                'make' => $v->make,
                'model' => $v->model,
                'fuel_type' => $v->fuel_type,
                'vehicle_type' => $v->vehicle_type,
                'status' => $v->status,
                'odometer_reading' => $v->odometer_reading,
                'monthly_distance_km' => $v->monthly_distance_km,
                'monthly_fuel_cost' => $v->monthly_fuel_cost,
            ])->toArray();

        $tripCount = Trip::query()->where('organization_id', $organizationId)->count();
        $totalCost = (float) CostAllocation::query()->where('organization_id', $organizationId)->sum('amount');

        $data = [
            'vehicles' => $vehicles,
            'vehicle_count' => count($vehicles),
            'trip_count' => $tripCount,
            'total_allocated_cost' => $totalCost,
        ];

        return "Analyze this fleet and provide right_sizing_summary, replacement_timing_summary, fleet_mix_summary, and what_if_scenarios (2-3 scenarios with title, description, estimated_impact).\n\n" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
