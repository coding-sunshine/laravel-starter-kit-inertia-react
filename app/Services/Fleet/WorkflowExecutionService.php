<?php

declare(strict_types=1);

namespace App\Services\Fleet;

use App\Models\Fleet\AiJobRun;
use App\Models\Fleet\Alert;
use App\Models\Fleet\WorkflowDefinition;
use App\Models\Fleet\WorkflowExecution;
use App\Models\Fleet\WorkOrder;
use App\Services\Ai\CompliancePredictionService;
use App\Services\Ai\FleetElectrificationService;
use App\Services\Ai\FleetOptimizationService;
use App\Services\Ai\FraudDetectionService;
use App\Services\Ai\PredictiveMaintenanceService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs workflow steps autonomously. Supports:
 * - ai_agent / run_ai / run_ai_job: config.agent = compliance_prediction | predictive_maintenance | fraud_detection | fleet_electrification | fleet_optimization
 * - create_alert: from prior step output (source_step, foreach, title_template, severity, alert_type)
 * - create_work_order: from prior step output (source_step, foreach, vehicle_id_from, title_template, min_urgency)
 * Audit: creates AiJobRun per AI step linked to workflow_execution.
 */
final readonly class WorkflowExecutionService
{
    private const array AI_AGENTS = [
        'compliance_prediction',
        'predictive_maintenance',
        'fraud_detection',
        'fleet_electrification',
        'fleet_optimization',
    ];

    public function __construct(
        private CompliancePredictionService $compliancePrediction,
        private PredictiveMaintenanceService $predictiveMaintenance,
        private FraudDetectionService $fraudDetection,
        private FleetElectrificationService $fleetElectrification,
        private FleetOptimizationService $fleetOptimization,
    ) {}

    public function run(WorkflowExecution $execution): void
    {
        $execution->load('workflowDefinition');
        $definition = $execution->workflowDefinition;
        if (! $definition instanceof WorkflowDefinition) {
            $this->fail($execution, 'Workflow definition not found.');

            return;
        }

        $organizationId = $definition->organization_id;
        $steps = $definition->steps;
        if (! is_array($steps) || $steps === []) {
            $execution->update([
                'status' => 'completed',
                'completed_at' => now(),
                'result_data' => ['message' => 'No steps to run', 'step_outputs' => []],
            ]);

            return;
        }

        $execution->update(['status' => 'running']);
        $stepResults = [];
        $stepOutputs = [];
        $attempted = 0;
        $completed = 0;
        $failed = 0;

        foreach ($steps as $index => $step) {
            if (! is_array($step)) {
                continue;
            }
            $type = $step['type'] ?? '';
            $config = $step['config'] ?? $step;

            if (in_array($type, ['ai_agent', 'run_ai', 'run_ai_job'], true)) {
                $agent = $config['agent'] ?? $config['job_class'] ?? '';
                $agent = $this->normalizeAgentName($agent);
                if (! in_array($agent, self::AI_AGENTS, true)) {
                    $stepResults[] = ['index' => $index, 'type' => $type, 'agent' => $agent, 'skipped' => true, 'reason' => 'Unknown or disallowed agent'];
                    $attempted++;
                    $failed++;

                    continue;
                }
                $attempted++;
                try {
                    $result = $this->runAiStep($organizationId, $agent);
                    $stepOutputs[$index] = $result;
                    $stepResults[] = ['index' => $index, 'type' => $type, 'agent' => $agent, 'success' => true, 'result' => $result];

                    $this->createAiJobRunAudit($execution, $organizationId, $agent, $result);

                    $completed++;
                } catch (Throwable $e) {
                    Log::warning('WorkflowExecutionService: AI step failed', [
                        'execution_id' => $execution->id,
                        'step_index' => $index,
                        'agent' => $agent,
                        'error' => $e->getMessage(),
                    ]);
                    $stepResults[] = ['index' => $index, 'type' => $type, 'agent' => $agent, 'success' => false, 'error' => $e->getMessage()];
                    $failed++;
                    $this->fail($execution, "Step {$index} ({$agent}): ".$e->getMessage(), $attempted, $completed, $failed, $stepResults, $stepOutputs);

                    return;
                }

                continue;
            }

            if ($type === 'create_alert') {
                $attempted++;
                try {
                    $created = $this->executeCreateAlert($organizationId, $config, $stepOutputs, $execution->id);
                    $stepOutputs[$index] = ['alerts_created' => $created];
                    $stepResults[] = ['index' => $index, 'type' => $type, 'success' => true, 'alerts_created' => $created];
                    $completed++;
                } catch (Throwable $e) {
                    Log::warning('WorkflowExecutionService: create_alert failed', ['execution_id' => $execution->id, 'step_index' => $index, 'error' => $e->getMessage()]);
                    $stepResults[] = ['index' => $index, 'type' => $type, 'success' => false, 'error' => $e->getMessage()];
                    $failed++;
                    $this->fail($execution, "Step {$index} (create_alert): ".$e->getMessage(), $attempted, $completed, $failed, $stepResults, $stepOutputs);

                    return;
                }

                continue;
            }

            if ($type === 'create_work_order') {
                $attempted++;
                try {
                    $created = $this->executeCreateWorkOrder($organizationId, $config, $stepOutputs, $execution->id);
                    $stepOutputs[$index] = ['work_orders_created' => $created];
                    $stepResults[] = ['index' => $index, 'type' => $type, 'success' => true, 'work_orders_created' => $created];
                    $completed++;
                } catch (Throwable $e) {
                    Log::warning('WorkflowExecutionService: create_work_order failed', ['execution_id' => $execution->id, 'step_index' => $index, 'error' => $e->getMessage()]);
                    $stepResults[] = ['index' => $index, 'type' => $type, 'success' => false, 'error' => $e->getMessage()];
                    $failed++;
                    $this->fail($execution, "Step {$index} (create_work_order): ".$e->getMessage(), $attempted, $completed, $failed, $stepResults, $stepOutputs);

                    return;
                }

                continue;
            }

            $stepResults[] = ['index' => $index, 'type' => $type, 'skipped' => true, 'reason' => 'Unsupported step type'];
            $attempted++;
        }

        $execution->update([
            'status' => 'completed',
            'completed_at' => now(),
            'steps_attempted' => $attempted,
            'steps_completed' => $completed,
            'steps_failed' => $failed,
            'result_data' => ['step_results' => $stepResults, 'step_outputs' => $stepOutputs],
        ]);
    }

    private function normalizeAgentName(string $name): string
    {
        $map = [
            'PredictiveMaintenanceAgent' => 'predictive_maintenance',
            'RunPredictiveMaintenanceJob' => 'predictive_maintenance',
            'FuelFraudDetectionAgent' => 'fraud_detection',
            'RunFraudDetectionJob' => 'fraud_detection',
            'CompliancePredictionAgent' => 'compliance_prediction',
            'FleetElectrificationAgent' => 'fleet_electrification',
            'FleetOptimizationAgent' => 'fleet_optimization',
        ];

        return $map[$name] ?? mb_strtolower(str_replace(['-', ' '], '_', $name));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function runAiStep(int $organizationId, string $agent): ?array
    {
        return match ($agent) {
            'compliance_prediction' => $this->compliancePrediction->run($organizationId),
            'predictive_maintenance' => $this->predictiveMaintenance->run($organizationId),
            'fraud_detection' => $this->fraudDetection->run($organizationId, \Illuminate\Support\Facades\Date::now()->subDays(30), \Illuminate\Support\Facades\Date::now()),
            'fleet_electrification' => $this->fleetElectrification->generate($organizationId),
            'fleet_optimization' => $this->fleetOptimization->analyze($organizationId),
            default => null,
        };
    }

    private function createAiJobRunAudit(WorkflowExecution $execution, int $organizationId, string $agent, ?array $result): void
    {
        $jobType = match ($agent) {
            'predictive_maintenance' => 'maintenance_prediction',
            'fraud_detection' => 'fraud_detection',
            'compliance_prediction' => 'compliance_prediction',
            default => 'cost_analysis',
        };
        AiJobRun::query()->withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->create([
            'organization_id' => $organizationId,
            'job_type' => $jobType,
            'entity_type' => 'workflow_execution',
            'entity_ids' => [$execution->id],
            'parameters' => ['workflow_execution_id' => $execution->id, 'agent' => $agent],
            'status' => 'completed',
            'started_at' => now(),
            'completed_at' => now(),
            'result_data' => $result,
        ]);
    }

    /**
     * @param  array<int, mixed>  $stepOutputs
     */
    private function executeCreateAlert(int $organizationId, array $config, array $stepOutputs, int $workflowExecutionId): int
    {
        $sourceStep = $config['source_step'] ?? 0;
        $foreachKey = $config['foreach'] ?? null;
        $data = $stepOutputs[$sourceStep] ?? null;
        if (! is_array($data)) {
            return 0;
        }
        $items = $foreachKey !== null && isset($data[$foreachKey]) && is_array($data[$foreachKey])
            ? $data[$foreachKey]
            : [];
        $titleTemplate = $config['title_template'] ?? 'Workflow alert: {{id}}';
        $severity = in_array($config['severity'] ?? 'warning', ['info', 'warning', 'critical', 'emergency'], true) ? $config['severity'] : 'warning';
        $alertType = $config['alert_type'] ?? 'compliance_expiry';
        $validTypes = ['compliance_expiry', 'maintenance_due', 'defect_reported', 'incident_reported', 'behavior_violation', 'fuel_anomaly', 'cost_threshold', 'geofence_violation', 'speed_violation', 'working_time_violation', 'system_error'];
        $alertType = in_array($alertType, $validTypes, true) ? $alertType : 'compliance_expiry';

        $created = 0;
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $title = $this->interpolate($titleTemplate, $item);
            $description = is_string($config['description_template'] ?? null)
                ? $this->interpolate($config['description_template'], $item)
                : json_encode($item);
            $entityType = $item['type'] ?? 'organization';
            $entityId = isset($item['id']) ? (int) $item['id'] : $organizationId;

            Alert::query()->create([
                'organization_id' => $organizationId,
                'alert_type' => $alertType,
                'severity' => $severity,
                'title' => mb_substr($title, 0, 200),
                'description' => $description,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'triggered_at' => now(),
                'status' => 'active',
                'metadata' => ['workflow_execution_id' => $workflowExecutionId],
            ]);
            $created++;
        }

        return $created;
    }

    /**
     * @param  array<int, mixed>  $stepOutputs
     */
    private function executeCreateWorkOrder(int $organizationId, array $config, array $stepOutputs, int $workflowExecutionId): int
    {
        $sourceStep = $config['source_step'] ?? 0;
        $foreachKey = $config['foreach'] ?? 'findings';
        $data = $stepOutputs[$sourceStep] ?? null;
        if (! is_array($data)) {
            return 0;
        }
        $items = isset($data[$foreachKey]) && is_array($data[$foreachKey]) ? $data[$foreachKey] : [];
        $vehicleIdKey = $config['vehicle_id_from'] ?? 'vehicle_id';
        $titleTemplate = $config['title_template'] ?? 'PM: {{component}}';
        $minUrgency = $config['min_urgency'] ?? 'high';
        $minConfidence = (float) ($config['min_confidence'] ?? 0);

        $created = 0;
        $urgencyOrder = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];
        $minLevel = $urgencyOrder[$minUrgency] ?? 2;

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $urgency = $item['urgency'] ?? $item['priority'] ?? 'medium';
            if (($urgencyOrder[$urgency] ?? 0) < $minLevel) {
                continue;
            }
            if (isset($item['confidence']) && (float) $item['confidence'] < $minConfidence) {
                continue;
            }
            $vehicleId = isset($item[$vehicleIdKey]) ? (int) $item[$vehicleIdKey] : null;
            if ($vehicleId === null) {
                continue;
            }
            if ($vehicleId < 1) {
                continue;
            }
            $title = $this->interpolate($titleTemplate, $item);
            $woNumber = 'WO-WF-'.$workflowExecutionId.'-'.($created + 1);

            WorkOrder::query()->create([
                'organization_id' => $organizationId,
                'vehicle_id' => $vehicleId,
                'work_order_number' => $woNumber,
                'title' => mb_substr($title, 0, 255),
                'description' => json_encode($item),
                'work_type' => 'preventive',
                'priority' => in_array($urgency, ['critical', 'high'], true) ? $urgency : 'medium',
                'status' => 'draft',
                'urgency' => $urgency,
            ]);
            $created++;
        }

        return $created;
    }

    private function interpolate(string $template, array $data): string
    {
        $out = $template;
        foreach ($data as $k => $v) {
            if (is_scalar($v)) {
                $out = str_replace(['{{'.$k.'}}', '{{ '.$k.' }}'], (string) $v, $out);
            }
        }

        return $out;
    }

    private function fail(
        WorkflowExecution $execution,
        string $message,
        int $attempted = 0,
        int $completed = 0,
        int $failed = 0,
        array $stepResults = [],
        array $stepOutputs = [],
    ): void {
        $execution->update([
            'status' => 'failed',
            'completed_at' => now(),
            'steps_attempted' => $attempted,
            'steps_completed' => $completed,
            'steps_failed' => $failed,
            'error_message' => $message,
            'result_data' => array_filter(['step_results' => $stepResults, 'step_outputs' => $stepOutputs]),
        ]);
    }
}
