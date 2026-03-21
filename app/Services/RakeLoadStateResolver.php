<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Rake;
use App\Models\RakeLoad;

final readonly class RakeLoadStateResolver
{
    public const STEP_PLACEMENT = 'placement';

    public const STEP_WAGON_LOADING = 'wagon_loading';

    public const STEP_GUARD_INSPECTION = 'guard_inspection';

    public const STEP_DISPATCH = 'dispatch';

    /**
     * @return array{active_step: string, attempt_no: int, failure_reason: string|null}
     */
    public function resolve(Rake $rake): array
    {
        $rakeLoad = $rake->rakeLoad;

        if (! $rakeLoad) {
            return [
                'active_step' => self::STEP_PLACEMENT,
                'attempt_no' => 1,
                'failure_reason' => null,
            ];
        }

        $attemptNo = $this->currentAttemptNo($rakeLoad);
        $loadingComplete = $this->allFitWagonsHavePositiveLoadingForAttempt($rake, $rakeLoad, $attemptNo);

        $latestApprovedInspection = $rakeLoad->guardInspections()
            ->where('is_approved', true)
            ->where('attempt_no', $attemptNo)
            ->latest('inspection_time')
            ->first();

        $needsNewInspection = $loadingComplete && ! $latestApprovedInspection;

        if ($rakeLoad->status === 'completed') {
            return [
                'active_step' => self::STEP_DISPATCH,
                'attempt_no' => $attemptNo,
                'failure_reason' => null,
            ];
        }

        if (! $loadingComplete) {
            return [
                'active_step' => self::STEP_WAGON_LOADING,
                'attempt_no' => $attemptNo,
                'failure_reason' => null,
            ];
        }

        if ($needsNewInspection) {
            return [
                'active_step' => self::STEP_GUARD_INSPECTION,
                'attempt_no' => $attemptNo,
                'failure_reason' => null,
            ];
        }

        // After guard inspection, go straight to dispatch (no in-flow weighment step)
        return [
            'active_step' => self::STEP_DISPATCH,
            'attempt_no' => $attemptNo,
            'failure_reason' => null,
        ];
    }

    private function currentAttemptNo(RakeLoad $rakeLoad): int
    {
        $maxWagonAttempt = $rakeLoad->wagonLoadings()->max('attempt_no');

        return max(1, (int) $maxWagonAttempt);
    }

    /**
     * Legacy rake load flow: status uses fit wagons only, each with quantity > 0 for the attempt.
     */
    private function allFitWagonsHavePositiveLoadingForAttempt(Rake $rake, RakeLoad $rakeLoad, int $attemptNo): bool
    {
        $fitWagonIds = $rake->wagons()->where('is_unfit', false)->pluck('id')->map(static fn ($id): int => (int) $id);
        if ($fitWagonIds->isEmpty()) {
            return false;
        }

        $loadedFitIds = $rakeLoad->wagonLoadings()
            ->where('attempt_no', $attemptNo)
            ->whereIn('wagon_id', $fitWagonIds)
            ->where('loaded_quantity_mt', '>', 0)
            ->pluck('wagon_id')
            ->map(static fn ($id): int => (int) $id)
            ->unique();

        return $fitWagonIds->every(static fn (int $id): bool => $loadedFitIds->contains($id));
    }
}
