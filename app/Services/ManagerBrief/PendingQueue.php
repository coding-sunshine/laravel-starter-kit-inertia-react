<?php

declare(strict_types=1);

namespace App\Services\ManagerBrief;

use App\Models\LoadingOverride;
use App\Services\ForceMajeure\Contracts\DowntimePenaltyMatcherContract;

/**
 * Returns counts that drive the "Pending" widget on the manager-brief page.
 *
 * - overrides_pending: LoadingOverride rows for rakes at this siding that have not yet
 *   been reviewed by a supervisor (supervisor_review_at IS NULL).
 * - overrides_oldest_minutes: age of the oldest pending override in minutes, or null if none.
 * - disputes_ready: force-majeure candidates from DowntimePenaltyMatcher ready to file.
 * - disputes_estimated_rs: rough monetary estimate for those disputes.
 *   Formula: (overlap_minutes / 60) × rs_per_hour (demurrage rate).
 *   The previous formula (overlap_minutes × rs_per_mt) was dimensionally wrong
 *   (minutes × Rs/MT produces a unit-less nonsense number).
 */
final readonly class PendingQueue
{
    private const float DEFAULT_RS_PER_HOUR = 5000.0;

    public function __construct(
        private DowntimePenaltyMatcherContract $matcher,
    ) {}

    /**
     * @return array{
     *     overrides_pending: int,
     *     overrides_oldest_minutes: ?int,
     *     disputes_ready: int,
     *     disputes_estimated_rs: float,
     * }
     */
    public function handle(int $sidingId): array
    {
        // Recovery value approximation: (overlap_minutes / 60) × rs_per_hour.
        // Using the demurrage hourly rate is dimensionally correct.
        $rsPerHour = (float) config('penalties.demurrage.rs_per_hour', self::DEFAULT_RS_PER_HOUR);

        // Pending overrides: supervisor_review_at IS NULL for rakes at this siding.
        $pending = LoadingOverride::query()
            ->whereNull('supervisor_review_at')
            ->whereHas('rake', fn ($q) => $q->where('siding_id', $sidingId))
            ->orderBy('created_at')
            ->get(['id', 'created_at']);

        $overridesPending = $pending->count();

        $overridesOldestMinutes = $overridesPending > 0
            ? (int) round($pending->first()->created_at->diffInMinutes(now()))
            : null;

        // Dispute candidates via force-majeure matcher.
        $candidates = $this->matcher->candidates($sidingId);

        $disputesReady = count($candidates);

        $disputesEstimatedRs = 0.0;

        foreach ($candidates as $candidate) {
            $disputesEstimatedRs += round(((float) $candidate['overlap_minutes'] / 60.0) * $rsPerHour, 2);
        }

        return [
            'overrides_pending' => $overridesPending,
            'overrides_oldest_minutes' => $overridesOldestMinutes,
            'disputes_ready' => $disputesReady,
            'disputes_estimated_rs' => $disputesEstimatedRs,
        ];
    }
}
