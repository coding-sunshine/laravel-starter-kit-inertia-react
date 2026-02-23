<?php

declare(strict_types=1);

namespace App\Http\Controllers\RoadDispatch;

use App\Actions\ConfirmVehicleUnload;
use App\Http\Controllers\Controller;
use App\Http\Requests\RoadDispatch\RecordGrossWeighmentRequest;
use App\Http\Requests\RoadDispatch\RecordTareWeighmentRequest;
use App\Http\Requests\RoadDispatch\StoreVehicleUnloadRequest;
use App\Models\Siding;
use App\Models\Vehicle;
use App\Models\VehicleArrival;
use App\Models\VehicleUnload;
use App\Services\RoadDispatch\StepTransitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class VehicleUnloadController extends Controller
{
    public function __construct(
        private readonly ConfirmVehicleUnload $confirmVehicleUnload,
        private readonly StepTransitionService $stepTransition
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

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

    public function show(Request $request, VehicleUnload $unload): Response
    {
        $this->authorize('view', $unload);

        $unload->load([
            'steps' => fn ($q) => $q->orderBy('step_number'),
            'weighments',
            'siding:id,name,code',
            'vehicle:id,vehicle_number,owner_name',
            'vehicleArrival:id,gross_weight,tare_weight,net_weight',
        ]);

        $lastGross = $unload->weighments
            ->where('weighment_type', 'GROSS')
            ->sortByDesc('weighment_time')
            ->first();

        return Inertia::render('road-dispatch/unloads/show', [
            'unload' => $unload,
            'lastGrossWeight' => $lastGross ? (float) $lastGross->gross_weight_mt : null,
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        // Get arrivals that don't have unloads yet
        $arrivals = VehicleArrival::query()
            ->with(['siding:id,name,code', 'vehicle:id,vehicle_number,owner_name'])
            ->whereIn('siding_id', $sidingIds)
            ->whereDoesntHave('vehicleUnload')
            ->latest('arrived_at')
            ->get(['id', 'siding_id', 'vehicle_id', 'arrived_at', 'gross_weight', 'tare_weight', 'net_weight', 'shift']);

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('road-dispatch/unloads/create', [
            'arrivals' => $arrivals,
            'sidings' => $sidings,
        ]);
    }

    public function store(StoreVehicleUnloadRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $arrival = VehicleArrival::findOrFail($data['arrival_id']);
        
        $variance = null;
        if (
            isset($data['mine_weight_mt'], $data['weighment_weight_mt'])
            && (float) $data['mine_weight_mt'] > 0
        ) {
            $variance = (float) $data['weighment_weight_mt'] - (float) $data['mine_weight_mt'];
        }
        
        VehicleUnload::query()->create([
            'vehicle_arrival_id' => $arrival->id,
            'siding_id' => $data['siding_id'],
            'vehicle_id' => $arrival->vehicle_id,
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

        return to_route('road-dispatch.unloads.index')
            ->with('success', 'Vehicle unload recorded.');
    }

    public function confirm(Request $request, VehicleUnload $unload): RedirectResponse
    {
        $this->authorize('update', $unload);

        $this->confirmVehicleUnload->handle($unload, (int) $request->user()->id);

        return back()
            ->with('success', 'Receipt confirmed and stock updated.');
    }

    public function recordGrossWeighment(RecordGrossWeighmentRequest $request, VehicleUnload $unload): RedirectResponse
    {
        $data = $request->validated();
        $status = $request->input('weighment_status', 'PASS'); // Default to PASS if not set
        
        $this->stepTransition->recordGrossWeighment(
            $unload,
            (float) $data['gross_weight_mt'],
            $status,
            (int) $request->user()->id
        );

        return back()->with('success', 'Gross weighment recorded.');
    }

    public function startUnload(Request $request, VehicleUnload $unload): RedirectResponse
    {
        $this->authorize('update', $unload);
        $this->stepTransition->startUnload($unload, (int) $request->user()->id);

        return back()->with('success', 'Unloading started.');
    }

    public function recordTareWeighment(RecordTareWeighmentRequest $request, VehicleUnload $unload): RedirectResponse
    {
        $data = $request->validated();
        $status = $request->input('weighment_status', 'PASS'); // Default to PASS if not set
        
        $this->stepTransition->recordTareWeighment(
            $unload,
            (float) $data['gross_weight_mt'],
            (float) $data['tare_weight_mt'],
            $status,
            (int) $request->user()->id
        );

        return back()->with('success', 'Tare weighment recorded.');
    }

    public function complete(Request $request, VehicleUnload $unload): RedirectResponse
    {
        $this->authorize('update', $unload);
        $this->stepTransition->completeUnload($unload, (int) $request->user()->id);

        return back()->with('success', 'Unload completed and stock updated.');
    }
}
