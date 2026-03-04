<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreGreyFleetVehicleRequest;
use App\Http\Requests\Fleet\UpdateGreyFleetVehicleRequest;
use App\Models\Fleet\Driver;
use App\Models\Fleet\GreyFleetVehicle;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class GreyFleetVehicleController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', GreyFleetVehicle::class);
        $vehicles = GreyFleetVehicle::query()->with(['user', 'driver'])->orderBy('registration')->paginate(15)->withQueryString();

        return Inertia::render('Fleet/GreyFleetVehicles/Index', [
            'greyFleetVehicles' => $vehicles,
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'drivers' => Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name'])->map(fn ($d): array => ['id' => $d->id, 'name' => $d->first_name.' '.$d->last_name]),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', GreyFleetVehicle::class);

        return Inertia::render('Fleet/GreyFleetVehicles/Create', [
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'drivers' => Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name'])->map(fn ($d): array => ['id' => $d->id, 'name' => $d->first_name.' '.$d->last_name]),
        ]);
    }

    public function store(StoreGreyFleetVehicleRequest $request): RedirectResponse
    {
        $this->authorize('create', GreyFleetVehicle::class);
        GreyFleetVehicle::query()->create($request->validated());

        return to_route('fleet.grey-fleet-vehicles.index')->with('flash', ['status' => 'success', 'message' => 'Grey fleet vehicle created.']);
    }

    public function show(GreyFleetVehicle $grey_fleet_vehicle): Response
    {
        $this->authorize('view', $grey_fleet_vehicle);
        $grey_fleet_vehicle->load(['user', 'driver']);

        return Inertia::render('Fleet/GreyFleetVehicles/Show', ['greyFleetVehicle' => $grey_fleet_vehicle]);
    }

    public function edit(GreyFleetVehicle $grey_fleet_vehicle): Response
    {
        $this->authorize('update', $grey_fleet_vehicle);

        return Inertia::render('Fleet/GreyFleetVehicles/Edit', [
            'greyFleetVehicle' => $grey_fleet_vehicle,
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'drivers' => Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name'])->map(fn ($d): array => ['id' => $d->id, 'name' => $d->first_name.' '.$d->last_name]),
        ]);
    }

    public function update(UpdateGreyFleetVehicleRequest $request, GreyFleetVehicle $grey_fleet_vehicle): RedirectResponse
    {
        $this->authorize('update', $grey_fleet_vehicle);
        $grey_fleet_vehicle->update($request->validated());

        return to_route('fleet.grey-fleet-vehicles.show', $grey_fleet_vehicle)->with('flash', ['status' => 'success', 'message' => 'Grey fleet vehicle updated.']);
    }

    public function destroy(GreyFleetVehicle $grey_fleet_vehicle): RedirectResponse
    {
        $this->authorize('delete', $grey_fleet_vehicle);
        $grey_fleet_vehicle->delete();

        return to_route('fleet.grey-fleet-vehicles.index')->with('flash', ['status' => 'success', 'message' => 'Grey fleet vehicle deleted.']);
    }
}
