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
        $loadedCount = $rakeLoad->wagonLoadings()->where('attempt_no', $attemptNo)->count();
        $totalWagons = $rake->wagons()->where('is_unfit', false)->count();
        $loadingComplete = $totalWagons > 0 && $loadedCount >= $totalWagons;

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
}
