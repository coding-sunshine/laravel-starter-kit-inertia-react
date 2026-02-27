<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Loader;
use App\Models\Siding;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LoadersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $loaders = Loader::with('siding')->orderBy('loader_name')->get();

        return Inertia::render('MasterData/Loaders/Index', [
            'loaders' => $loaders,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $sidings = Siding::orderBy('name')->get();

        return Inertia::render('MasterData/Loaders/Create', [
            'sidings' => $sidings,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'siding_id' => 'required|exists:sidings,id',
            'loader_name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:loaders,code',
            'loader_type' => 'required|string|max:255',
            'make_model' => 'nullable|string|max:255',
            'capacity_mt' => 'nullable|numeric|min:0',
            'last_calibration_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        Loader::create($validated);

        return redirect()->route('master-data.loaders.index')
            ->with('success', 'Loader created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Loader $loader)
    {
        $loader->load('siding');

        return Inertia::render('MasterData/Loaders/Show', [
            'loader' => $loader,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Loader $loader)
    {
        $sidings = Siding::orderBy('name')->get();
        $loader->load('siding');

        return Inertia::render('MasterData/Loaders/Edit', [
            'loader' => $loader,
            'sidings' => $sidings,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Loader $loader)
    {
        $validated = $request->validate([
            'siding_id' => 'required|exists:sidings,id',
            'loader_name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:loaders,code,'.$loader->id,
            'loader_type' => 'required|string|max:255',
            'make_model' => 'nullable|string|max:255',
            'capacity_mt' => 'nullable|numeric|min:0',
            'last_calibration_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $loader->update($validated);

        return redirect()->route('master-data.loaders.index')
            ->with('success', 'Loader updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Loader $loader)
    {
        $loader->delete();

        return redirect()->route('master-data.loaders.index')
            ->with('success', 'Loader deleted successfully.');
    }
}
