<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\FunnelInstance;
use App\Models\FunnelTemplate;
use App\Services\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manage funnel templates and enroll contacts into funnels.
 */
final class FunnelTemplateController extends Controller
{
    public function index(): Response
    {
        $orgId = TenantContext::id();

        $templates = FunnelTemplate::query()
            ->where(fn ($q) => $q->whereNull('organization_id')
                ->orWhere('organization_id', $orgId))
            ->withCount('instances')
            ->latest()
            ->paginate(20);

        $stats = [
            'total_templates' => FunnelTemplate::query()->count(),
            'active_instances' => FunnelInstance::query()->where('status', 'active')->count(),
            'completed_instances' => FunnelInstance::query()->where('status', 'completed')->count(),
        ];

        return Inertia::render('funnel-templates/index', [
            'templates' => $templates,
            'stats' => $stats,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:co-living,rooming,dual-occ,generic'],
            'description' => ['nullable', 'string'],
            'config' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ]);

        FunnelTemplate::query()->create([
            'organization_id' => TenantContext::id(),
            'name' => $data['name'],
            'type' => $data['type'],
            'description' => $data['description'] ?? null,
            'config' => $data['config'] ?? [],
            'is_active' => $data['is_active'] ?? true,
        ]);

        return redirect()->route('funnel.templates.index')->with('success', 'Funnel template created.');
    }

    public function enroll(Request $request, FunnelTemplate $template, Contact $contact): JsonResponse
    {
        $existing = FunnelInstance::query()
            ->where('funnel_template_id', $template->id)
            ->where('contact_id', $contact->id)
            ->where('status', 'active')
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Contact is already enrolled in this funnel.',
                'instance_id' => $existing->id,
            ]);
        }

        $instance = FunnelInstance::query()->create([
            'funnel_template_id' => $template->id,
            'contact_id' => $contact->id,
            'status' => 'active',
            'current_step' => 0,
            'started_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'instance_id' => $instance->id,
            'message' => "Contact enrolled in {$template->name}.",
        ]);
    }
}
