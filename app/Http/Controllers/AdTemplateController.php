<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateAdCopyAction;
use App\Models\AdTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class AdTemplateController extends Controller
{
    public function index(): Response
    {
        $templates = AdTemplate::query()
            ->where(function ($q): void {
                $q->where('organization_id', tenant('id'))
                    ->orWhereNull('organization_id');
            })
            ->latest()
            ->paginate(15);

        return Inertia::render('ad-templates/index', [
            'templates' => $templates,
        ]);
    }

    public function store(Request $request, GenerateAdCopyAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'channel' => 'required|string|in:facebook,instagram,twitter,linkedin,google',
            'type' => 'required|string|in:ad,social,carousel,story',
            'tone' => 'required|string|in:professional,casual,urgent,friendly',
            'context' => 'nullable|string|max:500',
            'generate_ai' => 'boolean',
        ]);

        $aiCopy = [];

        if ($validated['generate_ai'] ?? false) {
            $aiCopy = $action->handle(
                $validated['channel'],
                $validated['type'],
                $validated['tone'],
                $validated['context'] ?? ''
            );
        }

        AdTemplate::create([
            'organization_id' => tenant('id'),
            'name' => $validated['name'],
            'channel' => $validated['channel'],
            'type' => $validated['type'],
            'tone' => $validated['tone'],
            'headline' => $aiCopy['headline'] ?? null,
            'body_copy' => $aiCopy['body_copy'] ?? null,
            'cta_text' => $aiCopy['cta_text'] ?? null,
        ]);

        return redirect()->route('ad-templates.index')->with('success', 'Ad template created.');
    }

    public function generateCopy(Request $request, GenerateAdCopyAction $action): JsonResponse
    {
        $validated = $request->validate([
            'channel' => 'required|string',
            'type' => 'required|string',
            'tone' => 'required|string',
            'context' => 'nullable|string|max:500',
        ]);

        $copy = $action->handle(
            $validated['channel'],
            $validated['type'],
            $validated['tone'],
            $validated['context'] ?? ''
        );

        return response()->json($copy);
    }

    public function destroy(AdTemplate $adTemplate): RedirectResponse
    {
        $adTemplate->delete();

        return redirect()->route('ad-templates.index')->with('success', 'Template deleted.');
    }
}
