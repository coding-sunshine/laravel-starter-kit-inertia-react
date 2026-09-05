<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PenaltyReconciliation;
use App\Services\ForceMajeure\DowntimePenaltyMatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class StitchForceMajeureDisputesAction
{
    public function __construct(private readonly DowntimePenaltyMatcher $matcher) {}

    public function handle(int $lookbackDays = 30): ForceMajeureStitchOutcome
    {
        $candidates = [];
        $rakesScanned = 0;

        DB::transaction(function () use ($lookbackDays, &$candidates, &$rakesScanned): void {
            // Count all DEM reconciliations in the window for the scanned metric.
            $reconciliations = PenaltyReconciliation::query()
                ->where('penalty_code', 'DEM')
                ->where('dispute_candidate', false)
                ->whereNull('notes->force_majeure')
                ->where('reconciled_at', '>=', now()->subDays($lookbackDays))
                ->with(['rake:id,siding_id'])
                ->get();

            $rakesScanned = $reconciliations->count();

            // Collect distinct siding IDs from the in-window reconciliations.
            $sidingIds = $reconciliations
                ->pluck('rake.siding_id')
                ->filter()
                ->unique()
                ->values();

            foreach ($sidingIds as $sidingId) {
                // The dispute-stitching policy keeps the original 15-minute
                // overlap threshold so behaviour is preserved across the
                // matcher extraction. CollectSignals uses the matcher's
                // default (30 min) for the manager-brief signal.
                $matched = $this->matcher->candidates((int) $sidingId, $lookbackDays, 15);

                foreach ($matched as $candidate) {
                    $reconciliation = PenaltyReconciliation::find($candidate['reconciliation_id']);

                    if ($reconciliation === null) {
                        continue;
                    }

                    $reconciliation->update([
                        'dispute_candidate' => true,
                        'notes' => array_merge($reconciliation->notes ?? [], [
                            'force_majeure' => [
                                'overlap_minutes' => $candidate['overlap_minutes'],
                                'reason' => $candidate['reason'],
                            ],
                            'force_majeure_detail' => [
                                'reasons_all' => $candidate['reasons_all'],
                                'event_ids' => $candidate['event_ids'],
                                'stitched_at' => now()->toIso8601String(),
                            ],
                        ]),
                    ]);

                    $candidates[] = [
                        'rake_id' => $candidate['rake_id'],
                        'downtime_event_id' => $candidate['downtime_event_id'],
                        'overlap_minutes' => $candidate['overlap_minutes'],
                        'reason' => $candidate['reason'],
                    ];
                }
            }
        });

        Log::info('penalty.force_majeure.stitched', [
            'rakes_scanned' => $rakesScanned,
            'candidates_flagged' => count($candidates),
        ]);

        return new ForceMajeureStitchOutcome($candidates, $rakesScanned, 0);
    }
}
