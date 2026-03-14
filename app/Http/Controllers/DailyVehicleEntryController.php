<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DailyVehicleEntry;
use App\Models\Siding;
use App\Models\VehicleWorkorder;
use App\Services\DailyVehicleEntryService;
use App\Services\ShiftValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
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
        $user = Auth::user();
        $assignedShift = $user?->getAssignedRoadDispatchShift();

        $date = $request->get('date', now()->format('Y-m-d'));
        $sidingsOrdered = Siding::query()->orderBy('name')->get(['id', 'name']);
        $firstSidingId = $sidingsOrdered->first()?->id;

        $sidingId = $request->has('siding_id') ? (int) $request->get('siding_id') : $firstSidingId;
        $sidingIdForShifts = $sidingId ?? $firstSidingId;

        $restrictToAssignedShift = false;
        if ($assignedShift !== null) {
            $restrictToAssignedShift = true;
            $sidingId = $assignedShift['siding_id'];
        }

        $isToday = $date === now()->format('Y-m-d');
        if ($isToday) {
            $runningShift = $this->shiftValidation->getCurrentActiveShift(null, $sidingIdForShifts);
            $activeShift = $request->has('shift')
                ? (int) $request->get('shift')
                : ($assignedShift['shift'] ?? $runningShift);
        } else {
            $activeShift = (int) $request->get('shift', 1);
            if ($assignedShift !== null) {
                $activeShift = $assignedShift['shift'];
            }
        }

        if ($assignedShift !== null) {
            $activeShift = $assignedShift['shift'];
        }

        if ($isToday && ! $restrictToAssignedShift) {
            if (! $this->shiftValidation->canAccessShift($activeShift, $date, null, $sidingIdForShifts)) {
                $availableShifts = $this->shiftValidation->getAvailableShifts(null, $sidingIdForShifts);
                $defaultShift = $availableShifts->first() ?? 1;

                return redirect()->route('road-dispatch.daily-vehicle-entries.index', [
                    'date' => $date,
                    'shift' => $defaultShift,
                    'siding_id' => $sidingId,
                ]);
            }
        }

        $entries = $this->service->getEntriesByDateAndShift($date, $activeShift, $sidingId);
        $shiftSummary = $this->service->getShiftSummary($date);
        $shiftStatus = $this->shiftValidation->getShiftCompletionStatus($date, $sidingIdForShifts);
        $shiftTimes = $this->shiftValidation->getShiftTimesForSiding($sidingIdForShifts);

        $sidings = $sidingsOrdered;

        return Inertia::render('road-dispatch/daily-vehicle-entries/index', [
            'entries' => $entries->values()->all(),
            'date' => $date,
            'activeShift' => $activeShift,
            'shiftSummary' => $shiftSummary,
            'shiftStatus' => $shiftStatus,
            'shiftTimes' => $shiftTimes,
            'sidings' => $sidings,
            'sidingId' => $sidingId,
            'restrictToAssignedShift' => $restrictToAssignedShift,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        $assignedShift = $user?->getAssignedRoadDispatchShift();

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

        if ($assignedShift !== null) {
            $data['siding_id'] = $assignedShift['siding_id'];
            $data['shift'] = $assignedShift['shift'];
        }

        if ($data['entry_date'] === now()->format('Y-m-d')) {
            $sidingIdForValidation = $data['siding_id'] ?? Siding::query()->orderBy('name')->value('id');
            if (! $this->shiftValidation->canAccessShift($data['shift'], $data['entry_date'], null, $sidingIdForValidation)) {
                throw ValidationException::withMessages([
                    'shift' => 'This shift is not available at the current time. Please wait for the shift to become active.',
                ]);
            }
        }

        $entry = $this->service->createEntry($data);
        $entry = $this->service->updateEntry($entry, []);

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
        $entry = DailyVehicleEntry::findOrFail($id);
        $this->authorizeEntryForShiftUser($entry);

        $data = $request->validate([
            'e_challan_no' => 'nullable|string|max:255',
            'vehicle_no' => 'nullable|string|max:255',
            'trip_id_no' => 'nullable|string|max:255',
            'transport_name' => 'nullable|string|max:255',
            'gross_wt' => 'nullable|numeric|min:0',
            'tare_wt' => 'nullable|numeric|min:0',
            'tare_wt_two' => 'nullable|numeric|min:0',
            'wb_no' => 'nullable|string|max:255',
            'd_challan_no' => 'nullable|string|max:255',
            'challan_mode' => 'nullable|in:offline,online',
            'status' => 'nullable|in:draft,completed',
        ]);

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
        $this->authorizeEntryForShiftUser($entry);
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
        $this->authorizeEntryForShiftUser($entry);

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

    public function lookupVehicle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vehicle_no' => 'required|string|max:255',
        ]);

        $vehicleNo = $validated['vehicle_no'];

        $workorder = VehicleWorkorder::query()
            ->where('vehicle_no', $vehicleNo)
            ->latest('id')
            ->first();

        if ($workorder === null) {
            return response()->json([
                'message' => 'Vehicle workorder not found.',
            ], 404);
        }

        return response()->json([
            'tare_wt' => $workorder->tare_weight ?? null,
            'transport_name' => $workorder->transport_name ?? null,
        ]);
    }

    public function export(Request $request): Response|RedirectResponse
    {
        $user = Auth::user();
        $assignedShift = $user?->getAssignedRoadDispatchShift();

        $data = $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'siding' => 'required|integer|exists:sidings,id',
            'shift' => 'required|in:all,1,2,3',
        ]);

        if ($assignedShift !== null) {
            $data['siding'] = $assignedShift['siding_id'];
            $data['shift'] = (string) $assignedShift['shift'];
        }

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

    /**
     * Deny shift users from accessing entries outside their assigned siding and shift.
     */
    private function authorizeEntryForShiftUser(DailyVehicleEntry $entry): void
    {
        $assigned = Auth::user()?->getAssignedRoadDispatchShift();
        if ($assigned === null) {
            return;
        }
        if ($entry->siding_id !== $assigned['siding_id'] || (int) $entry->shift !== $assigned['shift']) {
            abort(403, 'You can only manage entries for your assigned shift.');
        }
    }
}
