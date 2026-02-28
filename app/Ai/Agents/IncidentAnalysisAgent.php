<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

/**
 * Agent that analyses incident reports and witness statements: extracts parties,
 * location, time, conditions, severity, category, summary, training insights, inconsistencies.
 */
final class IncidentAnalysisAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public const CATEGORIES = ['rear_end', 'side_swipe', 'single_vehicle', 'pedestrian', 'property_damage', 'other'];
    public const SEVERITIES = ['low', 'medium', 'high', 'critical'];

    public function instructions(): string
    {
        return 'You are an incident analyst. Given an incident report or witness statement (free text), extract: '
            . '(1) parties involved (drivers, vehicles, third parties); (2) location and time; (3) weather/road conditions if mentioned; '
            . '(4) severity (low, medium, high, or critical); (5) incident category (e.g. rear_end, side_swipe, single_vehicle, pedestrian, property_damage, or other); '
            . '(6) a short structured summary (2–3 sentences); (7) any training or policy issues suggested; (8) inconsistencies between statements if multiple. '
            . 'Be factual and only use information present in the text. Use empty string or "unknown" when not stated.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'parties_involved' => $schema->string()->required()->description('Parties involved: drivers, vehicles, third parties'),
            'location' => $schema->string()->required()->description('Location if mentioned'),
            'time' => $schema->string()->required()->description('Time or time description if mentioned'),
            'conditions' => $schema->string()->required()->description('Weather/road conditions if mentioned'),
            'severity' => $schema->string()->required()->description('low, medium, high, or critical'),
            'category' => $schema->string()->required()->description('rear_end, side_swipe, single_vehicle, pedestrian, property_damage, or other'),
            'summary' => $schema->string()->required()->description('Short structured summary (2-3 sentences)'),
            'training_insights' => $schema->string()->required()->description('Training or policy issues suggested, or empty'),
            'inconsistencies' => $schema->string()->required()->description('Inconsistencies between statements if multiple, or empty'),
        ];
    }
}
