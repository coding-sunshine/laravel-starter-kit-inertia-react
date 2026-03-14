<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateBrochureV2Action;
use App\Models\BrochureLayout;
use App\Models\Flyer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class BrochureLayoutController extends Controller
{
    public function index(): Response
    {
        $layouts = BrochureLayout::query()
            ->where(function ($q): void {
                $q->where('organization_id', tenant('id'))
                    ->orWhereNull('organization_id');
            })
            ->latest()
            ->paginate(15);

        $flyers = Flyer::query()
            ->with(['project', 'lot', 'flyerTemplate'])
            ->latest()
            ->limit(20)
            ->get();

        return Inertia::render('brochure-layouts/index', [
            'layouts' => $layouts,
            'flyers' => $flyers,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'template_type' => 'required|string|in:puck,blade',
            'is_default' => 'boolean',
        ]);

        BrochureLayout::create([
            'organization_id' => tenant('id'),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'template_type' => $validated['template_type'],
            'layout_config' => [],
            'is_default' => $validated['is_default'] ?? false,
        ]);

        return redirect()->route('brochure-layouts.index')->with('success', 'Brochure layout created.');
    }

    public function generatePdf(Request $request, Flyer $flyer, GenerateBrochureV2Action $action): JsonResponse
    {
        $layoutId = $request->input('layout_id');
        $layout = $layoutId ? BrochureLayout::find($layoutId) : null;

        $path = $action->handle($flyer, $layout);

        return response()->json(['path' => $path, 'success' => true]);
    }
}
