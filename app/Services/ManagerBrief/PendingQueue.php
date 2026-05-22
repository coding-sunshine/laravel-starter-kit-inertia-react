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
 * - disputes_estimated_rs: rough monetary estimate for those disputes
 *   (overlap_minutes × rs_per_mt, where rs_per_mt defaults to 1000).
 */
final readonly class PendingQueue
{
    private const float DEFAULT_RS_PER_MT = 1000.0;

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
        $rsPerMt = (float) config('penalties.overload.rs_per_mt', self::DEFAULT_RS_PER_MT);

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

        $disputesEstimatedRs = (float) array_sum(
            array_map(
                static fn (array $c): float => (float) ($c['overlap_minutes'] * $rsPerMt),
                $candidates,
            ),
        );

        return [
            'overrides_pending' => $overridesPending,
            'overrides_oldest_minutes' => $overridesOldestMinutes,
            'disputes_ready' => $disputesReady,
            'disputes_estimated_rs' => $disputesEstimatedRs,
        ];
    }
}
