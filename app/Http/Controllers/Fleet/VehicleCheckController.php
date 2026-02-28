<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Enums\Fleet\VehicleCheckStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreVehicleCheckRequest;
use App\Http\Requests\Fleet\UpdateVehicleCheckRequest;
use App\Models\Fleet\Defect;
use App\Models\Fleet\Driver;
use App\Models\Fleet\Vehicle;
use App\Models\Fleet\VehicleCheck;
use App\Models\Fleet\VehicleCheckTemplate;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class VehicleCheckController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', VehicleCheck::class);
        $checks = VehicleCheck::query()
            ->with(['vehicle', 'vehicleCheckTemplate', 'performedByDriver', 'performedByUser'])
            ->when($request->input('vehicle_id'), fn ($q, $v) => $q->where('vehicle_id', $v))
            ->when($request->input('vehicle_check_template_id'), fn ($q, $v) => $q->where('vehicle_check_template_id', $v))
            ->when($request->input('check_date'), fn ($q, $v) => $q->whereDate('check_date', $v))
            ->orderByDesc('check_date')
            ->paginate(15)
            ->withQueryString();

        $vehicles = Vehicle::query()->orderBy('registration')->get(['id', 'registration'])->map(fn ($v) => ['id' => $v->id, 'name' => $v->registration]);
        $templates = VehicleCheckTemplate::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])->map(fn ($t) => ['id' => $t->id, 'name' => $t->name]);

        return Inertia::render('Fleet/VehicleChecks/Index', [
            'vehicleChecks' => $checks,
            'filters' => $request->only(['vehicle_id', 'vehicle_check_template_id', 'check_date']),
            'vehicles' => $vehicles,
            'vehicleCheckTemplates' => $templates,
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], VehicleCheckStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', VehicleCheck::class);
        $vehicles = Vehicle::query()->orderBy('registration')->get(['id', 'registration'])->map(fn ($v) => ['id' => $v->id, 'name' => $v->registration]);
        $templates = VehicleCheckTemplate::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'checklist'])->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'checklist' => $t->checklist]);
        $drivers = Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name'])->map(fn ($d) => ['id' => $d->id, 'name' => $d->first_name . ' ' . $d->last_name]);
        $users = User::query()->orderBy('name')->get(['id', 'name'])->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]);
        $defects = Defect::query()->orderByDesc('reported_at')->limit(100)->get(['id', 'defect_number', 'title'])->map(fn ($d) => ['id' => $d->id, 'name' => $d->defect_number . ' – ' . $d->title]);

        return Inertia::render('Fleet/VehicleChecks/Create', [
            'vehicles' => $vehicles,
            'vehicleCheckTemplates' => $templates,
            'drivers' => $drivers,
            'users' => $users,
            'defects' => $defects,
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], VehicleCheckStatus::cases()),
        ]);
    }

    public function store(StoreVehicleCheckRequest $request): RedirectResponse
    {
        $this->authorize('create', VehicleCheck::class);
        VehicleCheck::create($request->validated());
        return to_route('fleet.vehicle-checks.index')->with('flash', ['status' => 'success', 'message' => 'Vehicle check created.']);
    }

    public function show(VehicleCheck $vehicle_check): Response
    {
        $this->authorize('view', $vehicle_check);
        $vehicle_check->load(['vehicle', 'vehicleCheckTemplate', 'performedByDriver', 'performedByUser', 'defect', 'vehicleCheckItems']);
        return Inertia::render('Fleet/VehicleChecks/Show', ['vehicleCheck' => $vehicle_check]);
    }

    public function edit(VehicleCheck $vehicle_check): Response
    {
        $this->authorize('update', $vehicle_check);
        $vehicle_check->load('vehicleCheckItems');
        $vehicles = Vehicle::query()->orderBy('registration')->get(['id', 'registration'])->map(fn ($v) => ['id' => $v->id, 'name' => $v->registration]);
        $templates = VehicleCheckTemplate::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'checklist'])->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'checklist' => $t->checklist]);
        $drivers = Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name'])->map(fn ($d) => ['id' => $d->id, 'name' => $d->first_name . ' ' . $d->last_name]);
        $users = User::query()->orderBy('name')->get(['id', 'name'])->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]);
        $defects = Defect::query()->orderByDesc('reported_at')->limit(100)->get(['id', 'defect_number', 'title'])->map(fn ($d) => ['id' => $d->id, 'name' => $d->defect_number . ' – ' . $d->title]);

        return Inertia::render('Fleet/VehicleChecks/Edit', [
            'vehicleCheck' => $vehicle_check,
            'vehicles' => $vehicles,
            'vehicleCheckTemplates' => $templates,
            'drivers' => $drivers,
            'users' => $users,
            'defects' => $defects,
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], VehicleCheckStatus::cases()),
        ]);
    }

    public function update(UpdateVehicleCheckRequest $request, VehicleCheck $vehicle_check): RedirectResponse
    {
        $this->authorize('update', $vehicle_check);
        $vehicle_check->update($request->validated());
        return to_route('fleet.vehicle-checks.show', $vehicle_check)->with('flash', ['status' => 'success', 'message' => 'Vehicle check updated.']);
    }

    public function destroy(VehicleCheck $vehicle_check): RedirectResponse
    {
        $this->authorize('delete', $vehicle_check);
        $vehicle_check->delete();
        return to_route('fleet.vehicle-checks.index')->with('flash', ['status' => 'success', 'message' => 'Vehicle check deleted.']);
    }
}
