<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Ai\Agents\PredictiveMaintenanceAgent;
use App\Models\Fleet\Defect;
use App\Models\Fleet\ServiceSchedule;
use App\Models\Fleet\Vehicle;
use App\Models\Fleet\WorkOrder;
use App\Models\Scopes\OrganizationScope;
use Laravel\Ai\Responses\StructuredAgentResponse;

final readonly class PredictiveMaintenanceService
{
    public function __construct(
        private PredictiveMaintenanceAgent $agent
    ) {}

    /**
     * Run predictive maintenance analysis for an organization. Uses organization_id
     * so it is safe to call from queued jobs (no tenant context).
     *
     * @return array{findings: array<int, array{vehicle_id: int, component: string, reason: string, recommended_action: string, urgency: string, confidence: float}>}
     */
    public function run(int $organizationId, ?array $vehicleIds = null): array
    {
        $context = $this->buildContext($organizationId, $vehicleIds);
        $response = $this->agent->prompt($context);

        if (! $response instanceof StructuredAgentResponse) {
            return ['findings' => []];
        }

        $structured = $response->structured;
        $findings = $structured['findings'] ?? [];
        if (! is_array($findings)) {
            return ['findings' => []];
        }

        $normalized = [];
        foreach ($findings as $f) {
            if (! is_array($f)) {
                continue;
            }
            $vehicleId = isset($f['vehicle_id']) ? (int) $f['vehicle_id'] : 0;
            if ($vehicleId < 1) {
                continue;
            }
            $normalized[] = [
                'vehicle_id' => $vehicleId,
                'component' => (string) ($f['component'] ?? ''),
                'reason' => (string) ($f['reason'] ?? ''),
                'recommended_action' => (string) ($f['recommended_action'] ?? ''),
                'urgency' => $this->normalizeUrgency((string) ($f['urgency'] ?? 'medium')),
                'confidence' => (float) ($f['confidence'] ?? 0.5),
            ];
        }

        return ['findings' => $normalized];
    }

    private function buildContext(int $organizationId, ?array $vehicleIds): string
    {
        $base = fn ($model) => $model::withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $organizationId);

        $vehicleQuery = $base(Vehicle::class)->select('id', 'registration', 'make', 'model', 'odometer_reading', 'status');
        if ($vehicleIds !== null && $vehicleIds !== []) {
            $vehicleQuery->whereIn('id', $vehicleIds);
        }
        $vehicles = $vehicleQuery->get()->map(fn ($v): array => [
            'id' => $v->id,
            'registration' => $v->registration,
            'make' => $v->make,
            'model' => $v->model,
            'odometer_reading' => $v->odometer_reading,
            'status' => $v->status,
        ])->toArray();

        $workOrderQuery = $base(WorkOrder::class)->where(function ($q): void {
            $q->whereIn('status', ['open', 'pending', 'in_progress', 'scheduled'])
                ->orWhere('completed_date', '>=', \Illuminate\Support\Facades\Date::now()->subDays(30));
        });
        if ($vehicleIds !== null && $vehicleIds !== []) {
            $workOrderQuery->whereIn('vehicle_id', $vehicleIds);
        }
        $workOrders = $workOrderQuery->get()->map(fn ($w): array => [
            'id' => $w->id,
            'vehicle_id' => $w->vehicle_id,
            'work_order_number' => $w->work_order_number,
            'title' => $w->title,
            'work_type' => $w->work_type,
            'status' => $w->status,
            'scheduled_date' => $w->scheduled_date?->toIso8601String(),
            'due_date' => $w->due_date?->toIso8601String(),
            'completed_date' => $w->completed_date?->toIso8601String(),
        ])->toArray();

        $scheduleQuery = $base(ServiceSchedule::class)->where('is_active', true);
        if ($vehicleIds !== null && $vehicleIds !== []) {
            $scheduleQuery->whereIn('vehicle_id', $vehicleIds);
        }
        $schedules = $scheduleQuery->get()->map(fn ($s): array => [
            'id' => $s->id,
            'vehicle_id' => $s->vehicle_id,
            'service_type' => $s->service_type,
            'interval_type' => $s->interval_type,
            'interval_value' => $s->interval_value,
            'interval_unit' => $s->interval_unit,
            'last_service_date' => $s->last_service_date?->toIso8601String(),
            'last_service_mileage' => $s->last_service_mileage,
            'next_service_due_date' => $s->next_service_due_date?->toIso8601String(),
            'next_service_due_mileage' => $s->next_service_due_mileage,
        ])->toArray();

        $defectQuery = $base(Defect::class)->whereIn('status', ['open', 'pending', 'in_progress']);
        if ($vehicleIds !== null && $vehicleIds !== []) {
            $defectQuery->whereIn('vehicle_id', $vehicleIds);
        }
        $defects = $defectQuery->get()->map(fn ($d): array => [
            'id' => $d->id,
            'vehicle_id' => $d->vehicle_id,
            'defect_number' => $d->defect_number,
            'title' => $d->title,
            'category' => $d->category,
            'severity' => $d->severity,
            'status' => $d->status,
        ])->toArray();

        $json = [
            'vehicles' => $vehicles,
            'work_orders' => $workOrders,
            'service_schedules' => $schedules,
            'defects' => $defects,
        ];

        return "Analyze the following fleet data and identify maintenance likely needed in the next 2–4 weeks. Return only the structured findings.\n\n".json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function normalizeUrgency(string $urgency): string
    {
        $u = mb_strtolower($urgency);

        return in_array($u, ['low', 'medium', 'high', 'critical'], true) ? $u : 'medium';
    }
}
