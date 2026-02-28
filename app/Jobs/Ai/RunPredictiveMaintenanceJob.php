<?php

declare(strict_types=1);

namespace App\Jobs\Ai;

use App\Models\Fleet\AiAnalysisResult;
use App\Models\Fleet\AiJobRun;
use App\Services\Ai\PredictiveMaintenanceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class RunPredictiveMaintenanceJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $organizationId,
        public ?array $vehicleIds,
        public int $aiJobRunId,
        public ?int $userId = null,
    ) {}

    public function handle(PredictiveMaintenanceService $service): void
    {
        $run = AiJobRun::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)
            ->find($this->aiJobRunId);

        if ($run === null || $run->organization_id !== $this->organizationId) {
            Log::warning('RunPredictiveMaintenanceJob: AiJobRun not found or org mismatch', [
                'ai_job_run_id' => $this->aiJobRunId,
                'organization_id' => $this->organizationId,
            ]);
            return;
        }

        $run->update([
            'status' => 'processing',
            'started_at' => now(),
            'laravel_job_id' => $this->job?->uuid(),
        ]);

        try {
            $result = $service->run($this->organizationId, $this->vehicleIds);
            $findings = $result['findings'] ?? [];

            foreach ($findings as $f) {
                AiAnalysisResult::create([
                    'organization_id' => $this->organizationId,
                    'analysis_type' => 'predictive_maintenance',
                    'entity_type' => 'vehicle',
                    'entity_id' => $f['vehicle_id'],
                    'model_name' => 'predictive_maintenance',
                    'model_version' => null,
                    'confidence_score' => $f['confidence'],
                    'risk_score' => 0,
                    'priority' => $f['urgency'],
                    'primary_finding' => mb_substr($f['component'] . ': ' . $f['reason'], 0, 500),
                    'detailed_analysis' => $f,
                    'recommendations' => ['action' => $f['recommended_action']],
                    'action_items' => null,
                    'business_impact' => null,
                    'status' => 'pending',
                    'created_by' => $this->userId,
                    'updated_by' => $this->userId,
                ]);
            }

            $run->update([
                'status' => 'completed',
                'completed_at' => now(),
                'result_data' => ['findings_count' => count($findings), 'findings' => $findings],
            ]);
        } catch (\Throwable $e) {
            Log::error('RunPredictiveMaintenanceJob: failed', [
                'ai_job_run_id' => $this->aiJobRunId,
                'error' => $e->getMessage(),
            ]);
            $run->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
