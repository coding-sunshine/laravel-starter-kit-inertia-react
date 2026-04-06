<?php

declare(strict_types=1);

namespace App\Http\Controllers\Rakes;

use App\Actions\ApplyDemurragePenaltyAction;
use App\Actions\SyncTxrUnfitFlagsToWagonsAction;
use App\DataTables\RakeDataTable;
use App\Http\Controllers\Controller;
use App\Models\AppliedPenalty;
use App\Models\PowerPlant;
use App\Models\Rake;
use App\Models\RakeCharge;
use App\Models\SectionTimer;
use App\Models\Siding;
use App\Models\Wagon;
use Carbon\Carbon;
use Closure;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class RakesController extends Controller
{
    public function __construct(
        private readonly SyncTxrUnfitFlagsToWagonsAction $syncTxrUnfitFlagsToWagons,
        private readonly ApplyDemurragePenaltyAction $applyDemurragePenalty,
    ) {}

    public function index(Request $request): Response
    {

        return Inertia::render('rakes/index', [
            'tableData' => RakeDataTable::makeTable($request),
        ]);
    }

    public function show(Request $request, Rake $rake): Response
    {
        // $this->authorize('view', $rake);

        $rake->load([
            'siding:id,name,code',
            'siding.loaders:id,siding_id,loader_name,code',
            'wagons',
            'rakeWeighments',
            'rakeWeighments.rakeWagonWeighments.wagon:id,wagon_number,wagon_sequence,pcc_weight_mt',
            'txr.wagonUnfitLogs.wagon:id,wagon_number,wagon_sequence,wagon_type',
            'wagonLoadings.wagon:id,wagon_number,wagon_sequence,wagon_type,pcc_weight_mt',
            'wagonLoadings.loader:id,loader_name,code',
            'guardInspections',
            'rrDocument',
            'powerPlantReceipts.powerPlant:id,name,code',
            'powerPlantReceipts.media',
            'penalties',
            'appliedPenalties.penaltyType',
            'appliedPenalties.wagon',
            'rakeCharges.appliedPenalties:id,rake_charge_id,amount',
            'rakeCharges.rrPenaltySnapshots:id,rake_charge_id,amount',
        ]);

        // Keep loader weighment rows in wagon sequence order even when rows were created later.
        if ($rake->relationLoaded('wagonLoadings')) {
            $rake->setRelation(
                'wagonLoadings',
                $rake->wagonLoadings
                    ->sortBy(static fn ($l): int => $l->wagon?->wagon_sequence ?? $l->id)
                    ->values()
            );
        }

        if ($rake->txr !== null) {
            $expectedUnfitIds = $rake->txr->wagonUnfitLogs
                ->pluck('wagon_id')
                ->unique()
                ->sort()
                ->values()
                ->all();
            $actualUnfitIds = $rake->wagons
                ->where('is_unfit', true)
                ->pluck('id')
                ->sort()
                ->values()
                ->all();
            if ($expectedUnfitIds !== $actualUnfitIds) {
                $this->syncTxrUnfitFlagsToWagons->handle($rake, $rake->txr);
                $rake->unsetRelation('wagons');
                $rake->load('wagons');
            }
        }

        if ($rake->state !== 'completed' && self::rakeWorkflowCoreComplete($rake)) {
            $rake->update(['state' => 'completed']);
        }

        $demurrageRemainingMinutes = null;
        if (
            $rake->state === 'loading'
            && $rake->placement_time
            && $rake->loading_free_minutes !== null
        ) {
            $end = $rake->placement_time->copy()->addMinutes((int) $rake->loading_free_minutes);
            $demurrageRemainingMinutes = max(0, (int) now()->diffInMinutes($end, false));
        }

        $loadingSection = SectionTimer::query()
            ->where('section_name', 'loading')
            ->first();

        $rakeArray = $rake->toArray();
        $rakeArray['loading_warning_minutes'] = $loadingSection?->warning_minutes;
        $rakeArray['loading_section_free_minutes'] = $loadingSection?->free_minutes ?? 180;

        // Build weighments for WeighmentWorkflow: includes manual-only rows (no document yet) and full imports.
        $rakeArray['weighments'] = collect($rake->rakeWeighments ?? [])
            ->sortByDesc('attempt_no')
            ->map(function ($rw): array {
                $wagonWeights = collect($rw->rakeWagonWeighments ?? [])
                    ->sortBy('wagon_sequence')
                    ->map(function ($ww): array {
                        return [
                            'wagon_id' => (int) ($ww->wagon_id ?? $ww->wagon?->id ?? 0),
                            'gross_weight_mt' => (float) ($ww->actual_gross_mt ?? 0),
                            'net_weight_mt' => (float) ($ww->net_weight_mt ?? 0),
                            'wagon' => [
                                'id' => (int) ($ww->wagon?->id ?? $ww->wagon_id ?? 0),
                                'wagon_number' => (string) ($ww->wagon?->wagon_number ?? $ww->wagon_number ?? '-'),
                                'wagon_sequence' => (int) ($ww->wagon?->wagon_sequence ?? $ww->wagon_sequence ?? 0),
                                'pcc_weight_mt' => $ww->wagon?->pcc_weight_mt,
                            ],
                        ];
                    })
                    ->values()
                    ->all();

                $isPendingDocument = $wagonWeights === [] && ($rw->pdf_file_path === null || $rw->pdf_file_path === '');

                return [
                    'id' => $rw->id,
                    'weighment_time' => $rw->gross_weighment_datetime?->toIso8601String(),
                    'total_weight_mt' => $rw->total_net_weight_mt,
                    'status' => $rw->status,
                    'train_speed_kmph' => $rw->maximum_train_speed_kmph,
                    'attempt_no' => $rw->attempt_no,
                    'wagonWeights' => $wagonWeights,
                    'isPendingDocument' => $isPendingDocument,
                    'from_station' => $rw->from_station,
                    'to_station' => $rw->to_station,
                    'priority_number' => $rw->priority_number,
                ];
            })
            ->values()
            ->all();

        // Normalize relation keys for frontend (camelCase expected)
        if (array_key_exists('guard_inspections', $rakeArray)) {
            $rakeArray['guardInspections'] = $rakeArray['guard_inspections'];
        }

        if (array_key_exists('wagon_loadings', $rakeArray)) {
            $rakeArray['wagonLoadings'] = $rakeArray['wagon_loadings'];
        }

        $rakeArray['rrDocuments'] = collect($rake->rrDocuments ?? [])->map(static function ($doc): array {
            return [
                'id' => $doc->id,
                'rr_number' => $doc->rr_number,
                'rr_received_date' => $doc->rr_received_date?->toIso8601String() ?? '',
                'rr_weight_mt' => $doc->rr_weight_mt,
                'document_status' => $doc->document_status,
                'diverrt_destination_id' => $doc->diverrt_destination_id,
            ];
        })->values()->all();

        $rakeArray['diverrtDestinations'] = collect($rake->diverrtDestinations ?? [])->map(static function ($row): array {
            return [
                'id' => $row->id,
                'location' => $row->location,
            ];
        })->values()->all();

        $rakeArray['powerPlantReceipts'] = collect($rake->powerPlantReceipts ?? [])->map(static function ($receipt): array {
            $media = $receipt->getFirstMedia('power_plant_receipt_pdf');

            return [
                'id' => $receipt->id,
                'power_plant_id' => $receipt->power_plant_id,
                'receipt_date' => $receipt->receipt_date?->toDateString(),
                'weight_mt' => $receipt->weight_mt,
                'rr_reference' => $receipt->rr_reference,
                'status' => $receipt->status,
                'file_url' => $receipt->getFirstMediaUrl('power_plant_receipt_pdf') ?: null,
                'file_name' => $media?->file_name,
                'powerPlant' => $receipt->relationLoaded('powerPlant') && $receipt->powerPlant
                    ? [
                        'id' => $receipt->powerPlant->id,
                        'name' => $receipt->powerPlant->name,
                        'code' => $receipt->powerPlant->code,
                    ]
                    : null,
            ];
        })->values()->all();

        unset($rakeArray['rr_document'], $rakeArray['rr_documents'], $rakeArray['diverrt_destinations']);

        if (array_key_exists('applied_penalties', $rakeArray)) {
            $rakeArray['appliedPenalties'] = $rakeArray['applied_penalties'];
        }

        $rakeArray['rakeCharges'] = collect($rake->rakeCharges ?? [])->map(static function (RakeCharge $charge): array {
            return [
                'id' => $charge->id,
                'charge_type' => $charge->charge_type,
                'is_actual_charges' => (bool) $charge->is_actual_charges,
                'amount' => $charge->amount,
                'appliedPenalties' => collect($charge->appliedPenalties ?? [])->map(static function ($row): array {
                    return [
                        'amount' => (float) ($row->amount ?? 0),
                    ];
                })->values()->all(),
                'rrPenaltySnapshots' => collect($charge->rrPenaltySnapshots ?? [])->map(static function ($row): array {
                    return [
                        'amount' => (float) ($row->amount ?? 0),
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        $this->reconcilePenaltyChargeTotal($rake);

        $powerPlants = PowerPlant::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('rakes/show', [
            'rake' => $rakeArray,
            'powerPlants' => $powerPlants,
            'demurrageRemainingMinutes' => $demurrageRemainingMinutes,
            'demurrage_rate_per_mt_hour' => config('rrmcs.demurrage_rate_per_mt_hour', 50),
        ]);
    }

    /**
     * Generate wagons for a rake based on its wagon count
     */
    public function generateWagons(Request $request, Rake $rake)
    {
        // $this->authorize('update', $rake);

        // Check if wagons already exist
        if ($rake->wagons()->count() > 0) {
            return redirect()->route('rakes.show', $rake)->with('error', 'Wagons already exist for this rake');
        }

        // Generate wagons based on wagon_count
        $wagonCount = $rake->wagon_count;
        if ($wagonCount <= 0) {
            return redirect()->route('rakes.show', $rake)->with('error', 'Rake has no wagon count specified');
        }

        // Clear existing wagons (if any) and create new ones
        $rake->wagons()->delete();

        for ($i = 1; $i <= $wagonCount; $i++) {
            $wagon = new Wagon;
            $wagon->rake_id = $rake->id;
            $wagon->wagon_number = "W{$i}"; // W1, W2, W3, etc.
            $wagon->wagon_sequence = $i;
            $wagon->state = 'pending';
            $wagon->save();
        }

        return redirect()->route('rakes.show', $rake)->with('success', "Successfully generated {$wagonCount} wagons");
    }

    /**
     * Show the form for editing a rake
     */
    public function edit(Request $request, Rake $rake): Response
    {
        // $this->authorize('update', $rake);

        $user = $request->user();
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('rakes/edit', [
            'rake' => $rake,
            'sidings' => $sidings,
        ]);
    }

    /**
     * Update the specified rake
     */
    public function update(Request $request, Rake $rake): RedirectResponse
    {
        // $this->authorize('update', $rake);

        $validated = $request->validate([
            'rake_number' => [
                'nullable',
                'string',
                'max:100',
                function (string $attribute, mixed $value, Closure $fail) use ($rake): void {
                    $trimmed = $value !== null && mb_trim((string) $value) !== '' ? mb_trim((string) $value) : null;

                    if ($trimmed === null || $trimmed === $rake->rake_number) {
                        return;
                    }

                    $existsInMonth = Rake::query()
                        ->where('rake_number', $trimmed)
                        ->whereYear('created_at', now()->year)
                        ->whereMonth('created_at', now()->month)
                        ->whereKeyNot($rake->getKey())
                        ->exists();

                    if ($existsInMonth) {
                        $fail('This rake number is already in use this month.');
                    }
                },
            ],
            'rake_type' => ['nullable', 'string', 'max:50'],
            'dispatch_time' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:pending,txr_in_progress,txr_completed,loading,loading_completed,guard_approved,guard_rejected,weighment_completed,rr_generated,closed'],
            'rr_expected_date' => ['nullable', 'date'],
            'placement_time' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $rake->update([
            'rake_number' => array_key_exists('rake_number', $validated) && mb_trim((string) $validated['rake_number']) !== ''
                ? mb_trim((string) $validated['rake_number'])
                : $rake->rake_number,
            'rake_type' => $validated['rake_type'] ?? $rake->rake_type,
            'dispatch_time' => $validated['dispatch_time'] ? new DateTimeImmutable($validated['dispatch_time']) : $rake->dispatch_time,
            'state' => $validated['status'] ?? $rake->state,
            'rr_expected_date' => $validated['rr_expected_date'] ?? $rake->rr_expected_date,
            'placement_time' => $validated['placement_time'] ? new DateTimeImmutable($validated['placement_time']) : $rake->placement_time,
            'updated_by' => $request->user()->id,
        ]);

        return to_route('rakes.show', $rake)
            ->with('success', 'Rake updated successfully.');
    }

    public function startLoadingTimer(Request $request, Rake $rake): RedirectResponse|JsonResponse
    {
        $freeMinutes = SectionTimer::query()
            ->where('section_name', 'loading')
            ->value('free_minutes') ?? 180;

        $now = now();

        $rake->update([
            'loading_start_time' => $now,
            'loading_end_time' => null,
            'loading_date' => $now->toDateString(),
            'loading_free_minutes' => $freeMinutes,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'loading_start_time' => $rake->loading_start_time?->toIso8601String(),
                'loading_free_minutes' => $rake->loading_free_minutes,
            ]);
        }

        $hours = $freeMinutes >= 60 && $freeMinutes % 60 === 0
            ? (int) ($freeMinutes / 60)
            : null;
        $message = $hours !== null
            ? "Loading timer started for {$hours} hour(s)."
            : "Loading timer started for {$freeMinutes} minutes.";

        return to_route('rakes.show', $rake)
            ->with('success', $message);
    }

    public function resetLoadingTimer(Request $request, Rake $rake): RedirectResponse|JsonResponse
    {
        $rake->update([
            'loading_start_time' => null,
            'loading_end_time' => null,
            'loading_date' => null,
            'loading_free_minutes' => null,
        ]);

        $this->applyDemurragePenalty->handle($rake);

        if ($request->wantsJson()) {
            return response()->json([
                'loading_start_time' => null,
                'loading_end_time' => null,
            ]);
        }

        return to_route('rakes.show', $rake)
            ->with('success', 'Loading timer reset.');
    }

    public function stopLoadingTimer(Request $request, Rake $rake): RedirectResponse|JsonResponse
    {
        $rake->update([
            'loading_end_time' => now(),
        ]);

        $this->applyDemurragePenalty->handle($rake);

        if ($request->wantsJson()) {
            return response()->json([
                'loading_start_time' => $rake->loading_start_time?->toIso8601String(),
                'loading_end_time' => $rake->loading_end_time?->toIso8601String(),
                'loading_free_minutes' => $rake->loading_free_minutes,
            ]);
        }

        return to_route('rakes.show', $rake)
            ->with('success', 'Loading timer stopped.');
    }

    public function updateLoadingTimes(Request $request, Rake $rake): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'loading_start_time' => ['nullable', 'date'],
            'loading_end_time' => ['nullable', 'date', 'after_or_equal:loading_start_time'],
        ]);

        $freeMinutes = SectionTimer::query()
            ->where('section_name', 'loading')
            ->value('free_minutes') ?? 180;

        $start = array_key_exists('loading_start_time', $validated) && $validated['loading_start_time'] !== null
            ? new DateTimeImmutable($validated['loading_start_time'])
            : null;

        $end = array_key_exists('loading_end_time', $validated) && $validated['loading_end_time'] !== null
            ? new DateTimeImmutable($validated['loading_end_time'])
            : null;

        // Use app timezone for loading_date so dashboard date filter matches (avoids UTC date shifting by a day).
        $loadingDate = $start
            ? Carbon::parse($start->format('c'))->timezone(config('app.timezone'))->toDateString()
            : null;

        $rake->update([
            'loading_start_time' => $start,
            'loading_end_time' => $end,
            'loading_date' => $loadingDate,
            'loading_free_minutes' => $freeMinutes,
        ]);

        $this->applyDemurragePenalty->handle($rake);

        if ($request->wantsJson()) {
            return response()->json([
                'loading_start_time' => $rake->loading_start_time?->toIso8601String(),
                'loading_end_time' => $rake->loading_end_time?->toIso8601String(),
                'loading_date' => $rake->loading_date?->toDateString(),
            ]);
        }

        return to_route('rakes.show', $rake)
            ->with('success', 'Loading times updated.');
    }

    /**
     * Delete a rake if it has no wagons
     */
    public function destroy(Request $request, Rake $rake): RedirectResponse
    {
        // $this->authorize('delete', $rake);

        // Check if rake has wagons
        if ($rake->wagons()->count() > 0) {
            return to_route('rakes.show', $rake)
                ->with('error', 'Cannot delete rake with wagons. Delete all wagons first.');
        }

        // Check if rake has TXR
        if ($rake->txr) {
            return to_route('rakes.show', $rake)
                ->with('error', 'Cannot delete rake with TXR records.');
        }

        $rakeNumber = $rake->rake_number;
        $rake->delete();

        return to_route('rakes.index')
            ->with('success', "Rake {$rakeNumber} deleted successfully.");
    }

    /**
     * True when TXR, wagon loading, and weighment are all done (used to auto-set state to completed).
     */
    private static function rakeWorkflowCoreComplete(Rake $rake): bool
    {
        if ($rake->txr?->status !== 'completed') {
            return false;
        }
        $fitWagons = $rake->wagons->filter(fn ($w) => ! $w->is_unfit);
        $loadedWagonIds = $rake->wagonLoadings
            ->filter(fn ($l) => (float) $l->loaded_quantity_mt > 0)
            ->pluck('wagon_id')
            ->flip();
        if ($fitWagons->isEmpty() || ! $fitWagons->every(fn ($w) => $loadedWagonIds->has($w->id))) {
            return false;
        }

        return $rake->rakeWeighments->isNotEmpty();
    }

    /**
     * Safety-net: ensure the PENALTY RakeCharge total matches the sum of all applied penalties.
     */
    private function reconcilePenaltyChargeTotal(Rake $rake): void
    {
        $penaltyCharge = RakeCharge::query()
            ->where('rake_id', $rake->id)
            ->where('charge_type', 'PENALTY')
            ->where('is_actual_charges', false)
            ->first();

        if (! $penaltyCharge) {
            return;
        }

        $actualTotal = round((float) AppliedPenalty::query()
            ->where('rake_charge_id', $penaltyCharge->id)
            ->sum('amount'), 2);

        if ((float) $penaltyCharge->amount !== $actualTotal) {
            $penaltyCharge->update(['amount' => $actualTotal]);
        }
    }
}
