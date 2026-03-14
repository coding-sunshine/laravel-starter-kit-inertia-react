<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\ProvisionWordpressSiteJob;
use App\Models\WordpressWebsite;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class WebsiteController extends Controller
{
    /**
     * Show the websites management page.
     */
    public function index(): Response
    {
        $websites = WordpressWebsite::query()
            ->where('organization_id', TenantContext::id())
            ->get()
            ->groupBy('site_type');

        return Inertia::render('websites/index', [
            'websites' => $websites,
        ]);
    }

    /**
     * Create a new website and dispatch provisioning job.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'site_type' => ['required', 'string', 'in:wp_real_estate,wp_wealth_creation,wp_finance,php_standard,php_premium'],
            'type' => ['nullable', 'string'],
            'primary_color' => ['nullable', 'string', 'max:20'],
            'secondary_color' => ['nullable', 'string', 'max:20'],
            'enquiry_recipient_emails' => ['nullable', 'array'],
            'enquiry_recipient_emails.*' => ['email'],
        ]);

        $website = WordpressWebsite::query()->create([
            ...$validated,
            'organization_id' => TenantContext::id(),
            'stage' => 1,
            'type' => $validated['type'] ?? $validated['site_type'],
        ]);

        dispatch(new ProvisionWordpressSiteJob($website));

        return redirect()->route('website-index.index')->with('success', 'Website provisioning started.');
    }

    /**
     * Soft-delete a website.
     */
    public function destroy(WordpressWebsite $website): RedirectResponse
    {
        $website->delete();

        return redirect()->route('website-index.index')->with('success', 'Website removed.');
    }

    /**
     * Receive provisioner callback to update website status.
     */
    public function provisionerCallback(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'stage' => ['required', 'integer'],
            'instance_id' => ['nullable', 'string', 'max:255'],
            'url_key' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:500'],
            'wp_username' => ['nullable', 'string', 'max:255'],
            'wp_password' => ['nullable', 'string', 'max:255'],
        ]);

        $website = WordpressWebsite::query()->findOrFail($id);
        $website->update($validated);

        return response()->json(['status' => 'ok']);
    }
}
