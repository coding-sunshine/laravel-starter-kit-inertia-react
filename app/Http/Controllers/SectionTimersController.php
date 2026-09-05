<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SectionTimer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SectionTimersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sectionTimers = SectionTimer::orderBy('section_name')->get();

        return Inertia::render('MasterData/SectionTimers/Index', [
            'sectionTimers' => $sectionTimers,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('MasterData/SectionTimers/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'section_name' => 'required|string|max:255|unique:section_timers,section_name',
            'free_minutes' => 'required|integer|min:0',
            'warning_minutes' => 'required|integer|min:0',
            'penalty_applicable' => 'boolean',
        ]);

        SectionTimer::create($validated);

        return redirect()->route('master-data.section-timers.index')
            ->with('success', 'Section timer created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(SectionTimer $sectionTimer)
    {
        return Inertia::render('MasterData/SectionTimers/Show', [
            'sectionTimer' => $sectionTimer,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SectionTimer $sectionTimer)
    {
        return Inertia::render('MasterData/SectionTimers/Edit', [
            'sectionTimer' => $sectionTimer,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SectionTimer $sectionTimer)
    {
        $validated = $request->validate([
            'section_name' => 'required|string|max:255|unique:section_timers,section_name,'.$sectionTimer->id,
            'free_minutes' => 'required|integer|min:0',
            'warning_minutes' => 'required|integer|min:0',
            'penalty_applicable' => 'boolean',
        ]);

        $sectionTimer->update($validated);

        return redirect()->route('master-data.section-timers.index')
            ->with('success', 'Section timer updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SectionTimer $sectionTimer)
    {
        $sectionTimer->delete();

        return redirect()->route('master-data.section-timers.index')
            ->with('success', 'Section timer deleted successfully.');
    }
}
