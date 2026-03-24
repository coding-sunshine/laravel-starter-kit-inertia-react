<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DailyVehicleEntry;
use App\Models\Siding;
use App\Models\SidingShift;
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
        $assignedShifts = $user?->activeSidingShifts()
            ->with('siding:id,name')
            ->orderByPivot('assigned_at')
            ->orderBy('siding_shift_user.id')
            ->get();
        if ($assignedShifts === null || $assignedShifts->isEmpty()) {
            abort(403, 'No shift and siding assignment found for your account.');
        }
        $allowedSidingIds = $assignedShifts->pluck('siding_id')->unique()->values();
        $sidingShiftPairs = $assignedShifts->map(
            fn (SidingShift $shift): string => $shift->siding_id.'|'.((int) $shift->sort_order)
        )->values();
        $firstAssignment = $assignedShifts->first();
        $restrictToAssignedShift = $assignedShifts->count() === 1 && $firstAssignment !== null;

        $entryType = DailyVehicleEntry::ENTRY_TYPE_ROAD_DISPATCH;

        $date = $request->get('date', now()->format('Y-m-d'));
        $sidingsOrdered = Siding::query()
            ->whereIn('id', $allowedSidingIds->all())
            ->orderBy('name')
            ->get(['id', 'name']);
        if ($sidingsOrdered->isEmpty()) {
            abort(403, 'No siding access found for your account.');
        }
        $firstSidingId = $sidingsOrdered->first()?->id;

        $sidingId = $request->has('siding_id') ? (int) $request->get('siding_id') : $firstSidingId;
        if (! $allowedSidingIds->contains($sidingId)) {
            abort(403, 'You are not allowed to access this siding.');
        }
        if ($restrictToAssignedShift) {
            $sidingId = $firstAssignment->siding_id;
        }
        $sidingIdForShifts = $sidingId ?? $firstSidingId;
        $allowedShifts = $assignedShifts
            ->where('siding_id', $sidingId)
            ->pluck('sort_order')
            ->map(fn (mixed $shift): int => (int) $shift)
            ->unique()
            ->sort()
            ->values();
        if ($allowedShifts->isEmpty()) {
            abort(403, 'No shift access found for the selected siding.');
        }

        $isToday = $date === now()->format('Y-m-d');
        $requestedShift = $request->has('shift') ? (int) $request->get('shift') : null;
        if ($isToday) {
            $runningShift = $this->shiftValidation->getCurrentActiveShift(null, $sidingIdForShifts);
            if ($requestedShift !== null) {
                $activeShift = $requestedShift;
            } elseif ($allowedShifts->contains((int) $runningShift)) {
                $activeShift = (int) $runningShift;
            } else {
                $activeShift = (int) ($allowedShifts->first() ?? 1);
            }
        } else {
            $activeShift = $requestedShift ?? (int) ($allowedShifts->first() ?? 1);
        }

        if ($restrictToAssignedShift) {
            $activeShift = (int) $firstAssignment->sort_order;
        }

        if (! $allowedShifts->contains($activeShift)) {
            $activeShift = (int) ($allowedShifts->first() ?? 1);
        }

        if (! $sidingShiftPairs->contains($sidingId.'|'.$activeShift)) {
            abort(403, 'You are not allowed to access this shift for the selected siding.');
        }

        $entries = $this->service->getEntriesByDateAndShift($date, $activeShift, $sidingId, $entryType);
        $shiftSummary = $this->service->getShiftSummary($date, $entryType);
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
            'allowedShifts' => $allowedShifts->all(),
            'restrictToAssignedShift' => $restrictToAssignedShift,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        $assignedShifts = $user?->activeSidingShifts()->get(['siding_id', 'sort_order']);
        if ($assignedShifts === null || $assignedShifts->isEmpty()) {
            abort(403, 'No shift and siding assignment found for your account.');
        }
        $firstAssignment = $assignedShifts->first();
        $isShiftRestrictedUser = $assignedShifts->count() === 1 && $firstAssignment !== null;
        $sidingShiftPairs = $assignedShifts->map(
            fn (SidingShift $shift): string => $shift->siding_id.'|'.((int) $shift->sort_order)
        )->values();

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
            'remarks' => 'nullable|string|max:2000',
        ]);

        if ($isShiftRestrictedUser) {
            $data['siding_id'] = $firstAssignment->siding_id;
            $data['shift'] = (int) $firstAssignment->sort_order;
        } elseif (! $sidingShiftPairs->contains(((int) $data['siding_id']).'|'.((int) $data['shift']))) {
            abort(403, 'You are not allowed to access this shift for the selected siding.');
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
            'remarks' => 'nullable|string|max:2000',
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
        $assignedShifts = $user?->activeSidingShifts()->get(['siding_id', 'sort_order']);
        if ($assignedShifts === null || $assignedShifts->isEmpty()) {
            abort(403, 'No shift and siding assignment found for your account.');
        }
        $firstAssignment = $assignedShifts->first();
        $isShiftRestrictedUser = $assignedShifts->count() === 1 && $firstAssignment !== null;
        $allowedSidingIds = $assignedShifts->pluck('siding_id')->unique()->values();
        $sidingShiftPairs = $assignedShifts->map(
            fn (SidingShift $shift): string => $shift->siding_id.'|'.((int) $shift->sort_order)
        )->values();

        $data = $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'siding' => 'required|integer|exists:sidings,id',
            'shift' => 'required|in:all,1,2,3',
        ]);

        if ($isShiftRestrictedUser) {
            $data['siding'] = $firstAssignment->siding_id;
            $data['shift'] = (string) $firstAssignment->sort_order;
        }
        if (! $allowedSidingIds->contains((int) $data['siding'])) {
            abort(403, 'You are not allowed to access this siding.');
        }
        if ($data['shift'] !== 'all' && ! $sidingShiftPairs->contains(((int) $data['siding']).'|'.((int) $data['shift']))) {
            abort(403, 'You are not allowed to access this shift for the selected siding.');
        }

        try {
            $filepath = $this->service->exportEntries(
                $data['date'],
                $data['siding'],
                $data['shift'],
                DailyVehicleEntry::ENTRY_TYPE_ROAD_DISPATCH
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
        $user = Auth::user();
        $assignedShifts = $user?->activeSidingShifts()->get(['siding_id', 'sort_order']);
        if ($assignedShifts === null || $assignedShifts->isEmpty()) {
            abort(403, 'No shift and siding assignment found for your account.');
        }
        $pair = $entry->siding_id.'|'.((int) $entry->shift);
        $allowedPairs = $assignedShifts->map(
            fn (SidingShift $shift): string => $shift->siding_id.'|'.((int) $shift->sort_order)
        )->values();
        if (! $allowedPairs->contains($pair)) {
            abort(403, 'You can only manage entries for your assigned shift.');
        }
    }
}
