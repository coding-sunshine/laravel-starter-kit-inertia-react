<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreWorkOrderRequest;
use App\Http\Requests\Fleet\UpdateWorkOrderRequest;
use App\Models\Fleet\WorkOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class WorkOrderController extends Controller
{
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', WorkOrder::class);
        $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer', 'min:1']]);
        $ids = array_values(array_unique($request->input('ids', [])));
        $deleted = 0;
        foreach ($ids as $id) {
            $workOrder = WorkOrder::query()->find($id);
            if ($workOrder !== null && $request->user()->can('delete', $workOrder)) {
                $workOrder->delete();
                $deleted++;
            }
        }

        return to_route('fleet.work-orders.index')
            ->with('flash', ['status' => 'success', 'message' => "{$deleted} work order(s) deleted."]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', WorkOrder::class);
        $orders = WorkOrder::query()
            ->with(['vehicle', 'assignedGarage'])
            ->when($request->input('vehicle_id'), fn ($q, $v) => $q->where('vehicle_id', $v))
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))->latest()
            ->get();

        $filename = 'work-orders-'.now()->format('Y-m-d').'.csv';

        return ResponseFacade::streamDownload(
            function () use ($orders): void {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Number', 'Title', 'Status', 'Priority', 'Scheduled', 'Vehicle', 'Garage'], escape: '\\');
                foreach ($orders as $o) {
                    fputcsv($out, [
                        $o->work_order_number,
                        $o->title,
                        $o->status,
                        $o->priority,
                        $o->scheduled_date?->format('Y-m-d') ?? '',
                        $o->vehicle?->registration ?? '',
                        $o->assignedGarage?->name ?? '',
                    ],
                        escape: '\\');
                }
                fclose($out);
            },
            $filename,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ],
        );
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', WorkOrder::class);
        $orders = WorkOrder::query()
            ->with(['vehicle', 'assignedGarage'])
            ->when($request->input('vehicle_id'), fn ($q, $v) => $q->where('vehicle_id', $v))
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))->latest()
            ->paginate(15)
            ->withQueryString();

        $summary = Inertia::defer(function () {
            $open = WorkOrder::query()->whereIn('status', ['draft', 'pending', 'approved', 'in_progress'])->count();
            $overdue = WorkOrder::query()
                ->whereIn('status', ['draft', 'pending', 'approved', 'in_progress'])
                ->whereNotNull('due_date')
                ->where('due_date', '<', now())
                ->count();
            $completedThisWeek = WorkOrder::query()
                ->where('status', 'completed')
                ->where('completed_date', '>=', now()->startOfWeek())
                ->count();
            $avgResolution = WorkOrder::query()
                ->where('status', 'completed')
                ->whereNotNull('completed_date')
                ->whereNotNull('scheduled_date')
                ->selectRaw('AVG(DATEDIFF(completed_date, scheduled_date)) as avg_days')
                ->value('avg_days');

            return [
                'open' => $open,
                'overdue' => $overdue,
                'completed_this_week' => $completedThisWeek,
                'avg_resolution_days' => is_numeric($avgResolution) ? round((float) $avgResolution, 1) : null,
            ];
        }, 'summary');

        return Inertia::render('Fleet/WorkOrders/Index', [
            'workOrders' => $orders,
            'filters' => $request->only(['vehicle_id', 'status']),
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'statuses' => array_map(fn (\App\Enums\Fleet\WorkOrderStatus $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\WorkOrderStatus::cases()),
            'summary' => $summary,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', WorkOrder::class);

        return Inertia::render('Fleet/WorkOrders/Create', [
            'workOrderNumber' => 'WO-'.now()->format('Ymd').'-',
            'workTypes' => array_map(fn (\App\Enums\Fleet\WorkOrderType $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\WorkOrderType::cases()),
            'priorities' => array_map(fn (\App\Enums\Fleet\WorkOrderPriority $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\WorkOrderPriority::cases()),
            'statuses' => array_map(fn (\App\Enums\Fleet\WorkOrderStatus $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\WorkOrderStatus::cases()),
            'urgencies' => array_map(fn (\App\Enums\Fleet\WorkOrderUrgency $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\WorkOrderUrgency::cases()),
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'garages' => \App\Models\Fleet\Garage::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreWorkOrderRequest $request): RedirectResponse
    {
        $this->authorize('create', WorkOrder::class);
        WorkOrder::query()->create($request->validated());

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
            'workTypes' => array_map(fn (\App\Enums\Fleet\WorkOrderType $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\WorkOrderType::cases()),
            'priorities' => array_map(fn (\App\Enums\Fleet\WorkOrderPriority $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\WorkOrderPriority::cases()),
            'statuses' => array_map(fn (\App\Enums\Fleet\WorkOrderStatus $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\WorkOrderStatus::cases()),
            'urgencies' => array_map(fn (\App\Enums\Fleet\WorkOrderUrgency $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\WorkOrderUrgency::cases()),
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
