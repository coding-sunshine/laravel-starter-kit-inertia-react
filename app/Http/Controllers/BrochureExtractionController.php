<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\ExtractBrochureMediaAction;
use App\Models\Flyer;
use App\Models\Lot;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Brochure extraction controller — upload brochures and extract media via AI.
 */
final class BrochureExtractionController extends Controller
{
    public function index(): Response
    {
        $projects = Project::query()
            ->where('organization_id', tenant('id'))
            ->select('id', 'title', 'stage')
            ->orderBy('title')
            ->get();

        return Inertia::render('brochure-extraction/index', [
            'projects' => $projects,
        ]);
    }

    public function extract(Request $request, ExtractBrochureMediaAction $action): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:20480'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'lot_id' => ['nullable', 'exists:lots,id'],
            'flyer_id' => ['nullable', 'exists:flyers,id'],
        ]);

        $project = isset($validated['project_id'])
            ? Project::find($validated['project_id'])
            : null;

        $lot = isset($validated['lot_id'])
            ? Lot::find($validated['lot_id'])
            : null;

        $flyer = isset($validated['flyer_id'])
            ? Flyer::find($validated['flyer_id'])
            : null;

        $result = $action->handle(
            file: $request->file('file'),
            project: $project,
            lot: $lot,
            flyer: $flyer,
        );

        return response()->json([
            'success' => true,
            'result' => $result,
        ]);
    }
}
