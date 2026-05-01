<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\LoadriteDowntimeEvent;
use App\Models\PenaltyReconciliation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class StitchForceMajeureDisputesAction
{
    private const MIN_OVERLAP_MINUTES = 15;

    public function handle(int $lookbackDays = 30): ForceMajeureStitchOutcome
    {
        $candidates = [];
        $rakesScanned = 0;
        $eventsConsidered = 0;

        DB::transaction(function () use ($lookbackDays, &$candidates, &$rakesScanned, &$eventsConsidered): void {
            $reconciliations = PenaltyReconciliation::query()
                ->where('penalty_code', 'DEM')
                ->where('dispute_candidate', false)
                ->whereNull('notes->force_majeure')
                ->where('reconciled_at', '>=', now()->subDays($lookbackDays))
                ->with(['rake:id,siding_id,placement_time,loading_end_time'])
                ->get();

            $rakesScanned = $reconciliations->count();

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

                $eventsConsidered += $events->count();

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

                if ($totalOverlap < self::MIN_OVERLAP_MINUTES) {
                    continue;
                }

                $reconciliation->update([
                    'dispute_candidate' => true,
                    'notes' => array_merge($reconciliation->notes ?? [], [
                        'force_majeure' => [
                            'overlap_minutes' => $totalOverlap,
                            'reason' => $reasons[0] ?? 'Loadrite downtime overlap',
                        ],
                        'force_majeure_detail' => [
                            'reasons_all' => $reasons,
                            'event_ids' => $eventIds,
                            'stitched_at' => now()->toIso8601String(),
                        ],
                    ]),
                ]);

                $candidates[] = [
                    'rake_id' => $rake->id,
                    'downtime_event_id' => $eventIds[0],
                    'overlap_minutes' => $totalOverlap,
                    'reason' => $reasons[0] ?? 'Loadrite downtime overlap',
                ];
            }
        });

        Log::info('penalty.force_majeure.stitched', [
            'rakes_scanned' => $rakesScanned,
            'events_considered' => $eventsConsidered,
            'candidates_flagged' => count($candidates),
        ]);

        return new ForceMajeureStitchOutcome($candidates, $rakesScanned, $eventsConsidered);
    }
}
