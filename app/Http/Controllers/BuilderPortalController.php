<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BuilderPortal;
use App\Models\Project;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class BuilderPortalController extends Controller
{
    public function index(): Response
    {
        $orgId = TenantContext::id();

        $portals = BuilderPortal::query()
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->orderByDesc('created_at')
            ->get();

        $projects = Project::query()
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->orderBy('title')
            ->get();

        return Inertia::render('builder-portal/index', [
            'portals' => $portals,
            'projects' => $projects,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'primary_color' => ['nullable', 'string', 'max:20'],
            'show_prices' => ['boolean'],
            'show_agent_details' => ['boolean'],
        ]);

        $orgId = TenantContext::id();

        BuilderPortal::query()->create([
            ...$validated,
            'organization_id' => $orgId,
        ]);

        return redirect()->back()->with('success', 'Builder portal created successfully.');
    }

    public function show(BuilderPortal $portal): Response
    {
        $portal->load('projects');

        return Inertia::render('builder-portal/show', [
            'portal' => $portal,
        ]);
    }
}
