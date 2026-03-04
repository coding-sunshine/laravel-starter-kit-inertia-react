<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Ai\Agents\FleetElectrificationAgent;
use App\Models\Fleet\CarbonTarget;
use App\Models\Fleet\EvChargingSession;
use App\Models\Fleet\SustainabilityGoal;
use App\Models\Fleet\Vehicle;
use Laravel\Ai\Responses\StructuredAgentResponse;

final readonly class FleetElectrificationService
{
    public function __construct(
        private FleetElectrificationAgent $agent
    ) {}

    /**
     * Generate electrification plan for the organization. Returns structured result for storage.
     *
     * @return array{readiness_score: float, replacement_order: array, charging_recommendations: array, tco_summary: array, milestones: array}|null
     */
    public function generate(int $organizationId): ?array
    {
        $context = $this->buildContext($organizationId);
        $response = $this->agent->prompt($context);

        if (! $response instanceof StructuredAgentResponse) {
            return null;
        }

        $s = $response->structured;

        return [
            'readiness_score' => (float) ($s['readiness_score'] ?? 0),
            'replacement_order' => $this->normalizeArray($s['replacement_order'] ?? []),
            'charging_recommendations' => $this->normalizeArray($s['charging_recommendations'] ?? []),
            'tco_summary' => is_array($s['tco_summary'] ?? null) ? [
                'current_tco' => (float) ($s['tco_summary']['current_tco'] ?? 0),
                'projected_ev_tco' => (float) ($s['tco_summary']['projected_ev_tco'] ?? 0),
                'savings' => (float) ($s['tco_summary']['savings'] ?? 0),
            ] : ['current_tco' => 0, 'projected_ev_tco' => 0, 'savings' => 0],
            'milestones' => $this->normalizeArray($s['milestones'] ?? []),
        ];
    }

    private function buildContext(int $organizationId): string
    {
        $vehicles = Vehicle::query()
            ->where('organization_id', $organizationId)
            ->get(['id', 'registration', 'make', 'model', 'fuel_type', 'vehicle_type', 'odometer_reading', 'monthly_distance_km', 'monthly_fuel_cost', 'co2_emissions'])
            ->map(fn ($v): array => [
                'id' => $v->id,
                'registration' => $v->registration,
                'make' => $v->make,
                'model' => $v->model,
                'fuel_type' => $v->fuel_type,
                'vehicle_type' => $v->vehicle_type,
                'odometer_reading' => $v->odometer_reading,
                'monthly_distance_km' => $v->monthly_distance_km,
                'monthly_fuel_cost' => $v->monthly_fuel_cost,
                'co2_emissions' => $v->co2_emissions,
            ])->all();

        $chargingSessions = EvChargingSession::query()->where('organization_id', $organizationId)->count();
        $chargingTotalKwh = (float) EvChargingSession::query()->where('organization_id', $organizationId)->sum('energy_delivered_kwh');

        $carbonTargets = CarbonTarget::query()
            ->where('organization_id', $organizationId)
            ->where('is_active', true)
            ->get(['name', 'period', 'target_year', 'target_co2_kg', 'baseline_co2_kg'])
            ->map(fn ($c): array => [
                'name' => $c->name,
                'period' => $c->period,
                'target_year' => $c->target_year,
                'target_co2_kg' => $c->target_co2_kg,
                'baseline_co2_kg' => $c->baseline_co2_kg,
            ])->all();

        $goals = SustainabilityGoal::query()
            ->where('organization_id', $organizationId)
            ->get(['title', 'status', 'target_date', 'target_value', 'target_unit'])
            ->map(fn ($g): array => [
                'title' => $g->title,
                'status' => $g->status,
                'target_date' => $g->target_date?->toDateString(),
                'target_value' => $g->target_value,
                'target_unit' => $g->target_unit,
            ])->all();

        $data = [
            'vehicles' => $vehicles,
            'ev_charging_sessions_count' => $chargingSessions,
            'ev_charging_total_kwh' => $chargingTotalKwh,
            'carbon_targets' => $carbonTargets,
            'sustainability_goals' => $goals,
        ];

        return "Generate a fleet electrification plan based on this data. Return readiness_score (0-100), replacement_order, charging_recommendations, tco_summary, and milestones.\n\n".json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function normalizeArray(mixed $arr): array
    {
        return is_array($arr) ? $arr : [];
    }
}
