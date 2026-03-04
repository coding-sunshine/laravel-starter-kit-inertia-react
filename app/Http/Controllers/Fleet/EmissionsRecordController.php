<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreEmissionsRecordRequest;
use App\Http\Requests\Fleet\UpdateEmissionsRecordRequest;
use App\Models\Fleet\EmissionsRecord;
use App\Models\Fleet\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class EmissionsRecordController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', EmissionsRecord::class);
        $records = EmissionsRecord::query()
            ->with(['vehicle', 'driver'])
            ->when($request->input('vehicle_id'), fn ($q, $v) => $q->where('vehicle_id', $v))
            ->when($request->input('scope'), fn ($q, $v) => $q->where('scope', $v))
            ->when($request->input('record_date'), fn ($q, $v) => $q->whereDate('record_date', $v))
            ->latest('record_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/EmissionsRecords/Index', [
            'emissionsRecords' => $records,
            'filters' => $request->only(['vehicle_id', 'scope', 'record_date']),
            'vehicles' => Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'scopes' => array_map(fn (\App\Enums\Fleet\EmissionsScope $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\EmissionsScope::cases()),
            'emissionsTypes' => array_map(fn (\App\Enums\Fleet\EmissionsType $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\EmissionsType::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', EmissionsRecord::class);

        return Inertia::render('Fleet/EmissionsRecords/Create', [
            'vehicles' => Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'scopes' => array_map(fn (\App\Enums\Fleet\EmissionsScope $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\EmissionsScope::cases()),
            'emissionsTypes' => array_map(fn (\App\Enums\Fleet\EmissionsType $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\EmissionsType::cases()),
        ]);
    }

    public function store(StoreEmissionsRecordRequest $request): RedirectResponse
    {
        $this->authorize('create', EmissionsRecord::class);
        EmissionsRecord::query()->create($request->validated());

        return to_route('fleet.emissions-records.index')->with('flash', ['status' => 'success', 'message' => 'Emissions record created.']);
    }

    public function show(EmissionsRecord $emissions_record): Response
    {
        $this->authorize('view', $emissions_record);
        $emissions_record->load(['vehicle', 'driver', 'trip']);

        return Inertia::render('Fleet/EmissionsRecords/Show', ['emissionsRecord' => $emissions_record]);
    }

    public function edit(EmissionsRecord $emissions_record): Response
    {
        $this->authorize('update', $emissions_record);

        return Inertia::render('Fleet/EmissionsRecords/Edit', [
            'emissionsRecord' => $emissions_record,
            'vehicles' => Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'scopes' => array_map(fn (\App\Enums\Fleet\EmissionsScope $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\EmissionsScope::cases()),
            'emissionsTypes' => array_map(fn (\App\Enums\Fleet\EmissionsType $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\EmissionsType::cases()),
        ]);
    }

    public function update(UpdateEmissionsRecordRequest $request, EmissionsRecord $emissions_record): RedirectResponse
    {
        $this->authorize('update', $emissions_record);
        $emissions_record->update($request->validated());

        return to_route('fleet.emissions-records.show', $emissions_record)->with('flash', ['status' => 'success', 'message' => 'Emissions record updated.']);
    }

    public function destroy(EmissionsRecord $emissions_record): RedirectResponse
    {
        $this->authorize('delete', $emissions_record);
        $emissions_record->delete();

        return to_route('fleet.emissions-records.index')->with('flash', ['status' => 'success', 'message' => 'Emissions record deleted.']);
    }
}
