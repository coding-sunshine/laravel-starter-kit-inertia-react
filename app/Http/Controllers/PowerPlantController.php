<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PowerPlant;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PowerPlantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $powerPlants = PowerPlant::orderBy('name')->get();

        return Inertia::render('MasterData/PowerPlants/Index', [
            'powerPlants' => $powerPlants,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('MasterData/PowerPlants/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:power_plants,code',
            'location' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        PowerPlant::create($validated);

        return redirect()->route('master-data.power-plants.index')
            ->with('success', 'Power plant created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(PowerPlant $powerPlant)
    {
        return Inertia::render('MasterData/PowerPlants/Show', [
            'powerPlant' => $powerPlant,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PowerPlant $powerPlant)
    {
        return Inertia::render('MasterData/PowerPlants/Edit', [
            'powerPlant' => $powerPlant,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PowerPlant $powerPlant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:power_plants,code,'.$powerPlant->id,
            'location' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $powerPlant->update($validated);

        return redirect()->route('master-data.power-plants.index')
            ->with('success', 'Power plant updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PowerPlant $powerPlant)
    {
        $powerPlant->delete();

        return redirect()->route('master-data.power-plants.index')
            ->with('success', 'Power plant deleted successfully.');
    }
}
