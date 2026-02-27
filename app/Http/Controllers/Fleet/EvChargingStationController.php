<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\EvChargingStation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class EvChargingStationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', EvChargingStation::class);
        $evChargingStations = EvChargingStation::query()
            ->with('location')
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/EvChargingStations/Index', [
            'evChargingStations' => $evChargingStations,
            'filters' => $request->only(['status']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', EvChargingStation::class);
        return Inertia::render('Fleet/EvChargingStations/Create', [
            'locations' => \App\Models\Fleet\Location::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', EvChargingStation::class);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'operator' => ['nullable', 'string', 'max:100'],
            'network' => ['nullable', 'string', 'max:100'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'address' => ['nullable', 'string'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'access_type' => ['required', 'string', 'in:public,private,restricted'],
            'total_connectors' => ['nullable', 'integer', 'min:1'],
            'available_connectors' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'string', 'in:operational,maintenance,out_of_service'],
        ]);
        EvChargingStation::create($validated);
        return to_route('fleet.ev-charging-stations.index')->with('flash', ['status' => 'success', 'message' => 'EV charging station created.']);
    }

    public function show(EvChargingStation $evChargingStation): Response
    {
        $this->authorize('view', $evChargingStation);
        $evChargingStation->load('location');
        return Inertia::render('Fleet/EvChargingStations/Show', ['evChargingStation' => $evChargingStation]);
    }

    public function edit(EvChargingStation $evChargingStation): Response
    {
        $this->authorize('update', $evChargingStation);
        return Inertia::render('Fleet/EvChargingStations/Edit', [
            'evChargingStation' => $evChargingStation,
            'locations' => \App\Models\Fleet\Location::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, EvChargingStation $evChargingStation): RedirectResponse
    {
        $this->authorize('update', $evChargingStation);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'operator' => ['nullable', 'string', 'max:100'],
            'network' => ['nullable', 'string', 'max:100'],
            'location_id' => ['nullable', 'exists:locations,id'],
            'address' => ['nullable', 'string'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'access_type' => ['required', 'string', 'in:public,private,restricted'],
            'total_connectors' => ['nullable', 'integer', 'min:1'],
            'available_connectors' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'string', 'in:operational,maintenance,out_of_service'],
        ]);
        $evChargingStation->update($validated);
        return to_route('fleet.ev-charging-stations.show', $evChargingStation)->with('flash', ['status' => 'success', 'message' => 'EV charging station updated.']);
    }

    public function destroy(EvChargingStation $evChargingStation): RedirectResponse
    {
        $this->authorize('delete', $evChargingStation);
        $evChargingStation->delete();
        return to_route('fleet.ev-charging-stations.index')->with('flash', ['status' => 'success', 'message' => 'EV charging station deleted.']);
    }
}
