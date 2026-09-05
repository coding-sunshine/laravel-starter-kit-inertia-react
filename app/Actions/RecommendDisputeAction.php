<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\Agents\DisputeAdvisorAgent;
use App\Models\Penalty;
use Illuminate\Support\Facades\Log;
use Throwable;

final class RecommendDisputeAction
{
    /**
     * Get an AI dispute recommendation for a specific penalty.
     *
     * @return array{should_dispute: bool, confidence: float, estimated_success_probability: float, reasoning: string, recommended_grounds: string[]}|null
     */
    public function handle(Penalty $penalty): ?array
    {
        $penalty->loadMissing('rake.siding');

        $prompt = $this->buildPrompt($penalty);

        try {
            $agent = new DisputeAdvisorAgent;
            $response = $agent->prompt($prompt);

            return $response->toArray();
        } catch (Throwable $e) {
            Log::warning('Dispute recommendation failed', [
                'penalty_id' => $penalty->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function buildPrompt(Penalty $penalty): string
    {
        $siding = $penalty->rake?->siding?->name ?? 'Unknown';
        $amount = number_format((float) $penalty->penalty_amount, 2);

        return <<<PROMPT
        Should I dispute this penalty?

        Penalty details:
        - Type: {$penalty->penalty_type}
        - Amount: ₹{$amount}
        - Status: {$penalty->penalty_status}
        - Date: {$penalty->penalty_date}
        - Siding: {$siding}
        - Responsible Party: {$penalty->responsible_party}
        - Root Cause: {$penalty->root_cause}
        - Description: {$penalty->description}

        Use the historical dispute tool to check success rates for similar penalties (same type, similar amounts).
        Then provide your recommendation.
        PROMPT;
    }
}
