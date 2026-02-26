<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DailyVehicleEntry;
use Illuminate\Support\Collection;

final readonly class DailyVehicleEntryService
{
    public function getEntriesByDateAndShift(string $date, int $shift): Collection
    {
        return DailyVehicleEntry::query()
            ->with(['siding', 'creator', 'updater'])
            ->where('entry_date', $date)
            ->where('shift', $shift)
            ->orderBy('created_at', 'desc') // Newest first
            ->get();
    }

    public function createEntry(array $data): DailyVehicleEntry
    {
        return DailyVehicleEntry::create([
            ...$data,
            'reached_at' => now(),
            'created_by' => auth()->id(),
        ]);
    }

    public function updateEntry(DailyVehicleEntry $entry, array $data): DailyVehicleEntry
    {
        $entry->update([
            ...$data,
            'updated_by' => auth()->id(),
        ]);

        return $entry->fresh();
    }

    public function markCompleted(DailyVehicleEntry $entry): DailyVehicleEntry
    {
        $entry->update([
            'status' => 'completed',
            'updated_by' => auth()->id(),
        ]);

        return $entry->fresh();
    }

    public function getShiftSummary(string $date): array
    {
        $summary = [];
        
        for ($shift = 1; $shift <= 3; $shift++) {
            $count = DailyVehicleEntry::where('entry_date', $date)
                ->where('shift', $shift)
                ->count();
                
            $summary[$shift] = $count;
        }
        
        return $summary;
    }
}