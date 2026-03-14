<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Siding;
use App\Models\SidingOpeningBalance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class OpeningCoalStockController extends Controller
{
    public function index(): InertiaResponse
    {
        $sidings = Siding::query()
            ->with('openingBalance')
            ->orderBy('name')
            ->get()
            ->map(fn (Siding $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'opening_balance_mt' => $s->openingBalance ? (float) $s->openingBalance->opening_balance_mt : 0.0,
            ]);

        return Inertia::render('MasterData/OpeningCoalStock/Index', [
            'sidings' => $sidings,
        ]);
    }

    public function edit(Siding $siding): InertiaResponse
    {
        $balance = SidingOpeningBalance::query()->firstOrCreate(
            ['siding_id' => $siding->id],
            ['opening_balance_mt' => 0, 'as_of_date' => null, 'remarks' => null]
        );

        return Inertia::render('MasterData/OpeningCoalStock/Edit', [
            'siding' => $siding->only(['id', 'name']),
            'openingBalance' => [
                'id' => $balance->id,
                'opening_balance_mt' => (float) $balance->opening_balance_mt,
                'as_of_date' => $balance->as_of_date?->toDateString(),
                'remarks' => $balance->remarks,
            ],
        ]);
    }

    public function update(Request $request, Siding $siding): RedirectResponse
    {
        $validated = $request->validate([
            'opening_balance_mt' => 'required|numeric|min:0',
            'as_of_date' => 'nullable|date',
            'remarks' => 'nullable|string|max:1000',
        ]);

        SidingOpeningBalance::query()->updateOrCreate(
            ['siding_id' => $siding->id],
            [
                'opening_balance_mt' => $validated['opening_balance_mt'],
                'as_of_date' => $validated['as_of_date'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
            ]
        );

        return redirect()->route('master-data.opening-coal-stock.index')
            ->with('success', 'Opening coal stock updated successfully.');
    }
}
