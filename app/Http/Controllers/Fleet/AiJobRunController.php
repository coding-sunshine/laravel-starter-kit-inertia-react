<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Jobs\Ai\RunFraudDetectionJob;
use App\Jobs\Ai\RunPredictiveMaintenanceJob;
use App\Models\Fleet\AiJobRun;
use App\Models\Scopes\OrganizationScope;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class AiJobRunController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', AiJobRun::class);
        $runs = AiJobRun::query()
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/AiJobRuns/Index', [
            'aiJobRuns' => $runs,
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\AiJobRunStatus::cases()),
            'runPredictiveMaintenanceUrl' => route('fleet.ai-job-runs.run-predictive-maintenance'),
            'runFraudDetectionUrl' => route('fleet.ai-job-runs.run-fraud-detection'),
        ]);
    }

    public function show(int $ai_job_run): Response
    {
        $run = AiJobRun::withoutGlobalScope(OrganizationScope::class)->findOrFail($ai_job_run);
        $this->authorize('view', $run);

        return Inertia::render('Fleet/AiJobRuns/Show', [
            'aiJobRun' => $run,
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\AiJobRunStatus::cases()),
        ]);
    }

    public function runPredictiveMaintenance(Request $request): JsonResponse
    {
        $this->authorize('create', AiJobRun::class);
        $organizationId = TenantContext::id();
        if ($organizationId === null) {
            return response()->json(['message' => 'No organization context.'], 422);
        }

        $vehicleIds = $request->input('vehicle_ids');
        if (is_array($vehicleIds)) {
            $vehicleIds = array_filter(array_map('intval', $vehicleIds));
        } else {
            $vehicleIds = null;
        }

        $run = AiJobRun::create([
            'organization_id' => $organizationId,
            'job_type' => 'maintenance_prediction',
            'entity_type' => 'vehicle',
            'entity_ids' => $vehicleIds,
            'parameters' => null,
            'status' => 'queued',
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        RunPredictiveMaintenanceJob::dispatch(
            $organizationId,
            $vehicleIds === [] ? null : $vehicleIds,
            $run->id,
            $request->user()?->id
        );

        return response()->json([
            'message' => 'Predictive maintenance job queued.',
            'ai_job_run_id' => $run->id,
            'url' => route('fleet.ai-job-runs.show', $run),
        ]);
    }

    public function runFraudDetection(Request $request): JsonResponse
    {
        $this->authorize('create', AiJobRun::class);
        $organizationId = TenantContext::id();
        if ($organizationId === null) {
            return response()->json(['message' => 'No organization context.'], 422);
        }

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $run = AiJobRun::create([
            'organization_id' => $organizationId,
            'job_type' => 'fraud_detection',
            'entity_type' => 'fuel_transaction',
            'entity_ids' => null,
            'parameters' => ['date_from' => $dateFrom, 'date_to' => $dateTo],
            'status' => 'queued',
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        RunFraudDetectionJob::dispatch(
            $organizationId,
            $dateFrom,
            $dateTo,
            $run->id,
            $request->user()?->id
        );

        return response()->json([
            'message' => 'Fraud detection job queued.',
            'ai_job_run_id' => $run->id,
            'url' => route('fleet.ai-job-runs.show', $run),
        ]);
    }
}
