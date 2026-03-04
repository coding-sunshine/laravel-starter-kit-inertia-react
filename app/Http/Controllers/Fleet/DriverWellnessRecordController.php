<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Enums\Fleet\DriverWellnessSleepQuality;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreDriverWellnessRecordRequest;
use App\Http\Requests\Fleet\UpdateDriverWellnessRecordRequest;
use App\Models\Fleet\Driver;
use App\Models\Fleet\DriverWellnessRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DriverWellnessRecordController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', DriverWellnessRecord::class);
        $records = DriverWellnessRecord::query()
            ->with('driver')
            ->when($request->input('driver_id'), fn ($q, $v) => $q->where('driver_id', $v))
            ->latest('record_date')
            ->paginate(15)
            ->withQueryString();

        $drivers = Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name'])
            ->map(fn ($d): array => ['id' => $d->id, 'name' => $d->first_name.' '.$d->last_name]);

        return Inertia::render('Fleet/DriverWellnessRecords/Index', [
            'driverWellnessRecords' => $records,
            'filters' => $request->only(['driver_id']),
            'drivers' => $drivers,
            'sleepQualities' => array_map(fn (DriverWellnessSleepQuality $c): array => ['value' => $c->value, 'name' => $c->name], DriverWellnessSleepQuality::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', DriverWellnessRecord::class);
        $drivers = Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name'])
            ->map(fn ($d): array => ['id' => $d->id, 'name' => $d->first_name.' '.$d->last_name]);

        return Inertia::render('Fleet/DriverWellnessRecords/Create', [
            'drivers' => $drivers,
            'sleepQualities' => array_map(fn (DriverWellnessSleepQuality $c): array => ['value' => $c->value, 'name' => $c->name], DriverWellnessSleepQuality::cases()),
        ]);
    }

    public function store(StoreDriverWellnessRecordRequest $request): RedirectResponse
    {
        $this->authorize('create', DriverWellnessRecord::class);
        DriverWellnessRecord::query()->create($request->validated());

        return to_route('fleet.driver-wellness-records.index')->with('flash', ['status' => 'success', 'message' => 'Wellness record created.']);
    }

    public function show(DriverWellnessRecord $driver_wellness_record): Response
    {
        $this->authorize('view', $driver_wellness_record);
        $driver_wellness_record->load('driver');

        return Inertia::render('Fleet/DriverWellnessRecords/Show', ['driverWellnessRecord' => $driver_wellness_record]);
    }

    public function edit(DriverWellnessRecord $driver_wellness_record): Response
    {
        $this->authorize('update', $driver_wellness_record);
        $drivers = Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name'])
            ->map(fn ($d): array => ['id' => $d->id, 'name' => $d->first_name.' '.$d->last_name]);

        return Inertia::render('Fleet/DriverWellnessRecords/Edit', [
            'driverWellnessRecord' => $driver_wellness_record,
            'drivers' => $drivers,
            'sleepQualities' => array_map(fn (DriverWellnessSleepQuality $c): array => ['value' => $c->value, 'name' => $c->name], DriverWellnessSleepQuality::cases()),
        ]);
    }

    public function update(UpdateDriverWellnessRecordRequest $request, DriverWellnessRecord $driver_wellness_record): RedirectResponse
    {
        $this->authorize('update', $driver_wellness_record);
        $driver_wellness_record->update($request->validated());

        return to_route('fleet.driver-wellness-records.show', $driver_wellness_record)->with('flash', ['status' => 'success', 'message' => 'Wellness record updated.']);
    }

    public function destroy(DriverWellnessRecord $driver_wellness_record): RedirectResponse
    {
        $this->authorize('delete', $driver_wellness_record);
        $driver_wellness_record->delete();

        return to_route('fleet.driver-wellness-records.index')->with('flash', ['status' => 'success', 'message' => 'Wellness record deleted.']);
    }
}
