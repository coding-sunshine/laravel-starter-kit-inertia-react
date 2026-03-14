<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CampaignWebsite;
use App\Models\Contact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PublicSiteController extends Controller
{
    public function show(string $uuid): Response
    {
        $campaign = CampaignWebsite::query()
            ->where('site_id', $uuid)
            ->firstOrFail();

        return Inertia::render('public/campaign-site', [
            'campaign' => $campaign,
        ]);
    }

    public function survey(string $uuid): Response
    {
        $campaign = CampaignWebsite::query()
            ->where('site_id', $uuid)
            ->firstOrFail();

        return Inertia::render('public/survey', [
            'campaign' => $campaign,
        ]);
    }

    public function submitSurvey(Request $request, string $uuid): RedirectResponse
    {
        $campaign = CampaignWebsite::query()
            ->where('site_id', $uuid)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        Contact::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'contact_origin' => 'property',
            'organization_id' => $campaign->organization_id,
        ]);

        return redirect()->back()->with('success', 'Thank you for your submission.');
    }
}
