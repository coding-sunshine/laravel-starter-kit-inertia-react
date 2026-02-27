<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
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
        return Inertia::render('Fleet/Drivers/Create', [
            'statuses' => \App\Enums\Fleet\DriverStatus::cases(),
            'licenseStatuses' => \App\Enums\Fleet\DriverLicenseStatus::cases(),
            'riskCategories' => \App\Enums\Fleet\DriverRiskCategory::cases(),
            'complianceStatuses' => \App\Enums\Fleet\DriverComplianceStatus::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Driver::class);
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'employee_id' => ['nullable', 'string', 'max:255'],
            'license_number' => ['required', 'string', 'max:50'],
            'license_expiry_date' => ['required', 'date'],
            'license_status' => ['required', 'string', 'in:valid,expired,suspended,revoked'],
            'status' => ['required', 'string', 'in:active,suspended,terminated,on_leave'],
            'compliance_status' => ['nullable', 'string', 'in:compliant,expiring_soon,expired'],
            'risk_category' => ['nullable', 'string', 'in:low,medium,high,critical'],
        ]);
        Driver::create($validated);
        return to_route('fleet.drivers.index')->with('flash', ['status' => 'success', 'message' => 'Driver created.']);
    }

    public function show(Driver $driver): Response
    {
        $this->authorize('view', $driver);
        $driver->load('user');
        return Inertia::render('Fleet/Drivers/Show', ['driver' => $driver]);
    }

    public function edit(Driver $driver): Response
    {
        $this->authorize('update', $driver);
        return Inertia::render('Fleet/Drivers/Edit', [
            'driver' => $driver,
            'statuses' => \App\Enums\Fleet\DriverStatus::cases(),
            'licenseStatuses' => \App\Enums\Fleet\DriverLicenseStatus::cases(),
            'riskCategories' => \App\Enums\Fleet\DriverRiskCategory::cases(),
            'complianceStatuses' => \App\Enums\Fleet\DriverComplianceStatus::cases(),
        ]);
    }

    public function update(Request $request, Driver $driver): RedirectResponse
    {
        $this->authorize('update', $driver);
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'employee_id' => ['nullable', 'string', 'max:255'],
            'license_number' => ['required', 'string', 'max:50'],
            'license_expiry_date' => ['required', 'date'],
            'license_status' => ['required', 'string', 'in:valid,expired,suspended,revoked'],
            'status' => ['required', 'string', 'in:active,suspended,terminated,on_leave'],
            'compliance_status' => ['nullable', 'string', 'in:compliant,expiring_soon,expired'],
            'risk_category' => ['nullable', 'string', 'in:low,medium,high,critical'],
        ]);
        $driver->update($validated);
        return to_route('fleet.drivers.show', $driver)->with('flash', ['status' => 'success', 'message' => 'Driver updated.']);
    }

    public function destroy(Driver $driver): RedirectResponse
    {
        $this->authorize('delete', $driver);
        $driver->delete();
        return to_route('fleet.drivers.index')->with('flash', ['status' => 'success', 'message' => 'Driver deleted.']);
    }
}
