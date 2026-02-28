<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreDriverQualificationRequest;
use App\Http\Requests\Fleet\UpdateDriverQualificationRequest;
use App\Models\Fleet\Driver;
use App\Models\Fleet\DriverQualification;
use App\Enums\Fleet\DriverQualificationStatus;
use App\Enums\Fleet\DriverQualificationType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DriverQualificationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', DriverQualification::class);
        $qualifications = DriverQualification::query()
            ->with('driver')
            ->when($request->input('driver_id'), fn ($q, $v) => $q->where('driver_id', $v))
            ->when($request->input('qualification_type'), fn ($q, $v) => $q->where('qualification_type', $v))
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('expiry_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/DriverQualifications/Index', [
            'driverQualifications' => $qualifications,
            'filters' => $request->only(['driver_id', 'qualification_type', 'status']),
            'drivers' => Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name'])->map(fn ($d) => ['id' => $d->id, 'name' => $d->first_name . ' ' . $d->last_name]),
            'qualificationTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], DriverQualificationType::cases()),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], DriverQualificationStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', DriverQualification::class);
        return Inertia::render('Fleet/DriverQualifications/Create', [
            'drivers' => Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name'])->map(fn ($d) => ['id' => $d->id, 'name' => $d->first_name . ' ' . $d->last_name]),
            'qualificationTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], DriverQualificationType::cases()),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], DriverQualificationStatus::cases()),
        ]);
    }

    public function store(StoreDriverQualificationRequest $request): RedirectResponse
    {
        $this->authorize('create', DriverQualification::class);
        DriverQualification::create($request->validated());
        return to_route('fleet.driver-qualifications.index')->with('flash', ['status' => 'success', 'message' => 'Driver qualification created.']);
    }

    public function show(DriverQualification $driver_qualification): Response
    {
        $this->authorize('view', $driver_qualification);
        $driver_qualification->load('driver');

        return Inertia::render('Fleet/DriverQualifications/Show', ['driverQualification' => $driver_qualification]);
    }

    public function edit(DriverQualification $driver_qualification): Response
    {
        $this->authorize('update', $driver_qualification);
        return Inertia::render('Fleet/DriverQualifications/Edit', [
            'driverQualification' => $driver_qualification,
            'drivers' => Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name'])->map(fn ($d) => ['id' => $d->id, 'name' => $d->first_name . ' ' . $d->last_name]),
            'qualificationTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], DriverQualificationType::cases()),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], DriverQualificationStatus::cases()),
        ]);
    }

    public function update(UpdateDriverQualificationRequest $request, DriverQualification $driver_qualification): RedirectResponse
    {
        $this->authorize('update', $driver_qualification);
        $driver_qualification->update($request->validated());
        return to_route('fleet.driver-qualifications.show', $driver_qualification)->with('flash', ['status' => 'success', 'message' => 'Driver qualification updated.']);
    }

    public function destroy(DriverQualification $driver_qualification): RedirectResponse
    {
        $this->authorize('delete', $driver_qualification);
        $driver_qualification->delete();
        return to_route('fleet.driver-qualifications.index')->with('flash', ['status' => 'success', 'message' => 'Driver qualification deleted.']);
    }
}
