<?php

declare(strict_types=1);

namespace App\Jobs\Ai;

use App\Events\Fleet\AiJobCompleted;
use App\Models\Fleet\AiAnalysisResult;
use App\Models\Fleet\AiJobRun;
use App\Models\Scopes\OrganizationScope;
use App\Services\Ai\CompliancePredictionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class RunCompliancePredictionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $organizationId,
        public int $aiJobRunId,
        public ?int $userId = null,
    ) {}

    public function handle(CompliancePredictionService $service): void
    {
        $run = AiJobRun::query()->withoutGlobalScope(OrganizationScope::class)->find($this->aiJobRunId);
        if ($run === null || $run->organization_id !== $this->organizationId) {
            Log::warning('RunCompliancePredictionJob: AiJobRun not found or org mismatch', [
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
            $result = $service->run($this->organizationId);
            $vehicles = $result['at_risk_vehicles'] ?? [];
            $drivers = $result['at_risk_drivers'] ?? [];
            $total = count($vehicles) + count($drivers);

            $maxPriority = 'low';
            foreach (array_merge($vehicles, $drivers) as $item) {
                $r = $item['risk_level'] ?? 'low';
                if ($r === 'critical') {
                    $maxPriority = 'critical';
                } elseif ($r === 'high' && $maxPriority !== 'critical') {
                    $maxPriority = 'high';
                } elseif ($r === 'medium' && $maxPriority === 'low') {
                    $maxPriority = 'medium';
                }
            }

            AiAnalysisResult::query()->withoutGlobalScope(OrganizationScope::class)->create([
                'organization_id' => $this->organizationId,
                'analysis_type' => 'compliance_prediction',
                'entity_type' => 'organization',
                'entity_id' => $this->organizationId,
                'model_name' => 'compliance_prediction',
                'model_version' => null,
                'confidence_score' => 0.9,
                'risk_score' => $total > 0 ? min(100, $total * 10) : 0,
                'priority' => $maxPriority,
                'primary_finding' => $total > 0
                    ? sprintf('%d vehicle(s) and %d driver(s) at compliance risk in next 90 days.', count($vehicles), count($drivers))
                    : 'No compliance at risk in next 90 days.',
                'detailed_analysis' => $result,
                'recommendations' => null,
                'action_items' => null,
                'business_impact' => null,
                'status' => 'pending',
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
            ]);

            $run->update([
                'status' => 'completed',
                'completed_at' => now(),
                'result_data' => ['at_risk_count' => $total, 'at_risk_vehicles' => count($vehicles), 'at_risk_drivers' => count($drivers), 'result' => $result],
            ]);

            event(new AiJobCompleted(
                $this->organizationId,
                'compliance_prediction',
                'organization',
                $this->organizationId,
                ['at_risk_count' => $total, 'at_risk_vehicles' => count($vehicles), 'at_risk_drivers' => count($drivers)],
            ));
        } catch (Throwable $e) {
            Log::error('RunCompliancePredictionJob: failed', [
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
