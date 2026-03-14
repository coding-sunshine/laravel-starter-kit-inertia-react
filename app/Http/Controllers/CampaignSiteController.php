<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CampaignWebsite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CampaignSiteController extends Controller
{
    public function index(Request $request): Response
    {
        $orgId = auth()->user()?->currentOrganization?->id;

        $sites = CampaignWebsite::query()
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->orderByDesc('created_at')
            ->paginate(20);

        return Inertia::render('campaign-sites/index', [
            'sites' => $sites,
        ]);
    }

    public function editPuck(CampaignWebsite $campaign): Response
    {
        return Inertia::render('campaign-sites/puck-editor', [
            'campaign' => $campaign->only(['id', 'title', 'puck_content', 'puck_enabled', 'site_id']),
        ]);
    }

    public function savePuck(Request $request, CampaignWebsite $campaign): JsonResponse
    {
        $validated = $request->validate([
            'puck_content' => ['required', 'array'],
            'publish' => ['boolean'],
        ]);

        $campaign->update([
            'puck_content' => $validated['puck_content'],
            'puck_enabled' => $validated['publish'] ?? false,
        ]);

        return response()->json(['success' => true, 'campaign' => $campaign->only(['id', 'puck_enabled'])]);
    }
}
