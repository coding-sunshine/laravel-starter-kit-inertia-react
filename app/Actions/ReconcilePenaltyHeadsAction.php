<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AppliedPenalty;
use App\Models\PenaltyReconciliation;
use App\Models\Rake;
use App\Models\RrPenaltySnapshot;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Reconcile predicted (applied_penalties) and billed (rr_penalty_snapshots)
 * amounts per penalty head, for a single rake. Idempotent.
 */
final readonly class ReconcilePenaltyHeadsAction
{
    public function handle(Rake $rake): ReconciliationOutcome
    {
        return DB::transaction(function () use ($rake): ReconciliationOutcome {
            $predictedByCode = $this->aggregatePredicted($rake);
            $billedByCode = $this->aggregateBilled($rake);
            $codes = $predictedByCode->keys()->merge($billedByCode->keys())->unique();

            $created = [];
            $updated = [];
            $candidates = [];

            foreach ($codes as $code) {
                $predicted = $predictedByCode->get($code);
                $billed = $billedByCode->get($code);
                $variance = ($billed ?? 0.0) - ($predicted ?? 0.0);
                $variancePct = $predicted !== null && $predicted > 0
                    ? round(($variance / $predicted) * 100, 2)
                    : null;
                $disputeCandidate = $this->isDisputeCandidate($predicted, $billed);

                $row = PenaltyReconciliation::query()->updateOrCreate(
                    ['rake_id' => $rake->id, 'penalty_code' => $code],
                    [
                        'predicted_amount' => $predicted,
                        'billed_amount' => $billed,
                        'variance' => round($variance, 2),
                        'variance_pct' => $variancePct,
                        'dispute_candidate' => $disputeCandidate,
                        'reconciled_at' => now(),
                    ],
                );

                if ($row->wasRecentlyCreated) {
                    $created[] = $row->id;
                } else {
                    $updated[] = $row->id;
                }
                if ($disputeCandidate) {
                    $candidates[] = $row->id;
                }
            }

            return new ReconciliationOutcome(
                rakeId: $rake->id,
                createdIds: $created,
                updatedIds: $updated,
                disputeCandidateIds: $candidates,
            );
        });
    }

    /**
     * @return Collection<string, float>
     */
    private function aggregatePredicted(Rake $rake): Collection
    {
        return AppliedPenalty::query()
            ->join('penalty_types', 'penalty_types.id', '=', 'applied_penalties.penalty_type_id')
            ->where('applied_penalties.rake_id', $rake->id)
            ->groupBy('penalty_types.code')
            ->selectRaw('penalty_types.code as code, sum(applied_penalties.amount) as total')
            ->get()
            ->mapWithKeys(fn ($row): array => [$row->code => (float) $row->total]);
    }

    /**
     * @return Collection<string, float>
     */
    private function aggregateBilled(Rake $rake): Collection
    {
        return RrPenaltySnapshot::query()
            ->where('rake_id', $rake->id)
            ->groupBy('penalty_code')
            ->selectRaw('penalty_code, sum(amount) as total')
            ->get()
            ->mapWithKeys(fn ($row): array => [$row->penalty_code => (float) $row->total]);
    }

    private function isDisputeCandidate(?float $predicted, ?float $billed): bool
    {
        if ($billed === null || $billed <= 0) {
            return false;
        }
        if ($predicted === null) {
            return true;
        }

        return $billed > $predicted * 1.15;
    }
}
