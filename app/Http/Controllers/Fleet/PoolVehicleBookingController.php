<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StorePoolVehicleBookingRequest;
use App\Http\Requests\Fleet\UpdatePoolVehicleBookingRequest;
use App\Models\Fleet\PoolVehicleBooking;
use App\Models\Fleet\Vehicle;
use App\Models\User;
use App\Enums\Fleet\PoolVehicleBookingStatus;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class PoolVehicleBookingController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', PoolVehicleBooking::class);
        $bookings = PoolVehicleBooking::query()->with(['vehicle', 'user'])->orderByDesc('booking_start')->paginate(15)->withQueryString();

        return Inertia::render('Fleet/PoolVehicleBookings/Index', [
            'poolVehicleBookings' => $bookings,
            'vehicles' => Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], PoolVehicleBookingStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', PoolVehicleBooking::class);
        return Inertia::render('Fleet/PoolVehicleBookings/Create', [
            'vehicles' => Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], PoolVehicleBookingStatus::cases()),
        ]);
    }

    public function store(StorePoolVehicleBookingRequest $request): RedirectResponse
    {
        $this->authorize('create', PoolVehicleBooking::class);
        PoolVehicleBooking::create($request->validated());
        return to_route('fleet.pool-vehicle-bookings.index')->with('flash', ['status' => 'success', 'message' => 'Pool vehicle booking created.']);
    }

    public function show(PoolVehicleBooking $pool_vehicle_booking): Response
    {
        $this->authorize('view', $pool_vehicle_booking);
        $pool_vehicle_booking->load(['vehicle', 'user']);
        return Inertia::render('Fleet/PoolVehicleBookings/Show', ['poolVehicleBooking' => $pool_vehicle_booking]);
    }

    public function edit(PoolVehicleBooking $pool_vehicle_booking): Response
    {
        $this->authorize('update', $pool_vehicle_booking);
        return Inertia::render('Fleet/PoolVehicleBookings/Edit', [
            'poolVehicleBooking' => $pool_vehicle_booking,
            'vehicles' => Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], PoolVehicleBookingStatus::cases()),
        ]);
    }

    public function update(UpdatePoolVehicleBookingRequest $request, PoolVehicleBooking $pool_vehicle_booking): RedirectResponse
    {
        $this->authorize('update', $pool_vehicle_booking);
        $pool_vehicle_booking->update($request->validated());
        return to_route('fleet.pool-vehicle-bookings.show', $pool_vehicle_booking)->with('flash', ['status' => 'success', 'message' => 'Pool vehicle booking updated.']);
    }

    public function destroy(PoolVehicleBooking $pool_vehicle_booking): RedirectResponse
    {
        $this->authorize('delete', $pool_vehicle_booking);
        $pool_vehicle_booking->delete();
        return to_route('fleet.pool-vehicle-bookings.index')->with('flash', ['status' => 'success', 'message' => 'Pool vehicle booking deleted.']);
    }
}
