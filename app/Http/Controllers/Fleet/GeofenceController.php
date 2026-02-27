<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
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
            ->when($request->boolean('is_active') !== null, fn ($q) => $q->where('is_active', $request->boolean('is_active')))
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
            'types' => \App\Enums\Fleet\GeofenceType::cases(),
            'locations' => \App\Models\Fleet\Location::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Geofence::class);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'geofence_type' => ['required', 'string', 'in:circle,polygon,administrative_boundary'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'center_lat' => ['nullable', 'numeric'],
            'center_lng' => ['nullable', 'numeric'],
            'radius_meters' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        Geofence::create($validated);
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
            'types' => \App\Enums\Fleet\GeofenceType::cases(),
            'locations' => \App\Models\Fleet\Location::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Geofence $geofence): RedirectResponse
    {
        $this->authorize('update', $geofence);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'geofence_type' => ['required', 'string', 'in:circle,polygon,administrative_boundary'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'center_lat' => ['nullable', 'numeric'],
            'center_lng' => ['nullable', 'numeric'],
            'radius_meters' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $geofence->update($validated);
        return to_route('fleet.geofences.show', $geofence)->with('flash', ['status' => 'success', 'message' => 'Geofence updated.']);
    }

    public function destroy(Geofence $geofence): RedirectResponse
    {
        $this->authorize('delete', $geofence);
        $geofence->delete();
        return to_route('fleet.geofences.index')->with('flash', ['status' => 'success', 'message' => 'Geofence deleted.']);
    }
}
