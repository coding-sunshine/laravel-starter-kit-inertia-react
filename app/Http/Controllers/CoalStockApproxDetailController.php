<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CoalStockApproxDetail;
use App\Models\Siding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class CoalStockApproxDetailController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $query = CoalStockApproxDetail::query()
            ->with('siding')
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->filled('siding_id')) {
            $query->where('siding_id', $request->input('siding_id'));
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->input('date_to'));
        }

        $coalStockDetails = $query->paginate(15)->withQueryString();

        $sidings = Siding::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('MasterData/DailyStockDetails/Index', [
            'coalStockDetails' => $coalStockDetails,
            'sidings' => $sidings,
            'filters' => $request->only(['siding_id', 'date_from', 'date_to']),
        ]);
    }

    public function create(): InertiaResponse
    {
        $sidings = Siding::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('MasterData/DailyStockDetails/Create', [
            'sidings' => $sidings,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'siding_id' => 'nullable|exists:sidings,id',
            'date' => 'nullable|date',
            'railway_siding_opening_coal_stock' => 'nullable|numeric|min:0',
            'railway_siding_closing_coal_stock' => 'nullable|numeric|min:0',
            'coal_dispatch_qty' => 'nullable|numeric|min:0',
            'no_of_rakes' => 'nullable|string|max:50',
            'rakes_qty' => 'nullable|numeric|min:0',
            'source' => 'nullable|in:manual,system',
            'remarks' => 'nullable|string|max:1000',
        ]);

        CoalStockApproxDetail::create($validated);

        return redirect()->route('master-data.daily-stock-details.index')
            ->with('success', 'Daily stock details created successfully.');
    }

    public function edit(CoalStockApproxDetail $coalStockApproxDetail): InertiaResponse
    {
        $sidings = Siding::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('MasterData/DailyStockDetails/Edit', [
            'coalStockDetail' => [
                'id' => $coalStockApproxDetail->id,
                'siding_id' => $coalStockApproxDetail->siding_id,
                'date' => $coalStockApproxDetail->date?->toDateString(),
                'railway_siding_opening_coal_stock' => (float) $coalStockApproxDetail->railway_siding_opening_coal_stock,
                'railway_siding_closing_coal_stock' => (float) $coalStockApproxDetail->railway_siding_closing_coal_stock,
                'coal_dispatch_qty' => (float) $coalStockApproxDetail->coal_dispatch_qty,
                'no_of_rakes' => $coalStockApproxDetail->no_of_rakes,
                'rakes_qty' => (float) $coalStockApproxDetail->rakes_qty,
                'source' => $coalStockApproxDetail->source,
                'remarks' => $coalStockApproxDetail->remarks,
            ],
            'sidings' => $sidings,
        ]);
    }

    public function update(Request $request, CoalStockApproxDetail $coalStockApproxDetail): RedirectResponse
    {
        $validated = $request->validate([
            'siding_id' => 'nullable|exists:sidings,id',
            'date' => 'nullable|date',
            'railway_siding_opening_coal_stock' => 'nullable|numeric|min:0',
            'railway_siding_closing_coal_stock' => 'nullable|numeric|min:0',
            'coal_dispatch_qty' => 'nullable|numeric|min:0',
            'no_of_rakes' => 'nullable|string|max:50',
            'rakes_qty' => 'nullable|numeric|min:0',
            'source' => 'nullable|in:manual,system',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $coalStockApproxDetail->update($validated);

        return redirect()->route('master-data.daily-stock-details.index')
            ->with('success', 'Daily stock details updated successfully.');
    }

    public function destroy(CoalStockApproxDetail $coalStockApproxDetail): RedirectResponse
    {
        $coalStockApproxDetail->delete();

        return redirect()->route('master-data.daily-stock-details.index')
            ->with('success', 'Daily stock details deleted successfully.');
    }
}
