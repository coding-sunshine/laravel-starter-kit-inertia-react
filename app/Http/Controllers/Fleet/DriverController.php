<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreDriverRequest;
use App\Http\Requests\Fleet\UpdateDriverRequest;
use App\Models\Fleet\Driver;
use App\Models\Fleet\DriverVehicleAssignment;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DriverController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Driver::class);
        $drivers = Driver::query()
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderBy('last_name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/Drivers/Index', [
            'drivers' => $drivers,
            'filters' => $request->only(['status']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Driver::class);
        $enum = fn ($cases) => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], $cases);
        return Inertia::render('Fleet/Drivers/Create', [
            'statuses' => $enum(\App\Enums\Fleet\DriverStatus::cases()),
            'licenseStatuses' => $enum(\App\Enums\Fleet\DriverLicenseStatus::cases()),
            'riskCategories' => $enum(\App\Enums\Fleet\DriverRiskCategory::cases()),
            'complianceStatuses' => $enum(\App\Enums\Fleet\DriverComplianceStatus::cases()),
        ]);
    }

    public function store(StoreDriverRequest $request): RedirectResponse
    {
        $this->authorize('create', Driver::class);
        Driver::create($request->validated());
        return to_route('fleet.drivers.index')->with('flash', ['status' => 'success', 'message' => 'Driver created.']);
    }

    public function show(Driver $driver): Response
    {
        $this->authorize('view', $driver);
        $driver->load([
            'user',
            'vehicleAssignments' => fn ($q) => $q->with('vehicle')->orderByDesc('assigned_date'),
            'currentAssignment' => fn ($q) => $q->with('vehicle'),
        ]);
        return Inertia::render('Fleet/Drivers/Show', [
            'driver' => $driver,
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'assignmentTypes' => array_map(fn ($c) => ['name' => $c->name, 'value' => $c->value], \App\Enums\Fleet\AssignmentType::cases()),
        ]);
    }

    public function assignVehicle(Request $request, Driver $driver): RedirectResponse
    {
        $this->authorize('update', $driver);
        $validated = $request->validate([
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'assignment_type' => ['required', 'string', 'in:primary,secondary,temporary'],
            'assigned_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:65535'],
        ]);
        $orgId = TenantContext::id();

        DriverVehicleAssignment::query()
            ->where('organization_id', $orgId)
            ->where('is_current', true)
            ->where(function ($q) use ($driver, $validated) {
                $q->where('driver_id', $driver->id)->orWhere('vehicle_id', $validated['vehicle_id']);
            })
            ->update(['is_current' => false, 'unassigned_date' => $validated['assigned_date']]);

        DriverVehicleAssignment::create([
            'organization_id' => $orgId,
            'driver_id' => $driver->id,
            'vehicle_id' => $validated['vehicle_id'],
            'assignment_type' => $validated['assignment_type'],
            'assigned_date' => $validated['assigned_date'],
            'is_current' => true,
            'notes' => $validated['notes'] ?? null,
            'assigned_by' => $request->user()->id,
        ]);

        \App\Models\Fleet\Vehicle::withoutGlobalScopes()->where('id', $validated['vehicle_id'])->update([
            'current_driver_id' => $driver->id,
        ]);

        return redirect()->route('fleet.drivers.show', $driver)->with('flash', ['status' => 'success', 'message' => 'Vehicle assigned.']);
    }

    public function unassignVehicle(Driver $driver): RedirectResponse
    {
        $this->authorize('update', $driver);
        $assignment = DriverVehicleAssignment::query()
            ->where('driver_id', $driver->id)
            ->where('is_current', true)
            ->first();
        if ($assignment) {
            $assignment->update(['is_current' => false, 'unassigned_date' => now()->toDateString()]);
            \App\Models\Fleet\Vehicle::withoutGlobalScopes()->where('id', $assignment->vehicle_id)->update(['current_driver_id' => null]);
        }
        return redirect()->route('fleet.drivers.show', $driver)->with('flash', ['status' => 'success', 'message' => 'Vehicle unassigned.']);
    }

    public function edit(Driver $driver): Response
    {
        $this->authorize('update', $driver);
        $enum = fn ($cases) => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], $cases);
        return Inertia::render('Fleet/Drivers/Edit', [
            'driver' => $driver,
            'statuses' => $enum(\App\Enums\Fleet\DriverStatus::cases()),
            'licenseStatuses' => $enum(\App\Enums\Fleet\DriverLicenseStatus::cases()),
            'riskCategories' => $enum(\App\Enums\Fleet\DriverRiskCategory::cases()),
            'complianceStatuses' => $enum(\App\Enums\Fleet\DriverComplianceStatus::cases()),
        ]);
    }

    public function update(UpdateDriverRequest $request, Driver $driver): RedirectResponse
    {
        $this->authorize('update', $driver);
        $driver->update($request->validated());
        return to_route('fleet.drivers.show', $driver)->with('flash', ['status' => 'success', 'message' => 'Driver updated.']);
    }

    public function destroy(Driver $driver): RedirectResponse
    {
        $this->authorize('delete', $driver);
        $driver->delete();
        return to_route('fleet.drivers.index')->with('flash', ['status' => 'success', 'message' => 'Driver deleted.']);
    }
}
