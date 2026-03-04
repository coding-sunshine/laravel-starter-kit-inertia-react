<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreLocationRequest;
use App\Http\Requests\Fleet\UpdateLocationRequest;
use App\Models\Fleet\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class LocationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Location::class);

        $locations = Location::query()
            ->when($request->input('type'), fn ($q, $type) => $q->where('type', $type))
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/Locations/Index', [
            'locations' => $locations,
            'filters' => $request->only(['type', 'is_active']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Location::class);

        return Inertia::render('Fleet/Locations/Create', [
            'locationTypes' => array_map(fn (\App\Enums\Fleet\LocationType $c): array => ['name' => $c->name, 'value' => $c->value], \App\Enums\Fleet\LocationType::cases()),
        ]);
    }

    public function store(StoreLocationRequest $request): RedirectResponse
    {
        Location::query()->create($request->validated());

        return to_route('fleet.locations.index')->with('flash', ['status' => 'success', 'message' => 'Location created.']);
    }

    public function show(Location $location): Response
    {
        $this->authorize('view', $location);
        $location->load(['vehiclesHomeLocation' => fn ($q) => $q->limit(5), 'geofences']);

        return Inertia::render('Fleet/Locations/Show', [
            'location' => $location,
        ]);
    }

    public function edit(Location $location): Response
    {
        $this->authorize('update', $location);

        return Inertia::render('Fleet/Locations/Edit', [
            'location' => $location,
            'locationTypes' => array_map(fn (\App\Enums\Fleet\LocationType $c): array => ['name' => $c->name, 'value' => $c->value], \App\Enums\Fleet\LocationType::cases()),
        ]);
    }

    public function update(UpdateLocationRequest $request, Location $location): RedirectResponse
    {
        $location->update($request->validated());

        return to_route('fleet.locations.show', $location)->with('flash', ['status' => 'success', 'message' => 'Location updated.']);
    }

    public function destroy(Location $location): RedirectResponse
    {
        $this->authorize('delete', $location);
        $location->delete();

        return to_route('fleet.locations.index')->with('flash', ['status' => 'success', 'message' => 'Location deleted.']);
    }
}
