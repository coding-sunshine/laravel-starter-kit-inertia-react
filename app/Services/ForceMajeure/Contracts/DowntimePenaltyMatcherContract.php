<?php

declare(strict_types=1);

namespace App\Services\ForceMajeure\Contracts;

interface DowntimePenaltyMatcherContract
{
    /**
     * Return force-majeure candidate pairs for a given siding.
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
    public function candidates(int $sidingId, int $lookbackDays = 30, int $minOverlapMinutes = 30): array;
}
