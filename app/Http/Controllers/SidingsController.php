<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Siding;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SidingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sidings = Siding::with('organization')->orderBy('name')->get();

        return Inertia::render('MasterData/Sidings/Index', [
            'sidings' => $sidings,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('MasterData/Sidings/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:sidings,code',
            'location' => 'required|string|max:255',
            'station_code' => 'required|string|max:10',
            'is_active' => 'boolean',
        ]);

        Siding::create($validated);

        return redirect()->route('master-data.sidings.index')
            ->with('success', 'Siding created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Siding $siding)
    {
        $siding->load('organization');

        return Inertia::render('MasterData/Sidings/Show', [
            'siding' => $siding,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Siding $siding)
    {
        $siding->load('organization');

        return Inertia::render('MasterData/Sidings/Edit', [
            'siding' => $siding,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Siding $siding)
    {
        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:sidings,code,'.$siding->id,
            'location' => 'required|string|max:255',
            'station_code' => 'required|string|max:10',
            'is_active' => 'boolean',
        ]);

        $siding->update($validated);

        return redirect()->route('master-data.sidings.index')
            ->with('success', 'Siding updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Siding $siding)
    {
        $siding->delete();

        return redirect()->route('master-data.sidings.index')
            ->with('success', 'Siding deleted successfully.');
    }
}
