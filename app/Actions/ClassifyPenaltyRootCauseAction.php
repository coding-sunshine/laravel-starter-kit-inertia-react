<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\Agents\RootCauseClassifierAgent;
use App\Models\Penalty;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ClassifyPenaltyRootCauseAction
{
    /**
     * Classify a penalty's root cause using AI.
     */
    public function handle(Penalty $penalty): void
    {
        if (blank($penalty->root_cause) && blank($penalty->description)) {
            return;
        }

        $prompt = $this->buildPrompt($penalty);

        try {
            $agent = new RootCauseClassifierAgent;
            $response = $agent->prompt($prompt);

            /** @var array{category: string, subcategory: string, is_preventable: bool, confidence: float, suggested_remediation: string} $result */
            $result = $response->toArray();

            $penalty->updateQuietly([
                'root_cause_category' => $result['category'],
                'is_preventable' => $result['is_preventable'],
                'suggested_remediation' => $result['suggested_remediation'],
            ]);
        } catch (Throwable $e) {
            Log::warning('Root cause classification failed', [
                'penalty_id' => $penalty->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function buildPrompt(Penalty $penalty): string
    {
        return <<<PROMPT
        Classify the root cause of this railway penalty:

        - Penalty Type: {$penalty->penalty_type}
        - Root Cause: {$penalty->root_cause}
        - Description: {$penalty->description}
        - Responsible Party: {$penalty->responsible_party}
        - Amount: {$penalty->penalty_amount}
        PROMPT;
    }
}
