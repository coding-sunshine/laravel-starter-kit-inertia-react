<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreWorkOrderPartRequest;
use App\Http\Requests\Fleet\UpdateWorkOrderPartRequest;
use App\Models\Fleet\WorkOrder;
use App\Models\Fleet\WorkOrderPart;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class WorkOrderPartController extends Controller
{
    public function index(WorkOrder $work_order): Response
    {
        $this->authorize('view', $work_order);
        $work_order->load(['workOrderParts.partsInventory']);

        return Inertia::render('Fleet/WorkOrderParts/Index', [
            'workOrder' => $work_order,
            'workOrderParts' => $work_order->workOrderParts,
        ]);
    }

    public function create(WorkOrder $work_order): Response
    {
        $this->authorize('update', $work_order);

        return Inertia::render('Fleet/WorkOrderParts/Create', [
            'workOrder' => $work_order,
            'partsInventory' => \App\Models\Fleet\PartsInventory::query()->orderBy('part_number')->get(['id', 'part_number', 'description', 'unit_cost']),
        ]);
    }

    public function store(StoreWorkOrderPartRequest $request, WorkOrder $work_order): RedirectResponse
    {
        $this->authorize('update', $work_order);
        WorkOrderPart::query()->create(array_merge($request->validated(), ['work_order_id' => $work_order->id]));

        return to_route('fleet.work-orders.show', $work_order)->with('flash', ['status' => 'success', 'message' => 'Work order part created.']);
    }

    public function edit(WorkOrder $work_order, WorkOrderPart $work_order_part): Response
    {
        $this->authorize('update', $work_order_part);

        return Inertia::render('Fleet/WorkOrderParts/Edit', [
            'workOrder' => $work_order,
            'workOrderPart' => $work_order_part->load('partsInventory'),
            'partsInventory' => \App\Models\Fleet\PartsInventory::query()->orderBy('part_number')->get(['id', 'part_number', 'description', 'unit_cost']),
        ]);
    }

    public function update(UpdateWorkOrderPartRequest $request, WorkOrder $work_order, WorkOrderPart $work_order_part): RedirectResponse
    {
        $this->authorize('update', $work_order_part);
        $work_order_part->update($request->validated());

        return to_route('fleet.work-orders.show', $work_order)->with('flash', ['status' => 'success', 'message' => 'Work order part updated.']);
    }

    public function destroy(WorkOrder $work_order, WorkOrderPart $work_order_part): RedirectResponse
    {
        $this->authorize('delete', $work_order_part);
        $work_order_part->delete();

        return to_route('fleet.work-orders.show', $work_order)->with('flash', ['status' => 'success', 'message' => 'Work order part deleted.']);
    }
}
