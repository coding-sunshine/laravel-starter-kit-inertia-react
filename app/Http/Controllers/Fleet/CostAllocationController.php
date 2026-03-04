<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Enums\Fleet\CostAllocationApprovalStatus;
use App\Enums\Fleet\CostAllocationSourceType;
use App\Enums\Fleet\CostAllocationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreCostAllocationRequest;
use App\Http\Requests\Fleet\UpdateCostAllocationRequest;
use App\Models\Fleet\CostAllocation;
use App\Models\Fleet\CostCenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CostAllocationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CostAllocation::class);
        $allocations = CostAllocation::query()
            ->with(['costCenter', 'allocatedBy', 'approvedBy'])
            ->when($request->input('cost_center_id'), fn ($q, $v) => $q->where('cost_center_id', $v))
            ->when($request->input('cost_type'), fn ($q, $v) => $q->where('cost_type', $v))
            ->when($request->input('source_type'), fn ($q, $v) => $q->where('source_type', $v))
            ->when($request->input('approval_status'), fn ($q, $v) => $q->where('approval_status', $v))
            ->latest('allocation_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/CostAllocations/Index', [
            'costAllocations' => $allocations,
            'filters' => $request->only(['cost_center_id', 'cost_type', 'source_type', 'approval_status']),
            'costCenters' => CostCenter::query()->orderBy('name')->get(['id', 'name']),
            'costTypes' => array_map(fn (CostAllocationType $c): array => ['value' => $c->value, 'name' => $c->name], CostAllocationType::cases()),
            'sourceTypes' => array_map(fn (CostAllocationSourceType $c): array => ['value' => $c->value, 'name' => $c->name], CostAllocationSourceType::cases()),
            'approvalStatuses' => array_map(fn (CostAllocationApprovalStatus $c): array => ['value' => $c->value, 'name' => $c->name], CostAllocationApprovalStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', CostAllocation::class);

        return Inertia::render('Fleet/CostAllocations/Create', [
            'costCenters' => CostCenter::query()->orderBy('name')->get(['id', 'name']),
            'costTypes' => array_map(fn (CostAllocationType $c): array => ['value' => $c->value, 'name' => $c->name], CostAllocationType::cases()),
            'sourceTypes' => array_map(fn (CostAllocationSourceType $c): array => ['value' => $c->value, 'name' => $c->name], CostAllocationSourceType::cases()),
            'approvalStatuses' => array_map(fn (CostAllocationApprovalStatus $c): array => ['value' => $c->value, 'name' => $c->name], CostAllocationApprovalStatus::cases()),
        ]);
    }

    public function store(StoreCostAllocationRequest $request): RedirectResponse
    {
        $this->authorize('create', CostAllocation::class);
        CostAllocation::query()->create(array_merge($request->validated(), ['allocated_by' => $request->user()->id]));

        return to_route('fleet.cost-allocations.index')->with('flash', ['status' => 'success', 'message' => 'Cost allocation created.']);
    }

    public function show(CostAllocation $cost_allocation): Response
    {
        $this->authorize('view', $cost_allocation);
        $cost_allocation->load(['costCenter', 'allocatedBy', 'approvedBy']);

        return Inertia::render('Fleet/CostAllocations/Show', ['costAllocation' => $cost_allocation]);
    }

    public function edit(CostAllocation $cost_allocation): Response
    {
        $this->authorize('update', $cost_allocation);

        return Inertia::render('Fleet/CostAllocations/Edit', [
            'costAllocation' => $cost_allocation,
            'costCenters' => CostCenter::query()->orderBy('name')->get(['id', 'name']),
            'costTypes' => array_map(fn (CostAllocationType $c): array => ['value' => $c->value, 'name' => $c->name], CostAllocationType::cases()),
            'sourceTypes' => array_map(fn (CostAllocationSourceType $c): array => ['value' => $c->value, 'name' => $c->name], CostAllocationSourceType::cases()),
            'approvalStatuses' => array_map(fn (CostAllocationApprovalStatus $c): array => ['value' => $c->value, 'name' => $c->name], CostAllocationApprovalStatus::cases()),
        ]);
    }

    public function update(UpdateCostAllocationRequest $request, CostAllocation $cost_allocation): RedirectResponse
    {
        $this->authorize('update', $cost_allocation);
        $cost_allocation->update($request->validated());

        return to_route('fleet.cost-allocations.show', $cost_allocation)->with('flash', ['status' => 'success', 'message' => 'Cost allocation updated.']);
    }

    public function destroy(CostAllocation $cost_allocation): RedirectResponse
    {
        $this->authorize('delete', $cost_allocation);
        $cost_allocation->delete();

        return to_route('fleet.cost-allocations.index')->with('flash', ['status' => 'success', 'message' => 'Cost allocation deleted.']);
    }
}
