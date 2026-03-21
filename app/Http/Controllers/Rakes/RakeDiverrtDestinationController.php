<?php

declare(strict_types=1);

namespace App\Http\Controllers\Rakes;

use App\Http\Controllers\Controller;
use App\Models\DiverrtDestination;
use App\Models\Rake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class RakeDiverrtDestinationController extends Controller
{
    public function store(Request $request, Rake $rake): RedirectResponse
    {
        $validated = $request->validate([
            'location' => ['required', 'string', 'max:255'],
        ]);

        $rake->diverrtDestinations()->create([
            'location' => mb_trim($validated['location']),
            'data_source' => 'manual',
        ]);

        return back()->with('success', 'Diversion destination added.');
    }

    public function destroy(Rake $rake, DiverrtDestination $diverrtDestination): RedirectResponse
    {
        if ((int) $diverrtDestination->rake_id !== (int) $rake->id) {
            abort(404);
        }

        if ($diverrtDestination->rrDocuments()->exists()) {
            return back()->withErrors([
                'diverrt_destination' => 'Cannot delete a diversion destination that already has a Railway Receipt.',
            ]);
        }

        $diverrtDestination->delete();

        return back()->with('success', 'Diversion destination removed.');
    }
}
