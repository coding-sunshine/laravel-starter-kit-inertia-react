<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateVehicleWorkorderRequest;
use App\Models\Siding;
use App\Models\VehicleWorkorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class VehicleWorkorderController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $query = VehicleWorkorder::with('siding:id,name,code')
            ->whereIn('siding_id', $sidingIds)
            ->when($request->siding_id, fn ($q) => $q->where('siding_id', $request->siding_id))
            ->when($request->vehicle_no, fn ($q) => $q->whereRaw('vehicle_no ILIKE ?', ['%'.$request->vehicle_no.'%']))
            ->when($request->wo_no, fn ($q) => $q->whereRaw('wo_no ILIKE ?', ['%'.$request->wo_no.'%']))
            ->when($request->transport_name, fn ($q) => $q->whereRaw('transport_name ILIKE ?', ['%'.$request->transport_name.'%']))
            ->orderBy('work_order_date', 'desc')
            ->orderBy('created_at', 'desc');

        $vehicleWorkorders = $query->paginate(15)->withQueryString();

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('VehicleWorkorders/Index', [
            'vehicleWorkorders' => $vehicleWorkorders,
            'sidings' => $sidings,
            'filters' => $request->only(['siding_id', 'vehicle_no', 'wo_no', 'transport_name']),
            'flash' => [
                'success' => $request->session()->get('success'),
            ],
        ]);
    }

    public function edit(VehicleWorkorder $vehicleWorkorder): Response|RedirectResponse
    {
        $user = Auth::user();
        if (! $user->canAccessSiding($vehicleWorkorder->siding_id)) {
            abort(403, 'You do not have access to this work order.');
        }

        $vehicleWorkorder->load('siding:id,name,code');

        return Inertia::render('VehicleWorkorders/Edit', [
            'vehicleWorkorder' => $vehicleWorkorder,
        ]);
    }

    public function update(UpdateVehicleWorkorderRequest $request, VehicleWorkorder $vehicleWorkorder): RedirectResponse
    {
        $vehicleWorkorder->update($request->validated());

        return redirect()
            ->route('vehicle-workorders.index')
            ->with('success', 'Vehicle work order updated successfully.');
    }
}
