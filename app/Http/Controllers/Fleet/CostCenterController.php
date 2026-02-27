<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\CostCenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CostCenterController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', CostCenter::class);
        $costCenters = CostCenter::query()
            ->with('parent')
            ->when($request->input('type'), fn ($q, $type) => $q->where('cost_center_type', $type))
            ->when($request->boolean('is_active') !== null, fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/CostCenters/Index', [
            'costCenters' => $costCenters,
            'filters' => $request->only(['type', 'is_active']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', CostCenter::class);
        return Inertia::render('Fleet/CostCenters/Create', [
            'costCenterTypes' => \App\Enums\Fleet\CostCenterType::cases(),
            'parents' => CostCenter::query()->orderBy('code')->get(['id', 'code', 'name']),
            'users' => \App\Models\User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', CostCenter::class);
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'parent_cost_center_id' => ['nullable', 'exists:cost_centers,id'],
            'cost_center_type' => ['required', 'string', 'in:department,project,location,vehicle_type'],
            'manager_user_id' => ['nullable', 'exists:users,id'],
            'budget_annual' => ['nullable', 'numeric', 'min:0'],
            'budget_monthly' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        CostCenter::create($validated);
        return to_route('fleet.cost-centers.index')->with('flash', ['status' => 'success', 'message' => 'Cost center created.']);
    }

    public function show(CostCenter $costCenter): Response
    {
        $this->authorize('view', $costCenter);
        $costCenter->load(['parent', 'manager', 'children']);
        return Inertia::render('Fleet/CostCenters/Show', ['costCenter' => $costCenter]);
    }

    public function edit(CostCenter $costCenter): Response
    {
        $this->authorize('update', $costCenter);
        return Inertia::render('Fleet/CostCenters/Edit', [
            'costCenter' => $costCenter,
            'costCenterTypes' => \App\Enums\Fleet\CostCenterType::cases(),
            'parents' => CostCenter::query()->where('id', '!=', $costCenter->id)->orderBy('code')->get(['id', 'code', 'name']),
            'users' => \App\Models\User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, CostCenter $costCenter): RedirectResponse
    {
        $this->authorize('update', $costCenter);
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'parent_cost_center_id' => ['nullable', 'exists:cost_centers,id'],
            'cost_center_type' => ['required', 'string', 'in:department,project,location,vehicle_type'],
            'manager_user_id' => ['nullable', 'exists:users,id'],
            'budget_annual' => ['nullable', 'numeric', 'min:0'],
            'budget_monthly' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $costCenter->update($validated);
        return to_route('fleet.cost-centers.show', $costCenter)->with('flash', ['status' => 'success', 'message' => 'Cost center updated.']);
    }

    public function destroy(CostCenter $costCenter): RedirectResponse
    {
        $this->authorize('delete', $costCenter);
        $costCenter->delete();
        return to_route('fleet.cost-centers.index')->with('flash', ['status' => 'success', 'message' => 'Cost center deleted.']);
    }
}
