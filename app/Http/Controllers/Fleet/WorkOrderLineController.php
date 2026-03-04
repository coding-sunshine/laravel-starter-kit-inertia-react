<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreWorkOrderLineRequest;
use App\Http\Requests\Fleet\UpdateWorkOrderLineRequest;
use App\Models\Fleet\WorkOrder;
use App\Models\Fleet\WorkOrderLine;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class WorkOrderLineController extends Controller
{
    public function index(WorkOrder $work_order): Response
    {
        $this->authorize('view', $work_order);
        $work_order->load(['workOrderLines.partsInventory']);

        return Inertia::render('Fleet/WorkOrderLines/Index', [
            'workOrder' => $work_order,
            'workOrderLines' => $work_order->workOrderLines,
        ]);
    }

    public function create(WorkOrder $work_order): Response
    {
        $this->authorize('update', $work_order);

        return Inertia::render('Fleet/WorkOrderLines/Create', [
            'workOrder' => $work_order,
            'lineTypes' => array_map(fn (\App\Enums\Fleet\WorkOrderLineType $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\WorkOrderLineType::cases()),
            'partsInventory' => \App\Models\Fleet\PartsInventory::query()->orderBy('part_number')->get(['id', 'part_number', 'description', 'unit_cost']),
        ]);
    }

    public function store(StoreWorkOrderLineRequest $request, WorkOrder $work_order): RedirectResponse
    {
        $this->authorize('update', $work_order);
        $data = array_merge($request->validated(), ['work_order_id' => $work_order->id]);
        $data['sort_order'] ??= ($work_order->workOrderLines()->max('sort_order') ?? 0) + 1;
        WorkOrderLine::query()->create($data);

        return to_route('fleet.work-orders.show', $work_order)->with('flash', ['status' => 'success', 'message' => 'Work order line created.']);
    }

    public function edit(WorkOrder $work_order, WorkOrderLine $work_order_line): Response
    {
        $this->authorize('update', $work_order_line);

        return Inertia::render('Fleet/WorkOrderLines/Edit', [
            'workOrder' => $work_order,
            'workOrderLine' => $work_order_line,
            'lineTypes' => array_map(fn (\App\Enums\Fleet\WorkOrderLineType $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\WorkOrderLineType::cases()),
            'partsInventory' => \App\Models\Fleet\PartsInventory::query()->orderBy('part_number')->get(['id', 'part_number', 'description', 'unit_cost']),
        ]);
    }

    public function update(UpdateWorkOrderLineRequest $request, WorkOrder $work_order, WorkOrderLine $work_order_line): RedirectResponse
    {
        $this->authorize('update', $work_order_line);
        $work_order_line->update($request->validated());

        return to_route('fleet.work-orders.show', $work_order)->with('flash', ['status' => 'success', 'message' => 'Work order line updated.']);
    }

    public function destroy(WorkOrder $work_order, WorkOrderLine $work_order_line): RedirectResponse
    {
        $this->authorize('delete', $work_order_line);
        $work_order_line->delete();

        return to_route('fleet.work-orders.show', $work_order)->with('flash', ['status' => 'success', 'message' => 'Work order line deleted.']);
    }
}
