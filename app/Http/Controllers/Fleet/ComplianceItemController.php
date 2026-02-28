<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreComplianceItemRequest;
use App\Http\Requests\Fleet\UpdateComplianceItemRequest;
use App\Models\Fleet\ComplianceItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ComplianceItemController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ComplianceItem::class);
        $items = ComplianceItem::query()
            ->when($request->input('entity_type'), fn ($q, $v) => $q->where('entity_type', $v))
            ->when($request->input('entity_id'), fn ($q, $v) => $q->where('entity_id', $v))
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->orderBy('expiry_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/ComplianceItems/Index', [
            'complianceItems' => $items,
            'filters' => $request->only(['entity_type', 'entity_id', 'status']),
            'entityTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\ComplianceEntityType::cases()),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\ComplianceItemStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', ComplianceItem::class);
        return Inertia::render('Fleet/ComplianceItems/Create', [
            'entityTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\ComplianceEntityType::cases()),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\ComplianceItemStatus::cases()),
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'drivers' => \App\Models\Fleet\Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function store(StoreComplianceItemRequest $request): RedirectResponse
    {
        $this->authorize('create', ComplianceItem::class);
        ComplianceItem::create($request->validated());
        return to_route('fleet.compliance-items.index')->with('flash', ['status' => 'success', 'message' => 'Compliance item created.']);
    }

    public function show(ComplianceItem $compliance_item): Response
    {
        $this->authorize('view', $compliance_item);
        $compliance_item->load('compliant');

        return Inertia::render('Fleet/ComplianceItems/Show', ['complianceItem' => $compliance_item]);
    }

    public function edit(ComplianceItem $compliance_item): Response
    {
        $this->authorize('update', $compliance_item);
        return Inertia::render('Fleet/ComplianceItems/Edit', [
            'complianceItem' => $compliance_item,
            'entityTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\ComplianceEntityType::cases()),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\ComplianceItemStatus::cases()),
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'drivers' => \App\Models\Fleet\Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function update(UpdateComplianceItemRequest $request, ComplianceItem $compliance_item): RedirectResponse
    {
        $this->authorize('update', $compliance_item);
        $compliance_item->update($request->validated());
        return to_route('fleet.compliance-items.show', $compliance_item)->with('flash', ['status' => 'success', 'message' => 'Compliance item updated.']);
    }

    public function destroy(ComplianceItem $compliance_item): RedirectResponse
    {
        $this->authorize('delete', $compliance_item);
        $compliance_item->delete();
        return to_route('fleet.compliance-items.index')->with('flash', ['status' => 'success', 'message' => 'Compliance item deleted.']);
    }
}
