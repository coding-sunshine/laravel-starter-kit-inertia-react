<?php

declare(strict_types=1);

namespace App\Http\Controllers\RoadDispatch;

use App\Actions\CreateVehicleArrival;
use App\Http\Controllers\Controller;
use App\Http\Requests\RoadDispatch\StoreVehicleArrivalRequest;
use App\Models\Siding;
use App\Models\VehicleArrival;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class VehicleArrivalController extends Controller
{
    public function __construct(
        private readonly CreateVehicleArrival $createVehicleArrival
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $query = VehicleArrival::query()
            ->with(['siding:id,name,code', 'vehicle:id,vehicle_number,owner_name'])
            ->whereIn('siding_id', $sidingIds)
            ->latest('arrived_at');

        if ($request->filled('siding_id')) {
            $query->where('siding_id', $request->input('siding_id'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('arrived_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('arrived_at', '<=', $request->input('date_to'));
        }

        $arrivals = $query->paginate(15)->withQueryString();
        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('road-dispatch/arrivals/index', [
            'arrivals' => $arrivals,
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
        $vehicles = \App\Models\Vehicle::query()
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

        return redirect()
            ->route('road-dispatch.arrivals.index')
            ->with('success', 'Vehicle arrival recorded.');
    }
}
