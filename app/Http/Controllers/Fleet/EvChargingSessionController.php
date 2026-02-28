<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreEvChargingSessionRequest;
use App\Http\Requests\Fleet\UpdateEvChargingSessionRequest;
use App\Models\Fleet\EvChargingSession;
use App\Models\Fleet\EvChargingStation;
use App\Models\Fleet\Driver;
use App\Models\Fleet\Vehicle;
use App\Enums\Fleet\EvChargingSessionType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class EvChargingSessionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', EvChargingSession::class);
        $sessions = EvChargingSession::query()
            ->with(['vehicle', 'driver', 'chargingStation'])
            ->when($request->input('vehicle_id'), fn ($q, $v) => $q->where('vehicle_id', $v))
            ->when($request->input('driver_id'), fn ($q, $v) => $q->where('driver_id', $v))
            ->when($request->input('charging_station_id'), fn ($q, $v) => $q->where('charging_station_id', $v))
            ->when($request->input('session_type'), fn ($q, $v) => $q->where('session_type', $v))
            ->orderByDesc('start_timestamp')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/EvChargingSessions/Index', [
            'evChargingSessions' => $sessions,
            'filters' => $request->only(['vehicle_id', 'driver_id', 'charging_station_id', 'session_type']),
            'vehicles' => Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'drivers' => Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name'])->map(fn ($d) => ['id' => $d->id, 'name' => $d->first_name . ' ' . $d->last_name]),
            'evChargingStations' => EvChargingStation::query()->orderBy('name')->get(['id', 'name']),
            'sessionTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], EvChargingSessionType::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', EvChargingSession::class);
        return Inertia::render('Fleet/EvChargingSessions/Create', [
            'vehicles' => Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'drivers' => Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name'])->map(fn ($d) => ['id' => $d->id, 'name' => $d->first_name . ' ' . $d->last_name]),
            'evChargingStations' => EvChargingStation::query()->orderBy('name')->get(['id', 'name']),
            'sessionTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], EvChargingSessionType::cases()),
        ]);
    }

    public function store(StoreEvChargingSessionRequest $request): RedirectResponse
    {
        $this->authorize('create', EvChargingSession::class);
        EvChargingSession::create($request->validated());
        return to_route('fleet.ev-charging-sessions.index')->with('flash', ['status' => 'success', 'message' => 'EV charging session created.']);
    }

    public function show(EvChargingSession $ev_charging_session): Response
    {
        $this->authorize('view', $ev_charging_session);
        $ev_charging_session->load(['vehicle', 'driver', 'chargingStation']);

        return Inertia::render('Fleet/EvChargingSessions/Show', ['evChargingSession' => $ev_charging_session]);
    }

    public function edit(EvChargingSession $ev_charging_session): Response
    {
        $this->authorize('update', $ev_charging_session);
        return Inertia::render('Fleet/EvChargingSessions/Edit', [
            'evChargingSession' => $ev_charging_session,
            'vehicles' => Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'drivers' => Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name'])->map(fn ($d) => ['id' => $d->id, 'name' => $d->first_name . ' ' . $d->last_name]),
            'evChargingStations' => EvChargingStation::query()->orderBy('name')->get(['id', 'name']),
            'sessionTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], EvChargingSessionType::cases()),
        ]);
    }

    public function update(UpdateEvChargingSessionRequest $request, EvChargingSession $ev_charging_session): RedirectResponse
    {
        $this->authorize('update', $ev_charging_session);
        $ev_charging_session->update($request->validated());
        return to_route('fleet.ev-charging-sessions.show', $ev_charging_session)->with('flash', ['status' => 'success', 'message' => 'EV charging session updated.']);
    }

    public function destroy(EvChargingSession $ev_charging_session): RedirectResponse
    {
        $this->authorize('delete', $ev_charging_session);
        $ev_charging_session->delete();
        return to_route('fleet.ev-charging-sessions.index')->with('flash', ['status' => 'success', 'message' => 'EV charging session deleted.']);
    }
}
