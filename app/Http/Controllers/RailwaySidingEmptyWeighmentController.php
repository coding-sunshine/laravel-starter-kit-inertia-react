<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DailyVehicleEntry;
use App\Models\Siding;
use App\Models\SidingShift;
use App\Services\DailyVehicleEntryService;
use App\Services\ShiftValidationService;
use Carbon\Carbon;
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

        // Keep time-based shift locking consistent with DailyVehicleEntryController.
        $canBypassShiftLock = $this->canBypassShiftLock();
        $timeEditableShift = null;
        $shiftGraceEndsAtIso = null;

        if ($isToday && ! $canBypassShiftLock) {
            $timeEditableShift = $this->shiftValidation->resolveEditableShiftForAssignments(
                $allowedShifts->all(),
                (int) $sidingIdForShifts
            );
            if ($timeEditableShift !== null) {
                $graceEnd = $this->shiftValidation->getContainingExtendedWindowEnd(
                    $timeEditableShift,
                    null,
                    (int) $sidingIdForShifts
                );
                $shiftGraceEndsAtIso = $graceEnd?->toIso8601String();
            }
        }

        if (! $canBypassShiftLock && $isToday && ! $restrictToAssignedShift) {
            if ($timeEditableShift !== null && $activeShift !== $timeEditableShift) {
                return redirect()->route('railway-siding-empty-weighment.index', array_filter([
                    'date' => $date,
                    'shift' => $timeEditableShift,
                    'siding_id' => $sidingId,
                ], fn (mixed $v): bool => $v !== null && $v !== ''));
            }
            if ($timeEditableShift !== null) {
                $activeShift = $timeEditableShift;
            } elseif ($allowedShifts->isNotEmpty()) {
                $activeShift = (int) $allowedShifts->first();
            }
        }

        if (! $sidingShiftPairs->contains($sidingId.'|'.$activeShift)) {
            abort(403, 'You are not allowed to access this shift for the selected siding.');
        }

        $viewAllEntries = $user?->canViewAllRoadDispatchDailyVehicleEntries() ?? false;
        $scopedToUserId = $viewAllEntries ? null : (int) $user->id;

        $entries = DailyVehicleEntry::query()
            ->with(['siding'])
            ->where('entry_date', $date)
            ->where('shift', $activeShift)
            ->where('siding_id', $sidingId)
            ->where('entry_type', DailyVehicleEntry::ENTRY_TYPE_RAILWAY_SIDING_EMPTY_WEIGHMENT)
            ->when(
                $scopedToUserId !== null,
                fn ($q) => $q->where('created_by', $scopedToUserId),
            )
            ->orderBy('created_at', 'asc')
            ->get();

        $shiftSummary = [];
        for ($shift = 1; $shift <= 3; $shift++) {
            $shiftSummary[$shift] = DailyVehicleEntry::query()
                ->where('entry_date', $date)
                ->where('shift', $shift)
                ->where('siding_id', $sidingId)
                ->where('entry_type', DailyVehicleEntry::ENTRY_TYPE_RAILWAY_SIDING_EMPTY_WEIGHMENT)
                ->when(
                    $scopedToUserId !== null,
                    fn ($q) => $q->where('created_by', $scopedToUserId),
                )
                ->count();
        }
        $shiftStatus = $this->shiftValidation->getShiftCompletionStatus($date, $sidingIdForShifts);
        $shiftTimes = $this->shiftValidation->getShiftTimesForSiding($sidingIdForShifts);

        $shiftLock = $this->buildShiftLock(
            date: (string) $date,
            sidingId: (int) $sidingIdForShifts,
            allowedShifts: $allowedShifts->all(),
            canBypass: $canBypassShiftLock
        );

        $sidings = $sidingsOrdered;

        return Inertia::render('railway-siding-empty-weighment/index', [
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
            'canBypassShiftLock' => $canBypassShiftLock,
            'shiftLock' => $shiftLock,
            'timeEditableShift' => $timeEditableShift,
            'shiftGraceEndsAtIso' => $shiftGraceEndsAtIso,
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
            'vehicle_no' => 'nullable|string|max:255',
            'transport_name' => 'nullable|string|max:255',
            'tare_wt_two' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:draft,completed',
            'reached_at' => 'required|date',
            'inline_submit' => 'sometimes|boolean',
        ]);

        $inlineSubmit = $request->boolean('inline_submit');
        unset($data['inline_submit']);

        $this->assertReachedAtDateMatchesEntryDate(
            (string) $data['reached_at'],
            (string) $data['entry_date'],
        );

        if ($isShiftRestrictedUser) {
            $data['siding_id'] = $firstAssignment->siding_id;
            $data['shift'] = (int) $firstAssignment->sort_order;
        } elseif (! $sidingShiftPairs->contains(((int) $data['siding_id']).'|'.((int) $data['shift']))) {
            abort(403, 'You are not allowed to access this shift for the selected siding.');
        }

        $sidingIdForValidation = (int) ($data['siding_id'] ?? 0);
        if (! $this->canBypassShiftLock()) {
            $this->enforceStrictActiveShift(
                shift: (int) $data['shift'],
                entryDate: (string) $data['entry_date'],
                sidingId: $sidingIdForValidation
            );
        }

        $vehicleNo = mb_trim((string) ($data['vehicle_no'] ?? ''));
        $tareWtTwo = (float) ($data['tare_wt_two'] ?? 0);
        $shouldAutoComplete = $vehicleNo !== '' && $tareWtTwo > 0;

        $entry = DailyVehicleEntry::create([
            ...$data,
            'entry_type' => DailyVehicleEntry::ENTRY_TYPE_RAILWAY_SIDING_EMPTY_WEIGHMENT,
            'status' => $shouldAutoComplete ? 'completed' : ($data['status'] ?? 'draft'),
            'created_by' => auth()->id(),
        ]);

        if ($inlineSubmit) {
            $entry->update(['inline_submitted_at' => now()]);
        }

        $entry->refresh();

        if ($request->wantsJson()) {
            return response()->json(['entry' => $entry->load(['siding'])], 201);
        }

        return redirect()->route('railway-siding-empty-weighment.index', [
            'date' => $data['entry_date'],
            'shift' => $data['shift'],
        ]);
    }

    public function update(Request $request, DailyVehicleEntry $entry): RedirectResponse|JsonResponse
    {
        $this->authorizeEntryForEmptyWeighmentShiftUser($entry);
        $this->enforceStrictActiveShiftForEntry($entry);

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
            'reached_at' => 'sometimes|date',
            'inline_submit' => 'sometimes|boolean',
        ]);

        $inlineSubmit = $request->boolean('inline_submit');
        unset($data['inline_submit']);

        if (array_key_exists('reached_at', $data) && is_string($data['reached_at']) && $data['reached_at'] !== '') {
            $this->assertReachedAtDateMatchesEntryDate(
                (string) $data['reached_at'],
                $this->formatEntryDateYmd($entry)
            );
        } else {
            unset($data['reached_at']);
        }

        $vehicleNo = mb_trim((string) ($data['vehicle_no'] ?? $entry->vehicle_no ?? ''));
        $tareWtTwo = (float) ($data['tare_wt_two'] ?? $entry->tare_wt_two ?? 0);
        $shouldAutoComplete = $vehicleNo !== '' && $tareWtTwo > 0;

        $entry->update([
            ...$data,
            'status' => $shouldAutoComplete ? 'completed' : ($data['status'] ?? $entry->status ?? 'draft'),
            'updated_by' => auth()->id(),
        ]);

        if ($inlineSubmit && $entry->inline_submitted_at === null) {
            $entry->update(['inline_submitted_at' => now()]);
        }

        if ($request->wantsJson()) {
            return response()->json(['entry' => $entry->fresh()->load(['siding'])]);
        }

        return redirect()->route('railway-siding-empty-weighment.index', [
            'date' => $entry->entry_date->format('Y-m-d'),
            'shift' => $entry->shift,
        ]);
    }

    public function markCompleted(Request $request, DailyVehicleEntry $entry): RedirectResponse|JsonResponse
    {
        $this->authorizeEntryForEmptyWeighmentShiftUser($entry);
        $this->enforceStrictActiveShiftForEntry($entry);

        if ($entry->entry_type !== DailyVehicleEntry::ENTRY_TYPE_RAILWAY_SIDING_EMPTY_WEIGHMENT) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Entry does not belong to railway siding empty weighment.'], 403);
            }

            return back()->with('error', 'Entry does not belong to railway siding empty weighment.');
        }

        $entry->update([
            'status' => 'completed',
            'updated_by' => auth()->id(),
            'inline_submitted_at' => $entry->inline_submitted_at ?? now(),
        ]);
        $updatedEntry = $entry->fresh();

        if ($request->wantsJson()) {
            return response()->json(['entry' => $updatedEntry->load('siding')]);
        }

        return redirect()->route('railway-siding-empty-weighment.index', [
            'date' => $entry->entry_date->format('Y-m-d'),
            'shift' => $entry->shift,
        ]);
    }

    public function hourlySummary(Request $request): JsonResponse
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

        $firstAssignment = $assignedShifts->first();
        $restrictToAssignedShift = $assignedShifts->count() === 1 && $firstAssignment !== null;

        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'siding_id' => 'required|integer|exists:sidings,id',
        ]);

        $date = (string) $validated['date'];
        $sidingId = (int) $validated['siding_id'];

        if ($restrictToAssignedShift) {
            $date = now()->format('Y-m-d');
            $sidingId = (int) $firstAssignment->siding_id;
        }

        $allowedSidingIds = $assignedShifts->pluck('siding_id')->unique()->values();
        if (! $allowedSidingIds->contains($sidingId)) {
            abort(403, 'You are not allowed to access this siding.');
        }

        $allowedShiftsForSiding = $assignedShifts
            ->where('siding_id', $sidingId)
            ->pluck('sort_order')
            ->map(fn ($v): int => (int) $v)
            ->unique()
            ->sort()
            ->values();

        if ($allowedShiftsForSiding->isEmpty()) {
            abort(403, 'You are not allowed to access this siding.');
        }

        $activeShift = $this->shiftValidation->getCurrentShift();
        $includedShifts = $allowedShiftsForSiding;
        if ($activeShift !== null) {
            $includedShifts = $allowedShiftsForSiding
                ->filter(fn (int $shift): bool => $shift <= $activeShift)
                ->values();
        }

        if ($includedShifts->isEmpty()) {
            $includedShifts = $allowedShiftsForSiding->values();
        }

        $viewAllEntries = $user?->canViewAllRoadDispatchDailyVehicleEntries() ?? false;
        $scopedToUserId = $viewAllEntries ? null : (int) $user->id;

        $entries = DailyVehicleEntry::query()
            ->where('entry_date', $date)
            ->whereIn('shift', $includedShifts->all())
            ->where('siding_id', $sidingId)
            ->where('entry_type', DailyVehicleEntry::ENTRY_TYPE_RAILWAY_SIDING_EMPTY_WEIGHMENT)
            ->when(
                $scopedToUserId !== null,
                fn ($q) => $q->where('created_by', $scopedToUserId),
            )
            ->orderBy('created_at', 'asc')
            ->get();

        /** @var array<int, int> $counts */
        $counts = array_fill(0, 24, 0);

        foreach ($entries as $entry) {
            $reachedAt = $entry->reached_at;
            if ($reachedAt === null || $reachedAt === '') {
                continue;
            }

            try {
                $hour = Carbon::parse((string) $reachedAt)->hour;
            } catch (Throwable) {
                continue;
            }

            if ($hour >= 0 && $hour <= 23) {
                $counts[$hour] += 1;
            }
        }

        $rows = [];
        $total = 0;
        for ($h = 0; $h < 24; $h++) {
            $count = (int) ($counts[$h] ?? 0);
            $total += $count;
            $hh = mb_str_pad((string) $h, 2, '0', STR_PAD_LEFT);
            $rows[] = [
                'hour' => $hh,
                'label' => $hh.':00–'.$hh.':59',
                'count' => $count,
            ];
        }

        return response()->json([
            'now' => now()->toIso8601String(),
            'date' => $date,
            'siding_id' => $sidingId,
            'shifts' => $includedShifts->all(),
            'scoped_to_user_id' => $scopedToUserId,
            'total' => $total,
            'rows' => $rows,
        ]);
    }

    public function destroy(Request $request, DailyVehicleEntry $entry): RedirectResponse|JsonResponse
    {
        $this->authorizeEntryForEmptyWeighmentShiftUser($entry);
        $this->enforceStrictActiveShiftForEntry($entry);

        if ($entry->entry_type !== DailyVehicleEntry::ENTRY_TYPE_RAILWAY_SIDING_EMPTY_WEIGHMENT) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Entry does not belong to railway siding empty weighment.'], 403);
            }

            return back()->with('error', 'Entry does not belong to railway siding empty weighment.');
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

        $exportSidingId = (int) $data['siding'];
        $allowedForExportSiding = $assignedShifts
            ->where('siding_id', $exportSidingId)
            ->pluck('sort_order')
            ->map(fn (mixed $s): int => (int) $s)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if (! $user->canViewAllRoadDispatchDailyVehicleEntries() && $data['date'] === now()->format('Y-m-d')) {
            if ($data['shift'] === 'all') {
                throw ValidationException::withMessages([
                    'shift' => 'You can only export the current shift for today.',
                ]);
            }
            $editable = $this->shiftValidation->resolveEditableShiftForAssignments(
                $allowedForExportSiding,
                $exportSidingId
            );
            if ($editable === null || (int) $data['shift'] !== $editable) {
                throw ValidationException::withMessages([
                    'shift' => 'You can only export the shift that is currently active for your assignment.',
                ]);
            }
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

    private function canBypassShiftLock(): bool
    {
        return (bool) Auth::user()?->canViewAllRoadDispatchDailyVehicleEntries();
    }

    private function canBypassShiftLockForSiding(int $sidingId): bool
    {
        $user = Auth::user();
        $assignedShiftOrders = $user?->activeSidingShifts()
            ->where('siding_id', $sidingId)
            ->pluck('sort_order')
            ->map(fn (mixed $shift): int => (int) $shift)
            ->unique()
            ->values();

        if ($assignedShiftOrders === null) {
            return false;
        }

        return $assignedShiftOrders->contains(1)
            && $assignedShiftOrders->contains(2)
            && $assignedShiftOrders->contains(3);
    }

    private function enforceStrictActiveShiftForEntry(DailyVehicleEntry $entry): void
    {
        if ($this->canBypassShiftLock()) {
            return;
        }

        $entryDate = $entry->entry_date instanceof Carbon
            ? $entry->entry_date->format('Y-m-d')
            : (string) $entry->entry_date;

        $this->enforceStrictActiveShift(
            shift: (int) $entry->shift,
            entryDate: $entryDate,
            sidingId: (int) $entry->siding_id
        );
    }

    private function enforceStrictActiveShift(int $shift, string $entryDate, int $sidingId): void
    {
        $today = now()->format('Y-m-d');
        try {
            $normalizedEntryDate = Carbon::parse($entryDate)->format('Y-m-d');
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'entry_date' => 'Invalid entry date.',
            ]);
        }

        if ($normalizedEntryDate !== $today) {
            throw ValidationException::withMessages([
                'entry_date' => 'You can only update entries for today during your active shift.',
            ]);
        }

        if (! $this->shiftValidation->isShiftActive($shift, null, $sidingId)) {
            throw ValidationException::withMessages([
                'shift' => 'Your shift is not active yet (or has ended). Please wait for your shift to start.',
            ]);
        }
    }

    /**
     * @param  array<int, int>  $allowedShifts
     * @return array{isLocked: bool, message: string, nextShiftStartAt: string|null, now: string}
     */
    private function buildShiftLock(string $date, int $sidingId, array $allowedShifts, bool $canBypass): array
    {
        $now = Carbon::now();

        if ($canBypass) {
            return [
                'isLocked' => false,
                'message' => '',
                'nextShiftStartAt' => null,
                'now' => $now->toIso8601String(),
            ];
        }

        if ($date !== $now->format('Y-m-d')) {
            return [
                'isLocked' => true,
                'message' => 'You can only update entries for today during your active shift.',
                'nextShiftStartAt' => null,
                'now' => $now->toIso8601String(),
            ];
        }

        $assignedShiftOrders = collect($allowedShifts)
            ->map(fn (mixed $shift): int => (int) $shift)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $isAnyAssignedShiftEditable = collect($assignedShiftOrders)->contains(
            fn (int $shift): bool => $this->shiftValidation->isShiftWithinExtendedWindow($shift, $now, $sidingId)
        );

        if ($isAnyAssignedShiftEditable) {
            return [
                'isLocked' => false,
                'message' => '',
                'nextShiftStartAt' => null,
                'now' => $now->toIso8601String(),
            ];
        }

        $nextStart = $this->shiftValidation->getNextAssignedShiftWindowStartAfter(
            $now,
            $sidingId,
            $assignedShiftOrders
        );

        return [
            'isLocked' => true,
            'message' => 'Your shift is not active yet (or has ended). You cannot update data right now.',
            'nextShiftStartAt' => $nextStart?->toIso8601String(),
            'now' => $now->toIso8601String(),
        ];
    }

    private function formatEntryDateYmd(DailyVehicleEntry $entry): string
    {
        $d = $entry->entry_date;

        if ($d instanceof Carbon) {
            return $d->format('Y-m-d');
        }

        return Carbon::parse((string) $d)->format('Y-m-d');
    }

    /**
     * The calendar day of {@see $reachedAt} (app timezone) must match the shift sheet's {@see $entryDateYmd}.
     */
    private function assertReachedAtDateMatchesEntryDate(string $reachedAt, string $entryDateYmd): void
    {
        $tz = config('app.timezone') ?: 'UTC';
        try {
            $reached = Carbon::parse($reachedAt, $tz);
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'reached_at' => 'Invalid arrival time.',
            ]);
        }
        $entryDay = Carbon::parse($entryDateYmd, $tz)->format('Y-m-d');
        if ($reached->format('Y-m-d') !== $entryDay) {
            throw ValidationException::withMessages([
                'reached_at' => 'Arrival time must fall on the sheet date.',
            ]);
        }
    }
}
