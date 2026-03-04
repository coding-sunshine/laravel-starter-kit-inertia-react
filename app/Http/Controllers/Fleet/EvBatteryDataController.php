<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Enums\Fleet\EvBatteryChargingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreEvBatteryDataRequest;
use App\Http\Requests\Fleet\UpdateEvBatteryDataRequest;
use App\Models\Fleet\EvBatteryData;
use App\Models\Fleet\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class EvBatteryDataController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', EvBatteryData::class);
        $vehicleIds = $this->vehicleIdsForOrganization();
        $records = EvBatteryData::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->with('vehicle')
            ->when($request->input('vehicle_id'), fn ($q, $v) => $q->where('vehicle_id', $v))
            ->when($request->input('charging_status'), fn ($q, $v) => $q->where('charging_status', $v))
            ->orderByDesc('recorded_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/EvBatteryData/Index', [
            'evBatteryData' => $records,
            'filters' => $request->only(['vehicle_id', 'charging_status']),
            'vehicles' => Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'chargingStatuses' => array_map(fn (EvBatteryChargingStatus $c): array => ['value' => $c->value, 'name' => $c->name], EvBatteryChargingStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', EvBatteryData::class);

        return Inertia::render('Fleet/EvBatteryData/Create', [
            'vehicles' => Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'chargingStatuses' => array_map(fn (EvBatteryChargingStatus $c): array => ['value' => $c->value, 'name' => $c->name], EvBatteryChargingStatus::cases()),
        ]);
    }

    public function store(StoreEvBatteryDataRequest $request): RedirectResponse
    {
        $this->authorize('create', EvBatteryData::class);
        EvBatteryData::query()->create($request->validated());

        return to_route('fleet.ev-battery-data.index')->with('flash', ['status' => 'success', 'message' => 'EV battery data created.']);
    }

    public function show(EvBatteryData $ev_battery_data): Response
    {
        $this->authorize('view', $ev_battery_data);
        $ev_battery_data->load('vehicle');

        return Inertia::render('Fleet/EvBatteryData/Show', ['evBatteryData' => $ev_battery_data]);
    }

    public function edit(EvBatteryData $ev_battery_data): Response
    {
        $this->authorize('update', $ev_battery_data);

        return Inertia::render('Fleet/EvBatteryData/Edit', [
            'evBatteryData' => $ev_battery_data,
            'vehicles' => Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'chargingStatuses' => array_map(fn (EvBatteryChargingStatus $c): array => ['value' => $c->value, 'name' => $c->name], EvBatteryChargingStatus::cases()),
        ]);
    }

    public function update(UpdateEvBatteryDataRequest $request, EvBatteryData $ev_battery_data): RedirectResponse
    {
        $this->authorize('update', $ev_battery_data);
        $ev_battery_data->update($request->validated());

        return to_route('fleet.ev-battery-data.show', $ev_battery_data)->with('flash', ['status' => 'success', 'message' => 'EV battery data updated.']);
    }

    public function destroy(EvBatteryData $ev_battery_data): RedirectResponse
    {
        $this->authorize('delete', $ev_battery_data);
        $ev_battery_data->delete();

        return to_route('fleet.ev-battery-data.index')->with('flash', ['status' => 'success', 'message' => 'EV battery data deleted.']);
    }

    private function vehicleIdsForOrganization(): \Illuminate\Support\Collection
    {
        return Vehicle::query()->pluck('id');
    }
}
