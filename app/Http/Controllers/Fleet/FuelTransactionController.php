<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreFuelTransactionRequest;
use App\Http\Requests\Fleet\UpdateFuelTransactionRequest;
use App\Models\Fleet\FuelTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class FuelTransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', FuelTransaction::class);
        $transactions = FuelTransaction::query()
            ->with(['vehicle', 'driver', 'fuelCard', 'fuelStation'])
            ->when($request->input('vehicle_id'), fn ($q, $v) => $q->where('vehicle_id', $v))
            ->when($request->input('fuel_card_id'), fn ($q, $v) => $q->where('fuel_card_id', $v))
            ->orderByDesc('transaction_timestamp')
            ->paginate(15)
            ->withQueryString();

        $summary = Inertia::defer(function () {
            $thirtyDaysAgo = now()->subDays(30);
            $totalSpend = FuelTransaction::query()
                ->where('transaction_timestamp', '>=', $thirtyDaysAgo)
                ->sum('total_cost');
            $vehicleCount = FuelTransaction::query()
                ->where('transaction_timestamp', '>=', $thirtyDaysAgo)
                ->distinct('vehicle_id')
                ->count('vehicle_id');
            $avgPerVehicle = $vehicleCount > 0 ? round((float) $totalSpend / $vehicleCount, 2) : 0;
            $flagged = \App\Models\Fleet\AiAnalysisResult::query()
                ->where('entity_type', 'transaction')
                ->whereIn('analysis_type', ['fraud_detection', 'fuel_efficiency'])
                ->whereIn('status', ['pending', 'reviewed'])
                ->count();

            return [
                'total_spend_30d' => round((float) $totalSpend, 2),
                'avg_per_vehicle' => $avgPerVehicle,
                'flagged' => $flagged,
            ];
        }, 'summary');

        $dailySpend = Inertia::defer(function () {
            $thirtyDaysAgo = now()->subDays(29)->startOfDay();

            $rows = FuelTransaction::query()
                ->where('transaction_timestamp', '>=', $thirtyDaysAgo)
                ->selectRaw('DATE(transaction_timestamp) as date, SUM(total_cost) as total')
                ->groupByRaw('DATE(transaction_timestamp)')
                ->orderBy('date')
                ->pluck('total', 'date');

            $result = [];
            for ($i = 0; $i < 30; $i++) {
                $date = now()->subDays(29 - $i)->format('Y-m-d');
                $result[] = [
                    'date' => $date,
                    'spend' => round((float) ($rows[$date] ?? 0), 2),
                ];
            }

            return $result;
        }, 'dailySpend');

        return Inertia::render('Fleet/FuelTransactions/Index', [
            'fuelTransactions' => $transactions,
            'filters' => $request->only(['vehicle_id', 'fuel_card_id']),
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'fuelCards' => \App\Models\Fleet\FuelCard::query()->orderBy('card_number')->get(['id', 'card_number']),
            'summary' => $summary,
            'dailySpend' => $dailySpend,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', FuelTransaction::class);

        return Inertia::render('Fleet/FuelTransactions/Create', [
            'fuelTypes' => array_map(fn (\App\Enums\Fleet\FuelType $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\FuelType::cases()),
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'drivers' => \App\Models\Fleet\Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'fuelCards' => \App\Models\Fleet\FuelCard::query()->orderBy('card_number')->get(['id', 'card_number']),
            'fuelStations' => \App\Models\Fleet\FuelStation::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreFuelTransactionRequest $request): RedirectResponse
    {
        $this->authorize('create', FuelTransaction::class);
        FuelTransaction::query()->create($request->validated());

        return to_route('fleet.fuel-transactions.index')->with('flash', ['status' => 'success', 'message' => 'Fuel transaction created.']);
    }

    public function show(FuelTransaction $fuel_transaction): Response
    {
        $this->authorize('view', $fuel_transaction);
        $fuel_transaction->load(['vehicle', 'driver', 'fuelCard', 'fuelStation']);

        return Inertia::render('Fleet/FuelTransactions/Show', ['fuelTransaction' => $fuel_transaction]);
    }

    public function edit(FuelTransaction $fuel_transaction): Response
    {
        $this->authorize('update', $fuel_transaction);

        return Inertia::render('Fleet/FuelTransactions/Edit', [
            'fuelTransaction' => $fuel_transaction,
            'fuelTypes' => array_map(fn (\App\Enums\Fleet\FuelType $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\FuelType::cases()),
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'drivers' => \App\Models\Fleet\Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'fuelCards' => \App\Models\Fleet\FuelCard::query()->orderBy('card_number')->get(['id', 'card_number']),
            'fuelStations' => \App\Models\Fleet\FuelStation::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateFuelTransactionRequest $request, FuelTransaction $fuel_transaction): RedirectResponse
    {
        $this->authorize('update', $fuel_transaction);
        $fuel_transaction->update($request->validated());

        return to_route('fleet.fuel-transactions.show', $fuel_transaction)->with('flash', ['status' => 'success', 'message' => 'Fuel transaction updated.']);
    }

    public function destroy(FuelTransaction $fuel_transaction): RedirectResponse
    {
        $this->authorize('delete', $fuel_transaction);
        $fuel_transaction->delete();

        return to_route('fleet.fuel-transactions.index')->with('flash', ['status' => 'success', 'message' => 'Fuel transaction deleted.']);
    }
}
