<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Enums\Fleet\SafetyObservationCategory;
use App\Enums\Fleet\SafetyObservationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreSafetyObservationRequest;
use App\Http\Requests\Fleet\UpdateSafetyObservationRequest;
use App\Models\Fleet\Location;
use App\Models\Fleet\SafetyObservation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class SafetyObservationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SafetyObservation::class);
        $observations = SafetyObservation::query()
            ->with(['reportedBy', 'location'])
            ->when($request->input('category'), fn ($q, $v) => $q->where('category', $v))
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/SafetyObservations/Index', [
            'safetyObservations' => $observations,
            'filters' => $request->only(['category', 'status']),
            'categories' => array_map(fn (SafetyObservationCategory $c): array => ['value' => $c->value, 'name' => $c->name], SafetyObservationCategory::cases()),
            'statuses' => array_map(fn (SafetyObservationStatus $c): array => ['value' => $c->value, 'name' => $c->name], SafetyObservationStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', SafetyObservation::class);
        $users = User::query()->orderBy('name')->get(['id', 'name'])->map(fn ($u): array => ['id' => $u->id, 'name' => $u->name]);
        $locations = Location::query()->orderBy('name')->get(['id', 'name'])->map(fn ($l): array => ['id' => $l->id, 'name' => $l->name]);

        return Inertia::render('Fleet/SafetyObservations/Create', [
            'users' => $users,
            'locations' => $locations,
            'categories' => array_map(fn (SafetyObservationCategory $c): array => ['value' => $c->value, 'name' => $c->name], SafetyObservationCategory::cases()),
            'statuses' => array_map(fn (SafetyObservationStatus $c): array => ['value' => $c->value, 'name' => $c->name], SafetyObservationStatus::cases()),
        ]);
    }

    public function store(StoreSafetyObservationRequest $request): RedirectResponse
    {
        $this->authorize('create', SafetyObservation::class);
        SafetyObservation::query()->create($request->validated());

        return to_route('fleet.safety-observations.index')->with('flash', ['status' => 'success', 'message' => 'Safety observation created.']);
    }

    public function show(SafetyObservation $safety_observation): Response
    {
        $this->authorize('view', $safety_observation);
        $safety_observation->load(['reportedBy', 'location']);

        return Inertia::render('Fleet/SafetyObservations/Show', ['safetyObservation' => $safety_observation]);
    }

    public function edit(SafetyObservation $safety_observation): Response
    {
        $this->authorize('update', $safety_observation);
        $users = User::query()->orderBy('name')->get(['id', 'name'])->map(fn ($u): array => ['id' => $u->id, 'name' => $u->name]);
        $locations = Location::query()->orderBy('name')->get(['id', 'name'])->map(fn ($l): array => ['id' => $l->id, 'name' => $l->name]);

        return Inertia::render('Fleet/SafetyObservations/Edit', [
            'safetyObservation' => $safety_observation,
            'users' => $users,
            'locations' => $locations,
            'categories' => array_map(fn (SafetyObservationCategory $c): array => ['value' => $c->value, 'name' => $c->name], SafetyObservationCategory::cases()),
            'statuses' => array_map(fn (SafetyObservationStatus $c): array => ['value' => $c->value, 'name' => $c->name], SafetyObservationStatus::cases()),
        ]);
    }

    public function update(UpdateSafetyObservationRequest $request, SafetyObservation $safety_observation): RedirectResponse
    {
        $this->authorize('update', $safety_observation);
        $safety_observation->update($request->validated());

        return to_route('fleet.safety-observations.show', $safety_observation)->with('flash', ['status' => 'success', 'message' => 'Safety observation updated.']);
    }

    public function destroy(SafetyObservation $safety_observation): RedirectResponse
    {
        $this->authorize('delete', $safety_observation);
        $safety_observation->delete();

        return to_route('fleet.safety-observations.index')->with('flash', ['status' => 'success', 'message' => 'Safety observation deleted.']);
    }
}
