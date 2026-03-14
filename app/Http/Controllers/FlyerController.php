<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\ExportFlyerPdfJob;
use App\Models\Flyer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class FlyerController extends Controller
{
    public function editPuck(Flyer $flyer): Response
    {
        return Inertia::render('flyers/puck-editor', [
            'flyer' => $flyer->only(['id', 'title', 'puck_content', 'puck_enabled']),
        ]);
    }

    public function savePuck(Request $request, Flyer $flyer): JsonResponse
    {
        $validated = $request->validate([
            'puck_content' => ['required', 'array'],
            'publish' => ['boolean'],
        ]);

        $flyer->update([
            'puck_content' => $validated['puck_content'],
            'puck_enabled' => $validated['publish'] ?? false,
        ]);

        return response()->json(['success' => true, 'flyer' => $flyer->only(['id', 'puck_enabled'])]);
    }

    public function exportPdf(Flyer $flyer): JsonResponse
    {
        ExportFlyerPdfJob::dispatch($flyer);

        return response()->json(['message' => 'PDF export has been queued. You will be notified when it is ready.']);
    }
}
