<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

final readonly class ShiftValidationService
{
    /**
     * Define shift time ranges
     * Shift 1: 06:00 - 11:00
     * Shift 2: 11:00 - 22:00
     * Shift 3: 22:00 - 06:00 (next day)
     */
    private const SHIFT_TIMES = [
        1 => ['start' => '06:00', 'end' => '11:00'],
        2 => ['start' => '11:00', 'end' => '22:00'],
        3 => ['start' => '22:00', 'end' => '06:00'], // Overnight shift
    ];

    /**
     * Get the currently active shift based on current time
     */
    public function getCurrentActiveShift(?Carbon $dateTime = null): int
    {
        $now = $dateTime ?? Carbon::now();
        $currentTime = $now->format('H:i');

        foreach (self::SHIFT_TIMES as $shift => $times) {
            if ($this->isTimeInRange($currentTime, $times['start'], $times['end'])) {
                return $shift;
            }
        }

        return 1; // Default fallback
    }

    /**
     * Check if a specific shift is active at the given time
     */
    public function isShiftActive(int $shift, ?Carbon $dateTime = null): bool
    {
        if (! isset(self::SHIFT_TIMES[$shift])) {
            return false;
        }

        $now = $dateTime ?? Carbon::now();
        $currentTime = $now->format('H:i');
        $times = self::SHIFT_TIMES[$shift];

        return $this->isTimeInRange($currentTime, $times['start'], $times['end']);
    }

    /**
     * Get all available shifts (completed shifts + current active shift)
     */
    public function getAvailableShifts(?Carbon $dateTime = null): Collection
    {
        $now = $dateTime ?? Carbon::now();
        $currentTime = $now->format('H:i');
        $availableShifts = collect();

        // Simple time-based logic
        // Shift 1 (06:00-11:00): Available from 06:00 onwards
        if ($currentTime >= '06:00') {
            $availableShifts->push(1);
        }

        // Shift 2 (11:00-22:00): Available from 11:00 onwards
        if ($currentTime >= '11:00') {
            $availableShifts->push(2);
        }

        // Shift 3 (22:00-06:00): Available from 22:00 onwards, or before 06:00 (overnight)
        if ($currentTime >= '22:00' || $currentTime < '06:00') {
            $availableShifts->push(3);
        }

        return $availableShifts->unique()->sort()->values();
    }

    /**
     * Check if a shift can be accessed based on time and previous shift completion
     */
    public function canAccessShift(int $requestedShift, string $date, ?Carbon $currentTime = null): bool
    {
        $now = $currentTime ?? Carbon::now();

        // If requested date is not today, allow access to all shifts
        if ($date !== $now->format('Y-m-d')) {
            return true;
        }

        $availableShifts = $this->getAvailableShifts($now);

        return $availableShifts->contains($requestedShift);
    }

    /**
     * Get shift completion status for a given date
     */
    public function getShiftCompletionStatus(string $date): array
    {
        $status = [];
        $now = Carbon::now();
        $isToday = $date === $now->format('Y-m-d');

        for ($shift = 1; $shift <= 3; $shift++) {
            $status[$shift] = [
                'is_active' => $isToday && $this->isShiftActive($shift, $now),
                'is_available' => ! $isToday || $this->canAccessShift($shift, $date, $now),
                'is_completed' => $isToday ? $this->isShiftCompleted($shift, $now) : true,
            ];
        }

        return $status;
    }

    /**
     * Get shift time range for display
     */
    public function getShiftTimeRange(int $shift): array
    {
        return self::SHIFT_TIMES[$shift] ?? ['start' => '00:00', 'end' => '00:00'];
    }

    /**
     * Get shift display name with time
     */
    public function getShiftDisplayName(int $shift): string
    {
        $names = [
            1 => '1ST SHIFT',
            2 => '2ND SHIFT',
            3 => '3RD SHIFT',
        ];

        $times = $this->getShiftTimeRange($shift);
        $baseName = $names[$shift] ?? "SHIFT {$shift}";

        return "{$baseName} ({$times['start']} - {$times['end']})";
    }

    /**
     * Check if a shift is completed (time has passed)
     */
    private function isShiftCompleted(int $shift, Carbon $currentTime): bool
    {
        if (! isset(self::SHIFT_TIMES[$shift])) {
            return false;
        }

        $times = self::SHIFT_TIMES[$shift];
        $current = $currentTime->format('H:i');

        // For overnight shift (3), it's completed when we're past 06:00
        if ($shift === 3) {
            return $current >= '06:00';
        }

        // For other shifts, they're completed when current time is past their end time
        return $current >= $times['end'];
    }

    /**
     * Check if a time falls within a range, handling overnight ranges
     */
    private function isTimeInRange(string $currentTime, string $startTime, string $endTime): bool
    {
        // Handle overnight shift (22:00 - 06:00)
        if ($startTime > $endTime) {
            return $currentTime >= $startTime || $currentTime < $endTime;
        }

        // Normal time range
        return $currentTime >= $startTime && $currentTime < $endTime;
    }
}
