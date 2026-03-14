<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateLandingPageAction;
use App\Models\LandingPageTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class LandingPageController extends Controller
{
    public function index(): Response
    {
        $pages = LandingPageTemplate::query()
            ->where('organization_id', tenant('id'))
            ->latest()
            ->paginate(15);

        return Inertia::render('landing-pages/index', [
            'pages' => $pages,
        ]);
    }

    public function generate(Request $request, GenerateLandingPageAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'project_name' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'target_audience' => 'nullable|string|max:255',
        ]);

        $page = $action->handle(
            $validated['project_name'],
            $validated['description'],
            $validated['target_audience'] ?? 'home buyers'
        );

        return redirect()->route('landing-pages.index')->with('success', "Landing page '{$page->name}' generated.");
    }

    public function update(Request $request, LandingPageTemplate $landingPageTemplate): RedirectResponse
    {
        $validated = $request->validate([
            'headline' => 'nullable|string|max:255',
            'sub_headline' => 'nullable|string',
            'html_content' => 'nullable|string',
            'status' => 'required|string|in:draft,published,archived',
            'is_active' => 'boolean',
        ]);

        $landingPageTemplate->update($validated);

        return redirect()->route('landing-pages.index')->with('success', 'Landing page updated.');
    }

    public function destroy(LandingPageTemplate $landingPageTemplate): RedirectResponse
    {
        $landingPageTemplate->delete();

        return redirect()->route('landing-pages.index')->with('success', 'Landing page deleted.');
    }
}
