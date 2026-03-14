<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use Illuminate\Support\Carbon;

/**
 * Rule-based lead scoring service.
 * Computes a 0–100 score for a contact based on recency, activity, and profile completeness.
 * Future: replace with ML-based scoring via Laravel AI SDK.
 */
final class LeadScoringService
{
    public function score(Contact $contact): int
    {
        $score = 0;

        // Stage score (max 30)
        $score += match ($contact->stage) {
            'hot' => 30,
            'qualified' => 20,
            'warm' => 15,
            'new' => 10,
            'cold' => 5,
            'nurture' => 8,
            default => 0,
        };

        // Recency score based on last_contacted_at (max 30)
        if ($contact->last_contacted_at !== null) {
            $daysSince = (int) Carbon::now()->diffInDays($contact->last_contacted_at);
            $score += match (true) {
                $daysSince <= 7 => 30,
                $daysSince <= 30 => 20,
                $daysSince <= 60 => 10,
                $daysSince <= 90 => 5,
                default => 0,
            };
        }

        // Profile completeness (max 20)
        if ($contact->email !== null) {
            $score += 5;
        }
        if ($contact->phone !== null) {
            $score += 5;
        }
        if ($contact->company_name !== null) {
            $score += 5;
        }
        if ($contact->suburb !== null) {
            $score += 5;
        }

        // Contact origin bonus (max 10)
        $score += match ($contact->contact_origin) {
            'property' => 10,
            'referral' => 8,
            'website' => 5,
            default => 2,
        };

        // Has next follow-up scheduled (max 10)
        if ($contact->next_followup_at !== null && $contact->next_followup_at->isFuture()) {
            $score += 10;
        }

        return min(100, max(0, $score));
    }

    /**
     * Compute and persist lead scores for all contacts (or a specific one).
     */
    public function refresh(Contact $contact): void
    {
        $contact->lead_score = $this->score($contact);
        $contact->saveQuietly();
    }

    /**
     * Batch-refresh lead scores for contacts.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, Contact>  $contacts
     */
    public function refreshBatch(\Illuminate\Database\Eloquent\Collection $contacts): void
    {
        foreach ($contacts as $contact) {
            $this->refresh($contact);
        }
    }
}
