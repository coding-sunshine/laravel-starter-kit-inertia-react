<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreServiceScheduleRequest;
use App\Http\Requests\Fleet\UpdateServiceScheduleRequest;
use App\Models\Fleet\ServiceSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ServiceScheduleController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ServiceSchedule::class);
        $schedules = ServiceSchedule::query()
            ->with(['vehicle', 'preferredGarage'])
            ->when($request->input('vehicle_id'), fn ($q, $v) => $q->where('vehicle_id', $v))
            ->when($request->input('service_type'), fn ($q, $v) => $q->where('service_type', $v))
            ->oldest('next_service_due_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/ServiceSchedules/Index', [
            'serviceSchedules' => $schedules,
            'filters' => $request->only(['vehicle_id', 'service_type']),
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'serviceTypes' => array_map(fn (\App\Enums\Fleet\ServiceScheduleType $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\ServiceScheduleType::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', ServiceSchedule::class);

        return Inertia::render('Fleet/ServiceSchedules/Create', [
            'serviceTypes' => array_map(fn (\App\Enums\Fleet\ServiceScheduleType $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\ServiceScheduleType::cases()),
            'intervalTypes' => array_map(fn (\App\Enums\Fleet\ServiceScheduleIntervalType $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\ServiceScheduleIntervalType::cases()),
            'intervalUnits' => array_map(fn (\App\Enums\Fleet\ServiceScheduleIntervalUnit $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\ServiceScheduleIntervalUnit::cases()),
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'garages' => \App\Models\Fleet\Garage::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreServiceScheduleRequest $request): RedirectResponse
    {
        $this->authorize('create', ServiceSchedule::class);
        ServiceSchedule::query()->create($request->validated());

        return to_route('fleet.service-schedules.index')->with('flash', ['status' => 'success', 'message' => 'Service schedule created.']);
    }

    public function show(ServiceSchedule $service_schedule): Response
    {
        $this->authorize('view', $service_schedule);
        $service_schedule->load(['vehicle', 'preferredGarage']);

        return Inertia::render('Fleet/ServiceSchedules/Show', ['serviceSchedule' => $service_schedule]);
    }

    public function edit(ServiceSchedule $service_schedule): Response
    {
        $this->authorize('update', $service_schedule);

        return Inertia::render('Fleet/ServiceSchedules/Edit', [
            'serviceSchedule' => $service_schedule,
            'serviceTypes' => array_map(fn (\App\Enums\Fleet\ServiceScheduleType $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\ServiceScheduleType::cases()),
            'intervalTypes' => array_map(fn (\App\Enums\Fleet\ServiceScheduleIntervalType $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\ServiceScheduleIntervalType::cases()),
            'intervalUnits' => array_map(fn (\App\Enums\Fleet\ServiceScheduleIntervalUnit $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\ServiceScheduleIntervalUnit::cases()),
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'garages' => \App\Models\Fleet\Garage::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateServiceScheduleRequest $request, ServiceSchedule $service_schedule): RedirectResponse
    {
        $this->authorize('update', $service_schedule);
        $service_schedule->update($request->validated());

        return to_route('fleet.service-schedules.show', $service_schedule)->with('flash', ['status' => 'success', 'message' => 'Service schedule updated.']);
    }

    public function destroy(ServiceSchedule $service_schedule): RedirectResponse
    {
        $this->authorize('delete', $service_schedule);
        $service_schedule->delete();

        return to_route('fleet.service-schedules.index')->with('flash', ['status' => 'success', 'message' => 'Service schedule deleted.']);
    }
}
