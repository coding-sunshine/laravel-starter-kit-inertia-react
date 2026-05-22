<?php

declare(strict_types=1);

namespace App\Actions\ManagerBrief;

use App\DataTransferObjects\ManagerBrief\Signal;

/**
 * Ranks a list of raw signals by composite score and returns the top 15.
 *
 * Scoring formula:
 *   score = rs_at_stake × recencyWeight(recency_minutes) × actionability
 *
 * Tie-break: when two signals share the same numeric score, the one with the
 * lower `recency_minutes` value (more recent) is ranked first.
 *
 * Pure function — no database access, no I/O, no logging.
 */
final readonly class RankSignals
{
    /**
     * Rank signals and return the top 15 (or fewer when input has < 15).
     *
     * @param  list<Signal>  $signals  Unsorted raw signals from CollectSignals.
     * @return list<Signal> Top-15 signals ordered highest score first.
     */
    public function handle(array $signals): array
    {
        if ($signals === []) {
            return [];
        }

        usort($signals, function (Signal $a, Signal $b): int {
            $scoreA = $a->rsAtStake * self::recencyWeight($a->recencyMinutes) * $a->actionability;
            $scoreB = $b->rsAtStake * self::recencyWeight($b->recencyMinutes) * $b->actionability;

            // Higher score first.
            if ($scoreB !== $scoreA) {
                return $scoreB <=> $scoreA;
            }

            // Tie-break: lower recency_minutes (more recent) first.
            return $a->recencyMinutes <=> $b->recencyMinutes;
        });

        return array_values(array_slice($signals, 0, 15));
    }

    /**
     * Linear recency decay weight based on how many minutes ago the signal occurred.
     *
     * Decay table:
     *   ≤ 60 min   (≤ 1 h)   → 1.0  (very fresh)
     *   ≤ 1440 min (≤ 24 h)  → 0.8
     *   ≤ 4320 min (≤ 3 days) → 0.5
     *   ≤ 10080 min (≤ 7 days) → 0.2
     *   > 10080 min (> 7 days) → 0.1  (stale)
     */
    private static function recencyWeight(int $minutes): float
    {
        return match (true) {
            $minutes <= 60 => 1.0,
            $minutes <= 1440 => 0.8,
            $minutes <= 4320 => 0.5,
            $minutes <= 10080 => 0.2,
            default => 0.1,
        };
    }
}
