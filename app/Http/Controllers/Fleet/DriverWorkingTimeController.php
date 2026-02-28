<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreDriverWorkingTimeRequest;
use App\Http\Requests\Fleet\UpdateDriverWorkingTimeRequest;
use App\Models\Fleet\DriverWorkingTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DriverWorkingTimeController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', DriverWorkingTime::class);
        $records = DriverWorkingTime::query()
            ->with('driver')
            ->when($request->input('driver_id'), fn ($q, $v) => $q->where('driver_id', $v))
            ->when($request->input('from_date'), fn ($q, $v) => $q->whereDate('date', '>=', $v))
            ->when($request->input('to_date'), fn ($q, $v) => $q->whereDate('date', '<=', $v))
            ->orderByDesc('date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/DriverWorkingTime/Index', [
            'driverWorkingTime' => $records,
            'filters' => $request->only(['driver_id', 'from_date', 'to_date']),
            'drivers' => \App\Models\Fleet\Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', DriverWorkingTime::class);
        return Inertia::render('Fleet/DriverWorkingTime/Create', [
            'drivers' => \App\Models\Fleet\Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function store(StoreDriverWorkingTimeRequest $request): RedirectResponse
    {
        $this->authorize('create', DriverWorkingTime::class);
        DriverWorkingTime::create($request->validated());
        return to_route('fleet.driver-working-time.index')->with('flash', ['status' => 'success', 'message' => 'Driver working time record created.']);
    }

    public function show(DriverWorkingTime $driver_working_time): Response
    {
        $this->authorize('view', $driver_working_time);
        $driver_working_time->load('driver');

        return Inertia::render('Fleet/DriverWorkingTime/Show', ['driverWorkingTime' => $driver_working_time]);
    }

    public function edit(DriverWorkingTime $driver_working_time): Response
    {
        $this->authorize('update', $driver_working_time);
        return Inertia::render('Fleet/DriverWorkingTime/Edit', [
            'driverWorkingTime' => $driver_working_time,
            'drivers' => \App\Models\Fleet\Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function update(UpdateDriverWorkingTimeRequest $request, DriverWorkingTime $driver_working_time): RedirectResponse
    {
        $this->authorize('update', $driver_working_time);
        $driver_working_time->update($request->validated());
        return to_route('fleet.driver-working-time.show', $driver_working_time)->with('flash', ['status' => 'success', 'message' => 'Driver working time updated.']);
    }

    public function destroy(DriverWorkingTime $driver_working_time): RedirectResponse
    {
        $this->authorize('delete', $driver_working_time);
        $driver_working_time->delete();
        return to_route('fleet.driver-working-time.index')->with('flash', ['status' => 'success', 'message' => 'Driver working time deleted.']);
    }
}
