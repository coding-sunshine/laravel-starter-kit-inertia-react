<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\Trip;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class TripController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Trip::class);
        $trips = Trip::query()
            ->with(['vehicle', 'driver', 'route', 'startLocation', 'endLocation'])
            ->when($request->input('vehicle_id'), fn ($q, $id) => $q->where('vehicle_id', $id))
            ->when($request->input('driver_id'), fn ($q, $id) => $q->where('driver_id', $id))
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->input('from_date'), fn ($q, $date) => $q->whereDate('planned_start_time', '>=', $date))
            ->when($request->input('to_date'), fn ($q, $date) => $q->whereDate('planned_start_time', '<=', $date))
            ->orderByDesc('planned_start_time')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/Trips/Index', [
            'trips' => $trips,
            'filters' => $request->only(['vehicle_id', 'driver_id', 'status', 'from_date', 'to_date']),
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'drivers' => \App\Models\Fleet\Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function show(Trip $trip): Response
    {
        $this->authorize('view', $trip);
        $trip->load([
            'vehicle',
            'driver',
            'route',
            'startLocation',
            'endLocation',
            'waypoints',
            'behaviorEvents',
        ]);

        return Inertia::render('Fleet/Trips/Show', ['trip' => $trip]);
    }
}
