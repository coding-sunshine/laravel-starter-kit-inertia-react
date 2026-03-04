<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateOrganizationAction;
use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Models\Organization;
use App\Services\TenantContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class OrganizationController
{
    use AuthorizesRequests;

    public function index(Request $request): Response
    {
        $user = $request->user();
        $organizations = $user->organizations()
            ->withCount('users')
            ->orderBy('name')
            ->get();
        $currentOrganization = TenantContext::get();

        return Inertia::render('organizations/index', [
            'organizations' => $organizations,
            'currentOrganization' => $currentOrganization,
        ]);
    }

    public function create(Request $request): Response
    {
        $organizations = $request->user()->organizations()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('organizations/create', [
            'organizations' => $organizations,
        ]);
    }

    public function store(StoreOrganizationRequest $request, CreateOrganizationAction $action): RedirectResponse
    {
        $user = $request->user();
        $organization = $action->handle($user, $request->string('name')->value());
        if ($request->filled('parent_id')) {
            $organization->update(['parent_id' => (int) $request->input('parent_id')]);
        }

        $user->switchOrganization($organization);

        return to_route('organizations.show', $organization)
            ->with('status', __('Organization created.'));
    }

    public function show(Request $request, Organization $organization): Response|RedirectResponse
    {
        $this->authorize('view', $organization);

        $request->user()->switchOrganization($organization);

        $organizations = $request->user()->organizations()
            ->where('organizations.id', '!=', $organization->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('organizations/show', [
            'organization' => $organization->load('owner', 'parent'),
            'organizations' => $organizations,
        ]);
    }

    public function edit(Request $request, Organization $organization): Response
    {
        $this->authorize('update', $organization);
        $organizations = $request->user()->organizations()
            ->where('organizations.id', '!=', $organization->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('organizations/show', [
            'organization' => $organization->load('owner', 'parent'),
            'organizations' => $organizations,
        ]);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): RedirectResponse
    {
        $organization->update($request->safe()->only(['name', 'parent_id']));

        return to_route('organizations.show', $organization)
            ->with('status', __('Organization updated.'));
    }

    public function destroy(Request $request, Organization $organization): RedirectResponse
    {
        $this->authorize('delete', $organization);

        $organization->delete();

        $user = $request->user();
        if (TenantContext::id() === $organization->id) {
            TenantContext::forget();
            $default = $user->defaultOrganization();
            if ($default instanceof Organization) {
                $user->switchOrganization($default);
            }
        }

        return to_route('organizations.index')
            ->with('status', __('Organization deleted.'));
    }
}
