<?php

declare(strict_types=1);

namespace App\Actions;

/**
 * Pure result struct for StitchForceMajeureDisputesAction.
 * Compute-then-apply split: callers can dry-run before persisting.
 */
final readonly class ForceMajeureStitchOutcome
{
    /**
     * @param  list<array{rake_id: int, downtime_event_id: int, overlap_minutes: int, reason: string}>  $candidates
     */
    public function __construct(
        public array $candidates,
        public int $rakesScanned,
        public int $downtimeEventsConsidered,
    ) {}
}
