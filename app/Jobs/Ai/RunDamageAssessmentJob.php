<?php

declare(strict_types=1);

namespace App\Jobs\Ai;

use App\Models\Fleet\Defect;
use App\Models\Fleet\Incident;
use App\Models\Fleet\InsuranceClaim;
use App\Models\Scopes\OrganizationScope;
use App\Services\Ai\DamageAssessmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Runs damage assessment (vision) on the first photo of a Defect, Incident, or InsuranceClaim
 * and persists the result to ai_analysis_results.
 */
final class RunDamageAssessmentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $entityType,
        public int $entityId,
        public ?int $userId = null,
    ) {}

    public function handle(DamageAssessmentService $service): void
    {
        $entity = $this->resolveEntity();
        if ($entity === null) {
            Log::warning('RunDamageAssessmentJob: entity not found', [
                'entity_type' => $this->entityType,
                'entity_id' => $this->entityId,
            ]);
            return;
        }

        try {
            $service->runAndPersist($entity, $this->userId);
        } catch (\Throwable $e) {
            Log::error('RunDamageAssessmentJob: assessment failed', [
                'entity_type' => $this->entityType,
                'entity_id' => $this->entityId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /** @return Defect|Incident|InsuranceClaim|null */
    private function resolveEntity(): Model|null
    {
        $withoutScope = fn (string $model) => $model::withoutGlobalScope(OrganizationScope::class)->find($this->entityId);

        return match ($this->entityType) {
            'defect' => $withoutScope(Defect::class),
            'incident' => $withoutScope(Incident::class),
            'insurance_claim' => $withoutScope(InsuranceClaim::class),
            default => null,
        };
    }
}
