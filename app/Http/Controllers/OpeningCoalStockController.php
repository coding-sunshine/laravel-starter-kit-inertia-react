<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Siding;
use App\Models\SidingOpeningBalance;
use App\Models\StockLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        DB::transaction(function () use ($validated, $siding) {

            // 1. Save in opening balance table (your existing logic)
            SidingOpeningBalance::query()->updateOrCreate(
                ['siding_id' => $siding->id],
                [
                    'opening_balance_mt' => $validated['opening_balance_mt'],
                    'as_of_date' => $validated['as_of_date'] ?? null,
                    'remarks' => $validated['remarks'] ?? null,
                ]
            );

            // 2. Get latest ledger (lock for safety)
            $lastLedger = StockLedger::query()
                ->where('siding_id', $siding->id)
                ->lockForUpdate()
                ->latest('id')
                ->first();

            $current = $lastLedger
                ? (float) $lastLedger->closing_balance_mt
                : 0.0;

            $newBalance = (float) $validated['opening_balance_mt'];

            $delta = round($newBalance - $current, 2);

            // nothing to change
            if ($delta === 0) {
                return;
            }

            // 3. Insert ledger entry
            StockLedger::create([
                'siding_id' => $siding->id,
                'transaction_type' => 'correction',
                'quantity_mt' => $delta,
                'opening_balance_mt' => $current,
                'closing_balance_mt' => $newBalance,
                'reference_number' => 'OPENING-RESET-'.now()->format('Ymd'),
                'remarks' => 'Opening balance initialized',
                'created_by' => auth()->id(),
            ]);

            DB::afterCommit(function () use ($siding, $newBalance) {
                event(new \App\Events\CoalStockUpdated($siding->id, $newBalance));
            });
        });

        return redirect()->route('master-data.opening-coal-stock.index')
            ->with('success', 'Opening coal stock updated successfully.');
    }
}
