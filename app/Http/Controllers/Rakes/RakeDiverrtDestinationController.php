<?php

declare(strict_types=1);

namespace App\Http\Controllers\Rakes;

use App\Http\Controllers\Controller;
use App\Models\DiverrtDestination;
use App\Models\Rake;
use App\Support\RakeRrHubPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class RakeDiverrtDestinationController extends Controller
{
    public function store(Request $request, Rake $rake): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'location' => ['required', 'string', 'max:255'],
        ]);

        $rake->diverrtDestinations()->create([
            'location' => mb_trim($validated['location']),
            'data_source' => 'manual',
        ]);

        $rake->refresh()->load(['rrDocuments', 'diverrtDestinations']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Diversion destination added.',
                'rr_hub' => RakeRrHubPayload::fromRake($rake),
            ], 201);
        }

        return back()->with('success', 'Diversion destination added.');
    }

    public function destroy(Request $request, Rake $rake, DiverrtDestination $diverrtDestination): RedirectResponse|JsonResponse
    {
        if ((int) $diverrtDestination->rake_id !== (int) $rake->id) {
            abort(404);
        }

        if ($diverrtDestination->rrDocuments()->exists()) {
            $message = 'Cannot delete a diversion destination that already has a Railway Receipt.';
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'errors' => ['diverrt_destination' => [$message]],
                ], 422);
            }

            return back()->withErrors([
                'diverrt_destination' => $message,
            ]);
        }

        $diverrtDestination->delete();

        $rake->refresh()->load(['rrDocuments', 'diverrtDestinations']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Diversion destination removed.',
                'rr_hub' => RakeRrHubPayload::fromRake($rake),
            ]);
        }

        return back()->with('success', 'Diversion destination removed.');
    }
}
