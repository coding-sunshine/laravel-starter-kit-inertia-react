<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CaptureLeadAction;
use App\Actions\EnrollInNurtureSequenceAction;
use App\Actions\RouteLeadAction;
use App\Models\NurtureSequence;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Multi-channel lead capture endpoint.
 * Accepts leads from web forms, chat widgets, SMS webhooks, and API calls.
 */
final class LeadCaptureController extends Controller
{
    public function __construct(
        private CaptureLeadAction $captureAction,
        private RouteLeadAction $routeAction,
        private EnrollInNurtureSequenceAction $enrollAction,
    ) {
        //
    }

    /**
     * Capture a lead from a web form (public endpoint).
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'channel' => ['nullable', 'string', 'in:web_form,chat,sms,phone,api,landing_page,social'],
            'source_name' => ['nullable', 'string', 'max:100'],
            'campaign_name' => ['nullable', 'string', 'max:255'],
            'ad_name' => ['nullable', 'string', 'max:255'],
            'page_url' => ['nullable', 'url', 'max:500'],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
        ]);

        $organizationId = $data['organization_id'] ?? (int) config('tenancy.default_organization_id', 1);

        $contact = $this->captureAction->handle($data, $organizationId);

        // Auto-route to agent
        $this->routeAction->handle($contact);

        // Auto-enroll in matching nurture sequence if one exists
        $sequence = NurtureSequence::query()
            ->where('organization_id', $organizationId)
            ->where('is_active', true)
            ->where('trigger_stage', 'new')
            ->first();

        if ($sequence) {
            $this->enrollAction->handle($contact, $sequence);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'contact_id' => $contact->id,
                'message' => 'Thank you! We will be in touch shortly.',
            ]);
        }

        return redirect()->back()->with('success', 'Thank you! We will be in touch shortly.');
    }

    /**
     * Bulk capture from CSV/API (authenticated).
     */
    public function bulkStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'leads' => ['required', 'array', 'max:500'],
            'leads.*.first_name' => ['required', 'string', 'max:100'],
            'leads.*.email' => ['nullable', 'email'],
            'leads.*.phone' => ['nullable', 'string'],
            'leads.*.channel' => ['nullable', 'string'],
        ]);

        $organizationId = TenantContext::id() ?? 1;
        $created = 0;

        foreach ($data['leads'] as $lead) {
            $this->captureAction->handle($lead, $organizationId);
            $created++;
        }

        return response()->json(['success' => true, 'created' => $created]);
    }
}
