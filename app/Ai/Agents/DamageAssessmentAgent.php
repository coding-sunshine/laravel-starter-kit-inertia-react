<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

/**
 * Vision agent for vehicle/damage photo analysis: damage detection, severity, parts affected,
 * description for defect/incident/claim reports, and optional cost range.
 */
final class DamageAssessmentAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public const array SEVERITIES = ['cosmetic', 'functional', 'safety_critical'];

    public function instructions(): string
    {
        return 'You are a vehicle damage assessment specialist. Analyze the provided vehicle or damage photo and return structured data. '
            .'Identify: (1) Any visible damage (dents, scratches, breaks, panel damage). '
            .'(2) Severity: cosmetic (aesthetic only), functional (impairs use but not safety), or safety_critical (affects safety/roadworthiness). '
            .'(3) Body parts or areas affected (e.g. bumper, door, windscreen, wing, bonnet). '
            .'(4) A short description suitable for a defect/incident/claim report. '
            .'(5) An optional rough cost range: low (e.g. under £500), medium (£500–£2000), or high (over £2000), or a brief numeric range if possible. '
            .'Set damage_detected to false only if there is clearly no damage visible. Set confidence between 0 and 1.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'damage_detected' => $schema->boolean()->required()->description('Whether any damage is visible'),
            'severity' => $schema->string()->enum(self::SEVERITIES)->required()->description('cosmetic, functional, or safety_critical'),
            'parts_affected' => $schema->string()->nullable()->required()->description('Comma-separated list of body parts/areas affected, e.g. bumper, door, windscreen'),
            'description' => $schema->string()->required()->description('Short description for a defect/incident/claim report'),
            'cost_range' => $schema->string()->nullable()->required()->description('low, medium, high, or a brief range e.g. £500-1000'),
            'confidence' => $schema->number()->min(0)->max(1)->required()->description('Confidence score 0-1'),
        ];
    }
}
