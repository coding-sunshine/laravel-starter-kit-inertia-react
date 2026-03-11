<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DailyVehicleEntry;
use App\Services\DailyVehicleEntryService;
use App\Services\ShiftValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Throwable;

final class DailyVehicleEntryController extends Controller
{
    public function __construct(
        private DailyVehicleEntryService $service,
        private ShiftValidationService $shiftValidation
    ) {}

    public function index(Request $request): InertiaResponse|RedirectResponse
    {
        $date = $request->get('date', now()->format('Y-m-d'));
        $activeShift = (int) $request->get('shift', 1);

        // Validate shift access for today
        if ($date === now()->format('Y-m-d')) {
            if (! $this->shiftValidation->canAccessShift($activeShift, $date)) {
                $availableShifts = $this->shiftValidation->getAvailableShifts();
                $defaultShift = $availableShifts->first() ?? 1;

                // Redirect to the first available shift if requested shift is not accessible
                if ($activeShift !== $defaultShift) {
                    return redirect()->route('road-dispatch.daily-vehicle-entries.index', [
                        'date' => $date,
                        'shift' => $defaultShift,
                    ]);
                }
            }
        }

        // Return all entries for date+shift; frontend filters by the page siding dropdown
        $entries = $this->service->getEntriesByDateAndShift($date, $activeShift, null);
        $shiftSummary = $this->service->getShiftSummary($date);
        $shiftStatus = $this->shiftValidation->getShiftCompletionStatus($date);
        $shiftTimes = [
            1 => $this->shiftValidation->getShiftTimeRange(1),
            2 => $this->shiftValidation->getShiftTimeRange(2),
            3 => $this->shiftValidation->getShiftTimeRange(3),
        ];

        // Get available sidings for export dropdown and siding filter
        $sidings = \App\Models\Siding::orderBy('name')->get(['id', 'name']);
        $sidingId = $request->has('siding_id') ? (int) $request->get('siding_id') : null;

        return Inertia::render('road-dispatch/daily-vehicle-entries/index', [
            'entries' => $entries->values()->all(),
            'date' => $date,
            'activeShift' => $activeShift,
            'shiftSummary' => $shiftSummary,
            'shiftStatus' => $shiftStatus,
            'shiftTimes' => $shiftTimes,
            'sidings' => $sidings,
            'sidingId' => $sidingId,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'siding_id' => 'required|exists:sidings,id',
            'entry_date' => 'required|date',
            'shift' => 'required|integer|between:1,3',
            'e_challan_no' => 'nullable|string|max:255',
            'vehicle_no' => 'nullable|string|max:255',
            'trip_id_no' => 'nullable|string|max:255',
            'transport_name' => 'nullable|string|max:255',
            'gross_wt' => 'nullable|numeric|min:0',
            'tare_wt' => 'nullable|numeric|min:0',
            'wb_no' => 'nullable|string|max:255',
            'd_challan_no' => 'nullable|string|max:255',
            'challan_mode' => 'nullable|in:offline,online',
        ]);

        // Validate shift access for today
        if ($data['entry_date'] === now()->format('Y-m-d')) {
            if (! $this->shiftValidation->canAccessShift($data['shift'], $data['entry_date'])) {
                throw ValidationException::withMessages([
                    'shift' => 'This shift is not available at the current time. Please wait for the shift to become active.',
                ]);
            }
        }

        // Cannot add a new row until all entries in this shift (and siding) are completed (stock ledger is created on Complete)
        if (DailyVehicleEntry::query()
            ->where('entry_date', $data['entry_date'])
            ->where('shift', $data['shift'])
            ->where('siding_id', $data['siding_id'])
            ->where('status', 'draft')
            ->exists()) {
            throw ValidationException::withMessages([
                'shift' => 'Complete all entries in this shift before adding a new row. Coal stock is recorded when you mark an entry complete.',
            ]);
        }

        $entry = $this->service->createEntry($data);

        if ($request->wantsJson()) {
            return response()->json(['entry' => $entry->load('siding')], 201);
        }

        return redirect()->route('road-dispatch.daily-vehicle-entries.index', [
            'date' => $data['entry_date'],
            'shift' => $data['shift'],
        ]);
    }

    public function update(Request $request, $id): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'e_challan_no' => 'nullable|string|max:255',
            'vehicle_no' => 'nullable|string|max:255',
            'trip_id_no' => 'nullable|string|max:255',
            'transport_name' => 'nullable|string|max:255',
            'gross_wt' => 'nullable|numeric|min:0',
            'tare_wt' => 'nullable|numeric|min:0',
            'wb_no' => 'nullable|string|max:255',
            'd_challan_no' => 'nullable|string|max:255',
            'challan_mode' => 'nullable|in:offline,online',
            'status' => 'nullable|in:draft,completed',
        ]);

        $entry = DailyVehicleEntry::findOrFail($id);
        $this->service->updateEntry($entry, $data);

        if ($request->wantsJson()) {
            return response()->json(['entry' => $entry->fresh()->load('siding')]);
        }

        return redirect()->route('road-dispatch.daily-vehicle-entries.index', [
            'date' => $entry->entry_date,
            'shift' => $entry->shift,
        ]);
    }

    public function markCompleted(Request $request, $id): RedirectResponse|JsonResponse
    {
        $entry = DailyVehicleEntry::findOrFail($id);
        $updatedEntry = $this->service->markCompleted($entry);

        if ($request->wantsJson()) {
            return response()->json(['entry' => $updatedEntry->load('siding')]);
        }

        return redirect()->route('road-dispatch.daily-vehicle-entries.index', [
            'date' => $entry->entry_date,
            'shift' => $entry->shift,
        ]);
    }

    public function destroy(Request $request, $id): RedirectResponse|JsonResponse
    {
        $entry = DailyVehicleEntry::findOrFail($id);

        if ($entry->status !== 'draft') {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Cannot delete completed entries.'], 422);
            }

            return back()->with('error', 'Cannot delete completed entries.');
        }

        $hasData = ! empty($entry->e_challan_no) ||
                   ! empty($entry->vehicle_no) ||
                   ! empty($entry->trip_id_no) ||
                   ! empty($entry->transport_name) ||
                   ! is_null($entry->gross_wt) ||
                   ! is_null($entry->tare_wt) ||
                   ! empty($entry->wb_no) ||
                   ! empty($entry->d_challan_no) ||
                   ! empty($entry->challan_mode);

        if ($hasData) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Cannot delete entries with data.'], 422);
            }

            return back()->with('error', 'Cannot delete entries with data.');
        }

        $entryId = $entry->id;
        $entry->delete();

        if ($request->wantsJson()) {
            return response()->json(['deleted' => true, 'id' => $entryId]);
        }

        return redirect()->route('road-dispatch.daily-vehicle-entries.index', [
            'date' => $entry->entry_date,
            'shift' => $entry->shift,
        ])->with('success', 'Entry deleted successfully.');
    }

    public function export(Request $request): Response|RedirectResponse
    {
        $data = $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'siding' => 'required|integer|exists:sidings,id',
            'shift' => 'required|in:all,1,2,3',
        ]);

        try {
            $filepath = $this->service->exportEntries(
                $data['date'],
                $data['siding'],
                $data['shift']
            );

            $filename = basename($filepath);

            return response()->download($filepath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ])->deleteFileAfterSend(true);
        } catch (Throwable $th) {
            return back()->with('error', 'Failed to export data: '.$th->getMessage());
        }
    }
}
