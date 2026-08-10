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
 * Signals carrying no monetary exposure (rs_at_stake = 0, e.g.
 * data_quality_anomaly) substitute a severity-based notional rupee value —
 * a plain multiplication scores them 0 so they never reach the brief.
 *
 * Tie-break: when two signals share the same numeric score, the one with the
 * lower `recency_minutes` value (more recent) is ranked first.
 *
 * Pure function — no database access, no I/O, no logging.
 */
final readonly class RankSignals
{
    /**
     * At most this many signals of one type reach the brief, so a single noisy
     * detector (pcc_drift emits one signal per wagon type) cannot fill all 15
     * slots and reduce the brief to five restatements of the same finding.
     */
    private const MAX_PER_TYPE = 3;

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
            $scoreA = self::score($a);
            $scoreB = self::score($b);

            // Higher score first.
            if ($scoreB !== $scoreA) {
                return $scoreB <=> $scoreA;
            }

            // Tie-break: lower recency_minutes (more recent) first.
            return $a->recencyMinutes <=> $b->recencyMinutes;
        });

        $picked = [];
        $overflow = [];
        $perType = [];

        foreach ($signals as $signal) {
            $count = $perType[$signal->type] ?? 0;

            if ($count < self::MAX_PER_TYPE) {
                $picked[] = $signal;
                $perType[$signal->type] = $count + 1;

                continue;
            }

            $overflow[] = $signal;
        }

        // Only backfill from over-represented types when nothing else is left.
        $picked = array_merge($picked, $overflow);

        return array_values(array_slice($picked, 0, 15));
    }

    private static function score(Signal $signal): float
    {
        $rs = $signal->rsAtStake > 0.0 ? $signal->rsAtStake : self::notionalRs($signal->severity);

        return $rs * self::recencyWeight($signal->recencyMinutes) * $signal->actionability;
    }

    /**
     * Stand-in rupee weight for signals that carry no direct monetary exposure,
     * sized so they compete with small-money signals but never outrank a large
     * real one.
     */
    private static function notionalRs(string $severity): float
    {
        return match ($severity) {
            'critical' => 100000.0,
            'high' => 50000.0,
            'medium' => 20000.0,
            default => 5000.0,
        };
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
