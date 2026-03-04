<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Ai\Agents\FnolDraftAgent;
use App\Models\Fleet\InsuranceClaim;
use Laravel\Ai\Responses\StructuredAgentResponse;

final readonly class FnolDraftService
{
    public function __construct(
        private FnolDraftAgent $agent
    ) {}

    /**
     * Generate FNOL narrative from the claim's linked incident. Returns the narrative text or null on failure.
     */
    public function generate(InsuranceClaim $claim): ?string
    {
        $claim->load('incident');
        $incident = $claim->incident;
        if ($incident === null) {
            return null;
        }

        $text = $this->buildPromptText($incident);
        $response = $this->agent->prompt($text);

        if (! $response instanceof StructuredAgentResponse) {
            return null;
        }

        $s = $response->structured;
        $fnolText = $s['fnol_text'] ?? null;

        return $fnolText !== null ? (string) $fnolText : null;
    }

    private function buildPromptText(\App\Models\Fleet\Incident $incident): string
    {
        $parts = [];

        $desc = $incident->description ?? '';
        if ($desc !== '') {
            $parts[] = "Incident description:\n".mb_trim((string) $desc);
        }

        $witnesses = $incident->witnesses;
        if (is_array($witnesses) && $witnesses !== []) {
            foreach ($witnesses as $i => $w) {
                $statement = is_string($w) ? $w : (is_array($w) && isset($w['statement']) ? (string) $w['statement'] : json_encode($w));
                if ($statement !== '' && $statement !== '[]') {
                    $parts[] = 'Witness statement '.((int) $i + 1).":\n".$statement;
                }
            }
        }

        $parts[] = "\nProduce a First Notification of Loss (FNOL) narrative suitable for insurer submission.";

        return implode("\n\n", $parts);
    }
}
