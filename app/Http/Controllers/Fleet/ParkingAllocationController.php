<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreParkingAllocationRequest;
use App\Http\Requests\Fleet\UpdateParkingAllocationRequest;
use App\Models\Fleet\Location;
use App\Models\Fleet\ParkingAllocation;
use App\Models\Fleet\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ParkingAllocationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ParkingAllocation::class);
        $allocations = ParkingAllocation::query()
            ->with(['vehicle', 'location'])
            ->when($request->input('vehicle_id'), fn ($q, $id) => $q->where('vehicle_id', $id))
            ->when($request->input('location_id'), fn ($q, $id) => $q->where('location_id', $id))
            ->orderByDesc('allocated_from')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/ParkingAllocations/Index', [
            'parkingAllocations' => $allocations,
            'filters' => $request->only(['vehicle_id', 'location_id']),
            'vehicles' => Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'locations' => Location::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', ParkingAllocation::class);

        return Inertia::render('Fleet/ParkingAllocations/Create', [
            'vehicles' => Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'locations' => Location::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreParkingAllocationRequest $request): RedirectResponse
    {
        $this->authorize('create', ParkingAllocation::class);
        ParkingAllocation::query()->create($request->validated());

        return to_route('fleet.parking-allocations.index')->with('flash', ['status' => 'success', 'message' => 'Parking allocation created.']);
    }

    public function show(ParkingAllocation $parking_allocation): Response
    {
        $this->authorize('view', $parking_allocation);
        $parking_allocation->load(['vehicle', 'location']);

        return Inertia::render('Fleet/ParkingAllocations/Show', ['parkingAllocation' => $parking_allocation]);
    }

    public function edit(ParkingAllocation $parking_allocation): Response
    {
        $this->authorize('update', $parking_allocation);

        return Inertia::render('Fleet/ParkingAllocations/Edit', [
            'parkingAllocation' => $parking_allocation,
            'vehicles' => Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'locations' => Location::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateParkingAllocationRequest $request, ParkingAllocation $parking_allocation): RedirectResponse
    {
        $this->authorize('update', $parking_allocation);
        $parking_allocation->update($request->validated());

        return to_route('fleet.parking-allocations.show', $parking_allocation)->with('flash', ['status' => 'success', 'message' => 'Parking allocation updated.']);
    }

    public function destroy(ParkingAllocation $parking_allocation): RedirectResponse
    {
        $this->authorize('delete', $parking_allocation);
        $parking_allocation->delete();

        return to_route('fleet.parking-allocations.index')->with('flash', ['status' => 'success', 'message' => 'Parking allocation deleted.']);
    }
}
