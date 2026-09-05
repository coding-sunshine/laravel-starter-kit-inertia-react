<?php

declare(strict_types=1);

namespace App\Http\Controllers\Rakes;

use App\Http\Controllers\Controller;
use App\Models\GuardInspection;
use App\Models\Rake;
use App\Models\RakeLoad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class RakeGuardInspectionController extends Controller
{
    public function store(Request $request, Rake $rake): RedirectResponse
    {
        // $this->authorize('update', $rake);

        $validated = $request->validate([
            'inspection_time' => ['required', 'date'],
            'is_approved' => ['required', 'boolean'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $rakeLoad = $rake->rakeLoad;
        if (! $rakeLoad) {
            return back()->with('error', 'Loading process not started.');
        }

        // Always create a new inspection record for each attempt
        // This ensures proper state management during retry cycles
        $rake->guardInspections()->create([
            ...$validated,
            'rake_load_id' => $rakeLoad->id,
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('rakes.load.show', $rake)
            ->with('success', 'Guard inspection recorded.');
    }
}
