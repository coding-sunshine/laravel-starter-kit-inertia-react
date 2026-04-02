<?php

declare(strict_types=1);

namespace App\Http\Controllers\Rakes;

use App\Events\RakeGuardInspectionUpdated;
use App\Events\RakeLoadUpdated;
use App\Events\RakeWagonLoadingUpdated;
use App\Http\Controllers\Controller;
use App\Models\GuardInspection;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\RakeWagonLoading;
use App\Models\User;
use App\Services\RakeLoadStateResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class RakeLoadController extends Controller
{
    public function __construct(
        private RakeLoadStateResolver $stateResolver,
    ) {}

    public function show(Request $request, Rake $rake): Response
    {
        // $this->authorize('view', $rake);

        $rake->load([
            'siding:id,name,code',
            'siding.loaders:id,siding_id,loader_name,code',
            'wagons:id,rake_id,wagon_sequence,wagon_number,tare_weight_mt,pcc_weight_mt,is_unfit',
            'txr',
            'rakeLoad',
            'rakeLoad.wagonLoadings.wagon:id,wagon_number,wagon_sequence,tare_weight_mt,pcc_weight_mt,is_unfit',
            'rakeLoad.wagonLoadings.loader:id,loader_name,code',
            'rakeLoad.guardInspections',
        ]);

        $state = $this->stateResolver->resolve($rake);

        return Inertia::render('rakes/load', [
            'rake' => $rake,
            'loadState' => $state,
            'demurrage_rate_per_mt_hour' => (float) config('rrmcs.demurrage_rate_per_mt_hour', 50),
        ]);
    }

    public function confirmPlacement(Request $request, Rake $rake): RedirectResponse
    {
        // $this->authorize('update', $rake);

        if (! $rake->txr || $rake->txr->status !== 'completed') {
            return to_route('rakes.show', $rake)
                ->with('error', 'TXR must be completed before starting loading process.');
        }

        if ($rake->rakeLoad) {
            return redirect()
                ->route('rakes.load.show', $rake)
                ->setStatusCode(303);
        }

        $rakeLoad = $rake->rakeLoad()->create([
            'placement_time' => now(),
            'free_time_minutes' => 180,
            'status' => 'in_progress',
        ]);

        $rake->update([
            'placement_time' => now(),
            'state' => 'placed',
        ]);

        $state = $this->stateResolver->resolve($rake);
        RakeLoadUpdated::dispatch($rake, $rakeLoad, $state, 'placement_confirmed');

        return redirect()
            ->route('rakes.load.show', $rake)
            ->setStatusCode(303)
            ->with('success', 'Placement confirmed. 3-hour timer has begun.');
    }

    public function loadWagon(Request $request, Rake $rake): RedirectResponse
    {
        // $this->authorize('update', $rake);

        $rakeLoad = $rake->rakeLoad;
        if (! $rakeLoad) {
            return redirect()
                ->route('rakes.load.show', $rake)
                ->setStatusCode(303)
                ->with('error', 'Loading process not started.');
        }

        $state = $this->stateResolver->resolve($rake);
        $attemptNo = $state['attempt_no'];

        $validated = $request->validate([
            'wagon_id' => ['required', 'exists:wagons,id'],
            'loader_id' => ['required', 'exists:loaders,id'],
            'loaded_quantity_mt' => ['required', 'numeric', 'min:0'],
        ]);

        $existingLoading = RakeWagonLoading::where('rake_load_id', $rakeLoad->id)
            ->where('wagon_id', $validated['wagon_id'])
            ->where('attempt_no', $attemptNo)
            ->first();

        if ($existingLoading) {
            return redirect()
                ->route('rakes.load.show', $rake)
                ->setStatusCode(303)
                ->with('error', 'This wagon has already been loaded for this attempt.');
        }

        $wagonLoading = RakeWagonLoading::create([
            'rake_load_id' => $rakeLoad->id,
            'wagon_id' => $validated['wagon_id'],
            'loader_id' => $validated['loader_id'],
            'loaded_quantity_mt' => $validated['loaded_quantity_mt'],
            'attempt_no' => $attemptNo,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $state = $this->stateResolver->resolve($rake);
        RakeLoadUpdated::dispatch($rake, $rakeLoad, $state, 'wagon_loaded');
        RakeWagonLoadingUpdated::dispatch($rake, $wagonLoading, 'created');

        return redirect()
            ->route('rakes.load.show', $rake)
            ->setStatusCode(303)
            ->with('success', 'Wagon loaded successfully.');
    }

    public function storeWagonLoadings(Request $request, Rake $rake): RedirectResponse|JsonResponse
    {
        // $this->authorize('update', $rake);

        if (! $rake->txr || $rake->txr->status !== 'completed') {
            return to_route('rakes.show', $rake)
                ->with('error', 'TXR must be completed before wagon loading.');
        }

        $validated = $request->validate([
            'loadings' => ['required', 'array'],
            'loadings.*.wagon_id' => ['required', 'exists:wagons,id'],
            'loadings.*.loader_id' => ['required', 'exists:loaders,id'],
            'loadings.*.loaded_quantity_mt' => ['required', 'numeric', 'min:0'],
            'loadings.*.loading_time' => ['nullable', 'date'],
            'loadings.*.remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $rakeWagonIds = $rake->wagons()->pluck('id')->all();
        $seenWagonIds = [];

        foreach ($validated['loadings'] as $loading) {
            $wagonId = (int) $loading['wagon_id'];
            if (in_array($wagonId, $seenWagonIds, true)) {
                return to_route('rakes.show', $rake)
                    ->with('error', 'Duplicate wagon in loading list.');
            }
            if (! in_array($wagonId, $rakeWagonIds, true)) {
                return to_route('rakes.show', $rake)
                    ->with('error', 'Invalid wagon for this rake.');
            }
            $seenWagonIds[] = $wagonId;
        }

        DB::transaction(function () use ($rake, $validated): void {
            $rake->wagonLoadings()->delete();

            foreach ($validated['loadings'] as $loading) {
                RakeWagonLoading::create([
                    'rake_id' => $rake->id,
                    'wagon_id' => (int) $loading['wagon_id'],
                    'loader_id' => (int) $loading['loader_id'],
                    'loaded_quantity_mt' => $loading['loaded_quantity_mt'],
                    'loading_time' => $loading['loading_time'] ?? now(),
                    'remarks' => $loading['remarks'] ?? null,
                ]);
            }
        });

        $rake->load([
            'wagonLoadings.wagon:id,wagon_number,wagon_sequence,wagon_type,tare_weight_mt,pcc_weight_mt,is_unfit',
            'wagonLoadings.loader:id,loader_name,code',
        ]);

        if ($request->wantsJson()) {
            $wagonLoadings = $rake->wagonLoadings->map(static function ($loading) {
                return [
                    'id' => $loading->id,
                    'wagon_id' => $loading->wagon_id,
                    'loader_id' => $loading->loader_id,
                    'loaded_quantity_mt' => (string) $loading->loaded_quantity_mt,
                    'loading_time' => $loading->loading_time?->toIso8601String(),
                    'remarks' => $loading->remarks,
                    'wagon' => $loading->wagon ? [
                        'id' => $loading->wagon->id,
                        'wagon_number' => $loading->wagon->wagon_number,
                        'wagon_sequence' => $loading->wagon->wagon_sequence,
                        'tare_weight_mt' => $loading->wagon->tare_weight_mt,
                        'pcc_weight_mt' => $loading->wagon->pcc_weight_mt,
                    ] : null,
                    'loader' => $loading->loader ? [
                        'id' => $loading->loader->id,
                        'loader_name' => $loading->loader->loader_name,
                        'code' => $loading->loader->code,
                    ] : null,
                ];
            });

            return response()->json([
                'wagonLoadings' => $wagonLoadings,
            ]);
        }

        return to_route('rakes.show', $rake)
            ->with('success', 'Wagon loadings saved.');
    }

    /**
     * JSON list of wagon loading rows for the rake workflow (hydrate UI when Inertia props are empty).
     */
    public function indexWagonLoadings(Rake $rake): JsonResponse
    {
        $user = request()->user();
        abort_unless($user instanceof User && $this->canEditLoaderWeighment($user), 403);

        $rake->load([
            'wagonLoadings.wagon:id,wagon_number,wagon_sequence,wagon_type,tare_weight_mt,pcc_weight_mt,is_unfit',
            'wagonLoadings.loader:id,loader_name,code',
        ]);

        $wagonLoadings = $rake->wagonLoadings
            ->sortBy(static fn (RakeWagonLoading $l): int => $l->wagon?->wagon_sequence ?? $l->id)
            ->values()
            ->map(static function (RakeWagonLoading $loading): array {
                return [
                    'id' => $loading->id,
                    'wagon_id' => $loading->wagon_id,
                    'loader_id' => $loading->loader_id,
                    'loader_operator_name' => $loading->loader_operator_name,
                    'loaded_quantity_mt' => $loading->loaded_quantity_mt !== null ? (string) $loading->loaded_quantity_mt : '',
                    'loading_time' => $loading->loading_time?->toIso8601String(),
                    'remarks' => $loading->remarks,
                    'wagon' => $loading->wagon ? [
                        'id' => $loading->wagon->id,
                        'wagon_number' => $loading->wagon->wagon_number,
                        'wagon_sequence' => $loading->wagon->wagon_sequence,
                        'wagon_type' => $loading->wagon->wagon_type,
                        'tare_weight_mt' => $loading->wagon->tare_weight_mt,
                        'pcc_weight_mt' => $loading->wagon->pcc_weight_mt,
                    ] : null,
                    'loader' => $loading->loader ? [
                        'id' => $loading->loader->id,
                        'loader_name' => $loading->loader->loader_name,
                        'code' => $loading->loader->code,
                    ] : null,
                ];
            });

        return response()->json([
            'wagonLoadings' => $wagonLoadings,
        ]);
    }

    /**
     * Ensure one {@see RakeWagonLoading} row exists per wagon on the rake (idempotent).
     */
    public function ensureAllWagonLoadingRows(Rake $rake): JsonResponse
    {
        $user = request()->user();
        abort_unless($user instanceof User && $this->canEditLoaderWeighment($user), 403);

        $wagons = $rake->wagons()
            ->orderBy('wagon_sequence')
            ->get(['id', 'wagon_number']);

        $wagonIds = $wagons
            ->reject(static fn ($w): bool => self::shouldSkipLoaderWeighmentWagonNumber($w->wagon_number))
            ->pluck('id');

        if ($wagonIds->isEmpty()) {
            return response()->json([
                'wagonLoadings' => [],
            ]);
        }

        DB::transaction(function () use ($rake, $wagonIds): void {
            foreach ($wagonIds as $wagonId) {
                RakeWagonLoading::query()->firstOrCreate(
                    [
                        'rake_id' => $rake->id,
                        'wagon_id' => $wagonId,
                    ],
                    [
                        'loader_id' => null,
                        'loaded_quantity_mt' => null,
                        'loading_time' => null,
                        'remarks' => null,
                    ]
                );
            }
        });

        $this->syncLoaderWeighmentStatus($rake->fresh());

        return $this->indexWagonLoadings($rake->fresh());
    }

    public function storeWagonRow(Request $request, Rake $rake): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $this->canEditLoaderWeighment($user), 403);

        $validated = $request->validate([
            'wagon_id' => ['required', 'integer'],
        ]);

        $wagon = $rake->wagons()
            ->where('id', $validated['wagon_id'])
            ->firstOrFail();

        $exists = $rake->wagonLoadings()
            ->where('wagon_id', $wagon->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'This wagon is already loaded for this rake.',
            ], 422);
        }

        $loading = RakeWagonLoading::create([
            'rake_id' => $rake->id,
            'wagon_id' => $wagon->id,
            'loader_id' => null,
            'loaded_quantity_mt' => null,
            'loading_time' => null,
            'remarks' => null,
        ]);

        $loading->load([
            'wagon:id,wagon_number,wagon_sequence,wagon_type,tare_weight_mt,pcc_weight_mt,is_unfit',
        ]);

        $this->syncLoaderWeighmentStatus($rake->fresh());

        return response()->json([
            'loading' => [
                'id' => $loading->id,
                'wagon_id' => $loading->wagon_id,
                'loader_id' => $loading->loader_id,
                'loaded_quantity_mt' => $loading->loaded_quantity_mt !== null ? (string) $loading->loaded_quantity_mt : '',
                'loading_time' => $loading->loading_time?->toIso8601String(),
                'remarks' => $loading->remarks,
                'wagon' => $loading->wagon ? [
                    'id' => $loading->wagon->id,
                    'wagon_number' => $loading->wagon->wagon_number,
                    'wagon_sequence' => $loading->wagon->wagon_sequence,
                    'wagon_type' => $loading->wagon->wagon_type,
                    'tare_weight_mt' => $loading->wagon->tare_weight_mt,
                    'pcc_weight_mt' => $loading->wagon->pcc_weight_mt,
                ] : null,
                'loader' => null,
            ],
        ]);
    }

    public function updateWagonRow(Request $request, Rake $rake, RakeWagonLoading $loading): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $this->canEditLoaderWeighment($user), 403);

        if ($loading->rake_id !== $rake->id) {
            abort(404);
        }

        $validated = $request->validate([
            'loader_id' => ['nullable', 'integer', 'exists:loaders,id'],
            'loader_operator_name' => ['nullable', 'string', 'max:255'],
            'loaded_quantity_mt' => ['nullable', 'numeric', 'min:0'],
        ]);

        $update = [];
        if (array_key_exists('loader_id', $validated)) {
            $update['loader_id'] = $validated['loader_id'];
        }
        if (array_key_exists('loader_operator_name', $validated)) {
            $update['loader_operator_name'] = $validated['loader_operator_name'];
        }
        if (array_key_exists('loaded_quantity_mt', $validated) && $validated['loaded_quantity_mt'] !== null) {
            $update['loaded_quantity_mt'] = $validated['loaded_quantity_mt'];
            $update['loading_time'] = $loading->loading_time ?? now();
        }
        if ($update !== []) {
            $loading->update($update);
        }

        $this->syncLoaderWeighmentStatus($rake->fresh());

        $loading->load([
            'wagon:id,wagon_number,wagon_sequence,wagon_type,tare_weight_mt,pcc_weight_mt,is_unfit',
            'loader:id,loader_name,code',
        ]);

        return response()->json([
            'loading' => [
                'id' => $loading->id,
                'wagon_id' => $loading->wagon_id,
                'loader_id' => $loading->loader_id,
                'loader_operator_name' => $loading->loader_operator_name,
                'loaded_quantity_mt' => $loading->loaded_quantity_mt !== null ? (string) $loading->loaded_quantity_mt : '',
                'loading_time' => $loading->loading_time?->toIso8601String(),
                'remarks' => $loading->remarks,
                'wagon' => $loading->wagon ? [
                    'id' => $loading->wagon->id,
                    'wagon_number' => $loading->wagon->wagon_number,
                    'wagon_sequence' => $loading->wagon->wagon_sequence,
                    'wagon_type' => $loading->wagon->wagon_type,
                    'tare_weight_mt' => $loading->wagon->tare_weight_mt,
                    'pcc_weight_mt' => $loading->wagon->pcc_weight_mt,
                ] : null,
                'loader' => $loading->loader ? [
                    'id' => $loading->loader->id,
                    'loader_name' => $loading->loader->loader_name,
                    'code' => $loading->loader->code,
                ] : null,
            ],
        ]);
    }

    public function destroyWagonRow(Request $request, Rake $rake, RakeWagonLoading $loading): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $this->canEditLoaderWeighment($user), 403);

        if ($loading->rake_id !== $rake->id) {
            abort(404);
        }

        $loading->delete();

        $this->syncLoaderWeighmentStatus($rake->fresh());

        return response()->json(['deleted' => true]);
    }

    public function recordGuardInspection(Request $request, Rake $rake): RedirectResponse
    {
        // $this->authorize('update', $rake);

        $validated = $request->validate([
            'inspection_start_time' => ['required', 'date'],
            'inspection_end_time' => ['required', 'date'],
            'is_approved' => ['required', 'boolean'],
            'remarks' => ['required_if:is_approved,false', 'nullable', 'string', 'max:1000'],
        ], [
            'remarks.required_if' => 'Remarks are required when inspection is rejected.',
        ]);

        $guardInspection = GuardInspection::updateOrCreate(
            ['rake_id' => $rake->id],
            [
                'inspection_start_time' => $validated['inspection_start_time'],
                'inspection_end_time' => $validated['inspection_end_time'],
                'is_approved' => $validated['is_approved'],
                'movement_permission_time' => null,
                'remarks' => $validated['remarks'] ?? null,
                'created_by' => $request->user()?->id,
            ]
        );

        RakeGuardInspectionUpdated::dispatch($rake, $guardInspection, 'created');

        return redirect()
            ->route('rakes.show', $rake)
            ->setStatusCode(303)
            ->with('success', 'Guard inspection recorded.');
    }

    public function confirmDispatch(Request $request, Rake $rake): RedirectResponse
    {
        // $this->authorize('update', $rake);

        $rakeLoad = $rake->rakeLoad;
        if (! $rakeLoad) {
            return redirect()
                ->route('rakes.load.show', $rake)
                ->setStatusCode(303)
                ->with('error', 'Loading process not started.');
        }

        $state = $this->stateResolver->resolve($rake);
        if ($state['active_step'] !== RakeLoadStateResolver::STEP_DISPATCH) {
            return redirect()
                ->route('rakes.load.show', $rake)
                ->setStatusCode(303)
                ->with('error', 'Complete guard inspection before dispatch.');
        }

        if ($rakeLoad->status === 'completed') {
            return redirect()
                ->route('rakes.show', $rake)
                ->setStatusCode(303);
        }

        DB::transaction(function () use ($rake, $rakeLoad): void {
            $rake->update([
                'dispatch_time' => now(),
                'state' => 'ready_for_dispatch',
            ]);

            $rakeLoad->update(['status' => 'completed']);

            $state = $this->stateResolver->resolve($rake);
            RakeLoadUpdated::dispatch($rake, $rakeLoad, $state, 'dispatch_confirmed');

            $elapsedMinutes = now()->diffInMinutes($rakeLoad->placement_time);
            if ($elapsedMinutes > $rakeLoad->free_time_minutes) {
                $demurrageHours = (int) ceil(($elapsedMinutes - $rakeLoad->free_time_minutes) / 60);
                $penaltyType = PenaltyType::where('code', 'demurrage')->first();
                if ($penaltyType) {
                    $demurrageRate = (float) config('rrmcs.demurrage_rate_per_mt_hour', 50);
                    $attemptNo = (int) ($rakeLoad->wagonLoadings()->max('attempt_no') ?? 1);
                    $totalWeight = (float) $rakeLoad->wagonLoadings()
                        ->where('attempt_no', $attemptNo)
                        ->sum('loaded_quantity_mt');
                    $penaltyAmount = $demurrageHours * $totalWeight * $demurrageRate;

                    $rake->appliedPenalties()->create([
                        'penalty_type_id' => $penaltyType->id,
                        'quantity' => $totalWeight,
                        'rate' => $demurrageRate,
                        'amount' => $penaltyAmount,
                        'meta' => [
                            'formula' => 'demurrage_hours × weight_mt × rate_per_mt_hour',
                            'demurrage_hours' => $demurrageHours,
                            'weight_mt' => $totalWeight,
                            'rate_per_mt_hour' => $demurrageRate,
                            'free_hours' => $rakeLoad->free_time_minutes / 60,
                            'dwell_hours' => $elapsedMinutes / 60,
                        ],
                    ]);
                }
            }
        });

        return redirect()
            ->route('rakes.show', $rake)
            ->setStatusCode(303)
            ->with('success', 'Rake dispatched successfully.');
    }

    private static function shouldSkipLoaderWeighmentWagonNumber(?string $wagonNumber): bool
    {
        // Placeholder wagon numbers like W01 are now treated as normal wagons.
        return false;
    }

    private function canEditLoaderWeighment(User $user): bool
    {
        return $this->hasSectionPermission($user, 'sections.rakes.update')
            || $this->hasSectionPermission($user, 'sections.rake_loader.update');
    }

    private function hasSectionPermission(User $user, string $permission): bool
    {
        if ($user->can('bypass-permissions')) {
            return true;
        }

        if (\App\Services\TenantContext::check() && $user->canInCurrentOrganization($permission)) {
            return true;
        }

        return $user->hasPermissionTo($permission);
    }

    private function syncLoaderWeighmentStatus(Rake $rake): void
    {
        $wagons = $rake->wagons()
            ->get(['id', 'wagon_number', 'is_unfit'])
            ->filter(static fn ($w): bool => ! (bool) $w->is_unfit)
            ->reject(static fn ($w): bool => self::shouldSkipLoaderWeighmentWagonNumber($w->wagon_number))
            ->values();

        if ($wagons->isEmpty()) {
            $rake->update(['loader_weighment_status' => 'pending']);

            return;
        }

        $loadings = $rake->wagonLoadings()
            ->whereIn('wagon_id', $wagons->pluck('id'))
            ->get(['wagon_id', 'loader_id', 'loaded_quantity_mt']);

        $hasAnyInput = $loadings->contains(static function ($l): bool {
            return $l->loader_id !== null || (float) ($l->loaded_quantity_mt ?? 0) > 0;
        });

        $completeWagonIds = $loadings
            ->filter(static function ($l): bool {
                return $l->loader_id !== null && (float) ($l->loaded_quantity_mt ?? 0) > 0;
            })
            ->pluck('wagon_id')
            ->flip();

        $isComplete = $wagons->every(static fn ($w): bool => $completeWagonIds->has($w->id));

        $status = $isComplete ? 'completed' : ($hasAnyInput ? 'in_progress' : 'pending');

        if ($rake->loader_weighment_status !== $status) {
            $rake->update(['loader_weighment_status' => $status]);
        }
    }
}
