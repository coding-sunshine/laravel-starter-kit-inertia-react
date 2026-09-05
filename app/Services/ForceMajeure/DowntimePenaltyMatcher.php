<?php

declare(strict_types=1);

namespace App\Services\ForceMajeure;

use App\Models\LoadriteDowntimeEvent;
use App\Models\PenaltyReconciliation;
use App\Services\ForceMajeure\Contracts\DowntimePenaltyMatcherContract;

/**
 * Pure read-only service that pairs LoadriteDowntimeEvent rows with
 * PenaltyReconciliation (DEM) rows for force-majeure dispute detection.
 *
 * No DB writes. Safe to call from both DisputesStitchForceMajeure and CollectSignals.
 */
final readonly class DowntimePenaltyMatcher implements DowntimePenaltyMatcherContract
{
    /**
     * Default minimum overlap for the manager-brief signal pipeline.
     * Callers that have a different policy threshold (e.g. the existing
     * dispute-stitching action runs at 15 min) pass an override.
     */
    public const MIN_OVERLAP_MINUTES = 30;

    /**
     * Return force-majeure candidate pairs for a given siding.
     *
     * A candidate is a DEM reconciliation whose rake's loading window overlaps
     * a LoadriteDowntimeEvent for the same siding by at least
     * `$minOverlapMinutes`, and has not already been flagged as a dispute
     * candidate via force_majeure notes.
     *
     * @return list<array{
     *     rake_id: int,
     *     reconciliation_id: int,
     *     downtime_event_id: int,
     *     overlap_minutes: int,
     *     reason: string,
     *     reasons_all: list<string>,
     *     event_ids: list<int>,
     * }>
     */
    public function candidates(int $sidingId, int $lookbackDays = 30, int $minOverlapMinutes = self::MIN_OVERLAP_MINUTES): array
    {
        $candidates = [];

        $reconciliations = PenaltyReconciliation::query()
            ->where('penalty_code', 'DEM')
            ->where('dispute_candidate', false)
            ->whereNull('notes->force_majeure')
            ->where('reconciled_at', '>=', now()->subDays($lookbackDays))
            ->with(['rake:id,siding_id,placement_time,loading_end_time'])
            ->whereHas('rake', fn ($q) => $q->where('siding_id', $sidingId))
            ->get();

        foreach ($reconciliations as $reconciliation) {
            $rake = $reconciliation->rake;

            if ($rake === null || $rake->placement_time === null || $rake->loading_end_time === null) {
                continue;
            }

            $events = LoadriteDowntimeEvent::query()
                ->where('siding_id', $rake->siding_id)
                ->where('start_local_time', '<', $rake->loading_end_time)
                ->where(function ($q) use ($rake): void {
                    $q->whereNull('end_local_time')
                        ->orWhere('end_local_time', '>', $rake->placement_time);
                })
                ->get();

            $totalOverlap = 0;
            $reasons = [];
            $eventIds = [];

            foreach ($events as $event) {
                $start = max($event->start_local_time->getTimestamp(), $rake->placement_time->getTimestamp());
                $end = min(
                    ($event->end_local_time ?? now())->getTimestamp(),
                    $rake->loading_end_time->getTimestamp(),
                );
                $overlapSeconds = $end - $start;

                if ($overlapSeconds <= 0) {
                    continue;
                }

                $totalOverlap += (int) floor($overlapSeconds / 60);

                if ($event->reason_name !== null && ! in_array($event->reason_name, $reasons, true)) {
                    $reasons[] = $event->reason_name;
                }

                $eventIds[] = $event->id;
            }

            if ($totalOverlap < $minOverlapMinutes) {
                continue;
            }

            $candidates[] = [
                'rake_id' => $rake->id,
                'reconciliation_id' => $reconciliation->id,
                'downtime_event_id' => $eventIds[0],
                'overlap_minutes' => $totalOverlap,
                'reason' => $reasons[0] ?? 'Loadrite downtime overlap',
                'reasons_all' => $reasons,
                'event_ids' => $eventIds,
            ];
        }

        return $candidates;
    }
}
