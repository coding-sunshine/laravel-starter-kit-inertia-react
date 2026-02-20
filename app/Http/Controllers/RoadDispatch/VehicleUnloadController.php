<?php

declare(strict_types=1);

namespace App\Http\Controllers\RoadDispatch;

use App\Actions\ConfirmVehicleUnload;
use App\Http\Controllers\Controller;
use App\Http\Requests\RoadDispatch\StoreVehicleUnloadRequest;
use App\Models\Siding;
use App\Models\Vehicle;
use App\Models\VehicleUnload;
use App\Services\SidingContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class VehicleUnloadController extends Controller
{
    public function __construct(
        private readonly ConfirmVehicleUnload $confirmVehicleUnload
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $sidingIds = SidingContext::activeSidingIds($user);

        $query = VehicleUnload::query()
            ->with(['siding:id,name,code', 'vehicle:id,vehicle_number,owner_name'])
            ->whereIn('siding_id', $sidingIds)
            ->latest('arrival_time');

        if ($request->filled('siding_id')) {
            $query->where('siding_id', $request->input('siding_id'));
        }
        if ($request->filled('state')) {
            $query->where('state', $request->input('state'));
        }

        $unloads = $query->paginate(15)->withQueryString();
        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('road-dispatch/unloads/index', [
            'unloads' => $unloads,
            'sidings' => $sidings,
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $sidingIds = SidingContext::activeSidingIds($user);

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
        $vehicles = Vehicle::query()
            ->where('is_active', true)
            ->orderBy('vehicle_number')
            ->get(['id', 'vehicle_number', 'owner_name']);

        return Inertia::render('road-dispatch/unloads/create', [
            'sidings' => $sidings,
            'vehicles' => $vehicles,
            'currentSidingId' => SidingContext::id(),
        ]);
    }

    public function store(StoreVehicleUnloadRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $variance = null;
        if (
            isset($data['mine_weight_mt'], $data['weighment_weight_mt'])
            && (float) $data['mine_weight_mt'] > 0
        ) {
            $variance = (float) $data['weighment_weight_mt'] - (float) $data['mine_weight_mt'];
        }
        VehicleUnload::create([
            'siding_id' => $data['siding_id'],
            'vehicle_id' => $data['vehicle_id'],
            'jimms_challan_number' => $data['jimms_challan_number'] ?? null,
            'arrival_time' => $data['arrival_time'],
            'shift' => $data['shift'] ?? null,
            'mine_weight_mt' => $data['mine_weight_mt'] ?? null,
            'weighment_weight_mt' => $data['weighment_weight_mt'] ?? null,
            'variance_mt' => $variance,
            'state' => 'pending',
            'remarks' => $data['remarks'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('road-dispatch.unloads.index')
            ->with('success', 'Vehicle unload recorded.');
    }

    public function confirm(Request $request, VehicleUnload $unload): RedirectResponse
    {
        $this->authorize('update', $unload);

        $this->confirmVehicleUnload->handle($unload, (int) $request->user()->id);

        return redirect()
            ->back()
            ->with('success', 'Receipt confirmed and stock updated.');
    }
}
