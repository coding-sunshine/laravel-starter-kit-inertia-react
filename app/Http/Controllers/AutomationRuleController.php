<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AutomationRule;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class AutomationRuleController extends Controller
{
    public function index(): Response
    {
        $organizationId = TenantContext::id();

        $rules = AutomationRule::query()
            ->where('organization_id', $organizationId)
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('automation-rules/index', [
            'rules' => $rules,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'event' => ['required', 'string', 'max:100'],
            'conditions' => ['nullable', 'array'],
            'actions' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ]);

        AutomationRule::query()->create([
            ...$validated,
            'conditions' => $validated['conditions'] ?? [],
            'actions' => $validated['actions'] ?? [],
            'organization_id' => TenantContext::id(),
            'created_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Automation rule created.');
    }

    public function update(Request $request, AutomationRule $automationRule): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'event' => ['sometimes', 'string', 'max:100'],
            'conditions' => ['nullable', 'array'],
            'actions' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ]);

        $automationRule->update($validated);

        return back()->with('success', 'Automation rule updated.');
    }

    public function destroy(AutomationRule $automationRule): RedirectResponse
    {
        $automationRule->delete();

        return back()->with('success', 'Automation rule deleted.');
    }
}
