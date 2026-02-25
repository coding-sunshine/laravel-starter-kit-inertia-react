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

    public const STEP_WEIGHMENT = 'weighment';

    public const STEP_DISPATCH = 'dispatch';

    public const FAILURE_SPEED = 'speed';

    public const FAILURE_OVERLOAD = 'overload';

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

        // Get the latest approved inspection for the current attempt
        $latestApprovedInspection = $rakeLoad->guardInspections()
            ->where('is_approved', true)
            ->where('attempt_no', $attemptNo)
            ->latest('inspection_time')
            ->first();
        
        $latestWeighment = $rakeLoad->weighments()->latest('weighment_time')->first();
        $weighmentPassed = $latestWeighment?->status === 'passed';
        $weighmentFailedOverload = $latestWeighment?->status === 'failed_overload';
        $weighmentFailedSpeed = $latestWeighment?->status === 'failed_speed';

        // Simple logic: if loading is complete and we have no approved inspection for current attempt, we need one
        $needsNewInspection = $loadingComplete && !$latestApprovedInspection;

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
                'failure_reason' => $weighmentFailedOverload ? self::FAILURE_OVERLOAD : null,
            ];
        }

        if ($needsNewInspection) {
            return [
                'active_step' => self::STEP_GUARD_INSPECTION,
                'attempt_no' => $attemptNo,
                'failure_reason' => null,
            ];
        }

        if (! $weighmentPassed) {
            return [
                'active_step' => self::STEP_WEIGHMENT,
                'attempt_no' => $attemptNo,
                'failure_reason' => $weighmentFailedSpeed ? self::FAILURE_SPEED : ($weighmentFailedOverload ? self::FAILURE_OVERLOAD : null),
            ];
        }

        return [
            'active_step' => self::STEP_DISPATCH,
            'attempt_no' => $attemptNo,
            'failure_reason' => null,
        ];
    }

    private function currentAttemptNo(RakeLoad $rakeLoad): int
    {
        $latestWeighment = $rakeLoad->weighments()->latest('weighment_time')->first();

        if ($latestWeighment && $latestWeighment->status === 'failed_overload') {
            return $latestWeighment->attempt_no + 1;
        }

        $maxWagonAttempt = $rakeLoad->wagonLoadings()->max('attempt_no');

        return max(1, (int) $maxWagonAttempt);
    }
}
