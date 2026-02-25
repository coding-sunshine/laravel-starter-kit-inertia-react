<?php

declare(strict_types=1);

namespace App\Http\Controllers\Rakes;

use App\Http\Controllers\Controller;
use App\Events\RakeLoadUpdated;
use App\Events\RakeWagonLoadingUpdated;
use App\Events\RakeGuardInspectionUpdated;
use App\Events\RakeWeighmentUpdated;
use App\Models\GuardInspection;
use App\Models\Rake;
use App\Models\RakeLoad as RakeLoadModel;
use App\Models\RakeWagonLoading;
use App\Models\RakeWagonWeighment;
use App\Models\Weighment;
use App\Models\PenaltyType;
use App\Models\Loader;
use App\Services\RakeLoadStateResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class RakeLoadController extends Controller
{
    public function __construct(
        private RakeLoadStateResolver $stateResolver
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
            'rakeLoad.weighments.wagonWeighments',
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

    public function recordGuardInspection(Request $request, Rake $rake): RedirectResponse
    {
        // $this->authorize('update', $rake);

        $rakeLoad = $rake->rakeLoad;
        if (! $rakeLoad) {
            return redirect()
                ->route('rakes.load.show', $rake)
                ->setStatusCode(303)
                ->with('error', 'Loading process not started.');
        }

        $validated = $request->validate([
            'inspection_time' => ['required', 'date'],
            'movement_permission_time' => ['required', 'date'],
            'is_approved' => ['required', 'boolean'],
            'remarks' => ['required_if:is_approved,false', 'nullable', 'string', 'max:1000'],
        ], [
            'remarks.required_if' => 'Remarks are required when inspection is rejected.',
        ]);

        // Get current attempt number
        $attemptNo = $this->currentAttemptNo($rakeLoad);

        $guardInspection = GuardInspection::create([
            'rake_id' => $rake->id,
            'rake_load_id' => $rakeLoad->id,
            'attempt_no' => $attemptNo,
            'inspection_time' => $validated['inspection_time'],
            'movement_permission_time' => $validated['movement_permission_time'],
            'is_approved' => $validated['is_approved'],
            'remarks' => $validated['remarks'] ?? null,
        ]);

        $state = $this->stateResolver->resolve($rake);
        RakeLoadUpdated::dispatch($rake, $rakeLoad, $state, 'guard_inspection_recorded');
        RakeGuardInspectionUpdated::dispatch($rake, $guardInspection, 'created');

        return redirect()
            ->route('rakes.load.show', $rake)
            ->setStatusCode(303)
            ->with('success', 'Guard inspection recorded.');
    }

    private function currentAttemptNo(RakeLoadModel $rakeLoad): int
    {
        $latestWeighment = $rakeLoad->weighments()->latest('weighment_time')->first();

        if ($latestWeighment && $latestWeighment->status === 'failed_overload') {
            return $latestWeighment->attempt_no + 1;
        }

        $maxWagonAttempt = $rakeLoad->wagonLoadings()->max('attempt_no');

        return max(1, (int) $maxWagonAttempt);
    }

    public function recordWeighment(Request $request, Rake $rake): RedirectResponse
    {
        // $this->authorize('update', $rake);

        $rakeLoad = $rake->rakeLoad;
        if (! $rakeLoad) {
            return redirect()
                ->route('rakes.load.show', $rake)
                ->setStatusCode(303)
                ->with('error', 'Loading process not started.');
        }

        $validated = $request->validate([
            'train_speed_kmph' => ['required', 'numeric', 'min:5', 'max:7'],
            'wagon_weights' => ['required', 'array'],
            'wagon_weights.*.wagon_id' => ['required', 'exists:wagons,id'],
            'wagon_weights.*.gross_weight_mt' => ['required', 'numeric', 'min:0'],
        ]);

        $speed = (float) $validated['train_speed_kmph'];
        $attemptNo = Weighment::where('rake_load_id', $rakeLoad->id)->max('attempt_no') ?? 0;
        $attemptNo = (int) $attemptNo + 1;

        if ($speed < 5 || $speed > 7) {
            $weighment = Weighment::create([
                'rake_id' => $rake->id,
                'rake_load_id' => $rakeLoad->id,
                'attempt_no' => $attemptNo,
                'weighment_time' => now(),
                'train_speed_kmph' => $speed,
                'total_weight_mt' => 0,
                'status' => 'failed_speed',
            ]);

            $state = $this->stateResolver->resolve($rake);
            RakeLoadUpdated::dispatch($rake, $rakeLoad, $state, 'weighment_failed_speed');
            RakeWeighmentUpdated::dispatch($rake, $weighment, 'created');

            return redirect()
                ->route('rakes.load.show', $rake)
                ->setStatusCode(303)
                ->with('error', 'Train speed must be between 5 and 7 km/h. Weighment failed.');
        }

        $overloadedWagons = [];
        $totalWeight = 0;

        $weighment = Weighment::create([
            'rake_id' => $rake->id,
            'rake_load_id' => $rakeLoad->id,
            'attempt_no' => $attemptNo,
            'weighment_time' => now(),
            'train_speed_kmph' => $speed,
            'total_weight_mt' => 0,
            'status' => 'pending',
        ]);

        foreach ($validated['wagon_weights'] as $wagonWeight) {
            $wagon = $rake->wagons()->find($wagonWeight['wagon_id']);
            $pccWeight = $wagon?->pcc_weight_mt ? (float) $wagon->pcc_weight_mt : null;
            $grossWeight = (float) $wagonWeight['gross_weight_mt'];
            $isOverloaded = $pccWeight !== null && $grossWeight > $pccWeight;

            RakeWagonWeighment::create([
                'rake_weighment_id' => $weighment->id,
                'wagon_id' => $wagonWeight['wagon_id'],
                'gross_weight_mt' => $grossWeight,
                'is_overloaded' => $isOverloaded,
            ]);

            if ($isOverloaded && $wagon) {
                $overloadedWagons[] = $wagon->wagon_number;
            }
            $totalWeight += $grossWeight;
        }

        $status = ! empty($overloadedWagons) ? 'failed_overload' : 'passed';
        $weighment->update([
            'total_weight_mt' => $totalWeight,
            'status' => $status,
        ]);

        $state = $this->stateResolver->resolve($rake);
        $trigger = $status === 'passed' ? 'weighment_passed' : 'weighment_failed_overload';
        RakeLoadUpdated::dispatch($rake, $rakeLoad, $state, $trigger);
        RakeWeighmentUpdated::dispatch($rake, $weighment, 'updated');

        if (! empty($overloadedWagons)) {
            return redirect()
                ->route('rakes.load.show', $rake)
                ->setStatusCode(303)
                ->with('error', 'Weighment failed. Overloaded wagons: '.implode(', ', $overloadedWagons).'. Please unload excess coal and retry.');
        }

        return redirect()
            ->route('rakes.load.show', $rake)
            ->setStatusCode(303)
            ->with('success', 'Weighment passed. Proceed to Dispatch.');
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
                ->with('error', 'Weighment must pass before dispatch.');
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
                    $passedWeighment = $rakeLoad->weighments()->where('status', 'passed')->latest('weighment_time')->first();
                    $attemptNo = $passedWeighment ? $passedWeighment->attempt_no : 1;
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
}
