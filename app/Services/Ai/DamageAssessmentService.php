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

final readonly class DamageAssessmentService
{
    public function __construct(
        private DamageAssessmentAgent $agent
    ) {}

    public function analyze(array $imagePaths): array
    {
        $attachments = array_map(fn (string $path): \Laravel\Ai\Files\LocalImage => Image::fromPath($path), $imagePaths);
        $prompt = count($attachments) > 1
            ? 'Analyze these vehicle/damage photos (multiple angles). Provide a single combined assessment.'
            : 'Analyze this vehicle or damage photo.';

        $response = $this->agent->prompt($prompt, $attachments);

        if (! $response instanceof StructuredAgentResponse) {
            $desc = $response->text;

            return [
                'damage_detected' => false,
                'severity' => 'cosmetic',
                'parts_affected' => null,
                'description' => $desc,
                'cost_range' => null,
                'confidence' => 0.0,
            ];
        }

        $s = $response->structured;
        $damageDetected = (bool) ($s['damage_detected'] ?? false);
        $severity = (string) ($s['severity'] ?? 'cosmetic');
        $partsAffected = isset($s['parts_affected']) ? (string) $s['parts_affected'] : null;
        $description = (string) ($s['description'] ?? '');
        $costRange = isset($s['cost_range']) ? (string) $s['cost_range'] : null;
        $confidence = (float) ($s['confidence'] ?? 0.0);

        return [
            'damage_detected' => $damageDetected,
            'severity' => $severity,
            'parts_affected' => $partsAffected,
            'description' => $description,
            'cost_range' => $costRange,
            'confidence' => $confidence,
        ];
    }

    public function runAndPersist(Model $entity, ?int $userId = null): ?AiAnalysisResult
    {
        $path = $this->getFirstPhotoPath($entity);
        if ($path === null) {
            return null;
        }

        $result = $this->analyze([$path]);
        $entityType = $this->entityTypeFor($entity);
        $analysisType = $entity instanceof InsuranceClaim ? 'claims_processing' : 'damage_detection';
        $priority = $this->severityToPriority($result['severity']);
        $recommendations = ($result['cost_range'] !== null && $result['cost_range'] !== '')
            ? ['cost_range' => $result['cost_range']]
            : null;

        $aiResult = AiAnalysisResult::query()->create([
            'organization_id' => $entity->organization_id,
            'analysis_type' => $analysisType,
            'entity_type' => $entityType,
            'entity_id' => $entity->getKey(),
            'model_name' => 'damage_assessment',
            'model_version' => null,
            'confidence_score' => $result['confidence'],
            'risk_score' => 0,
            'priority' => $priority,
            'primary_finding' => mb_substr((string) $result['description'], 0, 500),
            'detailed_analysis' => $result,
            'recommendations' => $recommendations,
            'action_items' => null,
            'business_impact' => null,
            'status' => 'pending',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $this->applyToEntity($entity, $result);

        return $aiResult;
    }

    private function getFirstPhotoPath(Model $entity): ?string
    {
        if (! method_exists($entity, 'getFirstMedia')) {
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
