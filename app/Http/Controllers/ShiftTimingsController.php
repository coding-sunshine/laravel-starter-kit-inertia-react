<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Siding;
use App\Models\SidingShift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class ShiftTimingsController extends Controller
{
    /**
     * Default shift times used when creating missing shifts for a siding.
     * Client timings: 1st 12:01 AM–08:00 AM, 2nd 08:01 AM–04:00 PM, 3rd 04:01 PM–12:00 AM.
     */
    private const DEFAULT_SHIFTS = [
        ['shift_name' => '1st Shift', 'start_time' => '00:01:00', 'end_time' => '08:00:00', 'sort_order' => 1],
        ['shift_name' => '2nd Shift', 'start_time' => '08:01:00', 'end_time' => '16:00:00', 'sort_order' => 2],
        ['shift_name' => '3rd Shift', 'start_time' => '16:01:00', 'end_time' => '00:00:00', 'sort_order' => 3],
    ];

    public function index(): InertiaResponse
    {
        $sidings = Siding::query()
            ->with(['shifts' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('name')
            ->get();

        return Inertia::render('MasterData/ShiftTimings/Index', [
            'sidings' => $sidings,
        ]);
    }

    public function edit(Siding $siding): InertiaResponse|RedirectResponse
    {
        $siding->load(['shifts' => fn ($q) => $q->orderBy('sort_order')]);

        $this->ensureShiftsExist($siding);

        $siding->refresh();
        $siding->load(['shifts' => fn ($q) => $q->orderBy('sort_order')]);

        return Inertia::render('MasterData/ShiftTimings/Edit', [
            'siding' => $siding,
        ]);
    }

    public function update(Request $request, Siding $siding): RedirectResponse
    {
        $validated = $request->validate([
            'shifts' => 'required|array|size:3',
            'shifts.0.start_time' => 'required|date_format:H:i',
            'shifts.0.end_time' => 'required|date_format:H:i',
            'shifts.1.start_time' => 'required|date_format:H:i',
            'shifts.1.end_time' => 'required|date_format:H:i',
            'shifts.2.start_time' => 'required|date_format:H:i',
            'shifts.2.end_time' => 'required|date_format:H:i',
        ]);

        $this->ensureShiftsExist($siding);

        $shifts = $siding->shifts()->orderBy('sort_order')->get();

        foreach ($validated['shifts'] as $index => $times) {
            $shift = $shifts->get($index);
            if ($shift) {
                $shift->update([
                    'start_time' => $times['start_time'].':00',
                    'end_time' => $times['end_time'] === '00:00' ? '00:00:00' : $times['end_time'].':00',
                ]);
            }
        }

        return redirect()->route('master-data.shift-timings.index')
            ->with('success', 'Shift timings updated successfully.');
    }

    private function ensureShiftsExist(Siding $siding): void
    {
        $existing = $siding->shifts()->count();
        if ($existing >= 3) {
            return;
        }

        $baseName = str($siding->name)->before(' ')->title()->toString();
        foreach (self::DEFAULT_SHIFTS as $data) {
            SidingShift::query()->firstOrCreate(
                [
                    'siding_id' => $siding->id,
                    'sort_order' => $data['sort_order'],
                ],
                [
                    'shift_name' => "{$baseName} {$data['shift_name']}",
                    'start_time' => $data['start_time'],
                    'end_time' => $data['end_time'],
                    'is_active' => true,
                    'description' => null,
                ],
            );
        }
    }
}
