<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreEvChargingStationRequest;
use App\Http\Requests\Fleet\UpdateEvChargingStationRequest;
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

    public function store(StoreEvChargingStationRequest $request): RedirectResponse
    {
        $this->authorize('create', EvChargingStation::class);
        EvChargingStation::create($request->validated());
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

    public function update(UpdateEvChargingStationRequest $request, EvChargingStation $evChargingStation): RedirectResponse
    {
        $this->authorize('update', $evChargingStation);
        $evChargingStation->update($request->validated());
        return to_route('fleet.ev-charging-stations.show', $evChargingStation)->with('flash', ['status' => 'success', 'message' => 'EV charging station updated.']);
    }

    public function destroy(EvChargingStation $evChargingStation): RedirectResponse
    {
        $this->authorize('delete', $evChargingStation);
        $evChargingStation->delete();
        return to_route('fleet.ev-charging-stations.index')->with('flash', ['status' => 'success', 'message' => 'EV charging station deleted.']);
    }
}
