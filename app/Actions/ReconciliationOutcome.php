<?php

declare(strict_types=1);

namespace App\Actions;

/**
 * Result struct returned by ReconcilePenaltyHeadsAction::handle().
 *
 * Pure data — no behaviour. Lives next to the Action so callers reading the
 * action don't have to chase types across the codebase.
 */
final readonly class ReconciliationOutcome
{
    /**
     * @param  list<int>  $createdIds  IDs of penalty_reconciliations created in this run
     * @param  list<int>  $updatedIds  IDs of penalty_reconciliations updated in this run
     * @param  list<int>  $disputeCandidateIds  IDs flagged as dispute candidates after reconciliation
     */
    public function __construct(
        public int $rakeId,
        public array $createdIds,
        public array $updatedIds,
        public array $disputeCandidateIds,
    ) {}

    public function totalAffected(): int
    {
        return count($this->createdIds) + count($this->updatedIds);
    }
}
