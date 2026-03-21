<?php

declare(strict_types=1);

namespace App\Http\Controllers\Rakes;

use App\Http\Controllers\Controller;
use App\Models\Rake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class RakeDiversionModeController extends Controller
{
    public function __invoke(Request $request, Rake $rake): RedirectResponse
    {
        $validated = $request->validate([
            'is_diverted' => ['required', 'boolean'],
        ]);

        $toDiverted = (bool) $validated['is_diverted'];

        if (! $toDiverted) {
            if ($rake->rrDocuments()->whereNotNull('diverrt_destination_id')->exists()) {
                return back()->withErrors([
                    'is_diverted' => 'Remove diversion Railway Receipts before turning off diverted mode.',
                ]);
            }
        }

        $rake->update([
            'is_diverted' => $toDiverted,
            'updated_by' => $request->user()->id,
        ]);

        return back()->with(
            'success',
            $toDiverted
                ? 'Diverted mode enabled for this rake.'
                : 'Diverted mode disabled for this rake.'
        );
    }
}
