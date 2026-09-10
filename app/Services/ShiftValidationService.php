<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Siding;
use App\Models\SidingShift;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final readonly class ShiftValidationService
{
    /** Minutes after nominal shift end when operators may still finish entries. */
    public const SHIFT_END_GRACE_MINUTES = 5;

    /**
     * Fallback when no siding shifts exist in DB (e.g. before seed).
     * Client timings: 1st 12:01 AM–08:00 AM, 2nd 08:01 AM–04:00 PM, 3rd 04:01 PM–12:00 AM
     */
    private const SHIFT_TIMES_FALLBACK = [
        1 => ['start' => '00:01', 'end' => '08:00'],
        2 => ['start' => '08:01', 'end' => '16:00'],
        3 => ['start' => '16:01', 'end' => '00:00'],
    ];

    /**
     * Get shift time ranges from DB for a siding (or first siding when null).
     *
     * @return array<int, array{start: string, end: string}>
     */
    public function getShiftTimesForSiding(?int $sidingId = null): array
    {
        $resolvedId = $sidingId ?? Siding::query()->orderBy('name')->value('id');
        if ($resolvedId === null) {
            return self::SHIFT_TIMES_FALLBACK;
        }

        $shifts = SidingShift::query()
            ->where('siding_id', $resolvedId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($shifts->count() < 3) {
            return self::SHIFT_TIMES_FALLBACK;
        }

        $map = [];
        foreach ($shifts->take(3) as $index => $shift) {
            $map[$index + 1] = [
                'start' => $shift->start_time->format('H:i'),
                'end' => $shift->end_time->format('H:i'),
            ];
        }

        return $map;
    }

    /**
     * Get the currently active shift based on current time
     */
    public function getCurrentActiveShift(?Carbon $dateTime = null, ?int $sidingId = null): int
    {
        $times = $this->getShiftTimesForSiding($sidingId);
        $now = $dateTime ?? Carbon::now();
        $currentTime = $now->format('H:i');

        foreach ($times as $shift => $range) {
            if ($this->isTimeInRange($currentTime, $range['start'], $range['end'])) {
                return $shift;
            }
        }

        return 1;
    }

    /**
     * Check if a specific shift is active at the given time
     */
    public function isShiftActive(int $shift, ?Carbon $dateTime = null, ?int $sidingId = null): bool
    {
        $times = $this->getShiftTimesForSiding($sidingId);
        if (! isset($times[$shift])) {
            return false;
        }

        $now = $dateTime ?? Carbon::now();
        $currentTime = $now->format('H:i');

        return $this->isTimeInRange($currentTime, $times[$shift]['start'], $times[$shift]['end']);
    }

    /**
     * Whether {@see $now} falls in [nominal start, nominal end + grace] for this shift (wall-clock).
     * Overnight shifts (start time > end time) use the segment that spans midnight.
     */
    public function isShiftWithinExtendedWindow(int $shift, ?Carbon $dateTime = null, ?int $sidingId = null, int $graceMinutes = self::SHIFT_END_GRACE_MINUTES): bool
    {
        $times = $this->getShiftTimesForSiding($sidingId);
        if (! isset($times[$shift])) {
            return false;
        }

        $now = $dateTime ?? Carbon::now();
        $start = $times[$shift]['start'];
        $end = $times[$shift]['end'];

        if ($start > $end) {
            return $this->isNowInOvernightExtendedWindow($now, $start, $end, $graceMinutes);
        }

        $day = $now->copy()->startOfDay();
        $windowStart = $this->timeOnCalendarDay($day, $start);
        $windowEnd = $this->timeOnCalendarDay($day, $end)->addMinutes($graceMinutes);

        return $now->greaterThanOrEqualTo($windowStart) && $now->lessThanOrEqualTo($windowEnd);
    }

    /**
     * Among assigned shift numbers, the single editable shift for time-lock UX: lowest shift whose
     * extended window contains {@see $now}. Null if none.
     *
     * @param  array<int, int>  $allowedShiftOrders
     */
    public function resolveEditableShiftForAssignments(array $allowedShiftOrders, ?int $sidingId = null, ?Carbon $dateTime = null, int $graceMinutes = self::SHIFT_END_GRACE_MINUTES): ?int
    {
        $now = $dateTime ?? Carbon::now();
        $orders = collect($allowedShiftOrders)
            ->map(fn (mixed $s): int => (int) $s)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $candidates = [];
        foreach ($orders as $order) {
            if ($this->isShiftWithinExtendedWindow($order, $now, $sidingId, $graceMinutes)) {
                $candidates[] = $order;
            }
        }

        if ($candidates === []) {
            return null;
        }

        return min($candidates);
    }

    /**
     * Inclusive end instant of the extended window that contains {@see $now} for this shift, if any.
     */
    public function getContainingExtendedWindowEnd(int $shift, ?Carbon $dateTime = null, ?int $sidingId = null, int $graceMinutes = self::SHIFT_END_GRACE_MINUTES): ?Carbon
    {
        if (! $this->isShiftWithinExtendedWindow($shift, $dateTime, $sidingId, $graceMinutes)) {
            return null;
        }

        $times = $this->getShiftTimesForSiding($sidingId);
        if (! isset($times[$shift])) {
            return null;
        }

        $now = $dateTime ?? Carbon::now();
        $start = $times[$shift]['start'];
        $end = $times[$shift]['end'];

        if ($start > $end) {
            return $this->getOvernightExtendedWindowEndContaining($now, $start, $end, $graceMinutes);
        }

        $day = $now->copy()->startOfDay();

        return $this->timeOnCalendarDay($day, $end)->addMinutes($graceMinutes);
    }

    /**
     * Next instant (strictly after {@see $now}) when any assigned shift's extended window starts.
     *
     * @param  array<int, int>  $allowedShiftOrders
     */
    public function getNextAssignedShiftWindowStartAfter(
        Carbon $now,
        int $sidingId,
        array $allowedShiftOrders,
        int $graceMinutes = self::SHIFT_END_GRACE_MINUTES
    ): ?Carbon {
        $orders = collect($allowedShiftOrders)
            ->map(fn (mixed $s): int => (int) $s)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($orders === []) {
            return null;
        }

        $candidates = [];
        foreach ($orders as $shift) {
            $next = $this->getNextShiftNominalStartAfter($now, $sidingId, $shift, $graceMinutes);
            if ($next instanceof Carbon) {
                $candidates[] = $next;
            }
        }

        if ($candidates === []) {
            return null;
        }

        return collect($candidates)->sortBy(fn (Carbon $c): int => $c->getTimestamp())->first();
    }

    /**
     * Get all available shifts (completed shifts + current active shift) based on siding shift times
     */
    public function getAvailableShifts(?Carbon $dateTime = null, ?int $sidingId = null): Collection
    {
        $times = $this->getShiftTimesForSiding($sidingId);
        $now = $dateTime ?? Carbon::now();
        $currentTime = $now->format('H:i');
        $availableShifts = collect();

        foreach ($times as $shift => $range) {
            $start = $range['start'];
            $end = $range['end'];
            $isOvernight = $start > $end;
            $isActive = $isOvernight
                ? ($currentTime >= $start || $currentTime < $end)
                : ($currentTime >= $start && $currentTime < $end);
            $isCompleted = $isOvernight
                ? ($currentTime >= $end && $currentTime < $start)
                : ($currentTime >= $end);
            if ($isActive || $isCompleted) {
                $availableShifts->push($shift);
            }
        }

        return $availableShifts->unique()->sort()->values();
    }

    /**
     * Check if a shift can be accessed based on time and previous shift completion
     */
    public function canAccessShift(int $requestedShift, string $date, ?Carbon $currentTime = null, ?int $sidingId = null): bool
    {
        $now = $currentTime ?? Carbon::now();

        if ($date !== $now->format('Y-m-d')) {
            return true;
        }

        return $this->getAvailableShifts($now, $sidingId)->contains($requestedShift);
    }

    /**
     * Get shift completion status for a given date
     *
     * @return array<int, array{is_active: bool, is_available: bool, is_completed: bool}>
     */
    public function getShiftCompletionStatus(string $date, ?int $sidingId = null): array
    {
        $status = [];
        $now = Carbon::now();
        $isToday = $date === $now->format('Y-m-d');

        for ($shift = 1; $shift <= 3; $shift++) {
            $status[$shift] = [
                'is_active' => $isToday && $this->isShiftActive($shift, $now, $sidingId),
                'is_available' => ! $isToday || $this->canAccessShift($shift, $date, $now, $sidingId),
                'is_completed' => $isToday ? $this->isShiftCompleted($shift, $now, $sidingId) : true,
            ];
        }

        return $status;
    }

    /**
     * Get shift time range for display
     *
     * @return array{start: string, end: string}
     */
    public function getShiftTimeRange(int $shift, ?int $sidingId = null): array
    {
        $times = $this->getShiftTimesForSiding($sidingId);

        return $times[$shift] ?? ['start' => '00:00', 'end' => '00:00'];
    }

    /**
     * Get shift display name with time
     */
    public function getShiftDisplayName(int $shift, ?int $sidingId = null): string
    {
        $names = [
            1 => '1ST SHIFT',
            2 => '2ND SHIFT',
            3 => '3RD SHIFT',
        ];
        $times = $this->getShiftTimeRange($shift, $sidingId);
        $baseName = $names[$shift] ?? "SHIFT {$shift}";

        return "{$baseName} ({$times['start']} - {$times['end']})";
    }

    /**
     * Check if a shift is completed (time has passed)
     */
    private function isShiftCompleted(int $shift, Carbon $currentTime, ?int $sidingId = null): bool
    {
        $times = $this->getShiftTimesForSiding($sidingId);
        if (! isset($times[$shift])) {
            return false;
        }

        $range = $times[$shift];
        $current = $currentTime->format('H:i');
        $end = $range['end'];

        if ($range['start'] > $end) {
            return $current >= $end && $current < $range['start'];
        }

        return $current >= $end;
    }

    /**
     * Check if a time falls within a range, handling overnight ranges
     */
    private function isTimeInRange(string $currentTime, string $startTime, string $endTime): bool
    {
        if ($startTime > $endTime) {
            return $currentTime >= $startTime || $currentTime < $endTime;
        }

        return $currentTime >= $startTime && $currentTime < $endTime;
    }

    private function timeOnCalendarDay(Carbon $dayStart, string $hi): Carbon
    {
        $parts = explode(':', $hi);
        $hour = (int) ($parts[0] ?? 0);
        $minute = (int) ($parts[1] ?? 0);

        return $dayStart->copy()->startOfDay()->setTime($hour, $minute, 0);
    }

    private function isNowInOvernightExtendedWindow(Carbon $now, string $start, string $end, int $graceMinutes): bool
    {
        $today = $now->copy()->startOfDay();
        $yesterday = $today->copy()->subDay();
        $tomorrow = $today->copy()->addDay();

        $w1Start = $this->timeOnCalendarDay($yesterday, $start);
        $w1End = $this->timeOnCalendarDay($today, $end)->addMinutes($graceMinutes);
        if ($now->greaterThanOrEqualTo($w1Start) && $now->lessThanOrEqualTo($w1End)) {
            return true;
        }

        $w2Start = $this->timeOnCalendarDay($today, $start);
        $w2End = $this->timeOnCalendarDay($tomorrow, $end)->addMinutes($graceMinutes);

        return $now->greaterThanOrEqualTo($w2Start) && $now->lessThanOrEqualTo($w2End);
    }

    private function getOvernightExtendedWindowEndContaining(Carbon $now, string $start, string $end, int $graceMinutes): ?Carbon
    {
        $today = $now->copy()->startOfDay();
        $yesterday = $today->copy()->subDay();
        $tomorrow = $today->copy()->addDay();

        $w1Start = $this->timeOnCalendarDay($yesterday, $start);
        $w1End = $this->timeOnCalendarDay($today, $end)->addMinutes($graceMinutes);
        if ($now->greaterThanOrEqualTo($w1Start) && $now->lessThanOrEqualTo($w1End)) {
            return $w1End;
        }

        $w2Start = $this->timeOnCalendarDay($today, $start);
        $w2End = $this->timeOnCalendarDay($tomorrow, $end)->addMinutes($graceMinutes);
        if ($now->greaterThanOrEqualTo($w2Start) && $now->lessThanOrEqualTo($w2End)) {
            return $w2End;
        }

        return null;
    }

    private function getNextShiftNominalStartAfter(Carbon $now, int $sidingId, int $shift, int $graceMinutes): ?Carbon
    {
        $times = $this->getShiftTimesForSiding($sidingId);
        if (! isset($times[$shift])) {
            return null;
        }

        $start = $times[$shift]['start'];
        $end = $times[$shift]['end'];

        if ($start > $end) {
            return $this->getNextOvernightShiftStartAfter($now, $start, $end);
        }

        for ($i = 0; $i < 4; $i++) {
            $day = $now->copy()->startOfDay()->addDays($i);
            $windowStart = $this->timeOnCalendarDay($day, $start);
            $windowEndGrace = $this->timeOnCalendarDay($day, $end)->addMinutes($graceMinutes);
            if ($now->lessThan($windowStart)) {
                return $windowStart;
            }
            if ($now->lessThanOrEqualTo($windowEndGrace)) {
                return null;
            }
        }

        return $this->timeOnCalendarDay($now->copy()->startOfDay()->addDays(4), $start);
    }

    private function getNextOvernightShiftStartAfter(Carbon $now, string $start, string $end): ?Carbon
    {
        if ($this->isNowInOvernightExtendedWindow($now, $start, $end, self::SHIFT_END_GRACE_MINUTES)) {
            return null;
        }

        $best = null;
        for ($i = -2; $i <= 6; $i++) {
            $day = $now->copy()->startOfDay()->addDays($i);
            $candidate = $this->timeOnCalendarDay($day, $start);
            if ($candidate->greaterThan($now) && ($best === null || $candidate->lessThan($best))) {
                $best = $candidate->copy();
            }
        }

        return $best;
    }
}
