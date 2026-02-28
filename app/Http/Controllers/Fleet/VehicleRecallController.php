<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreVehicleRecallRequest;
use App\Http\Requests\Fleet\UpdateVehicleRecallRequest;
use App\Models\Fleet\VehicleRecall;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class VehicleRecallController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', VehicleRecall::class);
        $recalls = VehicleRecall::query()
            ->with(['vehicle'])
            ->when($request->input('vehicle_id'), fn ($q, $v) => $q->where('vehicle_id', $v))
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('issued_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/VehicleRecalls/Index', [
            'vehicleRecalls' => $recalls,
            'filters' => $request->only(['vehicle_id', 'status']),
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\VehicleRecallStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', VehicleRecall::class);

        return Inertia::render('Fleet/VehicleRecalls/Create', [
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\VehicleRecallStatus::cases()),
        ]);
    }

    public function store(StoreVehicleRecallRequest $request): RedirectResponse
    {
        $this->authorize('create', VehicleRecall::class);
        VehicleRecall::create($request->validated());

        return to_route('fleet.vehicle-recalls.index')->with('flash', ['status' => 'success', 'message' => 'Vehicle recall created.']);
    }

    public function show(VehicleRecall $vehicle_recall): Response
    {
        $this->authorize('view', $vehicle_recall);
        $vehicle_recall->load(['vehicle']);

        return Inertia::render('Fleet/VehicleRecalls/Show', ['vehicleRecall' => $vehicle_recall]);
    }

    public function edit(VehicleRecall $vehicle_recall): Response
    {
        $this->authorize('update', $vehicle_recall);

        return Inertia::render('Fleet/VehicleRecalls/Edit', [
            'vehicleRecall' => $vehicle_recall,
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\VehicleRecallStatus::cases()),
        ]);
    }

    public function update(UpdateVehicleRecallRequest $request, VehicleRecall $vehicle_recall): RedirectResponse
    {
        $this->authorize('update', $vehicle_recall);
        $vehicle_recall->update($request->validated());

        return to_route('fleet.vehicle-recalls.show', $vehicle_recall)->with('flash', ['status' => 'success', 'message' => 'Vehicle recall updated.']);
    }

    public function destroy(VehicleRecall $vehicle_recall): RedirectResponse
    {
        $this->authorize('delete', $vehicle_recall);
        $vehicle_recall->delete();

        return to_route('fleet.vehicle-recalls.index')->with('flash', ['status' => 'success', 'message' => 'Vehicle recall deleted.']);
    }
}
