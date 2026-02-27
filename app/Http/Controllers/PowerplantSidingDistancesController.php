<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PowerplantSidingDistance;
use App\Models\PowerPlant;
use App\Models\Siding;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PowerplantSidingDistancesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $distances = PowerplantSidingDistance::with(['powerPlant', 'siding'])
            ->orderBy('power_plant_id')
            ->orderBy('siding_id')
            ->get();

        return Inertia::render('MasterData/DistanceMatrix/Index', [
            'distances' => $distances,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $powerPlants = PowerPlant::orderBy('name')->get();
        $sidings = Siding::orderBy('name')->get();

        return Inertia::render('MasterData/DistanceMatrix/Create', [
            'powerPlants' => $powerPlants,
            'sidings' => $sidings,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'power_plant_id' => 'required|exists:power_plants,id',
            'siding_id' => 'required|exists:sidings,id',
            'distance_km' => 'required|numeric|min:0',
        ]);

        // Check for unique constraint
        $existing = PowerplantSidingDistance::where('power_plant_id', $validated['power_plant_id'])
            ->where('siding_id', $validated['siding_id'])
            ->first();

        if ($existing) {
            return back()->withErrors([
                'combination' => 'This power plant and siding combination already exists.',
            ]);
        }

        PowerplantSidingDistance::create($validated);

        return redirect()->route('master-data.distance-matrix.index')
            ->with('success', 'Distance created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(PowerplantSidingDistance $distance)
    {
        $distance->load(['powerPlant', 'siding']);

        return Inertia::render('MasterData/DistanceMatrix/Show', [
            'distance' => $distance,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PowerplantSidingDistance $distance)
    {
        $powerPlants = PowerPlant::orderBy('name')->get();
        $sidings = Siding::orderBy('name')->get();
        $distance->load(['powerPlant', 'siding']);

        return Inertia::render('MasterData/DistanceMatrix/Edit', [
            'distance' => $distance,
            'powerPlants' => $powerPlants,
            'sidings' => $sidings,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PowerplantSidingDistance $distance)
    {
        $validated = $request->validate([
            'power_plant_id' => 'required|exists:power_plants,id',
            'siding_id' => 'required|exists:sidings,id',
            'distance_km' => 'required|numeric|min:0',
        ]);

        // Check for unique constraint (excluding current record)
        $existing = PowerplantSidingDistance::where('power_plant_id', $validated['power_plant_id'])
            ->where('siding_id', $validated['siding_id'])
            ->where('id', '!=', $distance->id)
            ->first();

        if ($existing) {
            return back()->withErrors([
                'combination' => 'This power plant and siding combination already exists.',
            ]);
        }

        $distance->update($validated);

        return redirect()->route('master-data.distance-matrix.index')
            ->with('success', 'Distance updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PowerplantSidingDistance $distance)
    {
        $distance->delete();

        return redirect()->route('master-data.distance-matrix.index')
            ->with('success', 'Distance deleted successfully.');
    }
}
