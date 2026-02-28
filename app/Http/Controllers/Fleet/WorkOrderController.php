<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreWorkOrderRequest;
use App\Http\Requests\Fleet\UpdateWorkOrderRequest;
use App\Models\Fleet\WorkOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class WorkOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', WorkOrder::class);
        $orders = WorkOrder::query()
            ->with(['vehicle', 'assignedGarage'])
            ->when($request->input('vehicle_id'), fn ($q, $v) => $q->where('vehicle_id', $v))
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/WorkOrders/Index', [
            'workOrders' => $orders,
            'filters' => $request->only(['vehicle_id', 'status']),
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\WorkOrderStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', WorkOrder::class);

        return Inertia::render('Fleet/WorkOrders/Create', [
            'workOrderNumber' => 'WO-' . now()->format('Ymd') . '-',
            'workTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\WorkOrderType::cases()),
            'priorities' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\WorkOrderPriority::cases()),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\WorkOrderStatus::cases()),
            'urgencies' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\WorkOrderUrgency::cases()),
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'garages' => \App\Models\Fleet\Garage::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreWorkOrderRequest $request): RedirectResponse
    {
        $this->authorize('create', WorkOrder::class);
        WorkOrder::create($request->validated());
        return to_route('fleet.work-orders.index')->with('flash', ['status' => 'success', 'message' => 'Work order created.']);
    }

    public function show(WorkOrder $work_order): Response
    {
        $this->authorize('view', $work_order);
        $work_order->load(['vehicle', 'assignedGarage', 'defects', 'workOrderLines.partsInventory', 'workOrderParts.partsInventory', 'warrantyClaims']);

        return Inertia::render('Fleet/WorkOrders/Show', ['workOrder' => $work_order]);
    }

    public function edit(WorkOrder $work_order): Response
    {
        $this->authorize('update', $work_order);
        return Inertia::render('Fleet/WorkOrders/Edit', [
            'workOrder' => $work_order,
            'workTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\WorkOrderType::cases()),
            'priorities' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\WorkOrderPriority::cases()),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\WorkOrderStatus::cases()),
            'urgencies' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\WorkOrderUrgency::cases()),
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'garages' => \App\Models\Fleet\Garage::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateWorkOrderRequest $request, WorkOrder $work_order): RedirectResponse
    {
        $this->authorize('update', $work_order);
        $work_order->update($request->validated());
        return to_route('fleet.work-orders.show', $work_order)->with('flash', ['status' => 'success', 'message' => 'Work order updated.']);
    }

    public function destroy(WorkOrder $work_order): RedirectResponse
    {
        $this->authorize('delete', $work_order);
        $work_order->delete();
        return to_route('fleet.work-orders.index')->with('flash', ['status' => 'success', 'message' => 'Work order deleted.']);
    }
}
