<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreTelematicsDeviceRequest;
use App\Http\Requests\Fleet\UpdateTelematicsDeviceRequest;
use App\Models\Fleet\TelematicsDevice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class TelematicsDeviceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TelematicsDevice::class);
        $devices = TelematicsDevice::query()
            ->with('vehicle')
            ->when($request->has('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderBy('device_id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/TelematicsDevices/Index', [
            'telematicsDevices' => $devices,
            'filters' => $request->only(['is_active', 'status']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', TelematicsDevice::class);
        return Inertia::render('Fleet/TelematicsDevices/Create', [
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\TelematicsDeviceStatus::cases()),
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
        ]);
    }

    public function store(StoreTelematicsDeviceRequest $request): RedirectResponse
    {
        $this->authorize('create', TelematicsDevice::class);
        TelematicsDevice::create($request->validated());
        return to_route('fleet.telematics-devices.index')->with('flash', ['status' => 'success', 'message' => 'Telematics device created.']);
    }

    public function show(TelematicsDevice $telematics_device): Response
    {
        $this->authorize('view', $telematics_device);
        $telematics_device->load('vehicle');

        return Inertia::render('Fleet/TelematicsDevices/Show', ['telematicsDevice' => $telematics_device]);
    }

    public function edit(TelematicsDevice $telematics_device): Response
    {
        $this->authorize('update', $telematics_device);
        return Inertia::render('Fleet/TelematicsDevices/Edit', [
            'telematicsDevice' => $telematics_device,
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\TelematicsDeviceStatus::cases()),
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
        ]);
    }

    public function update(UpdateTelematicsDeviceRequest $request, TelematicsDevice $telematics_device): RedirectResponse
    {
        $this->authorize('update', $telematics_device);
        $telematics_device->update($request->validated());
        return to_route('fleet.telematics-devices.show', $telematics_device)->with('flash', ['status' => 'success', 'message' => 'Telematics device updated.']);
    }

    public function destroy(TelematicsDevice $telematics_device): RedirectResponse
    {
        $this->authorize('delete', $telematics_device);
        $telematics_device->delete();
        return to_route('fleet.telematics-devices.index')->with('flash', ['status' => 'success', 'message' => 'Telematics device deleted.']);
    }
}
