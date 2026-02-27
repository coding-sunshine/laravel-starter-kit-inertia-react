<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreDriverRequest;
use App\Http\Requests\Fleet\UpdateDriverRequest;
use App\Models\Fleet\Driver;
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
        ]);
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
