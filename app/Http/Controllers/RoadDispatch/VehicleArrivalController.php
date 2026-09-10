<?php

declare(strict_types=1);

namespace App\Http\Controllers\RoadDispatch;

use App\Actions\CreateUnloadFromArrival;
use App\Actions\CreateVehicleArrival;
use App\DataTables\VehicleArrivalDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\RoadDispatch\StoreVehicleArrivalRequest;
use App\Models\Siding;
use App\Models\Vehicle;
use App\Models\VehicleArrival;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class VehicleArrivalController extends Controller
{
    public function __construct(
        private readonly CreateVehicleArrival $createVehicleArrival,
        private readonly CreateUnloadFromArrival $createUnloadFromArrival
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('road-dispatch/arrivals/index', [
            'tableData' => VehicleArrivalDataTable::makeTable($request),
            'sidings' => $sidings,
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
        $vehicles = Vehicle::query()
            ->where('is_active', true)
            ->orderBy('vehicle_number')
            ->get(['id', 'vehicle_number', 'owner_name']);

        return Inertia::render('road-dispatch/arrivals/create', [
            'sidings' => $sidings,
            'vehicles' => $vehicles,
        ]);
    }

    public function store(StoreVehicleArrivalRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $arrivedAt = $data['arrived_at'] ?? null;
        if ($arrivedAt !== null) {
            $data['arrived_at'] = $arrivedAt;
        }

        $this->createVehicleArrival->handle($data, (int) $request->user()->id);

        return to_route('road-dispatch.arrivals.index')
            ->with('success', 'Vehicle arrival recorded.');
    }

    public function show(Request $request, VehicleArrival $arrival): Response
    {
        // $this->authorize('view', $arrival);

        $arrival->load([
            'siding:id,name,code',
            'vehicle:id,vehicle_number,owner_name',
            'vehicleUnload' => function ($query) {
                $query->select('id', 'vehicle_arrival_id', 'unload_start_time', 'unload_end_time', 'state');
            },
            'creator:id,name',
            'updater:id,name',
        ]);

        // Debug logging
        \Log::info('Arrival data for show:', [
            'arrival_id' => $arrival->id,
            'vehicle_unload' => $arrival->vehicleUnload,
            'vehicle_unload_id' => $arrival->vehicleUnload?->id,
            'unload_state' => $arrival->vehicleUnload?->state,
        ]);
        
        return Inertia::render('road-dispatch/arrivals/show', [
            'arrival' => $arrival->toArray(),
        ]);
    }

    public function unload(Request $request, VehicleArrival $arrival): RedirectResponse
    {
        // $this->authorize('view', $arrival);

        $unload = $this->createUnloadFromArrival->handle(
            $arrival,
            (int) $request->user()->id
        );

        return to_route('road-dispatch.unloads.show', $unload);
    }
}
