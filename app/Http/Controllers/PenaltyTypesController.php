<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PenaltyType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PenaltyTypesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $penaltyTypes = PenaltyType::orderBy('code')->get();

        return Inertia::render('MasterData/PenaltyTypes/Index', [
            'penaltyTypes' => $penaltyTypes,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('MasterData/PenaltyTypes/Create', [
            'categories' => [
                'overloading' => 'Overloading',
                'time_service' => 'Time Service',
                'operational' => 'Operational',
                'safety' => 'Safety',
                'other' => 'Other',
            ],
            'calculationTypes' => [
                'formula_based' => 'Formula Based',
                'fixed' => 'Fixed',
                'per_hour' => 'Per Hour',
                'per_mt' => 'Per MT',
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:penalty_types,code',
            'name' => 'required|string|max:255',
            'category' => 'required|in:overloading,time_service,operational,safety,other',
            'calculation_type' => 'required|in:formula_based,fixed,per_hour,per_mt',
            'description' => 'nullable|string',
            'default_rate' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        PenaltyType::create($validated);

        return redirect()->route('master-data.penalty-types.index')
            ->with('success', 'Penalty type created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(PenaltyType $penaltyType)
    {
        return Inertia::render('MasterData/PenaltyTypes/Show', [
            'penaltyType' => $penaltyType,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PenaltyType $penaltyType)
    {
        return Inertia::render('MasterData/PenaltyTypes/Edit', [
            'penaltyType' => $penaltyType,
            'categories' => [
                'overloading' => 'Overloading',
                'time_service' => 'Time Service',
                'operational' => 'Operational',
                'safety' => 'Safety',
                'other' => 'Other',
            ],
            'calculationTypes' => [
                'formula_based' => 'Formula Based',
                'fixed' => 'Fixed',
                'per_hour' => 'Per Hour',
                'per_mt' => 'Per MT',
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PenaltyType $penaltyType)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:penalty_types,code,'.$penaltyType->id,
            'name' => 'required|string|max:255',
            'category' => 'required|in:overloading,time_service,operational,safety,other',
            'calculation_type' => 'required|in:formula_based,fixed,per_hour,per_mt',
            'description' => 'nullable|string',
            'default_rate' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $penaltyType->update($validated);

        return redirect()->route('master-data.penalty-types.index')
            ->with('success', 'Penalty type updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PenaltyType $penaltyType)
    {
        $penaltyType->delete();

        return redirect()->route('master-data.penalty-types.index')
            ->with('success', 'Penalty type deleted successfully.');
    }
}
