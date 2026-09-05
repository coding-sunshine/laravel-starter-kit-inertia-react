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

    public function update(Request $request, Siding $siding): RedirectResponse{
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

            // 1. Lock latest row (to prevent race condition)
            $lastLedger = StockLedger::query()
                ->where('siding_id', $siding->id)
                ->lockForUpdate()
                ->latest('id')
                ->first();
        
            $openingInput = (float) $validated['opening_balance_mt'];
        
            // 2. Calculate TODAY's stock activity ONLY
            $todayTotal = StockLedger::query()
                ->where('siding_id', $siding->id)
                ->whereDate('created_at', today())
                ->sum('quantity_mt');
            
            // 3. New correct balance
            $newBalance = $openingInput + $todayTotal;
        
            // 4. Opening for this entry = yesterday closing (input)
            $openingBalance = $openingInput;
            // 5. Insert correction
            StockLedger::create([
                'siding_id' => $siding->id,
                'transaction_type' => 'correction',
                'quantity_mt' => $openingInput, // we are adding base
                'opening_balance_mt' => $todayTotal, // optional (see note below)
                'closing_balance_mt' => $newBalance,
                'reference_number' => 'OPENING-ADD-' . now()->format('YmdHis'),
                'remarks' => 'Opening balance (yesterday closing)',
                'created_by' => auth()->id(),
            ]);
        
            DB::afterCommit(function () use ($siding, $newBalance) {
                event(new \App\Events\CoalStockUpdated($siding->id, $newBalance));
            });
        });

        return redirect()->route('master-data.opening-coal-stock.index')
            ->with('success', 'Opening coal stock updated successfully.');
    }


    public function fixWrongOpening(Siding $siding): RedirectResponse
    {
        DB::transaction(function () use ($siding) {
    
            // 1. Lock ALL ledger rows for this siding (important)
            $ledgers = StockLedger::query()
                ->where('siding_id', $siding->id)
                ->whereDate('created_at',today())
                ->lockForUpdate()
                ->orderBy('id')
                ->get();
            // dd($ledgers);
            // 2. Find reset entry
            $reset = $ledgers
            ->filter(function ($row) {
                if(is_null($row->reference_number)){
                    
                }else{
                    return str_starts_with($row->reference_number, 'OPENING-RESET-');
                }
            })
            ->sortByDesc('id')   
            ->first();
    
            if (! $reset) {
                throw new \Exception('No reset entry found.');
            }
    
            // 3. Split ledger
            $beforeReset = $ledgers->where('id', '<', $reset->id)->sum('quantity_mt');
            $afterReset  = $ledgers->where('id', '>', $reset->id)->sum('quantity_mt');
    
            $resetValue = (float) $reset->closing_balance_mt;
    
            // 4. Compute correct final balance
            $correctFinal = $resetValue + $beforeReset + $afterReset;
    
            // 5. Get current (last row)
            $lastLedger = $ledgers->last();
            $current = (float) $lastLedger->closing_balance_mt;
    
            // 6. Calculate delta (IMPORTANT)
            $delta = round($correctFinal - $current, 2);
    
            if ($delta == 0) {
                return;
            }
    
            $newBalance = $current + $delta;
            // dd($newBalance,$current,$delta,$beforeReset,$afterReset,$correctFinal);
            // 7. Insert correction entry
            StockLedger::create([
                'siding_id' => $siding->id,
                'transaction_type' => 'correction',
                'quantity_mt' => $delta,
                'opening_balance_mt' => $current,
                'closing_balance_mt' => $newBalance,
                'reference_number' => 'PAKUR-FIX-' . now()->format('YmdHis'),
                'remarks' => 'Fix incorrect opening reset placement',
                'created_by' => auth()->id(),
            ]);
    
            DB::afterCommit(function () use ($siding, $newBalance) {
                event(new \App\Events\CoalStockUpdated($siding->id, $newBalance));
            });
        });
    
        return back()->with('success', 'Pakur stock fixed successfully.');
    }
}
