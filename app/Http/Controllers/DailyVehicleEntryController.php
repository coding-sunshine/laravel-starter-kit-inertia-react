<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DailyVehicleEntry;
use App\Models\Siding;
use App\Models\SidingShift;
use App\Models\VehicleWorkorder;
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
use App\Models\StockLedger;
use Illuminate\Support\Facades\DB;

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
        if ($restrictToAssignedShift) {
            $date = now()->format('Y-m-d');
        }
        $sidingsOrdered = Siding::query()
            ->whereIn('id', $allowedSidingIds->all())
            ->orderBy('name')
            ->get(['id', 'name', 'station_code']);
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
                return redirect()->route('road-dispatch.daily-vehicle-entries.index', array_filter([
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

        $viewAllEntries = $user->canViewAllRoadDispatchDailyVehicleEntries();
        $scopedToUserId = $viewAllEntries ? null : (int) $user->id;

        $entries = $this->service->getEntriesByDateAndShift($date, $activeShift, $sidingId, $entryType, $scopedToUserId);
        $shiftSummary = $this->service->getShiftSummary($date, $entryType, $sidingId, $scopedToUserId);
        $shiftStatus = $this->shiftValidation->getShiftCompletionStatus($date, $sidingIdForShifts);
        $shiftTimes = $this->shiftValidation->getShiftTimesForSiding($sidingIdForShifts);

        $shiftLock = $this->buildShiftLock(
            date: (string) $date,
            sidingId: (int) $sidingIdForShifts,
            allowedShifts: $allowedShifts->all(),
            canBypass: $canBypassShiftLock
        );

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
            'canBypassShiftLock' => $canBypassShiftLock,
            'shiftLock' => $shiftLock,
            'timeEditableShift' => $timeEditableShift,
            'shiftGraceEndsAtIso' => $shiftGraceEndsAtIso,
            'showCreatedByColumn' => $user?->canViewAllRoadDispatchDailyVehicleEntries() ?? false,
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
            'tare_wt_two' => 'nullable|numeric|min:0',
            'wb_no' => 'nullable|string|max:255',
            'd_challan_no' => 'nullable|string|max:255',
            'challan_mode' => 'nullable|in:offline,online',
            'status' => 'nullable|in:draft,completed',
            'remarks' => 'nullable|string|max:2000',
            'inline_submit' => 'sometimes|boolean',
        ]);

        $inlineSubmit = $request->boolean('inline_submit');
        unset($data['inline_submit']);

        if ($isShiftRestrictedUser) {
            $data['siding_id'] = $firstAssignment->siding_id;
            $data['shift'] = (int) $firstAssignment->sort_order;
            $data['entry_date'] = now()->format('Y-m-d');
        } elseif (! $sidingShiftPairs->contains(((int) $data['siding_id']).'|'.((int) $data['shift']))) {
            abort(403, 'You are not allowed to access this shift for the selected siding.');
        }

        $sidingIdForValidation = (int) ($data['siding_id'] ?? 0);
        if (! $this->canBypassShiftLock()) {
            $allowedForSiding = $assignedShifts
                ->where('siding_id', $sidingIdForValidation)
                ->pluck('sort_order')
                ->map(fn (mixed $s): int => (int) $s)
                ->unique()
                ->sort()
                ->values()
                ->all();
            $this->enforceStrictActiveShift(
                shift: (int) $data['shift'],
                entryDate: (string) $data['entry_date'],
                sidingId: $sidingIdForValidation,
                allowedShiftOrdersForSiding: $allowedForSiding
            );
        }

        $entry = $this->service->createEntry($data);
        $entry = $this->service->updateEntry($entry, []);

        $entry->refresh();

        if ($inlineSubmit && $entry->inline_submitted_at === null) {
            $entry->update([
                'inline_submitted_at' => now(),
            ]);
        }

        $entry->refresh();

        if ($request->wantsJson()) {
            return response()->json(['entry' => $entry->load(['siding', 'creator', 'updater'])], 201);
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
        $this->enforceStrictActiveShiftForEntry($entry);

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
            'inline_submit' => 'sometimes|boolean',
        ]);

        $inlineSubmit = $request->boolean('inline_submit');

        unset($data['inline_submit']);

        $this->service->updateEntry($entry, $data);

        $entry->refresh();

        if ($inlineSubmit && $entry->inline_submitted_at === null) {
            $entry->update([
                'inline_submitted_at' => now(),
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json(['entry' => $entry->fresh()->load(['siding', 'creator', 'updater'])]);
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
        $this->enforceStrictActiveShiftForEntry($entry);
        $updatedEntry = $this->service->markCompleted($entry);

        if ($request->wantsJson()) {
            return response()->json(['entry' => $updatedEntry->load(['siding', 'creator', 'updater'])]);
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
    $this->enforceStrictActiveShiftForEntry($entry);

    $entryId = $entry->id;

    DB::transaction(function () use ($entry) {

        //  If completed → reverse stock
        if ($entry->status === 'completed') {

            $ledger = StockLedger::query()
                ->where('daily_vehicle_entry_id', $entry->id)
                ->where('transaction_type', 'receipt')
                ->latest('id')
                ->first();

            if ($ledger) {

                $sidingId = $ledger->siding_id;

                // prevent double reversal
                $alreadyReversed = StockLedger::query()
                    ->where('reference_number', 'REV-RCPT-' . $ledger->id)
                    ->lockForUpdate()
                    ->exists();

                if (! $alreadyReversed) {

                    $reverseQty = -abs((float) $ledger->quantity_mt); //  negative

                    $lastLedger = StockLedger::query()
                        ->where('siding_id', $sidingId)
                        ->lockForUpdate()
                        ->latest('id')
                        ->first();

                    $opening = $lastLedger
                        ? (float) $lastLedger->closing_balance_mt
                        : SidingOpeningBalance::getOpeningBalanceForSiding($sidingId);

                    $closing = round($opening + $reverseQty, 2);

                    StockLedger::create([
                        'siding_id' => $sidingId,
                        'transaction_type' => 'correction',
                        'daily_vehicle_entry_id' => $entry->id,
                        'quantity_mt' => $reverseQty, //  negative
                        'opening_balance_mt' => $opening,
                        'closing_balance_mt' => $closing,
                        'reference_number' => 'REV-RCPT-' . $ledger->id,
                        'remarks' => 'Reversal for deleted vehicle entry #' . $entry->id,
                        'created_by' => auth()->id(),
                    ]);

                    DB::afterCommit(function () use ($sidingId, $closing) {
                        event(new \App\Events\CoalStockUpdated($sidingId, $closing));
                    });
                }
            }
        }

        //  Now delete entry
        $entry->delete();
    });

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

        $viewAllEntries = $user->canViewAllRoadDispatchDailyVehicleEntries();
        $scopedToUserId = $viewAllEntries ? null : (int) $user->id;

        $exportSidingId = (int) $data['siding'];
        $allowedForExportSiding = $assignedShifts
            ->where('siding_id', $exportSidingId)
            ->pluck('sort_order')
            ->map(fn (mixed $s): int => (int) $s)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if (! $viewAllEntries && $data['date'] === now()->format('Y-m-d')) {
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
                DailyVehicleEntry::ENTRY_TYPE_ROAD_DISPATCH,
                $scopedToUserId
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

        if (! $user->canViewAllRoadDispatchDailyVehicleEntries()
            && (int) $entry->created_by !== (int) $user->id) {
            abort(403, 'You can only manage entries you created.');
        }
    }

    private function canBypassShiftLock(): bool
    {
        return (bool) Auth::user()?->canViewAllRoadDispatchDailyVehicleEntries();
    }

    private function enforceStrictActiveShiftForEntry(DailyVehicleEntry $entry): void
    {
        if ($this->canBypassShiftLock()) {
            return;
        }

        $user = Auth::user();
        $allowedForSiding = $user?->activeSidingShifts()
            ->where('siding_id', $entry->siding_id)
            ->pluck('sort_order')
            ->map(fn (mixed $s): int => (int) $s)
            ->unique()
            ->sort()
            ->values()
            ->all() ?? [];

        $entryDate = $entry->entry_date instanceof Carbon
            ? $entry->entry_date->format('Y-m-d')
            : (string) $entry->entry_date;

        $this->enforceStrictActiveShift(
            shift: (int) $entry->shift,
            entryDate: $entryDate,
            sidingId: (int) $entry->siding_id,
            allowedShiftOrdersForSiding: $allowedForSiding
        );
    }

    /**
     * @param  array<int, int>  $allowedShiftOrdersForSiding
     */
    private function enforceStrictActiveShift(int $shift, string $entryDate, int $sidingId, array $allowedShiftOrdersForSiding): void
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

        $editable = $this->shiftValidation->resolveEditableShiftForAssignments(
            $allowedShiftOrdersForSiding,
            $sidingId
        );

        if ($editable === null || $editable !== $shift) {
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
}
