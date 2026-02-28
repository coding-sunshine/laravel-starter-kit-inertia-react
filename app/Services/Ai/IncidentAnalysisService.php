<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Ai\Agents\IncidentAnalysisAgent;
use App\Models\Fleet\Incident;
use Laravel\Ai\Responses\StructuredAgentResponse;

final class IncidentAnalysisService
{
    public function __construct(
        private readonly IncidentAnalysisAgent $agent
    ) {}

    /** @return array<string, string>|null */
    public function run(Incident $incident): ?array
    {
        $text = $this->buildPromptText($incident);
        if ($text === '') {
            return null;
        }

        $response = $this->agent->prompt($text);

        if (! $response instanceof StructuredAgentResponse) {
            return null;
        }

        $s = $response->structured;
        return [
            'parties_involved' => (string) ($s['parties_involved'] ?? ''),
            'location' => (string) ($s['location'] ?? ''),
            'time' => (string) ($s['time'] ?? ''),
            'conditions' => (string) ($s['conditions'] ?? ''),
            'severity' => $this->normalizeSeverity((string) ($s['severity'] ?? 'medium')),
            'category' => (string) ($s['category'] ?? 'other'),
            'summary' => (string) ($s['summary'] ?? ''),
            'training_insights' => (string) ($s['training_insights'] ?? ''),
            'inconsistencies' => (string) ($s['inconsistencies'] ?? ''),
        ];
    }

    private function buildPromptText(Incident $incident): string
    {
        $parts = [];

        $desc = $incident->description ?? '';
        if ($desc !== '') {
            $parts[] = "Incident description:\n" . trim($desc);
        }

        $witnesses = $incident->witnesses;
        if (is_array($witnesses) && $witnesses !== []) {
            foreach ($witnesses as $i => $w) {
                $statement = is_string($w) ? $w : (is_array($w) && isset($w['statement']) ? (string) $w['statement'] : json_encode($w));
                if ($statement !== '' && $statement !== '[]') {
                    $parts[] = 'Witness statement ' . ((int) $i + 1) . ":\n" . $statement;
                }
            }
        }

        if ($parts === []) {
            return '';
        }

        return "Analyze the following incident report and witness statements. Extract the requested structured information.\n\n" . implode("\n\n", $parts);
    }

    private function normalizeSeverity(string $severity): string
    {
        $s = strtolower(trim($severity));
        return in_array($s, ['low', 'medium', 'high', 'critical'], true) ? $s : 'medium';
    }
}
