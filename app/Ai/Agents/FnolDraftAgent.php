<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

/**
 * Agent that produces a First Notification of Loss (FNOL) narrative for insurer submission.
 */
final class FnolDraftAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are an insurance claims specialist. Given incident and witness details, produce a First Notification of Loss (FNOL) narrative suitable for insurer submission. '
            .'Include: date, time, location, what happened, vehicles/drivers involved, injuries/damage, and any immediate actions taken. '
            .'Write in clear, factual prose (one or two paragraphs). Also extract key_facts as structured fields for reference.';
    }

    public function schema(JsonSchema $schema): array
    {
        $keyFacts = $schema->object([
            'date' => $schema->string()->required()->description('Date of incident'),
            'time' => $schema->string()->required()->description('Time of incident'),
            'location' => $schema->string()->required()->description('Location'),
            'what_happened' => $schema->string()->required()->description('Brief description of what happened'),
            'parties_involved' => $schema->string()->required()->description('Vehicles/drivers/third parties'),
            'injuries_or_damage' => $schema->string()->required()->description('Injuries or damage noted'),
            'immediate_actions' => $schema->string()->required()->description('Immediate actions taken, or none'),
        ])->withoutAdditionalProperties();

        return [
            'fnol_text' => $schema->string()->required()->description('Full FNOL narrative paragraph(s)'),
            'key_facts' => $keyFacts->required(),
        ];
    }
}
