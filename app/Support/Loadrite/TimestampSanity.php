<?php

declare(strict_types=1);

namespace App\Support\Loadrite;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * Centralised sanity checks for Loadrite and rake timestamps.
 *
 * Provides static helpers to detect future timestamps, inverted
 * start/end pairs, and implausibly long durations that indicate
 * data-quality problems rather than real operational events.
 */
final class TimestampSanity
{
    /**
     * Maximum number of days considered a plausible rake turnaround.
     * Anything longer is almost certainly a stale-timestamp bug.
     */
    public const int MAX_REASONABLE_TURNAROUND_DAYS = 14;

    /**
     * True when `$t` is non-null AND its value is not in the future.
     */
    public static function isReasonablePast(?DateTimeInterface $t): bool
    {
        if ($t === null) {
            return false;
        }

        return $t->getTimestamp() <= CarbonImmutable::now()->getTimestamp();
    }

    /**
     * True when:
     *  - both timestamps are non-null
     *  - both are in the past (or at most "now")
     *  - start <= end
     *  - end − start <= $maxDays days
     */
    public static function isReasonableTurnaround(
        ?DateTimeInterface $start,
        ?DateTimeInterface $end,
        int $maxDays = self::MAX_REASONABLE_TURNAROUND_DAYS,
    ): bool {
        if ($start === null || $end === null) {
            return false;
        }

        if (! self::isReasonablePast($start)) {
            return false;
        }

        $startTs = $start->getTimestamp();
        $endTs = $end->getTimestamp();

        if ($endTs < $startTs) {
            return false;
        }

        $deltaSeconds = $endTs - $startTs;
        $maxSeconds = $maxDays * 24 * 3600;

        return $deltaSeconds <= $maxSeconds;
    }
}
