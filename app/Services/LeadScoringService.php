<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Hybrid lead scoring: 60% rule-based + 40% AI-enhanced factors.
 * AI factors include engagement velocity and embedding similarity to converted contacts.
 */
final class LeadScoringService
{
    /**
     * Compute a hybrid lead score (0–100).
     */
    public function score(Contact $contact): int
    {
        $ruleScore = $this->ruleBasedScore($contact);
        $aiScore = $this->aiEnhancedScore($contact);

        // 60% rule-based + 40% AI-enhanced
        $final = (int) round(($ruleScore * 0.6) + ($aiScore * 0.4));

        return min(100, max(0, $final));
    }

    /**
     * Rule-based scoring (0–100).
     */
    public function ruleBasedScore(Contact $contact): int
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
        $contact->loadMissing(['emails', 'phones']);

        if ($contact->emails->isNotEmpty()) {
            $score += 5;
        }
        if ($contact->phones->isNotEmpty()) {
            $score += 5;
        }
        if ($contact->company_name !== null) {
            $score += 5;
        }
        if ($contact->job_title !== null) {
            $score += 5;
        }

        // Contact origin bonus (max 10)
        $score += match ($contact->contact_origin) {
            'referral' => 10,
            'website' => 8,
            'event' => 7,
            'phone' => 6,
            'facebook_ad', 'google_ad' => 5,
            'email' => 4,
            default => 2,
        };

        // Has next follow-up scheduled (max 10)
        if ($contact->next_followup_at !== null && $contact->next_followup_at->isFuture()) {
            $score += 10;
        }

        return min(100, max(0, $score));
    }

    /**
     * AI-enhanced scoring factors (0–100).
     */
    public function aiEnhancedScore(Contact $contact): int
    {
        $score = 0;

        // Engagement velocity (max 50): recent interactions relative to account age
        $score += $this->engagementVelocityScore($contact);

        // Embedding similarity to converted contacts (max 50)
        $score += $this->similarityToConvertedScore($contact);

        return min(100, max(0, $score));
    }

    /**
     * Compute and persist lead score for a contact.
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

    /**
     * Score based on engagement velocity — how active the contact has been recently.
     */
    private function engagementVelocityScore(Contact $contact): int
    {
        $contact->loadMissing(['callLogs', 'tasks']);

        $recentDays = 30;
        $cutoff = now()->subDays($recentDays);

        $recentCalls = $contact->callLogs->where('created_at', '>=', $cutoff)->count();
        $recentTasks = $contact->tasks->where('created_at', '>=', $cutoff)->count();

        $totalRecent = $recentCalls + $recentTasks;

        return match (true) {
            $totalRecent >= 10 => 50,
            $totalRecent >= 5 => 35,
            $totalRecent >= 3 => 25,
            $totalRecent >= 1 => 15,
            default => 0,
        };
    }

    /**
     * Score based on embedding similarity to contacts who converted (stage=qualified/hot or have sales).
     */
    private function similarityToConvertedScore(Contact $contact): int
    {
        try {
            $hasEmbedding = DB::table('contact_embeddings')
                ->where('contact_id', $contact->id)
                ->where('type', 'full')
                ->whereNotNull('embedding')
                ->exists();

            if (! $hasEmbedding) {
                return 0;
            }

            // Average distance to top converted contacts' embeddings
            $avgDistance = DB::selectOne(
                "SELECT AVG(sub.distance) as avg_distance
                 FROM (
                     SELECT ce.embedding <=> target.embedding AS distance
                     FROM contact_embeddings ce
                     JOIN contacts c ON c.id = ce.contact_id
                     CROSS JOIN (
                         SELECT embedding FROM contact_embeddings
                         WHERE contact_id = ? AND type = 'full' AND embedding IS NOT NULL
                         LIMIT 1
                     ) target
                     WHERE ce.type = 'full'
                       AND ce.embedding IS NOT NULL
                       AND ce.contact_id != ?
                       AND c.stage IN ('hot', 'qualified')
                     ORDER BY distance ASC
                     LIMIT 10
                 ) sub",
                [$contact->id, $contact->id]
            );

            if ($avgDistance === null || $avgDistance->avg_distance === null) {
                return 0;
            }

            // Convert distance (0 = identical, 2 = opposite) to score (0–50)
            $distance = (float) $avgDistance->avg_distance;

            return match (true) {
                $distance <= 0.3 => 50,
                $distance <= 0.5 => 40,
                $distance <= 0.7 => 30,
                $distance <= 0.9 => 20,
                $distance <= 1.2 => 10,
                default => 0,
            };
        } catch (Throwable) {
            return 0;
        }
    }
}
