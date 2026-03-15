<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PuckTemplate;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PuckTemplateController extends Controller
{
    public function index(): Response
    {
        $orgId = TenantContext::id();

        $templates = PuckTemplate::query()
            ->where(function ($q) use ($orgId) {
                $q->whereNull('organization_id')
                    ->when($orgId, fn ($q) => $q->orWhere('organization_id', $orgId));
            })
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('puck-templates/index', [
            'templates' => $templates,
            'types' => ['campaign_site', 'flyer', 'landing_page'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:campaign_site,flyer,landing_page'],
            'puck_content' => ['required', 'array'],
        ]);

        $orgId = TenantContext::id();

        PuckTemplate::query()->create([
            ...$validated,
            'organization_id' => $orgId,
        ]);

        return redirect()->back()->with('success', 'Template created successfully.');
    }

    public function edit(PuckTemplate $template): Response
    {
        return Inertia::render('puck-templates/edit', [
            'template' => $template,
        ]);
    }
}
