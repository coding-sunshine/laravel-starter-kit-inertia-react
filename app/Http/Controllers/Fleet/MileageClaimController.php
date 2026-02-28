<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreMileageClaimRequest;
use App\Http\Requests\Fleet\UpdateMileageClaimRequest;
use App\Models\Fleet\MileageClaim;
use App\Models\Fleet\GreyFleetVehicle;
use App\Models\User;
use App\Enums\Fleet\MileageClaimStatus;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class MileageClaimController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', MileageClaim::class);
        $claims = MileageClaim::query()->with(['greyFleetVehicle', 'user'])->orderByDesc('claim_date')->paginate(15)->withQueryString();

        return Inertia::render('Fleet/MileageClaims/Index', [
            'mileageClaims' => $claims,
            'greyFleetVehicles' => GreyFleetVehicle::query()->orderBy('registration')->get(['id', 'registration', 'make', 'model'])->map(fn ($v) => ['id' => $v->id, 'label' => $v->registration . ' – ' . trim(($v->make ?? '') . ' ' . ($v->model ?? ''))]),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], MileageClaimStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', MileageClaim::class);
        return Inertia::render('Fleet/MileageClaims/Create', [
            'greyFleetVehicles' => GreyFleetVehicle::query()->orderBy('registration')->get(['id', 'registration', 'make', 'model'])->map(fn ($v) => ['id' => $v->id, 'label' => $v->registration . ' – ' . trim(($v->make ?? '') . ' ' . ($v->model ?? ''))]),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], MileageClaimStatus::cases()),
        ]);
    }

    public function store(StoreMileageClaimRequest $request): RedirectResponse
    {
        $this->authorize('create', MileageClaim::class);
        MileageClaim::create($request->validated());
        return to_route('fleet.mileage-claims.index')->with('flash', ['status' => 'success', 'message' => 'Mileage claim created.']);
    }

    public function show(MileageClaim $mileage_claim): Response
    {
        $this->authorize('view', $mileage_claim);
        $mileage_claim->load(['greyFleetVehicle', 'user', 'approvedByUser']);
        return Inertia::render('Fleet/MileageClaims/Show', ['mileageClaim' => $mileage_claim]);
    }

    public function edit(MileageClaim $mileage_claim): Response
    {
        $this->authorize('update', $mileage_claim);
        return Inertia::render('Fleet/MileageClaims/Edit', [
            'mileageClaim' => $mileage_claim,
            'greyFleetVehicles' => GreyFleetVehicle::query()->orderBy('registration')->get(['id', 'registration', 'make', 'model'])->map(fn ($v) => ['id' => $v->id, 'label' => $v->registration . ' – ' . trim(($v->make ?? '') . ' ' . ($v->model ?? ''))]),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], MileageClaimStatus::cases()),
        ]);
    }

    public function update(UpdateMileageClaimRequest $request, MileageClaim $mileage_claim): RedirectResponse
    {
        $this->authorize('update', $mileage_claim);
        $mileage_claim->update($request->validated());
        return to_route('fleet.mileage-claims.show', $mileage_claim)->with('flash', ['status' => 'success', 'message' => 'Mileage claim updated.']);
    }

    public function destroy(MileageClaim $mileage_claim): RedirectResponse
    {
        $this->authorize('delete', $mileage_claim);
        $mileage_claim->delete();
        return to_route('fleet.mileage-claims.index')->with('flash', ['status' => 'success', 'message' => 'Mileage claim deleted.']);
    }
}
