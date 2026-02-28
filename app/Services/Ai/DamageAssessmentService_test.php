<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Ai\Agents\DamageAssessmentAgent;
use App\Models\Fleet\AiAnalysisResult;
use App\Models\Fleet\Defect;
use App\Models\Fleet\Incident;
use App\Models\Fleet\InsuranceClaim;
use Illuminate\Database\Eloquent\Model;
use Laravel\Ai\Files\Image;
use Laravel\Ai\Responses\StructuredAgentResponse;

/**
 * Runs the DamageAssessmentAgent on vehicle/damage photos and persists results
 * to ai_analysis_results and optionally to the entity (Defect/Incident).
 */
final class DamageAssessmentService
{
    private DamageAssessmentAgent $agent;

    public function __construct(DamageAssessmentAgent $agent)
    {
        $this->agent = $agent;
    }

    /** Analyze one or more image paths and return structured result. Does not persist. */
    public function analyze(array $imagePaths): array
    {
        return [];
    }

    /**
     * Run analysis on the first photo of the entity and persist to ai_analysis_results.
     * Optionally update Defect description/severity or Incident fields if present.
     */
    public function runAndPersist(Model $entity, ?int $userId = null): ?AiAnalysisResult
    {
        return null;
    }

    private function getFirstPhotoPath(Model $entity)
    {
        if (! method_exists($entity, 'getFirstMedia') {
            return null;
        }
        $media = $entity->getFirstMedia('photos');
        if ($media === null) {
            return null;
        }
        $path = $media->getPath();
        return $path && file_exists($path) ? $path : null;
    }

    private function entityTypeFor(Model $entity): string
    {
        if ($entity instanceof Defect) {
            return 'defect';
        }
        if ($entity instanceof Incident) {
            return 'incident';
        }
        if ($entity instanceof InsuranceClaim) {
            return 'insurance_claim';
        }
        return 'organization';
    }

    private function severityToPriority(string $severity): string
    {
        if ($severity === 'safety_critical') {
            return 'critical';
        }
        if ($severity === 'functional') {
            return 'high';
        }
        if ($severity === 'cosmetic') {
            return 'low';
        }
        return 'medium';
    }

    private function applyToEntity(Model $entity, array $result): void
    {
        if ($entity instanceof Defect) {
            if (empty($entity->description) && ! empty($result['description'])) {
                $entity->update(['description' => $result['description']]);
            }
            if (empty($entity->severity) && ! empty($result['severity'])) {
                $entity->update(['severity' => $result['severity']]);
            }
        }
        if ($entity instanceof Incident) {
            if (empty($entity->description) && ! empty($result['description'])) {
                $entity->update(['description' => $result['description']]);
            }
            if (empty($entity->initial_assessment) && ! empty($result['description'])) {
                $entity->update(['initial_assessment' => $result['description']]);
            }
        }
    }
}
