<?php

declare(strict_types=1);

namespace App\Jobs\Ai;

use App\Models\Fleet\AiAnalysisResult;
use App\Models\Fleet\AiJobRun;
use App\Services\Ai\FraudDetectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class RunFraudDetectionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $organizationId,
        public ?string $dateFrom,
        public ?string $dateTo,
        public int $aiJobRunId,
        public ?int $userId = null,
    ) {}

    public function handle(FraudDetectionService $service): void
    {
        $run = AiJobRun::query()->withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)
            ->find($this->aiJobRunId);

        if ($run === null || $run->organization_id !== $this->organizationId) {
            Log::warning('RunFraudDetectionJob: AiJobRun not found or org mismatch', [
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

        $dateFrom = $this->dateFrom !== null ? \Illuminate\Support\Facades\Date::parse($this->dateFrom) : null;
        $dateTo = $this->dateTo !== null ? \Illuminate\Support\Facades\Date::parse($this->dateTo) : null;

        try {
            $result = $service->run($this->organizationId, $dateFrom, $dateTo);
            $findings = $result['findings'] ?? [];

            foreach ($findings as $f) {
                AiAnalysisResult::query()->create([
                    'organization_id' => $this->organizationId,
                    'analysis_type' => 'fraud_detection',
                    'entity_type' => 'fuel_transaction',
                    'entity_id' => $f['transaction_id'],
                    'model_name' => 'fuel_fraud_detection',
                    'model_version' => null,
                    'confidence_score' => $f['fraud_score'],
                    'risk_score' => $f['fraud_score'] * 100,
                    'priority' => $f['severity'],
                    'primary_finding' => mb_substr($f['reason'], 0, 500),
                    'detailed_analysis' => $f,
                    'recommendations' => null,
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
        } catch (Throwable $e) {
            Log::error('RunFraudDetectionJob: failed', [
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
