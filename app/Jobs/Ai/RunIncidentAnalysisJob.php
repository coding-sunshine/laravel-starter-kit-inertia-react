<?php

declare(strict_types=1);

namespace App\Jobs\Ai;

use App\Models\Fleet\AiAnalysisResult;
use App\Models\Fleet\Incident;
use App\Models\Scopes\OrganizationScope;
use App\Services\Ai\IncidentAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class RunIncidentAnalysisJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $incidentId,
        public ?int $userId = null,
    ) {}

    public function handle(IncidentAnalysisService $service): void
    {
        $incident = Incident::withoutGlobalScope(OrganizationScope::class)->find($this->incidentId);

        if ($incident === null) {
            Log::warning('RunIncidentAnalysisJob: incident not found', ['incident_id' => $this->incidentId]);
            return;
        }

        try {
            $result = $service->run($incident);
            if ($result === null) {
                Log::info('RunIncidentAnalysisJob: no text to analyze or no structured result', ['incident_id' => $this->incidentId]);
                return;
            }

            $priority = $result['severity'] === 'critical' ? 'critical' : ($result['severity'] === 'high' ? 'high' : ($result['severity'] === 'medium' ? 'medium' : 'low'));

            AiAnalysisResult::create([
                'organization_id' => $incident->organization_id,
                'analysis_type' => 'incident_analysis',
                'entity_type' => 'incident',
                'entity_id' => $incident->id,
                'model_name' => 'incident_analysis',
                'model_version' => null,
                'confidence_score' => 0.9,
                'risk_score' => 0,
                'priority' => $priority,
                'primary_finding' => mb_substr($result['summary'], 0, 500),
                'detailed_analysis' => $result,
                'recommendations' => $result['training_insights'] !== '' ? ['training_insights' => $result['training_insights']] : null,
                'action_items' => null,
                'business_impact' => null,
                'status' => 'pending',
                'created_by' => $this->userId,
                'updated_by' => $this->userId,
            ]);
        } catch (\Throwable $e) {
            Log::error('RunIncidentAnalysisJob: failed', [
                'incident_id' => $this->incidentId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
