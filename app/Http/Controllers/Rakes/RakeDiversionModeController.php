<?php

declare(strict_types=1);

namespace App\Http\Controllers\Rakes;

use App\Http\Controllers\Controller;
use App\Models\Rake;
use App\Support\RakeRrHubPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class RakeDiversionModeController extends Controller
{
    public function __invoke(Request $request, Rake $rake): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'is_diverted' => ['required', 'boolean'],
        ]);

        $toDiverted = (bool) $validated['is_diverted'];

        if (! $toDiverted) {
            if ($rake->rrDocuments()->whereNotNull('diverrt_destination_id')->exists()) {
                $message = 'Remove diversion Railway Receipts before turning off diverted mode.';
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => $message,
                        'errors' => ['is_diverted' => [$message]],
                    ], 422);
                }

                return back()->withErrors([
                    'is_diverted' => $message,
                ]);
            }
        }

        $rake->update([
            'is_diverted' => $toDiverted,
            'updated_by' => $request->user()->id,
        ]);

        $rake->refresh()->load(['rrDocuments', 'diverrtDestinations']);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $toDiverted
                    ? 'Diverted mode enabled for this rake.'
                    : 'Diverted mode disabled for this rake.',
                'rr_hub' => RakeRrHubPayload::fromRake($rake),
            ]);
        }

        return back()->with(
            'success',
            $toDiverted
                ? 'Diverted mode enabled for this rake.'
                : 'Diverted mode disabled for this rake.'
        );
    }
}
