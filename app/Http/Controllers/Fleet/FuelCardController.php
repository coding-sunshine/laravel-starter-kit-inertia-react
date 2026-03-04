<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreFuelCardRequest;
use App\Http\Requests\Fleet\UpdateFuelCardRequest;
use App\Models\Fleet\FuelCard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class FuelCardController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', FuelCard::class);
        $cards = FuelCard::query()
            ->with(['assignedVehicle', 'assignedDriver'])
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->orderBy('card_number')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/FuelCards/Index', [
            'fuelCards' => $cards,
            'filters' => $request->only(['status']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', FuelCard::class);

        return Inertia::render('Fleet/FuelCards/Create', [
            'cardTypes' => array_map(fn (\App\Enums\Fleet\FuelCardType $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\FuelCardType::cases()),
            'statuses' => array_map(fn (\App\Enums\Fleet\FuelCardStatus $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\FuelCardStatus::cases()),
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'drivers' => \App\Models\Fleet\Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function store(StoreFuelCardRequest $request): RedirectResponse
    {
        $this->authorize('create', FuelCard::class);
        FuelCard::query()->create($request->validated());

        return to_route('fleet.fuel-cards.index')->with('flash', ['status' => 'success', 'message' => 'Fuel card created.']);
    }

    public function show(FuelCard $fuel_card): Response
    {
        $this->authorize('view', $fuel_card);
        $fuel_card->load(['assignedVehicle', 'assignedDriver', 'fuelTransactions' => fn ($q) => $q->latest('transaction_timestamp')->limit(10)]);

        return Inertia::render('Fleet/FuelCards/Show', ['fuelCard' => $fuel_card]);
    }

    public function edit(FuelCard $fuel_card): Response
    {
        $this->authorize('update', $fuel_card);

        return Inertia::render('Fleet/FuelCards/Edit', [
            'fuelCard' => $fuel_card,
            'cardTypes' => array_map(fn (\App\Enums\Fleet\FuelCardType $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\FuelCardType::cases()),
            'statuses' => array_map(fn (\App\Enums\Fleet\FuelCardStatus $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\FuelCardStatus::cases()),
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'drivers' => \App\Models\Fleet\Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function update(UpdateFuelCardRequest $request, FuelCard $fuel_card): RedirectResponse
    {
        $this->authorize('update', $fuel_card);
        $fuel_card->update($request->validated());

        return to_route('fleet.fuel-cards.show', $fuel_card)->with('flash', ['status' => 'success', 'message' => 'Fuel card updated.']);
    }

    public function destroy(FuelCard $fuel_card): RedirectResponse
    {
        $this->authorize('delete', $fuel_card);
        $fuel_card->delete();

        return to_route('fleet.fuel-cards.index')->with('flash', ['status' => 'success', 'message' => 'Fuel card deleted.']);
    }
}
