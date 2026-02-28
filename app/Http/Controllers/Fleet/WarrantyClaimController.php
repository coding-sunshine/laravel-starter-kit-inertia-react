<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreWarrantyClaimRequest;
use App\Http\Requests\Fleet\UpdateWarrantyClaimRequest;
use App\Models\Fleet\WarrantyClaim;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class WarrantyClaimController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', WarrantyClaim::class);
        $claims = WarrantyClaim::query()
            ->with(['workOrder'])
            ->when($request->input('work_order_id'), fn ($q, $v) => $q->where('work_order_id', $v))
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('submitted_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/WarrantyClaims/Index', [
            'warrantyClaims' => $claims,
            'filters' => $request->only(['work_order_id', 'status']),
            'workOrders' => \App\Models\Fleet\WorkOrder::query()->orderByDesc('created_at')->limit(200)->get(['id', 'work_order_number', 'title']),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\WarrantyClaimStatus::cases()),
        ]);
    }

    public function create(\Illuminate\Http\Request $request): Response
    {
        $this->authorize('create', WarrantyClaim::class);

        return Inertia::render('Fleet/WarrantyClaims/Create', [
            'workOrders' => \App\Models\Fleet\WorkOrder::query()->orderByDesc('created_at')->limit(200)->get(['id', 'work_order_number', 'title']),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\WarrantyClaimStatus::cases()),
            'presetWorkOrderId' => $request->integer('work_order_id', 0) ?: null,
        ]);
    }

    public function store(StoreWarrantyClaimRequest $request): RedirectResponse
    {
        $this->authorize('create', WarrantyClaim::class);
        WarrantyClaim::create($request->validated());

        return to_route('fleet.warranty-claims.index')->with('flash', ['status' => 'success', 'message' => 'Warranty claim created.']);
    }

    public function show(WarrantyClaim $warranty_claim): Response
    {
        $this->authorize('view', $warranty_claim);
        $warranty_claim->load(['workOrder']);

        return Inertia::render('Fleet/WarrantyClaims/Show', ['warrantyClaim' => $warranty_claim]);
    }

    public function edit(WarrantyClaim $warranty_claim): Response
    {
        $this->authorize('update', $warranty_claim);

        return Inertia::render('Fleet/WarrantyClaims/Edit', [
            'warrantyClaim' => $warranty_claim,
            'workOrders' => \App\Models\Fleet\WorkOrder::query()->orderByDesc('created_at')->limit(200)->get(['id', 'work_order_number', 'title']),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\WarrantyClaimStatus::cases()),
        ]);
    }

    public function update(UpdateWarrantyClaimRequest $request, WarrantyClaim $warranty_claim): RedirectResponse
    {
        $this->authorize('update', $warranty_claim);
        $warranty_claim->update($request->validated());

        return to_route('fleet.warranty-claims.show', $warranty_claim)->with('flash', ['status' => 'success', 'message' => 'Warranty claim updated.']);
    }

    public function destroy(WarrantyClaim $warranty_claim): RedirectResponse
    {
        $this->authorize('delete', $warranty_claim);
        $warranty_claim->delete();

        return to_route('fleet.warranty-claims.index')->with('flash', ['status' => 'success', 'message' => 'Warranty claim deleted.']);
    }
}
