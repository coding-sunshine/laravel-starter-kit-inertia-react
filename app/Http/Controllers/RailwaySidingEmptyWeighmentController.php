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
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Throwable;

final class RailwaySidingEmptyWeighmentController extends Controller
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
        $activeShift = (int) $request->get('shift', 1);
        $sidingId = $request->has('siding_id') ? (int) $request->get('siding_id') : null;

        $restrictToAssignedShift = $assignedShift !== null && $user?->hasRole('empty-weighment-shift');
        if ($restrictToAssignedShift) {
            $sidingId = $assignedShift['siding_id'];
            $activeShift = $assignedShift['shift'];
        }

        if ($date === now()->format('Y-m-d')) {
            if (! $this->shiftValidation->canAccessShift($activeShift, $date)) {
                $availableShifts = $this->shiftValidation->getAvailableShifts();
                $defaultShift = $availableShifts->first() ?? 1;

                if (! $restrictToAssignedShift && $activeShift !== $defaultShift) {
                    return redirect()->route('railway-siding-empty-weighment.index', [
                        'date' => $date,
                        'shift' => $defaultShift,
                    ]);
                }
            }
        }

        $entries = $this->service->getEntriesByDateAndShift(
            $date,
            $activeShift,
            $sidingId,
            DailyVehicleEntry::ENTRY_TYPE_RAILWAY_SIDING_EMPTY_WEIGHMENT
        );
        $shiftSummary = $this->service->getShiftSummary($date, DailyVehicleEntry::ENTRY_TYPE_RAILWAY_SIDING_EMPTY_WEIGHMENT);
        $shiftStatus = $this->shiftValidation->getShiftCompletionStatus($date);
        $shiftTimes = [
            1 => $this->shiftValidation->getShiftTimeRange(1),
            2 => $this->shiftValidation->getShiftTimeRange(2),
            3 => $this->shiftValidation->getShiftTimeRange(3),
        ];

        $sidings = \App\Models\Siding::orderBy('name')->get(['id', 'name']);

        return Inertia::render('railway-siding-empty-weighment/index', [
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
        ]);

        if ($assignedShift !== null && $user?->hasRole('empty-weighment-shift')) {
            $data['siding_id'] = $assignedShift['siding_id'];
            $data['shift'] = $assignedShift['shift'];
        }

        if ($data['entry_date'] === now()->format('Y-m-d')) {
            if (! $this->shiftValidation->canAccessShift($data['shift'], $data['entry_date'])) {
                throw ValidationException::withMessages([
                    'shift' => 'This shift is not available at the current time. Please wait for the shift to become active.',
                ]);
            }
        }

        $data['entry_type'] = DailyVehicleEntry::ENTRY_TYPE_RAILWAY_SIDING_EMPTY_WEIGHMENT;
        $entry = $this->service->createEntry($data);

        if ($request->wantsJson()) {
            return response()->json(['entry' => $entry->load('siding')], 201);
        }

        return redirect()->route('railway-siding-empty-weighment.index', [
            'date' => $data['entry_date'],
            'shift' => $data['shift'],
        ]);
    }

    public function update(Request $request, DailyVehicleEntry $entry): RedirectResponse|JsonResponse
    {
        $this->authorizeEntryForEmptyWeighmentShiftUser($entry);

        if ($entry->entry_type !== DailyVehicleEntry::ENTRY_TYPE_RAILWAY_SIDING_EMPTY_WEIGHMENT) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Entry does not belong to railway siding empty weighment.'], 403);
            }

            return back()->with('error', 'Entry does not belong to railway siding empty weighment.');
        }

        $data = $request->validate([
            'vehicle_no' => 'nullable|string|max:255',
            'transport_name' => 'nullable|string|max:255',
            'tare_wt_two' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:draft,completed',
        ]);

        $this->service->updateEntry($entry, $data);

        if ($request->wantsJson()) {
            return response()->json(['entry' => $entry->fresh()->load('siding')]);
        }

        return redirect()->route('railway-siding-empty-weighment.index', [
            'date' => $entry->entry_date->format('Y-m-d'),
            'shift' => $entry->shift,
        ]);
    }

    public function markCompleted(Request $request, DailyVehicleEntry $entry): RedirectResponse|JsonResponse
    {
        $this->authorizeEntryForEmptyWeighmentShiftUser($entry);

        if ($entry->entry_type !== DailyVehicleEntry::ENTRY_TYPE_RAILWAY_SIDING_EMPTY_WEIGHMENT) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Entry does not belong to railway siding empty weighment.'], 403);
            }

            return back()->with('error', 'Entry does not belong to railway siding empty weighment.');
        }

        $updatedEntry = $this->service->markCompleted($entry);

        if ($request->wantsJson()) {
            return response()->json(['entry' => $updatedEntry->load('siding')]);
        }

        return redirect()->route('railway-siding-empty-weighment.index', [
            'date' => $entry->entry_date->format('Y-m-d'),
            'shift' => $entry->shift,
        ]);
    }

    public function destroy(Request $request, DailyVehicleEntry $entry): RedirectResponse|JsonResponse
    {
        $this->authorizeEntryForEmptyWeighmentShiftUser($entry);

        if ($entry->entry_type !== DailyVehicleEntry::ENTRY_TYPE_RAILWAY_SIDING_EMPTY_WEIGHMENT) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Entry does not belong to railway siding empty weighment.'], 403);
            }

            return back()->with('error', 'Entry does not belong to railway siding empty weighment.');
        }

        if ($entry->status !== 'draft') {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Cannot delete completed entries.'], 422);
            }

            return back()->with('error', 'Cannot delete completed entries.');
        }

        $hasData = ! empty($entry->vehicle_no) ||
                   ! empty($entry->transport_name) ||
                   ! is_null($entry->tare_wt_two);

        if ($hasData) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Cannot delete entries with data.'], 422);
            }

            return back()->with('error', 'Cannot delete entries with data.');
        }

        $entryId = $entry->id;
        $entryDate = $entry->entry_date->format('Y-m-d');
        $entryShift = $entry->shift;
        $entry->delete();

        if ($request->wantsJson()) {
            return response()->json(['deleted' => true, 'id' => $entryId]);
        }

        return redirect()->route('railway-siding-empty-weighment.index', [
            'date' => $entryDate,
            'shift' => $entryShift,
        ])->with('success', 'Entry deleted successfully.');
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

        if ($assignedShift !== null && $user?->hasRole('empty-weighment-shift')) {
            $data['siding'] = $assignedShift['siding_id'];
            $data['shift'] = (string) $assignedShift['shift'];
        }

        try {
            $filepath = $this->service->exportEntries(
                $data['date'],
                $data['siding'],
                $data['shift'],
                DailyVehicleEntry::ENTRY_TYPE_RAILWAY_SIDING_EMPTY_WEIGHMENT
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

    private function authorizeEntryForEmptyWeighmentShiftUser(DailyVehicleEntry $entry): void
    {
        $assigned = Auth::user()?->getAssignedRoadDispatchShift();
        if ($assigned === null) {
            return;
        }
        if (! Auth::user()?->hasRole('empty-weighment-shift')) {
            return;
        }
        if ($entry->siding_id !== $assigned['siding_id'] || (int) $entry->shift !== $assigned['shift']) {
            abort(403, 'You can only manage entries for your assigned shift.');
        }
    }
}
