<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreGeofenceRequest;
use App\Http\Requests\Fleet\UpdateGeofenceRequest;
use App\Models\Fleet\Geofence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class GeofenceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Geofence::class);
        $geofences = Geofence::query()
            ->with('location')
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/Geofences/Index', [
            'geofences' => $geofences,
            'filters' => $request->only(['is_active']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Geofence::class);

        return Inertia::render('Fleet/Geofences/Create', [
            'types' => array_map(fn (\App\Enums\Fleet\GeofenceType $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\GeofenceType::cases()),
            'locations' => \App\Models\Fleet\Location::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreGeofenceRequest $request): RedirectResponse
    {
        $this->authorize('create', Geofence::class);
        Geofence::query()->create($request->validated());

        return to_route('fleet.geofences.index')->with('flash', ['status' => 'success', 'message' => 'Geofence created.']);
    }

    public function show(Geofence $geofence): Response
    {
        $this->authorize('view', $geofence);
        $geofence->load('location');

        return Inertia::render('Fleet/Geofences/Show', ['geofence' => $geofence]);
    }

    public function edit(Geofence $geofence): Response
    {
        $this->authorize('update', $geofence);

        return Inertia::render('Fleet/Geofences/Edit', [
            'geofence' => $geofence,
            'types' => array_map(fn (\App\Enums\Fleet\GeofenceType $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\GeofenceType::cases()),
            'locations' => \App\Models\Fleet\Location::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateGeofenceRequest $request, Geofence $geofence): RedirectResponse
    {
        $this->authorize('update', $geofence);
        $geofence->update($request->validated());

        return to_route('fleet.geofences.show', $geofence)->with('flash', ['status' => 'success', 'message' => 'Geofence updated.']);
    }

    public function destroy(Geofence $geofence): RedirectResponse
    {
        $this->authorize('delete', $geofence);
        $geofence->delete();

        return to_route('fleet.geofences.index')->with('flash', ['status' => 'success', 'message' => 'Geofence deleted.']);
    }
}
